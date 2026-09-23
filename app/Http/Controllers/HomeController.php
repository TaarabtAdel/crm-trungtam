<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\WorkbenchApps;
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
            'headerNotifications' => $user->notifications()->latest()->limit(12)->get(),
            'headerUnreadNotifications' => $user->unreadNotifications()->count(),
        ]);
    }
}
