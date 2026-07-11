import { axe } from 'jest-axe';
import { render, screen, waitFor, fireEvent, act, userEvent } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    studentsApi: {
        list: vi.fn(),
    },
}));

import { StudentCombobox, type StudentOption } from './StudentCombobox';
import { studentsApi } from '../api/adminClient';

const makeStudent = (id: number, name: string): StudentOption => ({
    id,
    name,
    email: `aluno${id}@cefet-rj.br`,
});

const makePage = (
    items: StudentOption[],
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
    vi.mocked(studentsApi.list).mockReset();
    vi.mocked(studentsApi.list).mockResolvedValue(
        makePage([makeStudent(1, 'Ana Silva'), makeStudent(2, 'Bruno Costa')]) as any,
    );
});

describe('StudentCombobox', () => {
    it('renders the placeholder when no student is selected', () => {
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        expect(screen.getByText('Selecionar aluno')).toBeInTheDocument();
    });

    it('renders the selected student name in the trigger', () => {
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByText('Ana Silva')).toBeInTheDocument();
    });

    it('does not fetch students until opened', () => {
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        expect(studentsApi.list).not.toHaveBeenCalled();
    });

    it('opens on click and lists students with name and email, scoped by semester', async () => {
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /Ana Silva/ }),
        ).toBeInTheDocument();
        expect(screen.getByText('aluno1@cefet-rj.br')).toBeInTheDocument();
        expect(studentsApi.list).toHaveBeenCalledWith({
            semester: '2026.1',
            page: 1,
            search: undefined,
        });
    });

    it('re-fetches when the semester prop changes', async () => {
        const user = userEvent.setup();
        const { rerender } = render(
            <StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />,
        );

        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });
        expect(studentsApi.list).toHaveBeenLastCalledWith({
            semester: '2026.1',
            page: 1,
            search: undefined,
        });

        rerender(<StudentCombobox semester="2026.2" value={null} onChange={vi.fn()} />);

        await waitFor(() => {
            expect(studentsApi.list).toHaveBeenLastCalledWith({
                semester: '2026.2',
                page: 1,
                search: undefined,
            });
        });
    });

    it('calls onChange with the student when an option is clicked and closes the popover', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<StudentCombobox semester="2026.1" value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Bruno Costa/ }));

        expect(onChange).toHaveBeenCalledWith(
            expect.objectContaining({ id: 2, name: 'Bruno Costa' }),
        );
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });

    it('marks the selected student with aria-selected', async () => {
        const user = userEvent.setup();
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={vi.fn()}
            />,
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

    it('clears the selection via the clear (X) button without opening the popover', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={onChange}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Limpar seleção' }));

        expect(onChange).toHaveBeenCalledWith(null);
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });

    it('clears the selection via keyboard (Enter) on the clear button', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={onChange}
            />,
        );

        const clearButton = screen.getByRole('button', { name: 'Limpar seleção' });
        clearButton.focus();
        await user.keyboard('{Enter}');

        expect(onChange).toHaveBeenCalledWith(null);
    });

    it('clears the selection via keyboard (Space) on the clear button', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={onChange}
            />,
        );

        const clearButton = screen.getByRole('button', { name: 'Limpar seleção' });
        clearButton.focus();
        await user.keyboard(' ');

        expect(onChange).toHaveBeenCalledWith(null);
    });

    it('does not clear the selection for other keys on the clear button', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <StudentCombobox
                semester="2026.1"
                value={makeStudent(1, 'Ana Silva')}
                onChange={onChange}
            />,
        );

        const clearButton = screen.getByRole('button', { name: 'Limpar seleção' });
        clearButton.focus();
        await user.keyboard('x');

        expect(onChange).not.toHaveBeenCalled();
    });

    it('does not show the clear button when nothing is selected', () => {
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        expect(
            screen.queryByRole('button', { name: 'Limpar seleção' }),
        ).not.toBeInTheDocument();
    });

    it('searches students with the debounced term', async () => {
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(
            await screen.findByPlaceholderText('Buscar aluno...'),
            'ana',
        );

        await waitFor(
            () => {
                expect(studentsApi.list).toHaveBeenCalledWith({
                    semester: '2026.1',
                    page: 1,
                    search: 'ana',
                });
            },
            { timeout: 2000 },
        );
    });

    it('clears the search when the popover closes', async () => {
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));
        await user.type(await screen.findByPlaceholderText('Buscar aluno...'), 'abc');
        await user.keyboard('{Escape}');

        await user.click(screen.getByRole('combobox'));
        expect(await screen.findByPlaceholderText('Buscar aluno...')).toHaveValue('');
    });

    it('fetches the next page when scrolled near the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(studentsApi.list)
            .mockResolvedValueOnce(
                makePage([makeStudent(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
            )
            .mockResolvedValueOnce(
                makePage([makeStudent(2, 'Bruno Costa')], { current: 2, last: 2 }) as any,
            );

        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('student-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(
            await screen.findByRole('option', { name: /Bruno Costa/ }),
        ).toBeInTheDocument();
        expect(studentsApi.list).toHaveBeenCalledWith({
            semester: '2026.1',
            page: 2,
            search: undefined,
        });
    });

    it('does not fetch the next page when far from the bottom', async () => {
        const user = userEvent.setup();
        vi.mocked(studentsApi.list).mockResolvedValue(
            makePage([makeStudent(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
        );

        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('student-combobox-list');
        setScrollMetrics(list, { scrollTop: 0, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(studentsApi.list).toHaveBeenCalledTimes(1);
    });

    it('does not fetch beyond the last page', async () => {
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('student-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        await act(async () => {
            await Promise.resolve();
        });
        expect(studentsApi.list).toHaveBeenCalledTimes(1);
    });

    it('shows the loading state while the first page loads', async () => {
        vi.mocked(studentsApi.list).mockReturnValue(new Promise(() => {}) as any);
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(await screen.findByText('Carregando...')).toBeInTheDocument();
    });

    it('shows "Carregando mais..." while fetching the next page', async () => {
        const user = userEvent.setup();
        vi.mocked(studentsApi.list)
            .mockResolvedValueOnce(
                makePage([makeStudent(1, 'Ana Silva')], { current: 1, last: 2 }) as any,
            )
            .mockReturnValueOnce(new Promise(() => {}) as any);

        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);
        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

        const list = screen.getByTestId('student-combobox-list');
        setScrollMetrics(list, { scrollTop: 280, scrollHeight: 600, clientHeight: 300 });
        fireEvent.scroll(list);

        expect(await screen.findByText('Carregando mais...')).toBeInTheDocument();
    });

    it('shows the empty state when no students match', async () => {
        vi.mocked(studentsApi.list).mockResolvedValue(makePage([]) as any);
        const user = userEvent.setup();
        render(<StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} />);

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByText('Nenhum aluno encontrado'),
        ).toBeInTheDocument();
    });

    it('selects an option via keyboard (Enter)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<StudentCombobox semester="2026.1" value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Ana Silva/ });
        option.focus();
        await user.keyboard('{Enter}');

        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ id: 1 }));
    });

    it('selects an option via keyboard (Space)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<StudentCombobox semester="2026.1" value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Bruno Costa/ });
        option.focus();
        await user.keyboard(' ');

        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ id: 2 }));
    });

    it('does not select for other keys', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(<StudentCombobox semester="2026.1" value={null} onChange={onChange} />);

        await user.click(screen.getByRole('combobox'));
        const option = await screen.findByRole('option', { name: /Ana Silva/ });
        option.focus();
        await user.keyboard('x');

        expect(onChange).not.toHaveBeenCalled();
    });

    it('disables the trigger when disabled is set', () => {
        render(
            <StudentCombobox semester="2026.1" value={null} onChange={vi.fn()} disabled />,
        );
        expect(screen.getByRole('combobox')).toBeDisabled();
    });

    it('has no axe violations when open', async () => {
        const user = userEvent.setup();
        render(
            <div>
                <label htmlFor="student-picker">Aluno</label>
                <StudentCombobox
                    id="student-picker"
                    semester="2026.1"
                    value={null}
                    onChange={vi.fn()}
                />
            </div>,
        );

        await user.click(screen.getByRole('combobox'));
        await screen.findByRole('option', { name: /Ana Silva/ });

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
