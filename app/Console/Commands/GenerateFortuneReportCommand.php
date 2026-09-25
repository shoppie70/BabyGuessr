<?php

namespace App\Console\Commands;

use App\Models\BabyProfile;
use App\Services\Fortune\FortuneManager;
use App\Services\Fortune\GeminiFortuneException;
use App\Services\Fortune\GeminiFortuneService;
use Illuminate\Console\Command;

class GenerateFortuneReportCommand extends Command
{
    protected $signature = 'fortune:generate-report
                            {--id= : BabyProfile ID}
                            {--live : Call real Gemini API (default is blocked without this flag)}';

    protected $description = 'Generate AI fortune report via Gemini Interactions API (1 call)';

    public function handle(FortuneManager $manager, GeminiFortuneService $gemini): int
    {
        if (!$this->option('live')) {
            $this->error('本番API呼び出しには --live が必要です。');

            return self::FAILURE;
        }

        if (empty(config('services.gemini.api_key'))) {
            $this->error('GEMINI_API_KEY が未設定です。.env に設定してください。');

            return self::FAILURE;
        }

        $query = BabyProfile::query();
        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $profile = $query->first();
        if (!$profile) {
            $this->error('BabyProfile が見つかりません。');

            return self::FAILURE;
        }

        $this->info('Calculating fortunes...');
        $manager->calculateAll($profile);

        $this->info('Calling Gemini Interactions API (store=false)...');

        try {
            $report = $gemini->generate($profile);
        } catch (GeminiFortuneException $e) {
            $this->error("失敗: {$e->errorCode}");
            if ($e->httpStatus > 0) {
                $this->line("http: {$e->httpStatus}");
            }
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $data = $report->report_ciphertext ?? [];
        $keys = array_keys($data['reports'] ?? []);

        $this->info('成功');
        $this->line('status: '.$report->status);
        $this->line('model: '.$report->model);
        $this->line('reports: '.implode(', ', $keys));
        $this->line('has_integrated: '.(isset($data['integrated_report']) ? 'yes' : 'no'));
        $this->line('has_parenting: '.(isset($data['parenting_guide']) ? 'yes' : 'no'));
        $this->line('tokens: in='.($report->input_tokens ?? '—').' out='.($report->output_tokens ?? '—'));

        return self::SUCCESS;
    }
}
