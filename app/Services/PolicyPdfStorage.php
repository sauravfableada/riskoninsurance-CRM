<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PolicyPdfStorage
{
    public function inline(string $file, ?string $originalName = null): BinaryFileResponse
    {
        // AI uploads are private; manually uploaded policy PDFs use the public policies folder.
        $file = str_replace('\\', '/', $file);
        if (preg_match('#^policy_pdfs/[0-9]+/[^/]+\.pdf$#i', $file) && !str_contains($file, '..')) {
            $disk = Storage::disk('local');
            $path = $file;
        } else {
            $disk = Storage::disk('public');
            $path = 'policies/'.basename($file);
        }
        abort_unless($disk->exists($path), 404, 'File missing');

        return response()->file($disk->path($path), ['Content-Type' => $disk->mimeType($path) ?: 'application/pdf'])
            ->setContentDisposition('inline', basename($originalName ?: $file));
    }
}
