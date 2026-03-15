<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Livewire\DbViews;

use App\Base\Foundation\Livewire\Concerns\ResetsPaginationOnSearch;
use App\Modules\Core\User\Models\DbView;
use App\Modules\Core\User\Models\UserPin;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResetsPaginationOnSearch;
    use WithPagination;

    public string $search = '';

    /**
     * Delete a DB view owned by the current user.
     *
     * Also removes any user pins that reference this view's URL.
     *
     * @param  int  $id  The DB view ID to delete
     */
    public function deleteView(int $id): void
    {
        $dbView = DbView::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        UserPin::query()
            ->where('user_id', auth()->id())
            ->where('url', 'like', '%/db-views/'.$dbView->slug)
            ->delete();

        $dbView->delete();
    }

    /**
     * Duplicate a DB view for the current user.
     *
     * Creates a copy with a freshly generated unique slug.
     *
     * @param  int  $id  The DB view ID to duplicate
     */
    public function duplicateView(int $id): void
    {
        $dbView = DbView::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $userId = auth()->id();

        DbView::query()->create([
            'user_id' => $userId,
            'name' => $dbView->name,
            'slug' => DbView::generateSlug($dbView->name, $userId),
            'sql_query' => $dbView->sql_query,
            'description' => $dbView->description,
            'icon' => $dbView->icon,
        ]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.system.db-views.index', [
            'views' => DbView::query()
                ->where('user_id', auth()->id())
                ->when($this->search, function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('updated_at')
                ->paginate(25),
        ]);
    }
}
