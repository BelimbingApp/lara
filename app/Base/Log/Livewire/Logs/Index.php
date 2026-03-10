<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Log\Livewire\Logs;

use Illuminate\Support\Facades\File;
use Livewire\Component;

class Index extends Component
{
    public string $selectedFile = '';

    public function selectFile(string $filename): void
    {
        $this->selectedFile = basename($filename);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $logPath = storage_path('logs');
        $files = collect(File::files($logPath))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $tailContent = null;
        if ($this->selectedFile) {
            $path = $logPath.DIRECTORY_SEPARATOR.$this->selectedFile;
            if (File::exists($path) && str_starts_with(realpath($path), realpath($logPath))) {
                $lines = file($path, FILE_IGNORE_NEW_LINES);
                $tailContent = implode("\n", array_slice($lines, -100));
            }
        }

        return view('livewire.admin.system.logs.index', [
            'files' => $files,
            'tailContent' => $tailContent,
        ]);
    }
}
