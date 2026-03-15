# Log Viewer

**Module:** `app/Base/Log`
**Routes:** `admin/system/logs`
**Last Updated:** 2026-03-15

## Overview

The Log Viewer provides a browser-based interface for inspecting, searching, and managing Laravel log files from `storage/logs/`. It replaces the need to SSH into a server to read logs, offering windowed reading, real-time search, line trimming, and UTC-to-local-time conversion.

## Pages

### Index (`admin/system/logs`)

Lists all `.log` files in `storage/logs/`, sorted by last-modified time (newest first). Each entry shows:

- Filename
- File size
- Last modified timestamp

**Component:** `App\Base\Log\Livewire\Logs\Index`

### Show (`admin/system/logs/{filename}`)

Displays the contents of a single log file with chunked reading — the file is never loaded entirely into memory.

**Component:** `App\Base\Log\Livewire\Logs\Show`

## Features

### Windowed Reading

Log files are read in configurable chunks (default: 100 lines, max: 1000). Two modes control which end of the file the window anchors to:

| Mode | Anchor | "Next" direction | Default |
|------|--------|-------------------|---------|
| **Tail** | End of file | Older (upward) | ✓ |
| **Top** | Start of file | Further (downward) | |

The window number and mode are URL-synced (`#[Url]`), so the view state survives page refreshes and can be shared via URL.

### Search

A live search filter (`wire:model.live.debounce.300ms`) narrows displayed lines to those containing the search term (case-insensitive `stripos`). The filter is applied client-side within the current window — it does not re-read the file.

### UTC → Local Time Toggle

A toggle button converts ISO 8601 timestamps (e.g., `2026-03-15T10:30:00Z`) to the browser's local timezone using JavaScript's `Date.toLocaleString()`. This is purely client-side via Alpine.js — no server round-trip.

The regex pattern matches: `YYYY-MM-DDTHH:MM:SS`, with optional fractional seconds and timezone offset.

### Line Deletion

**Delete lines from top** removes a configurable number of lines from the beginning of the file. This is useful for trimming old entries from large log files without deleting the entire file. Uses streaming read/write (`SplFileObject`) to avoid loading the full file into memory.

### File Deletion

Permanently deletes the log file and redirects to the index.

### Refresh

Explicitly re-renders the component to pick up new log entries written since the page loaded.

## Security

- **Path traversal protection:** The filename is passed through `basename()` and the resolved path is validated to be within `storage/logs/` via `str_starts_with(realpath(...))`.
- **Auth required:** All routes are wrapped in the `auth` middleware.

## File Structure

```
app/Base/Log/
├── Livewire/
│   └── Logs/
│       ├── Index.php              # File listing
│       └── Show.php               # File viewer
├── Routes/
│   └── web.php                    # Route definitions
└── ServiceProvider.php

resources/core/views/livewire/admin/system/logs/
├── index.blade.php
└── show.blade.php
```

## Routes

| Route | Name | Component |
|-------|------|-----------|
| `GET admin/system/logs` | `admin.system.logs.index` | `Logs\Index` |
| `GET admin/system/logs/{filename}` | `admin.system.logs.show` | `Logs\Show` |
