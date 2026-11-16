<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            // The credentials are correct but the account is not active: send the
            // user to a screen that explains the suspension rather than letting it
            // read as a wrong password. No session is started. Wrong passwords and
            // unknown emails still get the generic error (no account enumeration).
            $user = User::where('email', $data['email'])->first();
            if ($user && $user->status !== 'active' && Hash::check($data['password'], $user->password)) {
                return redirect()->route('account.suspended');
            }

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function suspended()
    {
        return view('auth.suspended');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('messages.flash.signed_out'));
    }
}
