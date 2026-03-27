<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Quality\Livewire\Scar;

use App\Base\Foundation\Livewire\SearchablePaginatedList;
use App\Modules\Core\Quality\Models\Scar;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Index extends SearchablePaginatedList
{
    protected const string VIEW_NAME = 'livewire.quality.scar.index';

    protected const string VIEW_DATA_KEY = 'scars';

    protected const string SORT_COLUMN = 'created_at';

    public string $search = '';

    public string $statusFilter = '';

    public function statusVariant(string $status): string
    {
        return match ($status) {
            'draft' => 'default',
            'issued' => 'info',
            'acknowledged' => 'accent',
            'containment_submitted' => 'accent',
            'under_investigation' => 'warning',
            'response_submitted' => 'accent',
            'under_review' => 'accent',
            'action_required' => 'warning',
            'verification_pending' => 'info',
            'closed' => 'default',
            'rejected' => 'danger',
            'cancelled' => 'default',
            default => 'default',
        };
    }

    protected function query(): EloquentBuilder|QueryBuilder
    {
        return Scar::query()
            ->with('ncr', 'issueOwner')
            ->when($this->statusFilter !== '', function (EloquentBuilder $query): void {
                $query->where('status', $this->statusFilter);
            });
    }

    protected function applySearch(EloquentBuilder|QueryBuilder $query, string $search): void
    {
        $query->where(function (EloquentBuilder $builder) use ($search): void {
            $builder->where('scar_no', 'like', '%'.$search.'%')
                ->orWhere('supplier_name', 'like', '%'.$search.'%')
                ->orWhere('product_name', 'like', '%'.$search.'%');
        });
    }
}
