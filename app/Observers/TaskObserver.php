<?php

namespace App\Observers;

use App\Models\Task;
use App\Support\SmartCache;

class TaskObserver
{
    public function saved(Task $task): void
    {
        SmartCache::bumpTasksHome();
    }

    public function deleted(Task $task): void
    {
        SmartCache::bumpTasksHome();
    }
}
