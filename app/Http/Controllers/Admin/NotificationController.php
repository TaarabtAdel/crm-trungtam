<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SmartCache;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(30);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        if ($notification->read_at === null) {
            $notification->markAsRead();
            SmartCache::forgetNotifications($user);
        }

        $url = $notification->data['url'] ?? route('admin.notifications.index');

        return redirect($url);
    }

    public function markAsReadOnly(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        if ($notification->read_at === null) {
            $notification->markAsRead();
            SmartCache::forgetNotifications($user);
        }

        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        SmartCache::forgetNotifications($user);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
