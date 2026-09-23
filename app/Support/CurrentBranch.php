<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Phân tách dữ liệu theo chi nhánh.
 *
 * Quy tắc (mọi role, kể cả Super Admin — có thể nhiều SA theo từng CN):
 * - User có users.branch_id → chỉ xem/sửa dữ liệu chi nhánh đó (header khóa).
 * - User không gắn chi nhánh (HQ) → chọn "Tất cả" hoặc một CN trên header.
 * - Dữ liệu thuộc chi nhánh nào thì chỉ user của CN đó (hoặc HQ) được thấy.
 */
class CurrentBranch
{
    public const SESSION_KEY = 'current_branch_id';

    /**
     * Chi nhánh bắt buộc theo hồ sơ (mọi role).
     */
    public static function forcedId(): ?int
    {
        $user = auth()->user();
        if (! $user || ! $user->branch_id) {
            return null;
        }

        return (int) $user->branch_id;
    }

    /**
     * true = HQ (chưa gắn CN) → được đổi chi nhánh / xem tất cả.
     */
    public static function canSwitch(): bool
    {
        return self::forcedId() === null;
    }

    /**
     * Chi nhánh đang hiệu lực khi query.
     */
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
        if ($id = self::id()) {
            return [$id];
        }

        return Branch::query()->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Record có được thao tác với ngữ cảnh chi nhánh hiện tại không.
     * - HQ + "Tất cả" (id null) → mọi record
     * - Đang lọc/khóa CN X → chỉ record branch_id = X
     */
    public static function allows(?int $branchId): bool
    {
        $id = self::id();
        if ($id === null) {
            return true;
        }

        return $branchId !== null && (int) $branchId === $id;
    }

    public static function authorize(?int $branchId): void
    {
        if (! self::allows($branchId)) {
            abort(403, 'Bạn chỉ được thao tác dữ liệu thuộc chi nhánh của mình.');
        }
    }

    /**
     * User có được nhận thông báo/việc của chi nhánh $forBranchId không.
     * - HQ (không gắn CN) → nhận mọi CN
     * - Có gắn CN → chỉ đúng CN đó
     */
    public static function userBelongsToBranchContext(?int $userBranchId, ?int $forBranchId): bool
    {
        if ($forBranchId === null) {
            return true;
        }

        if ($userBranchId === null) {
            return true;
        }

        return $userBranchId === (int) $forBranchId;
    }

    /**
     * Ép branch khi tạo/sửa:
     * - User bị khóa CN → luôn ghi CN đó
     * - HQ đang chọn một CN trên header + field trống → điền CN đang chọn
     */
    public static function constrainPayload(array $data, string $key = 'branch_id'): array
    {
        if ($forced = self::forcedId()) {
            $data[$key] = $forced;

            return $data;
        }

        if (! filled($data[$key] ?? null) && ($id = self::id())) {
            $data[$key] = $id;
        }

        return $data;
    }
}
