<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user-pinned item for sidebar quick-access.
 *
 * Supports two pin types:
 * - menu_item: references a MenuItem::$id from the runtime menu registry
 * - page: references an arbitrary page by URL (e.g. a tool workspace)
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $pinnable_id
 * @property string $label
 * @property string $url
 * @property string|null $icon
 * @property int $sort_order
 */
class UserPin extends Model
{
    public const TYPE_MENU_ITEM = 'menu_item';

    public const TYPE_PAGE = 'page';

    protected $table = 'user_pins';

    protected $fillable = [
        'user_id',
        'type',
        'pinnable_id',
        'label',
        'url',
        'icon',
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
