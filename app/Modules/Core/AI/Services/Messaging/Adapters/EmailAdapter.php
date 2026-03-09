<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Services\Messaging\Adapters;

use App\Modules\Core\AI\DTO\Messaging\ChannelCapabilities;

/**
 * Email channel adapter stub.
 *
 * Provides the contract skeleton for email-based messaging integration.
 * All send methods return failure results until the channel integration
 * is fully configured with SMTP/IMAP and DB-backed accounts.
 */
class EmailAdapter extends BaseChannelAdapter
{
    protected function channelKey(): string
    {
        return 'email';
    }

    protected function channelLabel(): string
    {
        return 'Email';
    }

    public function capabilities(): ChannelCapabilities
    {
        return new ChannelCapabilities(
            supportsMedia: true,
            supportsSearch: true,
            maxMessageLength: 100000,
            mediaTypes: ['image', 'document', 'audio', 'video'],
        );
    }
}
