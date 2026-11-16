<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, $notification)
    {
        $n = auth()->user()->notifications()->findOrFail($notification);
        $n->update(['read_at' => now()]);

        return back();
    }

    /**
     * Open a notification: scoped lookup (never trusts the id or the stored
     * service_request_id), mark read, then redirect to the role-appropriate
     * subject. A notification with no viewable subject lands back on the list.
     */
    public function open(Request $request, $notification)
    {
        $user = auth()->user();
        $n = $user->notifications()->findOrFail($notification);
        $n->update(['read_at' => now()]);

        $subject = $n->request; // null-safe: FK is nullable / nullOnDelete

        if ($subject && $user->can('view', $subject)) {
            $destination = match ($user->role) {
                'customer' => route('customer.requests.show', $subject),
                'worker' => route('worker.jobs.show', $subject),
                'admin' => route('admin.requests.show', $subject),
                default => route('notifications.index'),
            };

            return redirect()->to($destination);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
