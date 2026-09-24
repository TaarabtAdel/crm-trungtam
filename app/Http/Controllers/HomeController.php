<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\SmartCache;
use App\Support\WorkbenchApps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('home.index', [
            'brand' => (string) Setting::get('center_name', Setting::get('logo_text', config('app.name'))),
            'logoText' => (string) Setting::get('logo_text', 'CRM'),
            'apps' => WorkbenchApps::forUser($user),
            'categories' => WorkbenchApps::categories(),
            'user' => $user,
            'canViewTasks' => $user->hasPermission('tasks.view') || $user->isSuperAdmin(),
        ]);
    }

    /**
     * AJAX: thông báo + việc hôm nay (+ ngày có hạn cho lịch). Poll ~30s từ /home.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $inbox = SmartCache::headerNotifications($user);

        $notifications = $inbox['items']->map(function ($n) {
            $data = $n->data ?? [];

            return [
                'id' => $n->id,
                'title' => (string) ($data['title'] ?? 'Thông báo'),
                'body' => (string) ($data['body'] ?? ''),
                'icon' => (string) ($data['icon'] ?? 'bi-bell'),
                'unread' => $n->read_at === null,
                'time' => $n->created_at?->diffForHumans() ?? '',
                'url' => route('admin.notifications.read', $n->id),
            ];
        })->values();

        $tasks = [];
        $dueDates = [];
        if ($user->hasPermission('tasks.view') || $user->isSuperAdmin()) {
            $homeTasks = SmartCache::homeTasks($user);
            $dueDates = $homeTasks['due_dates'];
            $tasks = $homeTasks['today']->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'due_time' => $task->due_date?->format('H:i') ?? '—',
                    'assignee' => $task->assignee?->name,
                    'is_overdue' => $task->isOverdue(),
                    'url' => route('admin.tasks.index', ['task' => $task->id]),
                ];
            })->values();
        }

        return response()->json([
            'notifications' => $notifications,
            'unread' => (int) $inbox['unread'],
            'tasks' => $tasks,
            'due_dates' => $dueDates,
            'urls' => [
                'notifications_index' => route('admin.notifications.index'),
                'notifications_read_all' => route('admin.notifications.read-all'),
                'tasks_index' => route('admin.tasks.index'),
            ],
        ]);
    }
}
