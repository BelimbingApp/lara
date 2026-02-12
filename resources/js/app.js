// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

import './echo'

// Alpine.js - only initialize if not already loaded by Livewire
if (!window.Alpine) {
    import('alpinejs').then((module) => {
        window.Alpine = module.default
        window.Alpine.start()
    })
}
