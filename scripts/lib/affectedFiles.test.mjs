// scripts/lib/affectedFiles.test.mjs
import { describe, it, expect } from 'vitest';
import { classifyChanges } from './affectedFiles.mjs';

describe('classifyChanges', () => {
    it('returns skip when there are no changes', () => {
        expect(classifyChanges([])).toEqual({
            skip: true,
            fullSuite: false,
            reason: 'no changes',
            sourceFiles: [],
        });
    });

    it('falls back to the full suite when vitest config changes', () => {
        const result = classifyChanges([
            'vitest.config.ts',
            'resources/js/system/foo.ts',
        ]);
        expect(result.fullSuite).toBe(true);
        expect(result.reason).toContain('vitest.config.ts');
    });

    it('falls back to the full suite when package.json changes', () => {
        expect(classifyChanges(['package.json']).fullSuite).toBe(true);
        expect(classifyChanges(['pnpm-lock.yaml']).fullSuite).toBe(true);
        expect(classifyChanges(['tsconfig.json']).fullSuite).toBe(true);
    });

    it('keeps only frontend source/test files', () => {
        const result = classifyChanges([
            'resources/js/system/foo.ts',
            'resources/js/system/foo.test.ts',
            'resources/views/welcome.blade.php',
            'app/Models/User.php',
        ]);
        expect(result).toEqual({
            skip: false,
            fullSuite: false,
            reason: null,
            sourceFiles: [
                'resources/js/system/foo.ts',
                'resources/js/system/foo.test.ts',
            ],
        });
    });

    it('skips when no frontend files are touched', () => {
        const result = classifyChanges([
            'app/Models/User.php',
            'README.md',
        ]);
        expect(result.skip).toBe(true);
    });

    it('ignores .d.ts and test/setup files for the source list', () => {
        const result = classifyChanges([
            'resources/js/types.d.ts',
            'resources/js/test/setup.ts',
            'resources/js/system/bar.ts',
        ]);
        expect(result.sourceFiles).toEqual(['resources/js/system/bar.ts']);
    });
});
