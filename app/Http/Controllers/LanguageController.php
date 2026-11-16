<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController
{
    private const SUPPORTED = ['en', 'ar'];

    /**
     * Switch the UI language. State-changing, so this is a POST with CSRF.
     * The locale is validated against an allow-list and the redirect target is
     * forced to a same-origin page (falling back to home) to avoid open
     * redirects via a spoofed referer or redirect_to value.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale');
        abort_unless(in_array($locale, self::SUPPORTED, true), 422);

        session(['locale' => $locale]);
        $request->user()?->update(['locale' => $locale]);

        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: $request->getHost();
        $target = $request->input('redirect_to') ?: url()->previous();
        $targetHost = parse_url((string) $target, PHP_URL_HOST);

        if ($targetHost !== null && $targetHost !== $appHost) {
            $target = route('home');
        }

        return redirect()->to($target ?: route('home'))
            ->with('success', __('messages.flash.language_changed'));
    }
}
