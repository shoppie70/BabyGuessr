<?php

namespace App\Console\Commands;

use App\Models\FortuneReport;
use App\Services\Fortune\GeminiFortuneService;
use Illuminate\Console\Command;

class SyncFortuneBatchCommand extends Command
{
    protected $signature = 'fortune:sync-batch';

    protected $description = '未送信の鑑定を Gemini Batch に渡し、完了した結果を保存する';

    public function handle(GeminiFortuneService $gemini): int
    {
        FortuneReport::query()
            ->where('status', FortuneReport::STATUS_QUEUED)
            ->with('babyProfile')
            ->each(function (FortuneReport $report) use ($gemini) {
                if ($report->babyProfile) {
                    $gemini->queueBatch($report->babyProfile);
                }
            });

        FortuneReport::query()
            ->where('status', FortuneReport::STATUS_GENERATING)
            ->whereNotNull('batch_name')
            ->each(fn (FortuneReport $report) => $gemini->pullBatch($report));

        return self::SUCCESS;
    }
}
