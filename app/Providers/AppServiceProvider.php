<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiters();

        // Localised email-verification message (EN/AR) built from the app's own
        // lang files rather than the framework's English JSON keys.
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject(__('auth.verify_email.mail_subject'))
            ->line(__('auth.verify_email.mail_intro'))
            ->action(__('auth.verify_email.mail_action'), $url)
            ->line(__('auth.verify_email.mail_outro')));

        // Production is HTTPS-only (HSTS is emitted on secure requests). Force the
        // scheme so every generated URL — redirects and, critically, the
        // password-reset link sent by mail — is https even when TLS terminates at
        // a proxy that the app is not configured to trust. Local/testing and
        // `php artisan serve` over http are unaffected.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Named rate limiters for sensitive endpoints. Limits are deliberately
     * generous for normal use and tight only on auth / money / spam surfaces.
     */
    private function configureRateLimiters(): void
    {
        // Authentication: keyed on the submitted email + IP so one attacker
        // cannot lock out an unrelated user, and brute force is still capped.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Password-reset request / confirm — IP-scoped; the broker also throttles
        // resend per user (config/auth.php passwords.users.throttle).
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('messaging', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));

        // Public, read-only marketplace API. Same 60/min ceiling the route has
        // always used; a named limiter just makes it consistent with the others
        // and keyed per-user when authenticated, per-IP otherwise.
        RateLimiter::for('marketplace', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(12)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(8)->by($request->user()?->id ?: $request->ip()));

        // Generic authenticated state changes (accept/decline/cancel/review/etc.).
        RateLimiter::for('mutations', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->id ?: $request->ip()));

        // AI Service Assistant (advisory-only category suggestion): a paid,
        // externally-hosted call, so a tighter ceiling than the other
        // authenticated mutations. Keyed by user id — the route requires auth,
        // so the IP fallback only matters if that guard is ever bypassed.
        RateLimiter::for('ai-assistant', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));
    }
}
