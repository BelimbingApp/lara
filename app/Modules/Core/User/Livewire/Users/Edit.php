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

class Edit extends Component
{
    public User $user;

    /** @var int|string|null */
    public $company_id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->company_id = $user->company_id;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user.
     */
    public function update(): void
    {
        if ($this->company_id === '') {
            $this->company_id = null;
        }

        $rules = [
            'company_id' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
        ];

        if (! empty($this->password)) {
            $rules['password'] = ['required', 'string', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules);

        $this->user->company_id = ($validated['company_id'] ?? null) ? (int) $validated['company_id'] : null;
        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];

        if (! empty($this->password)) {
            $this->user->password = Hash::make($validated['password']);
        }

        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        Session::flash('success', __('User updated successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.users.edit', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
