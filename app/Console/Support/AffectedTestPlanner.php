<?php

declare(strict_types=1);

namespace App\Console\Support;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * @phpstan-type Plan array{
 *   skip: bool,
 *   full_suite: bool,
 *   reason: ?string,
 *   source_files: list<string>,
 *   test_files: list<string>,
 *   coverage_filter: list<string>,
 * }
 */
class AffectedTestPlanner
{
    /** @var list<string> */
    private const FULL_SUITE_TRIGGERS = [
        'composer.json',
        'composer.lock',
        'phpunit.xml',
        'bootstrap/',
        'config/',
        'database/',
        'routes/',
        '.env.example',
        '.github/workflows/',
    ];

    /**
     * @return Plan
     */
    public function resolve(string $base): array
    {
        $files = $this->changedFiles($base);

        if ($files === []) {
            return $this->skip('no changes vs '.$base);
        }

        foreach ($files as $file) {
            foreach (self::FULL_SUITE_TRIGGERS as $trigger) {
                if (str_starts_with($file, $trigger)) {
                    return [
                        'skip' => false,
                        'full_suite' => true,
                        'reason' => 'trigger path changed: '.$file,
                        'source_files' => [],
                        'test_files' => [],
                        'coverage_filter' => ['app/'],
                    ];
                }
            }
        }

        $sources = array_values(array_filter(
            $files,
            fn (string $f): bool => str_starts_with($f, 'app/') && str_ends_with($f, '.php'),
        ));

        $changedTests = array_values(array_filter(
            $files,
            fn (string $f): bool => str_starts_with($f, 'tests/') && str_ends_with($f, '.php'),
        ));

        $mappedTests = $this->mapSourcesToTests($sources);

        $tests = array_values(array_unique([...$changedTests, ...$mappedTests]));

        if ($sources === [] && $tests === []) {
            return $this->skip('no PHP changes vs '.$base);
        }

        return [
            'skip' => false,
            'full_suite' => false,
            'reason' => null,
            'source_files' => $sources,
            'test_files' => $tests,
            'coverage_filter' => $sources,
        ];
    }

    /**
     * @return Plan
     */
    private function skip(string $reason): array
    {
        return [
            'skip' => true,
            'full_suite' => false,
            'reason' => $reason,
            'source_files' => [],
            'test_files' => [],
            'coverage_filter' => [],
        ];
    }

    /**
     * @return list<string>
     */
    private function changedFiles(string $base): array
    {
        // Two-dot form against $base diffs the working tree (HEAD + staged + unstaged)
        // against $base, so uncommitted edits are picked up during local development.
        $result = Process::run([
            'git', 'diff', '--name-only', '--diff-filter=ACMR', $base,
        ]);

        if ($result->failed()) {
            throw new RuntimeException('git diff failed: '.$result->errorOutput());
        }

        $lines = preg_split('/\R/', trim($result->output())) ?: [];

        return array_values(array_filter($lines, fn (string $l): bool => $l !== ''));
    }

    /**
     * @param  list<string>  $sources
     * @return list<string>
     */
    private function mapSourcesToTests(array $sources): array
    {
        $tests = [];

        foreach ($sources as $source) {
            $fqcn = $this->fqcnFromPath($source);

            if ($fqcn === null) {
                continue;
            }

            $result = Process::run([
                'grep', '-rlF', '--include=*.php', $fqcn, 'tests/',
            ]);

            if ($result->output() === '') {
                continue;
            }

            $hits = preg_split('/\R/', trim($result->output())) ?: [];

            foreach ($hits as $hit) {
                if ($hit !== '' && str_ends_with($hit, 'Test.php')) {
                    $tests[] = $hit;
                }
            }
        }

        return array_values(array_unique($tests));
    }

    private function fqcnFromPath(string $path): ?string
    {
        if (! str_starts_with($path, 'app/') || ! str_ends_with($path, '.php')) {
            return null;
        }

        $relative = substr($path, 4, -4);

        if ($relative === '') {
            return null;
        }

        $parts = explode('/', $relative);

        foreach ($parts as $part) {
            if (preg_match('/^[A-Z][A-Za-z0-9_]*$/', $part) !== 1) {
                return null;
            }
        }

        return 'App\\'.implode('\\', $parts);
    }
}
