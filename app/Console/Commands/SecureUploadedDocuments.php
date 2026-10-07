<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SecureUploadedDocuments extends Command
{
    protected $signature = 'documents:secure';

    protected $description = 'Move existing COA and QC report files to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0;

        foreach (['coas', 'qc-reports'] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $stream = $public->readStream($path);
                if ($stream === false) {
                    throw new RuntimeException("Unable to read public document: {$path}");
                }

                try {
                    $written = $private->put($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if (! $written || ! $private->exists($path)) {
                    throw new RuntimeException("Unable to copy document to private storage: {$path}");
                }

                if ($public->size($path) !== $private->size($path)) {
                    throw new RuntimeException("Document copy verification failed: {$path}");
                }

                if (hash_file('sha256', $public->path($path)) !== hash_file('sha256', $private->path($path))) {
                    throw new RuntimeException("Document integrity verification failed: {$path}");
                }

                if (! $public->delete($path)) {
                    throw new RuntimeException("Unable to remove public document: {$path}");
                }

                $moved++;
            }
        }

        $this->info("Secured {$moved} document(s).");

        return self::SUCCESS;
    }
}
