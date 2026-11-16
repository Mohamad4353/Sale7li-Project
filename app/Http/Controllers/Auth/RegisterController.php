<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^\\+?[0-9][0-9 .()\\-]{6,28}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:customer,worker'],
        ], [
            'phone.regex' => __('validation.custom.phone.regex'),
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);

            if ($user->role === 'worker') {
                WorkerProfile::create(['user_id' => $user->id]);
            }

            return $user;
        });

        event(new Registered($user)); // dispatches the email-verification notification

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with(
            'success',
            $user->role === 'worker'
                ? __('messages.flash.worker_pending_verification')
                : __('messages.flash.welcome')
        );
    }
}
