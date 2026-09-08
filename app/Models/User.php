<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'daily_rate',
        'branch_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'daily_rate' => 'decimal:0',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staffAttendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleUser::class);
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_sales_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'sales_id');
    }

    /**
     * @return list<string>
     */
    public function roleKeys(): array
    {
        if ($this->relationLoaded('roleAssignments')) {
            $keys = $this->roleAssignments->pluck('role')->filter()->unique()->values()->all();
        } else {
            $keys = $this->roleAssignments()->pluck('role')->filter()->unique()->values()->all();
        }

        if ($keys === [] && filled($this->role)) {
            return [(string) $this->role];
        }

        return array_values(array_map('strval', $keys));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleKeys(), true);
    }

    public function hasAnyRole(string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncRoles(array $roles): void
    {
        $valid = array_values(array_unique(array_filter(
            $roles,
            fn ($r) => is_string($r) && array_key_exists($r, config('permissions.roles', []))
        )));

        if ($valid === []) {
            $valid = ['admin'];
        }

        $this->roleAssignments()->delete();
        foreach ($valid as $role) {
            $this->roleAssignments()->create(['role' => $role]);
        }

        // Cột role giữ vai trò chính (ưu tiên super_admin, rồi role đầu tiên)
        $primary = in_array('super_admin', $valid, true)
            ? 'super_admin'
            : $valid[0];
        $this->forceFill(['role' => $primary])->save();
        $this->unsetRelation('roleAssignments');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->role === 'super_admin';
    }

    public function isSales(): bool
    {
        return $this->hasRole('sales');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->roleKeys() as $role) {
            if (\App\Support\Permissions::roleHas($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function roleLabel(): string
    {
        $labels = $this->roleLabels();

        return $labels !== [] ? implode(', ', $labels) : (config('permissions.roles.'.$this->role) ?? (string) $this->role);
    }

    /**
     * @return list<string>
     */
    public function roleLabels(): array
    {
        $out = [];
        foreach ($this->roleKeys() as $role) {
            $out[] = config('permissions.roles.'.$role)
                ?? match ($role) {
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'accountant' => 'Kế toán',
                    'sales' => 'Sales',
                    'training' => 'Đào Tạo',
                    'teacher' => 'Giáo viên',
                    default => $role,
                };
        }

        return $out;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return 'U';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
