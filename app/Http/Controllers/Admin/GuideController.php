<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GuideSetupChecklist;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roleKeys = method_exists($user, 'roleKeys') ? $user->roleKeys() : [(string) $user->role];
        $primary = $roleKeys[0] ?? (string) $user->role;

        $defaultTab = match (true) {
            in_array('accountant', $roleKeys, true) => 'accountant',
            in_array('training', $roleKeys, true), in_array('teacher', $roleKeys, true) => 'training',
            in_array('sales', $roleKeys, true) => 'sales',
            default => match ($primary) {
                'accountant' => 'accountant',
                'training', 'teacher' => 'training',
                'sales' => 'sales',
                default => 'setup',
            },
        };

        $allowed = ['setup', 'flow', 'accountant', 'training', 'sales', 'admin'];
        $tab = $request->get('tab', $defaultTab);
        if (! in_array($tab, $allowed, true)) {
            $tab = $defaultTab;
        }

        $setupChecklist = GuideSetupChecklist::summary();

        return view('admin.guide.index', compact('defaultTab', 'tab', 'setupChecklist'));
    }
}
