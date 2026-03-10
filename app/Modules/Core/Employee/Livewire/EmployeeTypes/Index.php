<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Employee\Livewire\EmployeeTypes;

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Modules\Core\Employee\Models\EmployeeType;
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

    public function delete(int $id): void
    {
        $type = EmployeeType::query()->findOrFail($id);
        if ($type->is_system) {
            return;
        }
        if ($type->employees_count > 0) {
            session()->flash('error', __('Cannot delete: employees are using this type.'));

            return;
        }
        $type->delete();
        session()->flash('success', __('Employee type deleted.'));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $authUser = auth()->user();
        $authActor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );
        $canCreate = app(AuthorizationService::class)->can($authActor, 'core.employee_type.create')->allowed;

        return view('livewire.admin.employee-types.index', [
            'canCreate' => $canCreate,
            'employeeTypes' => EmployeeType::query()
                ->global()
                ->withCount('employees')
                ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('label', 'like', '%'.$this->search.'%'))
                ->orderByDesc('is_system')
                ->orderBy('code')
                ->paginate(15),
        ]);
    }
}
