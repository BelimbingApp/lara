<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Log\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class LogController
{
    /**
     * Show log files and selected tail content.
     */
    public function index(Request $request): View
    {
        $logPath = storage_path('logs');
        $selectedFile = basename($request->string('file', '')->toString());

        $files = collect(File::files($logPath))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $tailContent = null;
        if ($selectedFile !== '') {
            $path = $logPath.DIRECTORY_SEPARATOR.$selectedFile;
            if (File::exists($path) && str_starts_with(realpath($path), realpath($logPath))) {
                $tailLines = 100;
                $fileObject = new \SplFileObject($path, 'r');
                $fileObject->seek(PHP_INT_MAX);
                $lastLine = $fileObject->key();
                $startLine = max(0, $lastLine - $tailLines + 1);

                $lines = [];
                for ($lineNumber = $startLine; $lineNumber <= $lastLine; $lineNumber++) {
                    $fileObject->seek($lineNumber);
                    $lines[] = rtrim($fileObject->current(), "\r\n");
                }

                $tailContent = implode("\n", $lines);
            }
        }

        return view('admin.system.logs.index', compact('files', 'selectedFile', 'tailContent'));
    }
}
