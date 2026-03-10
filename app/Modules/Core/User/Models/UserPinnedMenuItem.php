<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A menu item pinned by a user for sidebar quick-access.
 *
 * The menu_item_id is a string identifier matching MenuItem::$id from the
 * runtime menu registry (not a foreign key — menu items are discovered
 * from config files, not stored in the database).
 *
 * @property int $id
 * @property int $user_id
 * @property string $menu_item_id
 * @property int $sort_order
 */
class UserPinnedMenuItem extends Model
{
    protected $table = 'user_pinned_menu_items';

    protected $fillable = [
        'user_id',
        'menu_item_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the user who pinned this item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
