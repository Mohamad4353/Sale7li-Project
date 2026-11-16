<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Fire the standard broker. We deliberately ignore the result so the
        // response is identical whether or not the address belongs to an
        // account — no account enumeration.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('auth.reset_link_sent'));
    }
}
