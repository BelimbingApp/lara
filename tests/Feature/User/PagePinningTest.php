<?php

use App\Modules\Core\User\Models\User;
use App\Modules\Core\User\Models\UserPin;

test('pin reorder uses type-aware identities', function (): void {
    $user = User::factory()->create();

    UserPin::query()->create([
        'user_id' => $user->id,
        'type' => UserPin::TYPE_MENU_ITEM,
        'pinnable_id' => 'shared',
        'label' => 'Menu Pin',
        'url' => '/menu',
        'sort_order' => 0,
    ]);

    UserPin::query()->create([
        'user_id' => $user->id,
        'type' => UserPin::TYPE_PAGE,
        'pinnable_id' => 'shared',
        'label' => 'Page Pin',
        'url' => '/page',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->postJson(route('pins.reorder'), [
            'pins' => [
                ['type' => UserPin::TYPE_PAGE, 'pinnable_id' => 'shared'],
                ['type' => UserPin::TYPE_MENU_ITEM, 'pinnable_id' => 'shared'],
            ],
        ])
        ->assertOk();

    $orderedPins = UserPin::query()
        ->where('user_id', $user->id)
        ->orderBy('sort_order')
        ->get(['type', 'pinnable_id'])
        ->map(fn (UserPin $pin): string => $pin->type.':'.$pin->pinnable_id)
        ->all();

    expect($orderedPins)->toBe([
        UserPin::TYPE_PAGE.':shared',
        UserPin::TYPE_MENU_ITEM.':shared',
    ]);
});
