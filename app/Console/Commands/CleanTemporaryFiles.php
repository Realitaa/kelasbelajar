<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\FileService;
use Illuminate\Console\Command;

class CleanTemporaryFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary media uploads older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(FileService $fileService): void
    {
        $cutoff = now()->subHours(24);

        $temporaryMedia = Media::where('status', 'temporary')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($temporaryMedia as $media) {
            $fileService->remove($media);
        }

        $this->info("Cleaned up {$temporaryMedia->count()} temporary media file(s).");
    }
}
