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
                $ran = ReminderMatrix::schedulerRunHint($job);
                $schedulerGroups[$group][$i]['ran_at'] = ReminderMatrix::formatRanAt($ran);
                $schedulerGroups[$group][$i]['delivery'] = ReminderMatrix::deliverySummary($job);
            }
        }

        foreach ($eventGroups as $group => $entries) {
            foreach ($entries as $i => $entry) {
                $eventGroups[$group][$i]['delivery'] = ReminderMatrix::deliverySummary($entry);
            }
        }

        return view('admin.system.reminder_matrix', compact('schedulerGroups', 'eventGroups'));
    }
}
