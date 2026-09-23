<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class CurrentBranch
{
    public const SESSION_KEY = 'current_branch_id';

    /**
     * Chi nhánh bắt buộc theo hồ sơ user.
     * null = được xem tất cả / đổi chi nhánh.
     *
     * Không khóa: Super Admin, role Admin, hoặc quyền system.branches.manage.
     */
    public static function forcedId(): ?int
    {
        $user = auth()->user();
        if (! $user || ! $user->branch_id) {
            return null;
        }

        if ($user->isSuperAdmin()
            || $user->hasAnyRole('admin')
            || $user->hasPermission('system.branches.manage')) {
            return null;
        }

        return (int) $user->branch_id;
    }

    /**
     * User không gắn chi nhánh mới được chọn "Tất cả" / chuyển chi nhánh.
     */
    public static function canSwitch(): bool
    {
        return self::forcedId() === null;
    }

    public static function id(): ?int
    {
        if ($forced = self::forcedId()) {
            return $forced;
        }

        $id = session(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public static function set(null|int|string $id): void
    {
        if (! self::canSwitch()) {
            session([self::SESSION_KEY => self::forcedId()]);

            return;
        }

        if ($id === null || $id === '' || $id === 'all') {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => (int) $id]);
    }

    /**
     * Đồng bộ session với branch bắt buộc (gọi mỗi request đã đăng nhập).
     */
    public static function syncFromUser(): void
    {
        if ($forced = self::forcedId()) {
            session([self::SESSION_KEY => $forced]);
        }
    }

    public static function apply(Builder|Relation $query, string $column = 'branch_id'): Builder|Relation
    {
        $id = self::id();
        if ($id) {
            $query->where($column, $id);
        }

        return $query;
    }

    public static function applyThrough(Builder|Relation $query, string $relation, string $column = 'branch_id'): Builder|Relation
    {
        $id = self::id();
        if ($id) {
            $query->whereHas($relation, fn (Builder $q) => $q->where($column, $id));
        }

        return $query;
    }

    /**
     * @return Collection<int, Branch>
     */
    public static function activeBranches(): Collection
    {
        $q = Branch::query()->where('is_active', true)->orderBy('name');
        if ($forced = self::forcedId()) {
            $q->whereKey($forced);
        }

        return $q->get();
    }

    /**
     * @return list<int>
     */
    public static function allowedIds(): array
    {
        if ($forced = self::forcedId()) {
            return [$forced];
        }

        return Branch::query()->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function allows(?int $branchId): bool
    {
        $forced = self::forcedId();
        if ($forced === null) {
            return true;
        }

        return $branchId !== null && (int) $branchId === $forced;
    }

    public static function authorize(?int $branchId): void
    {
        if (! self::allows($branchId)) {
            abort(403, 'Bạn chỉ được thao tác dữ liệu thuộc chi nhánh của mình.');
        }
    }

    /**
     * Ép branch_id khi tạo/sửa: user gắn chi nhánh thì luôn dùng chi nhánh đó.
     */
    public static function constrainPayload(array $data, string $key = 'branch_id'): array
    {
        if ($forced = self::forcedId()) {
            $data[$key] = $forced;
        }

        return $data;
    }
}
