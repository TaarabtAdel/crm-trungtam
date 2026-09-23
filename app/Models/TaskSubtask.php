<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubtask extends Model
{
    protected $fillable = ['task_id', 'title', 'is_done', 'assignee_id', 'position'];

    protected function casts(): array
    {
        return ['is_done' => 'boolean', 'position' => 'integer'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
