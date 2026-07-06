import { axe } from 'jest-axe';
import { render, screen, waitFor, fireEvent, act, userEvent } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    seminarsApi: {
        list: vi.fn(),
    },
}));

import { SeminarCombobox, type SeminarOption } from './SeminarCombobox';
import { seminarsApi } from '../api/adminClient';

const makeSeminar = (id: number, name: string): SeminarOption => ({
    id,
    name,
    scheduled_at: '2026-06-15T14:00:00Z',
});

const makePage = (
    items: SeminarOption[],
    { current = 1, last = 1 }: { current?: number; last?: number } = {},
) => ({
    data: items,
    meta: {
        current_page: current,
        last_page: last,
        per_page: 15,
        total: items.length,
        from: items.length ? 1 : 0,
        to: items.length,
    },
    links: { first: '', last: '', prev: null, next: null },
});

const setScrollMetrics = (
    el: Element,
    {
        scrollTop,
        scrollHeight,
        clientHeight,
    }: { scrollTop: number; scrollHeight: number; clientHeight: number },
) => {
    Object.defineProperty(el, 'scrollTop', { value: scrollTop, configurable: true });
    Object.defineProperty(el, 'scrollHeight', { value: scrollHeight, configurable: true });
    Object.defineProperty(el, 'clientHeight', { value: clientHeight, configurable: true });
};

beforeEach(() => {
    vi.mocked(seminarsApi.list).mockReset();
    vi.mocked(seminarsApi.list).mockResolvedValue(
        makePage([makeSeminar(1, 'Seminar A'), makeSeminar(2, 'Seminar B')]) as any,
    );
});

describe('SeminarCombobox', () => {
    it('renders the placeholder when no seminar is selected', () => {
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        expect(screen.getByText('Todos os seminarios')).toBeInTheDocument();
    });

    it('renders the selected seminar name in the trigger', () => {
        render(
            <SeminarCombobox value={makeSeminar(1, 'Seminar A')} onChange={vi.fn()} />,
        );
        expect(screen.getByText('Seminar A')).toBeInTheDocument();
    });

    it('does not fetch seminars until opened', () => {
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        expect(seminarsApi.list).not.toHaveBeenCalled();
    });

    it('opens on click and lists seminars from the API', async () => {
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /Seminar A/ }),
        ).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /Seminar B/ })).toBeInTheDocument();
        expect(seminarsApi.list).toHaveBeenCalledWith({ page: 1, search: undefined });
    });

    it('calls onChange with the seminar when an option is clicked and closes', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<SeminarCombobox value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Seminar B/ }));

        expect(onChange).toHaveBeenCalledWith(
            expect.objectContaining({ id: 2, name: 'Seminar B' }),
        );
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });

    it('calls onChange with null when "Todos os seminarios" is selected', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <SeminarCombobox value={makeSeminar(1, 'Seminar A')} onChange={onChange} />,
        );

        await user.click(screen.getByRole('combobox'));
        await user.click(
            await screen.findByRole('option', { name: /Todos os seminarios/ }),
        );

        expect(onChange).toHaveBeenCalledWith(null);
    });

    it('searches seminars with the debounced term', async () => {
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(
            await screen.findByPlaceholderText('Buscar seminario...'),
            'machine',
        );

        await waitFor(
            () => {
                expect(seminarsApi.list).toHaveBeenCalledWith({
                    page: 1,
                    search: 'machine',
                });
            },
            { timeout: 2000 },
        );
    });

    it('clears the search when the popover closes', async () => {
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(
            await screen.findByPlaceholderText('Buscar seminario...'),
            'abc',
        );
        await user.keyboard('{Escape}');

        await user.click(screen.getByRole('combobox'));
        expect(
            await screen.findByPlaceholderText('Buscar seminario...'),
        ).toHaveValue('');
    });

    it('marks the selected seminar with aria-selected', async () => {
        const user = userEvent.setup();
        render(
            <SeminarCombobox value={makeSeminar(1, 'Seminar A')} onChange={vi.fn()} />,
        );

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /Seminar A/ }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByRole('option', { name: /Seminar B/ })).toHaveAttribute(
            'aria-selected',
            'false',
        );
    });

    it('fetches the next page when scrolled near the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(seminarsApi.list)
            .mockResolvedValueOnce(
                makePage([makeSeminar(1, 'Seminar A')], { current: 1, last: 2 }) as any,
            )
            .mockResolvedValueOnce(
                makePage([makeSeminar(2, 'Seminar B')], { current: 2, last: 2 }) as any,
            );

        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Seminar A/ });

        const list = screen.getByTestId('seminar-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(
            await screen.findByRole('option', { name: /Seminar B/ }),
        ).toBeInTheDocument();
        expect(seminarsApi.list).toHaveBeenCalledWith({ page: 2, search: undefined });
    });

    it('does not fetch the next page when far from the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(seminarsApi.list).mockResolvedValue(
            makePage([makeSeminar(1, 'Seminar A')], { current: 1, last: 2 }) as any,
        );

        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Seminar A/ });

        const list = screen.getByTestId('seminar-combobox-list');
        setScrollMetrics(list, { scrollTop: 0, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(seminarsApi.list).toHaveBeenCalledTimes(1);
    });

    it('does not fetch beyond the last page', async () => {
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Seminar A/ });

        const list = screen.getByTestId('seminar-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(seminarsApi.list).toHaveBeenCalledTimes(1);
    });

    it('shows the loading state while the first page loads', async () => {
        vi.mocked(seminarsApi.list).mockReturnValue(new Promise(() => {}) as any);
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(await screen.findByText('Carregando...')).toBeInTheDocument();
    });

    it('shows "Carregando mais..." while fetching the next page', async () => {
        const user = userEvent.setup();
        vi.mocked(seminarsApi.list)
            .mockResolvedValueOnce(
                makePage([makeSeminar(1, 'Seminar A')], { current: 1, last: 2 }) as any,
            )
            .mockReturnValueOnce(new Promise(() => {}) as any);

        render(<SeminarCombobox value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Seminar A/ });

        const list = screen.getByTestId('seminar-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(await screen.findByText('Carregando mais...')).toBeInTheDocument();
    });

    it('shows the empty state when no seminars match', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(makePage([]) as any);
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByText('Nenhum seminario encontrado'),
        ).toBeInTheDocument();
    });

    it('selects an option via keyboard (Enter)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<SeminarCombobox value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Seminar A/ });
        option.focus();
        await user.keyboard('{Enter}');

        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ id: 1 }));
    });

    it('selects an option via keyboard (Space)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<SeminarCombobox value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Seminar B/ });
        option.focus();
        await user.keyboard(' ');

        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ id: 2 }));
    });

    it('does not select for other keys', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<SeminarCombobox value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Seminar A/ });
        option.focus();
        await user.keyboard('x');

        expect(onChange).not.toHaveBeenCalled();
    });

    it('has no axe violations when open', async () => {
        const user = userEvent.setup();
        render(<SeminarCombobox value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Seminar A/ });

        // The "region" best-practice rule flags the RTL render root (a bare
        // <div> at document.body) for not being wrapped in a page landmark
        // (e.g. <main>) — an artifact of mounting this component in
        // isolation, not a real defect. In the app it always renders inside
        // a page layout that provides landmarks. Every other rule (including
        // the popover dialog's accessible name) is still checked.
        expect(
            await axe(document.body, { rules: { region: { enabled: false } } }),
        ).toHaveNoViolations();
    });
});
