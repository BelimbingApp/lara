<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Livewire\DbViews;

use App\Base\Database\Exceptions\DbViewQueryException;
use App\Base\Database\Services\DbViewQueryExecutor;
use App\Modules\Core\User\Models\DbView;
use App\Modules\Core\User\Models\UserPin;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Show page for a single DB View — executes the saved SQL query
 * and renders paginated results.
 *
 * Supports inline editing of name, description, and SQL query,
 * as well as sharing (copy-on-share) to another user with auto-pinning.
 */
class Show extends Component
{
    use WithPagination;

    public DbView $dbView;

    public string $error = '';

    public bool $editing = false;

    public string $editName = '';

    public string $editSql = '';

    public string $editDescription = '';

    /**
     * Initialize the component by loading the DB view for the authenticated user.
     *
     * @param  string  $slug  The URL slug identifying the view
     */
    public function mount(string $slug): void
    {
        $this->dbView = DbView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->firstOrFail();

        $this->editName = $this->dbView->name;
        $this->editSql = $this->dbView->sql_query;
        $this->editDescription = $this->dbView->description ?? '';
    }

    /**
     * Toggle inline editing mode.
     */
    public function toggleEdit(): void
    {
        $this->editing = ! $this->editing;

        if ($this->editing) {
            $this->editName = $this->dbView->name;
            $this->editSql = $this->dbView->sql_query;
            $this->editDescription = $this->dbView->description ?? '';
        }
    }

    /**
     * Validate and persist changes to the DB view's name, SQL query, and description.
     *
     * Regenerates the slug when the name changes and clears any previous
     * execution error so the updated query runs fresh.
     */
    public function saveEdit(): void
    {
        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editSql' => ['required', 'string'],
            'editDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        $nameChanged = $validated['editName'] !== $this->dbView->name;

        $this->dbView->name = $validated['editName'];
        $this->dbView->sql_query = $validated['editSql'];
        $this->dbView->description = $validated['editDescription'] ?: null;

        if ($nameChanged) {
            $this->dbView->slug = DbView::generateSlug(
                $validated['editName'],
                $this->dbView->user_id,
            );
        }

        $this->dbView->save();
        $this->editing = false;
        $this->error = '';
        $this->resetPage();
    }

    /**
     * Share this DB view with another user by creating an independent copy.
     *
     * The copy is owned by the target user, includes attribution in the
     * description, and a UserPin is auto-created for quick sidebar access.
     *
     * @param  int  $userId  The target user's ID
     */
    public function shareWith(int $userId): void
    {
        $newView = DbView::query()->create([
            'user_id' => $userId,
            'name' => $this->dbView->name,
            'slug' => DbView::generateSlug($this->dbView->name, $userId),
            'sql_query' => $this->dbView->sql_query,
            'description' => 'Shared by '.auth()->user()->name
                .($this->dbView->description ? "\n\n".$this->dbView->description : ''),
            'icon' => $this->dbView->icon,
        ]);

        $pinUrl = route('admin.system.db-views.show', $newView->slug);

        UserPin::query()->create([
            'user_id' => $userId,
            'label' => $newView->name,
            'url' => $pinUrl,
            'url_hash' => UserPin::hashUrl($pinUrl),
            'icon' => $newView->icon ?? 'heroicon-o-circle-stack',
            'sort_order' => (UserPin::query()->where('user_id', $userId)->max('sort_order') ?? -1) + 1,
        ]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $columns = [];
        $rows = [];
        $total = 0;
        $perPage = 25;
        $currentPage = 1;
        $lastPage = 1;

        try {
            $executor = app(DbViewQueryExecutor::class);
            $result = $executor->execute($this->dbView->sql_query, $this->getPage());

            $columns = $result['columns'];
            $rows = $result['rows'];
            $total = $result['total'];
            $perPage = $result['per_page'];
            $currentPage = $result['current_page'];
            $lastPage = $result['last_page'];
            $this->error = '';
        } catch (DbViewQueryException $e) {
            $this->error = $e->getMessage();
        }

        return view('livewire.admin.system.db-views.show', [
            'columns' => $columns,
            'rows' => $rows,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'error' => $this->error,
        ]);
    }
}
