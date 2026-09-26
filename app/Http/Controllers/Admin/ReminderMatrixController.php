<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReminderMatrix;

class ReminderMatrixController extends Controller
{
    public function index()
    {
        $schedulerGroups = ReminderMatrix::schedulerGrouped();
        $eventGroups = ReminderMatrix::eventGrouped();

        foreach ($schedulerGroups as $group => $jobs) {
            foreach ($jobs as $i => $job) {
                $schedulerGroups[$group][$i]['ran_at'] = ReminderMatrix::schedulerRunHint($job);
                $schedulerGroups[$group][$i]['output_label'] = ReminderMatrix::outputLabel((string) ($job['output'] ?? ''));
            }
        }

        foreach ($eventGroups as $group => $entries) {
            foreach ($entries as $i => $entry) {
                $eventGroups[$group][$i]['output_label'] = ReminderMatrix::outputLabel((string) ($entry['output'] ?? ''));
            }
        }

        return view('admin.system.reminder_matrix', compact('schedulerGroups', 'eventGroups'));
    }
}
