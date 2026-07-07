import { axe } from 'jest-axe';
import { render, screen, waitFor, fireEvent, act, userEvent } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    usersApi: {
        list: vi.fn(),
    },
}));

import { UserMultiCombobox, type UserOption } from './UserMultiCombobox';
import { usersApi } from '../api/adminClient';

const makeUser = (id: number, name: string): UserOption => ({
    id,
    name,
    email: `user${id}@cefet-rj.br`,
});

const makePage = (
    items: UserOption[],
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
    vi.mocked(usersApi.list).mockReset();
    vi.mocked(usersApi.list).mockResolvedValue(
        makePage([makeUser(1, 'Ana Silva'), makeUser(2, 'Bruno Costa')]) as any,
    );
});

describe('UserMultiCombobox', () => {
    it('renders the placeholder when nothing is selected', () => {
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        expect(screen.getByText('Selecionar usuarios')).toBeInTheDocument();
    });

    it('renders selected users as chips', () => {
        render(
            <UserMultiCombobox
                value={[makeUser(1, 'Ana Silva'), makeUser(2, 'Bruno Costa')]}
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByText('Ana Silva')).toBeInTheDocument();
        expect(screen.getByText('Bruno Costa')).toBeInTheDocument();
        expect(screen.queryByText('Selecionar usuarios')).not.toBeInTheDocument();
    });

    it('does not fetch users until opened', () => {
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        expect(usersApi.list).not.toHaveBeenCalled();
    });

    it('opens on click and lists users with name and email', async () => {
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /Ana Silva/ }),
        ).toBeInTheDocument();
        expect(screen.getByText('user1@cefet-rj.br')).toBeInTheDocument();
        expect(usersApi.list).toHaveBeenCalledWith({ page: 1, search: undefined });
    });

    it('selects a user and keeps the popover open', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<UserMultiCombobox value={[]} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));

        expect(onChange).toHaveBeenCalledWith([
            expect.objectContaining({ id: 1, name: 'Ana Silva' }),
        ]);
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'true');
    });

    it('deselects an already-selected user from the list', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <UserMultiCombobox value={[makeUser(1, 'Ana Silva')]} onChange={onChange} />,
        );

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));

        expect(onChange).toHaveBeenCalledWith([]);
    });

    it('marks selected users with aria-selected', async () => {
        const user = userEvent.setup();
        render(
            <UserMultiCombobox value={[makeUser(1, 'Ana Silva')]} onChange={vi.fn()} />,
        );

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /Ana Silva/ }),
        ).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByRole('option', { name: /Bruno Costa/ })).toHaveAttribute(
            'aria-selected',
            'false',
        );
    });

    it('removes a user via the chip X without opening the popover', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <UserMultiCombobox
                value={[makeUser(1, 'Ana Silva'), makeUser(2, 'Bruno Costa')]}
                onChange={onChange}
            />,
        );

        const chip = screen.getByText('Ana Silva');
        const xIcon = chip.parentElement!.querySelector('svg.cursor-pointer')!;
        await user.click(xIcon);

        expect(onChange).toHaveBeenCalledWith([
            expect.objectContaining({ id: 2 }),
        ]);
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });

    it('searches users with the debounced term', async () => {
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(
            await screen.findByPlaceholderText('Buscar usuario...'),
            'ana',
        );

        await waitFor(
            () => {
                expect(usersApi.list).toHaveBeenCalledWith({ page: 1, search: 'ana' });
            },
            { timeout: 2000 },
        );
    });

    it('clears the search when the popover closes', async () => {
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(await screen.findByPlaceholderText('Buscar usuario...'), 'abc');
        await user.keyboard('{Escape}');

        await user.click(screen.getByRole('combobox'));
        expect(await screen.findByPlaceholderText('Buscar usuario...')).toHaveValue('');
    });

    it('fetches the next page when scrolled near the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(usersApi.list)
            .mockResolvedValueOnce(
                makePage([makeUser(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
            )
            .mockResolvedValueOnce(
                makePage([makeUser(2, 'Bruno Costa')], { current: 2, last: 2 }) as any,
            );

        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('user-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(
            await screen.findByRole('option', { name: /Bruno Costa/ }),
        ).toBeInTheDocument();
        expect(usersApi.list).toHaveBeenCalledWith({ page: 2, search: undefined });
    });

    it('does not fetch the next page when far from the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(usersApi.list).mockResolvedValue(
            makePage([makeUser(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
        );

        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('user-combobox-list');
        setScrollMetrics(list, { scrollTop: 0, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(usersApi.list).toHaveBeenCalledTimes(1);
    });

    it('does not fetch beyond the last page', async () => {
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('user-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(usersApi.list).toHaveBeenCalledTimes(1);
    });

    it('shows the loading state while the first page loads', async () => {
        vi.mocked(usersApi.list).mockReturnValue(new Promise(() => {}) as any);
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(await screen.findByText('Carregando...')).toBeInTheDocument();
    });

    it('shows "Carregando mais..." while fetching the next page', async () => {
        const user = userEvent.setup();
        vi.mocked(usersApi.list)
            .mockResolvedValueOnce(
                makePage([makeUser(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
            )
            .mockReturnValueOnce(new Promise(() => {}) as any);

        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('user-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(await screen.findByText('Carregando mais...')).toBeInTheDocument();
    });

    it('shows the empty state when no users match', async () => {
        vi.mocked(usersApi.list).mockResolvedValue(makePage([]) as any);
        const user = userEvent.setup();
        render(<UserMultiCombobox value={[]} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByText('Nenhum usuario encontrado'),
        ).toBeInTheDocument();
    });

    it('toggles a user via keyboard (Enter)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<UserMultiCombobox value={[]} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Ana Silva/ });
        option.focus();
        await user.keyboard('{Enter}');

        expect(onChange).toHaveBeenCalledWith([
            expect.objectContaining({ id: 1 }),
        ]);
    });

    it('toggles a user via keyboard (Space)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<UserMultiCombobox value={[]} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Bruno Costa/ });
        option.focus();
        await user.keyboard(' ');

        expect(onChange).toHaveBeenCalledWith([
            expect.objectContaining({ id: 2 }),
        ]);
    });

    it('does not toggle for other keys', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<UserMultiCombobox value={[]} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Ana Silva/ });
        option.focus();
        await user.keyboard('x');

        expect(onChange).not.toHaveBeenCalled();
    });

    it('has no axe violations when open', async () => {
        const user = userEvent.setup();
        render(
            <div>
                <label htmlFor="user-picker">Usuarios</label>
                <UserMultiCombobox id="user-picker" value={[]} onChange={vi.fn()} />
            </div>,
        );

        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        expect(
            await axe(document.body, { rules: { region: { enabled: false } } }),
        ).toHaveNoViolations();
    });
});
