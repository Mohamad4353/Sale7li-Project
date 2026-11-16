<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->string('role')->value();
        $q = $request->string('q')->value();
        $users = User::query()->when(in_array($role, ['customer', 'worker', 'admin'], true), fn ($x) => $x->where('role', $role))->when($q, fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')))->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users', 'role', 'q'));
    }

    public function toggle(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot suspend yourself.');
        $user->update(['status' => $user->status === 'active' ? 'suspended' : 'active']);

        return back()->with('success', __('messages.flash.user_status_updated'));
    }

    /**
     * Email the user a standard password-reset link. The admin never sees or
     * sets the password — this only triggers Laravel's password broker.
     */
    public function sendPasswordResetLink(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', __('messages.flash.reset_link_sent'));
    }
}
