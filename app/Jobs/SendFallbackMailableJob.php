<?php

namespace App\Jobs;

use App\Services\FallbackMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFallbackMailableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string|array $to,
        public readonly Mailable $mailable,
        public readonly array $context = []
    ) {
    }

    public function handle(FallbackMailService $mailService): void
    {
        $sent = $mailService->sendMailable($this->to, $this->mailable, array_merge($this->context, [
            'queued' => true,
            'job_id' => $this->job?->getJobId(),
        ]));

        if (!$sent) {
            throw new \RuntimeException('Both primary and fallback SMTP deliveries failed.');
        }
    }
}
