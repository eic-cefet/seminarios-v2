declare module 'jest-axe' {
    export interface AxeResults {
        violations: AxeViolation[];
        [key: string]: unknown;
    }

    export interface AxeViolation {
        id: string;
        impact: string | null;
        description: string;
        help: string;
        helpUrl: string;
        nodes: unknown[];
        tags: string[];
    }

    export interface RunOptions {
        [key: string]: unknown;
    }

    export function axe(
        html: Element | string,
        options?: RunOptions,
    ): Promise<AxeResults>;

    export function configureAxe(options?: { globalOptions?: RunOptions }): typeof axe;

    export const toHaveNoViolations: {
        toHaveNoViolations(results: AxeResults): { pass: boolean; message(): string };
    };
}
