#!/usr/bin/env node
// scripts/test-affected-frontend.mjs
import { spawnSync } from 'node:child_process';
import { classifyChanges } from './lib/affectedFiles.mjs';

const args = process.argv.slice(2);
const baseFlag = args.find((a) => a.startsWith('--base='));
const base = baseFlag ? baseFlag.slice('--base='.length) : 'origin/main';

// Two-dot form against `base` includes the working tree (committed + staged + unstaged)
// so uncommitted edits are picked up during local development.
const diff = spawnSync(
    'git',
    ['diff', '--name-only', '--diff-filter=ACMR', base],
    { encoding: 'utf8' },
);

if (diff.status !== 0) {
    console.error('git diff failed:', diff.stderr);
    process.exit(diff.status ?? 1);
}

const files = diff.stdout
    .split(/\r?\n/)
    .map((f) => f.trim())
    .filter((f) => f.length > 0);

const plan = classifyChanges(files);

if (plan.skip) {
    console.log(`No affected frontend tests (${plan.reason}).`);
    process.exit(0);
}

const vitestArgs = ['exec', 'vitest', 'run', '--coverage'];

if (plan.fullSuite) {
    console.warn(`Running full frontend suite — ${plan.reason}`);
} else {
    vitestArgs.push(`--changed=${base}`);
    for (const file of plan.sourceFiles) {
        vitestArgs.push(`--coverage.include=${file}`);
    }
    vitestArgs.push('--coverage.thresholds.100');
    console.log(
        `Running vitest --changed against ${base} for ${plan.sourceFiles.length} file(s).`,
    );
}

const result = spawnSync('pnpm', vitestArgs, { stdio: 'inherit' });
process.exit(result.status ?? 1);
