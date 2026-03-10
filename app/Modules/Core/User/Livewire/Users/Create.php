<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Livewire\Users;

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Component;

class Create extends Component
{
    /** @var int|string|null */
    public $company_id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Store a newly created user.
     */
    public function store(): void
    {
        if ($this->company_id === '') {
            $this->company_id = null;
        }

        $validated = $this->validate([
            'company_id' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['company_id'] = ($validated['company_id'] ?? null) ? (int) $validated['company_id'] : null;

        User::create($validated);

        Session::flash('success', __('User created successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.users.create', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
