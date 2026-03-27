<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Quality\Livewire\Ncr;

use App\Base\Foundation\Livewire\SearchablePaginatedList;
use App\Modules\Core\Quality\Models\Ncr;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Index extends SearchablePaginatedList
{
    protected const string VIEW_NAME = 'livewire.quality.ncr.index';

    protected const string VIEW_DATA_KEY = 'ncrs';

    protected const string SORT_COLUMN = 'created_at';

    public string $search = '';

    public string $statusFilter = '';

    public function severityVariant(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'major' => 'warning',
            'minor' => 'info',
            'observation' => 'default',
            default => 'default',
        };
    }

    public function statusVariant(string $status): string
    {
        return match ($status) {
            'open' => 'info',
            'under_triage' => 'accent',
            'assigned' => 'accent',
            'in_progress' => 'warning',
            'under_review' => 'accent',
            'verified' => 'success',
            'closed' => 'default',
            'rejected' => 'danger',
            default => 'default',
        };
    }

    protected function query(): EloquentBuilder|QueryBuilder
    {
        return Ncr::query()
            ->with('createdByUser', 'currentOwner')
            ->when($this->statusFilter !== '', function (EloquentBuilder $query): void {
                $query->where('status', $this->statusFilter);
            });
    }

    protected function applySearch(EloquentBuilder|QueryBuilder $query, string $search): void
    {
        $query->where(function (EloquentBuilder $builder) use ($search): void {
            $builder->where('ncr_no', 'like', '%'.$search.'%')
                ->orWhere('title', 'like', '%'.$search.'%')
                ->orWhere('reported_by_name', 'like', '%'.$search.'%');
        });
    }
}
