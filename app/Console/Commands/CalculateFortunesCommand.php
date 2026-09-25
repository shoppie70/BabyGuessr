<?php

namespace App\Console\Commands;

use App\Models\BabyProfile;
use App\Services\Fortune\FortuneManager;
use Illuminate\Console\Command;

class CalculateFortunesCommand extends Command
{
    protected $signature = 'fortune:calculate
                            {--id= : BabyProfile ID}
                            {--token= : Manage token or Setup token}
                            {--calculator= : Calculate only a specific calculator}
                            {--force : Force recalculation ignoring cache}';

    protected $description = 'Calculate deterministic fortune results for baby profiles';

    public function handle(FortuneManager $manager): int
    {
        $query = BabyProfile::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($token = $this->option('token')) {
            $query->where(function ($q) use ($token) {
                $q->where('manage_token', $token)
                  ->orWhere('setup_token', $token);
            });
        }

        $profiles = $query->get();

        if ($profiles->isEmpty()) {
            $this->warn('No matching BabyProfile found.');
            return self::FAILURE;
        }

        $calculatorKey = $this->option('calculator');
        $force = (bool)$this->option('force');

        foreach ($profiles as $profile) {
            $this->info("Processing Profile ID: {$profile->id} (Status: {$profile->status})");

            if ($calculatorKey) {
                $this->line("  Calculating single: {$calculatorKey}...");
                $result = $manager->calculateSingle($profile, $calculatorKey, $force);
                $this->info("  [{$result->status}] {$calculatorKey} (v{$result->calculatorVersion})");
            } else {
                $results = $manager->calculateAll($profile, $force);
                foreach ($results as $key => $result) {
                    $color = match ($result->status) {
                        'completed' => 'info',
                        'partial' => 'comment',
                        'unavailable' => 'warn',
                        default => 'error',
                    };
                    $this->$color("  [{$result->status}] {$key} (v{$result->calculatorVersion})");
                }
            }
        }

        $this->info('Fortune calculation complete.');
        return self::SUCCESS;
    }
}
