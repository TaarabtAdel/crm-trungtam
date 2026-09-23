<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const STATUSES = [
        'todo' => 'Cần làm',
        'doing' => 'Đang làm',
        'review' => 'Chờ duyệt',
        'done' => 'Hoàn thành',
    ];

    public const PRIORITIES = [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ];

    protected $fillable = [
        'title',
        'description',
        'creator_id',
        'assignee_id',
        'branch_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'position',
        'completed_at',
        'is_published',
        'published_at',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_watchers')->withTimestamps();
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position')->orderBy('id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class)->orderBy('position')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TaskActivityLog::class)->latest();
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(TaskReminderLog::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isDraft(): bool
    {
        return ! $this->is_published;
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && ! $this->isDone()
            && $this->due_date->isPast();
    }

    public function checklistProgress(): array
    {
        $total = $this->checklistItems->count();
        $done = $this->checklistItems->where('is_done', true)->count();

        return ['done' => $done, 'total' => $total];
    }

    public function subtaskProgress(): array
    {
        $total = $this->subtasks->count();
        $done = $this->subtasks->where('is_done', true)->count();

        return ['done' => $done, 'total' => $total];
    }

    public function participantIds(): array
    {
        return collect([$this->creator_id, $this->assignee_id])
            ->merge($this->relationLoaded('watchers') ? $this->watchers->pluck('id') : $this->watchers()->pluck('users.id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Người nhận thông báo: người thực hiện + người liên quan (không gồm người tạo).
     *
     * @return list<int>
     */
    public function notifyRecipientIds(): array
    {
        return collect([$this->assignee_id])
            ->merge($this->relationLoaded('watchers') ? $this->watchers->pluck('id') : $this->watchers()->pluck('users.id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $this->creator_id)
            ->values()
            ->all();
    }
}
