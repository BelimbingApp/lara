<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Licensee company tests
// ---------------------------------------------------------------------------

test('licensee company cannot be deleted from index', function (): void {
    $user = User::factory()->create();
    $licensee = Company::query()->find(Company::LICENSEE_ID)
        ?? Company::factory()->create(['id' => Company::LICENSEE_ID]);

    $this->actingAs($user);

    Livewire::test('companies.index')
        ->call('delete', $licensee->id);

    expect(Company::query()->find($licensee->id))->not()->toBeNull();
});

test('licensee company model prevents deletion', function (): void {
    $licensee = Company::query()->find(Company::LICENSEE_ID)
        ?? Company::factory()->create(['id' => Company::LICENSEE_ID]);

    $licensee->delete();
})->throws(\LogicException::class, 'The licensee company (id=1) cannot be deleted.');

test('company isLicensee returns true for id 1 and false for others', function (): void {
    $licensee = Company::query()->find(Company::LICENSEE_ID)
        ?? Company::factory()->create(['id' => Company::LICENSEE_ID]);
    $other = Company::factory()->create();

    expect($licensee->isLicensee())->toBeTrue()
        ->and($other->isLicensee())->toBeFalse();
});

test('licensee company shows licensee badge on index page', function (): void {
    $user = User::factory()->create();
    Company::query()->find(Company::LICENSEE_ID)
        ?? Company::factory()->create(['id' => Company::LICENSEE_ID]);

    $this->actingAs($user);

    Livewire::test('companies.index')
        ->assertSee(__('Licensee'));
});

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
        ->and($company->code)
        ->toBe('northwind_holdings')
        ->and($company->scope_activities['industry'])
        ->toBe('Logistics');
});
