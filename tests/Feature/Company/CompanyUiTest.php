<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Livewire\Livewire;

test('guests are redirected to login from company pages', function (): void {
    $this->get(route('admin.companies.index'))->assertRedirect(route('login'));
    $this->get(route('admin.companies.create'))->assertRedirect(route('login'));
});

test('authenticated users can view company pages', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $this->actingAs($user);

    $this->get(route('admin.companies.index'))->assertOk();
    $this->get(route('admin.companies.create'))->assertOk();
    $this->get(route('admin.companies.show', $company))->assertOk();
});

test('company can be created from create page component', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('companies.create')
        ->set('name', 'Northwind Holdings')
        ->set('status', 'active')
        ->set('email', 'ops@northwind.example')
        ->set('scope_activities_json', '{"industry":"Logistics"}')
        ->set('metadata_json', '{"employee_count":250}')
        ->call('store')
        ->assertRedirect(route('admin.companies.index'));

    $company = Company::query()->where('name', 'Northwind Holdings')->first();

    expect($company)
        ->not()->toBeNull()
        ->and($company->slug)
        ->toBe('northwind-holdings')
        ->and($company->scope_activities['industry'])
        ->toBe('Logistics');
});
