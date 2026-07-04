/**
 * Shared mock for the project's `components/ui/select` wrapper around Radix Select.
 *
 * Why: Several admin tests need to drive a `Select` component without dragging in
 * Radix's portal/positioning logic, which is brittle under jsdom. Each consumer
 * was inlining ~35 lines of near-identical mock — this helper is the single
 * source of truth.
 *
 * Usage in a test file:
 *
 *   import { createSelectMockModule } from '@/test/mocks/radixSelect';
 *
 *   vi.mock('../../components/ui/select', async () => {
 *       const React = await vi.importActual<typeof import('react')>('react');
 *       return createSelectMockModule(React);
 *   });
 *
 * The path passed to `vi.mock` must remain literal at the call site (so Vitest
 * can hoist the registration), but the body can call into this helper because
 * `vi.mock` factories re-execute per test file at runtime.
 *
 * Options:
 *   - `renderItemsAsSiblings` (default `false`): when `true`, the original
 *     SelectItem children are rendered as sibling DOM nodes in addition to the
 *     native `<select>`. SeminarForm relies on this to keep certain visual
 *     labels queryable by text. Other consumers (FeedbackInsights,
 *     AuditLogReport) don't need it.
 *   - `includeEmptyOption` (default `false`): when `true`, the native `<select>`
 *     starts with an empty `<option value="">`. SeminarForm uses this so tests
 *     can `fireEvent.change(select, { target: { value: '' } })` to clear it.
 *   - `defaultValue` (default `''`): the value rendered on the native `<select>`
 *     when the consumer hasn't provided one yet. AuditLogReport / FeedbackInsights
 *     consumers initialize with `'all'`, so leaving this at `''` is fine for them.
 */

import type * as ReactNS from 'react';

export interface SelectMockOptions {
    renderItemsAsSiblings?: boolean;
    includeEmptyOption?: boolean;
    defaultValue?: string;
}

export interface SelectMockModule {
    Select: ReactNS.ComponentType<{
        children?: ReactNS.ReactNode;
        value?: string;
        onValueChange?: (value: string) => void;
    }>;
    SelectTrigger: ReactNS.ComponentType<{
        children?: ReactNS.ReactNode;
        id?: string;
    }>;
    SelectValue: ReactNS.ComponentType<{
        placeholder?: ReactNS.ReactNode;
        children?: ReactNS.ReactNode;
    }>;
    SelectContent: ReactNS.ComponentType<{ children?: ReactNS.ReactNode }>;
    SelectItem: ReactNS.ComponentType<{
        children?: ReactNS.ReactNode;
        value: string;
    }>;
}

/**
 * Build the mock module for `components/ui/select`.
 *
 * The caller passes in the test-time React instance (via `vi.importActual`) so
 * that the components rendered by the mock share the same React copy as the
 * component under test.
 */
export function createSelectMockModule(
    React: typeof ReactNS,
    options: SelectMockOptions = {},
): SelectMockModule {
    const {
        renderItemsAsSiblings = false,
        includeEmptyOption = false,
        defaultValue = '',
    } = options;

    interface SelectContextValue {
        onValueChange?: (value: string) => void;
        value?: string;
    }

    const SelectContext = React.createContext<SelectContextValue>({});

    function MockSelect({
        children,
        value,
        onValueChange,
    }: {
        children?: ReactNS.ReactNode;
        value?: string;
        onValueChange?: (value: string) => void;
    }) {
        return React.createElement(
            SelectContext.Provider,
            { value: { onValueChange, value } },
            React.createElement(
                'div',
                { 'data-mock-select': 'true' },
                children,
            ),
        );
    }

    function MockSelectTrigger({
        children,
        id,
    }: {
        children?: ReactNS.ReactNode;
        id?: string;
    }) {
        return React.createElement(
            'div',
            { role: 'combobox', id },
            children,
        );
    }

    function MockSelectValue({
        placeholder,
        children,
    }: {
        placeholder?: ReactNS.ReactNode;
        children?: ReactNS.ReactNode;
    }) {
        return React.createElement('span', null, children ?? placeholder);
    }

    function MockSelectItem({
        children,
        value,
    }: {
        children?: ReactNS.ReactNode;
        value: string;
    }) {
        return React.createElement(
            'div',
            { 'data-value': value, role: 'option' },
            children,
        );
    }

    function MockSelectContent({
        children,
    }: {
        children?: ReactNS.ReactNode;
    }) {
        const ctx = React.useContext(SelectContext);

        const options: Array<{ value: string; label: ReactNS.ReactNode }> = [];
        const collectOptions = (nodes: ReactNS.ReactNode) => {
            React.Children.forEach(nodes, (child) => {
                if (!child || typeof child !== 'object') {
                    return;
                }
                const element = child as ReactNS.ReactElement<{
                    value?: string;
                    children?: ReactNS.ReactNode;
                }>;
                if (element.type === MockSelectItem) {
                    options.push({
                        value: element.props.value ?? '',
                        label: element.props.children,
                    });
                }
            });
        };
        collectOptions(children);

        const nativeOptions: ReactNS.ReactElement[] = [];
        if (includeEmptyOption) {
            nativeOptions.push(
                React.createElement(
                    'option',
                    { key: '__empty__', value: '' },
                    '',
                ),
            );
        }
        for (const opt of options) {
            nativeOptions.push(
                React.createElement(
                    'option',
                    { key: opt.value, value: opt.value },
                    opt.label,
                ),
            );
        }

        const nativeSelect = React.createElement(
            'select',
            {
                'data-testid': 'mock-native-select',
                value: ctx.value ?? defaultValue,
                onChange: (event: ReactNS.ChangeEvent<HTMLSelectElement>) =>
                    ctx.onValueChange?.(event.target.value),
            },
            ...nativeOptions,
        );

        if (renderItemsAsSiblings) {
            return React.createElement('div', null, nativeSelect, children);
        }

        return nativeSelect;
    }

    return {
        Select: MockSelect,
        SelectTrigger: MockSelectTrigger,
        SelectValue: MockSelectValue,
        SelectContent: MockSelectContent,
        SelectItem: MockSelectItem,
    };
}
