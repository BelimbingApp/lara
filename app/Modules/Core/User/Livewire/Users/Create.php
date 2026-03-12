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
    public $companyId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /**
     * Store a newly created user.
     */
    public function store(): void
    {
        if ($this->companyId === '') {
            $this->companyId = null;
        }

        $validated = $this->validate([
            'companyId' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', Rules\Password::defaults()],
            'passwordConfirmation' => ['required', 'same:password'],
        ]);

        User::create([
            'company_id' => ($validated['companyId'] ?? null) ? (int) $validated['companyId'] : null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

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
