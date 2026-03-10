<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Livewire\Users;

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Exceptions\AuthorizationDeniedException;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $userId): void
    {
        $authUser = auth()->user();

        $actor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );

        try {
            app(AuthorizationService::class)->authorize($actor, 'core.user.delete');
        } catch (AuthorizationDeniedException) {
            Session::flash('error', __('You do not have permission to delete users.'));

            return;
        }

        $user = User::findOrFail($userId);

        if ($user->id === $authUser->getAuthIdentifier()) {
            Session::flash('error', __('You cannot delete your own account.'));

            return;
        }

        $user->delete();
        Session::flash('success', __('User deleted successfully.'));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $authUser = auth()->user();

        $actor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );

        $canDelete = app(AuthorizationService::class)
            ->can($actor, 'core.user.delete')
            ->allowed;

        return view('livewire.users.index', [
            'users' => User::query()
                ->with('company')
                ->when($this->search, function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                })
                ->latest()
                ->paginate(10),
            'canDelete' => $canDelete,
        ]);
    }
}
