// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong

// Alpine.js - only initialize if not already loaded by Livewire
if (!window.Alpine) {
    import('alpinejs').then((module) => {
        window.Alpine = module.default
        window.Alpine.start()
    })
}
