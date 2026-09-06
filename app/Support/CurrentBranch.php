<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class CurrentBranch
{
    public const SESSION_KEY = 'current_branch_id';

    public static function id(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public static function set(null|int|string $id): void
    {
        if ($id === null || $id === '' || $id === 'all') {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => (int) $id]);
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
}
