<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 23 — email verification, using Laravel's standard mechanism
 * (EmailVerificationRequest handles the signed URL, the id/hash match and the
 * "wrong user" / tampered-hash rejection). No third-party package.
 */
class EmailVerificationController extends Controller
{
    /** The verify-your-email screen (or straight past it if already verified). */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard'))
            : view('auth.verify-email');
    }

    /** Consume a valid signed verification link. */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $request->fulfill(); // markEmailAsVerified() + event(new Verified($user))

        return redirect()->intended(route('dashboard'))
            ->with('success', __('auth.verify_email.done'));
    }

    /** Re-send the verification link (throttled at the route). */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', __('auth.verify_email.resent'));
    }
}
