<?php

namespace App\Models;

use App\Support\VietQr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'phone', 'is_active',
        'bank_bin', 'bank_account_number', 'bank_account_name', 'bank_name',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function hasPaymentAccount(): bool
    {
        return filled($this->bank_bin) && filled($this->bank_account_number);
    }

    public function bankDisplayName(): ?string
    {
        return $this->bank_name
            ?: VietQr::bankName($this->bank_bin)
            ?: $this->bank_bin;
    }

    public function paymentQrUrl(?float $amount = null, ?string $addInfo = null): ?string
    {
        if (! $this->hasPaymentAccount()) {
            return null;
        }

        return VietQr::imageUrl(
            (string) $this->bank_bin,
            (string) $this->bank_account_number,
            $this->bank_account_name,
            $amount,
            $addInfo
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
