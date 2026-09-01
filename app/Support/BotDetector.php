<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;

class BotDetector
{
    /**
     * Built-in crawler / bot signatures (case-insensitive substring).
     *
     * @var list<string>
     */
    public const DEFAULT_PATTERNS = [
        'bot',
        'spider',
        'crawl',
        'slurp',
        'curl/',
        'wget',
        'python-requests',
        'python-urllib',
        'httpclient',
        'java/',
        'libwww',
        'scrapy',
        'httpx',
        'aiohttp',
        'go-http-client',
        'okhttp',
        'postman',
        'insomnia',
        'headless',
        'phantomjs',
        'selenium',
        'puppeteer',
        'playwright',
        'chatgpt',
        'gptbot',
        'claudebot',
        'anthropic',
        'bytespider',
        'petalbot',
        'semrush',
        'ahrefs',
        'mj12bot',
        'dotbot',
        'rogerbot',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'whatsapp',
        'telegrambot',
        'discordbot',
        'embedly',
        'quora link preview',
        'showyoubot',
        'outbrain',
        'pinterest',
        'slackbot',
        'vkshare',
        'w3c_validator',
        'bingpreview',
        'yandex',
        'baidu',
        'duckduckbot',
        'sogou',
        'exabot',
        'ia_archiver',
        'archive.org',
        'googlebot',
        'google-inspectiontool',
        'adsbot-google',
        'mediapartners-google',
        'apis-google',
        'feedfetcher',
        'applebot',
        'bingbot',
        'msnbot',
        'nuhk',
        'grove',
        'nutch',
        'www::mechanize',
        'lighthouse',
        'chrome-lighthouse',
        'gtmetrix',
        'pingdom',
        'uptimerobot',
        'statuscake',
        'sitespeed',
        'pagespeed',
    ];

    public static function isBot(Request $request): bool
    {
        return self::inspect($request) !== null;
    }

    public static function inspect(Request $request): ?string
    {
        $ua = trim((string) $request->userAgent());

        if ($ua === '') {
            return 'Empty User-Agent';
        }

        if (strlen($ua) < 12) {
            return 'User-Agent too short ('.strlen($ua).' chars)';
        }

        $haystack = strtolower($ua);

        foreach (self::patterns() as $pattern) {
            if ($pattern !== '' && str_contains($haystack, $pattern)) {
                return 'UA matched: '.$pattern;
            }
        }

        if (Setting::bool('anti_bot_strict', false)) {
            $accept = (string) $request->header('Accept', '');
            $lang = (string) $request->header('Accept-Language', '');
            if ($accept === '' && $lang === '') {
                return 'Missing Accept and Accept-Language headers';
            }
            if ($accept === '') {
                return 'Missing Accept header';
            }
            if ($lang === '') {
                return 'Missing Accept-Language header';
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function patterns(): array
    {
        $extra = (string) Setting::get('anti_bot_extra_patterns', '');
        $custom = [];

        foreach (preg_split('/\r\n|\r|\n/', $extra) ?: [] as $line) {
            $line = strtolower(trim($line));
            if ($line !== '' && ! str_starts_with($line, '#')) {
                $custom[] = $line;
            }
        }

        return array_values(array_unique(array_merge(self::DEFAULT_PATTERNS, $custom)));
    }
}
