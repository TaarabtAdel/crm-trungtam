<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementTest extends Model
{
    protected $fillable = [
        'lead_id', 'student_id', 'subject_id', 'interaction_id',
        'recommended_class_id', 'created_by', 'score', 'level', 'tested_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'tested_at' => 'date',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function recommendedClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'recommended_class_id');
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function levelOptions(): array
    {
        return [
            'A0' => 'A0 / Mất gốc',
            'A1' => 'A1',
            'A2' => 'A2',
            'B1' => 'B1',
            'B2' => 'B2',
            'C1' => 'C1',
            'C2' => 'C2',
            'basic' => 'Cơ bản (Tin học)',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Nâng cao',
            'other' => 'Khác',
        ];
    }

    public function levelLabel(): string
    {
        return self::levelOptions()[$this->level] ?? ($this->level ?: '—');
    }
}
