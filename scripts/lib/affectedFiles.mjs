// scripts/lib/affectedFiles.mjs

const FULL_SUITE_TRIGGERS = [
    'vitest.config.ts',
    'vite.config.ts',
    'package.json',
    'pnpm-lock.yaml',
    'tsconfig.json',
    'tsconfig.node.json',
    'eslint.config.js',
    'eslint.a11y.config.js',
];

const FRONTEND_PREFIX = 'resources/js/';
const FRONTEND_EXT = /\.(tsx?|jsx?)$/;

export function classifyChanges(files) {
    if (files.length === 0) {
        return {
            skip: true,
            fullSuite: false,
            reason: 'no changes',
            sourceFiles: [],
        };
    }

    const trigger = files.find((f) => FULL_SUITE_TRIGGERS.includes(f));
    if (trigger) {
        return {
            skip: false,
            fullSuite: true,
            reason: `trigger path changed: ${trigger}`,
            sourceFiles: [],
        };
    }

    const sourceFiles = files.filter(
        (f) =>
            f.startsWith(FRONTEND_PREFIX) &&
            FRONTEND_EXT.test(f) &&
            !f.endsWith('.d.ts') &&
            !f.startsWith('resources/js/test/'),
    );

    if (sourceFiles.length === 0) {
        return {
            skip: true,
            fullSuite: false,
            reason: 'no frontend changes',
            sourceFiles: [],
        };
    }

    return {
        skip: false,
        fullSuite: false,
        reason: null,
        sourceFiles,
    };
}
