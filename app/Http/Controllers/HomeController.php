<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\SmartCache;
use App\Support\WorkbenchApps;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $inbox = SmartCache::headerNotifications($user);
        $homeTasks = SmartCache::homeTasks($user);

        return view('home.index', [
            'brand' => (string) Setting::get('center_name', Setting::get('logo_text', config('app.name'))),
            'logoText' => (string) Setting::get('logo_text', 'CRM'),
            'apps' => WorkbenchApps::forUser($user),
            'categories' => WorkbenchApps::categories(),
            'user' => $user,
            'headerNotifications' => $inbox['items'],
            'headerUnreadNotifications' => $inbox['unread'],
            'taskDueDates' => $homeTasks['due_dates'],
            'tasksToday' => $homeTasks['today'],
        ]);
    }
}
