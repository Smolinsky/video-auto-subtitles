<?php

namespace App\Models\Scopes;

use App\Dto\Video\ListVideoFilterDto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait VideoScope
{
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function scopeApplyFilters(Builder $query, ListVideoFilterDto $filters): Builder
    {
        $status = $filters->status;
        $search = trim((string) ($filters->search ?? ''));

        return $query
            ->when($status !== null && $status !== '', fn (Builder $builder): Builder => $builder->where('status', $status))
            ->when($search !== '', fn (Builder $builder): Builder => $builder->where('originalName', 'like', "%{$search}%"));
    }
}
