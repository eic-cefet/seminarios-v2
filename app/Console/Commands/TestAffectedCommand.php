<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Support\AffectedTestPlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TestAffectedCommand extends Command
{
    protected $signature = 'test:affected
                            {--base=origin/main : Git ref to diff against}
                            {--clover= : Optional clover output path}';

    protected $description = 'Run only the backend tests affected by changes vs the given base ref.';

    public function handle(AffectedTestPlanner $planner): int
    {
        $base = (string) $this->option('base');
        $plan = $planner->resolve($base);

        if ($plan['skip']) {
            $this->info('No affected backend tests ('.$plan['reason'].').');

            return self::SUCCESS;
        }

        $command = ['php', 'artisan', 'test', '--compact'];

        if ($plan['full_suite']) {
            $this->warn('Running full suite — '.$plan['reason']);
            $command[] = '--min=95';
        } else {
            $this->info(sprintf(
                'Running %d test file(s) for %d source file(s).',
                count($plan['test_files']),
                count($plan['source_files']),
            ));

            foreach ($plan['coverage_filter'] as $path) {
                $command[] = '--coverage-filter='.$path;
            }

            $command[] = '--min=100';

            foreach ($plan['test_files'] as $testFile) {
                $command[] = $testFile;
            }
        }

        if ($this->option('clover')) {
            $command[] = '--coverage-clover='.$this->option('clover');
        }

        $result = Process::forever()->tty(false)->run(implode(' ', array_map('escapeshellarg', $command)), function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $result->exitCode() ?? 1;
    }
}
