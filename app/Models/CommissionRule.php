<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    protected $fillable = [
        'scope', 'class_id', 'percent', 'tier_min_revenue', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'tier_min_revenue' => 'decimal:0',
            'is_active' => 'boolean',
        ];
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }
}
