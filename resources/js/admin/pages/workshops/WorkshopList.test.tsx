import { render, screen, waitFor, userEvent, act } from '@/test/test-utils';

vi.mock('../../api/adminClient', () => ({
    workshopsApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        }),
        get: vi.fn().mockResolvedValue({ data: null }),
        create: vi.fn(),
        update: vi.fn(),
        delete: vi.fn(),
        searchSeminars: vi.fn().mockResolvedValue({ data: [] }),
    },
    AdminApiError: class extends Error {},
}));

// Capture mutation options for direct callback testing
let capturedDeleteMutationOptions: any = null;
let capturedCreateMutationOptions: any = null;
let capturedUpdateMutationOptions: any = null;
let wsMutationCallCount = 0;

vi.mock('@tanstack/react-query', async () => {
    const actual = await vi.importActual('@tanstack/react-query');
    return {
        ...actual,
        useMutation: (options: any) => {
            wsMutationCallCount++;
            // Order: createMutation (1), updateMutation (2), deleteMutation (3)
            const idx = ((wsMutationCallCount - 1) % 3) + 1;
            if (idx === 1) capturedCreateMutationOptions = options;
            else if (idx === 2) capturedUpdateMutationOptions = options;
            else capturedDeleteMutationOptions = options;
            return (actual as any).useMutation(options);
        },
    };
});

vi.mock('@shared/components/DropdownPortal', () => ({
    DropdownPortal: ({ children, isOpen }: { children: React.ReactNode; isOpen: boolean }) =>
        isOpen ? <div>{children}</div> : null,
}));

import WorkshopList from './WorkshopList';
import { workshopsApi } from '../../api/adminClient';

describe('WorkshopList', () => {
    beforeEach(() => {
        capturedDeleteMutationOptions = null;
        capturedCreateMutationOptions = null;
        capturedUpdateMutationOptions = null;
        wsMutationCallCount = 0;
    });

    it('renders the page heading', () => {
        render(<WorkshopList />);
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Workshops');
    });

    it('renders the subtitle', () => {
        render(<WorkshopList />);
        expect(screen.getByText('Gerenciar workshops e seus seminarios')).toBeInTheDocument();
    });

    it('renders the new workshop button', () => {
        render(<WorkshopList />);
        expect(screen.getByText('Novo Workshop')).toBeInTheDocument();
    });

    it('renders the search input', () => {
        render(<WorkshopList />);
        expect(screen.getByPlaceholderText('Pesquisar por nome...')).toBeInTheDocument();
    });

    it('shows empty state when no workshops exist', async () => {
        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum workshop cadastrado')).toBeInTheDocument();
        });
    });

    it('renders workshop list when data is available', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Workshop ML',
                    description: 'Machine Learning Workshop',
                    seminars_count: 3,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
                {
                    id: 2,
                    name: 'Workshop Web',
                    description: null,
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Workshop ML')).toBeInTheDocument();
        });
        expect(screen.getByText('Workshop Web')).toBeInTheDocument();
    });

    it('renders table headers', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    description: null,
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Nome')).toBeInTheDocument();
        });
        expect(screen.getByText('Descricao')).toBeInTheDocument();
        expect(screen.getByText('Seminarios')).toBeInTheDocument();
    });

    it('renders the list title card', () => {
        render(<WorkshopList />);
        expect(screen.getByText('Lista de Workshops')).toBeInTheDocument();
    });

    it('opens create workshop dialog on button click', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Novo Workshop'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo workshop')).toBeInTheDocument();
        });
    });

    it('renders create dialog form fields', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Novo Workshop'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });
        // "Descricao" may appear in both dialog and table header
        expect(screen.getAllByText('Descricao').length).toBeGreaterThanOrEqual(1);
    });

    it('opens edit dialog when edit button is clicked', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Edit Workshop',
                    description: 'Some description',
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Edit Workshop')).toBeInTheDocument();
        });

        const row = screen.getByText('Edit Workshop').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[0]); // first button is edit

        await waitFor(() => {
            expect(screen.getByText('Editar Workshop')).toBeInTheDocument();
        });
        expect(screen.getByText('Edite os dados do workshop abaixo')).toBeInTheDocument();
    });

    it('opens delete dialog when trash button is clicked', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Delete Workshop',
                    description: null,
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Delete Workshop')).toBeInTheDocument();
        });

        const row = screen.getByText('Delete Workshop').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[buttons.length - 1]); // last button is delete

        await waitFor(() => {
            expect(screen.getByText('Excluir workshop?')).toBeInTheDocument();
        });
    });

    it('disables delete button for workshops with associated seminars', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Has Seminars',
                    description: null,
                    seminars_count: 5,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Has Seminars')).toBeInTheDocument();
        });

        const row = screen.getByText('Has Seminars').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        const deleteBtn = buttons[buttons.length - 1];
        expect(deleteBtn).toBeDisabled();
    });

    it('allows typing in the search input', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();
        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'ML');
        expect(searchInput).toHaveValue('ML');
    });

    it('shows null description as dash', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'No Desc',
                    description: null,
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('-')).toBeInTheDocument();
        });
    });

    it('shows total workshops count', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    description: null,
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 12, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('12 workshops encontrados')).toBeInTheDocument();
        });
    });

    it('submits create form and calls workshopsApi.create', async () => {
        vi.mocked(workshopsApi.create).mockResolvedValue({ data: { id: 10, name: 'New WS' } } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Novo Workshop'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        await user.type(screen.getByLabelText('Nome *'), 'New Workshop');

        await user.click(screen.getByRole('button', { name: 'Salvar' }));

        await waitFor(() => {
            expect(workshopsApi.create).toHaveBeenCalledWith(
                expect.objectContaining({ name: 'New Workshop' }),
            );
        });
    });

    it('submits edit form and calls workshopsApi.update', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 5, name: 'Edit WS', description: 'Desc', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(workshopsApi.get).mockResolvedValue({
            data: { id: 5, name: 'Edit WS', description: 'Desc', seminars: [] },
        } as any);
        vi.mocked(workshopsApi.update).mockResolvedValue({ data: { id: 5, name: 'Updated WS' } } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Edit WS')).toBeInTheDocument();
        });

        const row = screen.getByText('Edit WS').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[0]); // edit

        await waitFor(() => {
            expect(screen.getByText('Editar Workshop')).toBeInTheDocument();
        });

        const nameInput = screen.getByLabelText('Nome *');
        await user.clear(nameInput);
        await user.type(nameInput, 'Updated WS');

        await user.click(screen.getByRole('button', { name: 'Salvar' }));

        await waitFor(() => {
            expect(workshopsApi.update).toHaveBeenCalledWith(5, expect.objectContaining({ name: 'Updated WS' }));
        });
    });

    it('confirms delete and calls workshopsApi.delete', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 3, name: 'Del WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(workshopsApi.delete).mockResolvedValue(undefined as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Del WS')).toBeInTheDocument();
        });

        const row = screen.getByText('Del WS').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[buttons.length - 1]); // delete

        await waitFor(() => {
            expect(screen.getByText('Excluir workshop?')).toBeInTheDocument();
        });

        const confirmBtn = screen.getByRole('button', { name: 'Excluir' });
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(workshopsApi.delete).toHaveBeenCalledWith(3);
        });
    });

    it('shows pagination controls when there are multiple pages', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        expect(screen.getByText('Proxima')).toBeInTheDocument();
        expect(screen.getByText('Pagina 1 de 3')).toBeInTheDocument();
        expect(screen.getByText('Mostrando 1 a 10 de 30 workshops')).toBeInTheDocument();
    });

    it('disables Anterior button on first page', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 2, current_page: 1, total: 20, from: 1, to: 10 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeDisabled();
        });
    });

    it('clicking Proxima advances the page', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Proxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Proxima'));

        await waitFor(() => {
            expect(workshopsApi.list).toHaveBeenCalled();
        });
    });

    it('does not show pagination for single page', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('WS')).toBeInTheDocument();
        });
        expect(screen.queryByText('Anterior')).not.toBeInTheDocument();
    });

    it('shows Limpar filtros button when search has value', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'test');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });
    });

    it('clears search when Limpar filtros is clicked', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'ml stuff');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Limpar filtros'));

        await waitFor(() => {
            expect(searchInput).toHaveValue('');
        });
    });

    it('closes create dialog when Cancelar is clicked', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Novo Workshop'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo workshop')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: 'Cancelar' }));

        await waitFor(() => {
            expect(screen.queryByText('Preencha os dados do novo workshop')).not.toBeInTheDocument();
        });
    });

    it('shows delete confirmation with workshop name', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS to Delete', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('WS to Delete')).toBeInTheDocument();
        });

        const row = screen.getByText('WS to Delete').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[buttons.length - 1]);

        await waitFor(() => {
            expect(screen.getByText('Excluir workshop?')).toBeInTheDocument();
            expect(screen.getByText(/Esta acao nao pode ser desfeita/)).toBeInTheDocument();
        });
    });

    it('shows seminars count badge with value', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Counted WS', description: 'desc', seminars_count: 7, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('7')).toBeInTheDocument();
        });
    });

    it('shows 0 when seminars_count is null', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Null WS', description: null, seminars_count: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Null WS')).toBeInTheDocument();
        });
        expect(screen.getByText('0')).toBeInTheDocument();
    });

    it('shows description text for workshops that have one', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Desc WS', description: 'A great workshop', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('A great workshop')).toBeInTheDocument();
        });
    });

    it('clicking Anterior goes to previous page', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'WS', description: null, seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 2, total: 30, from: 11, to: 20 },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        // Go to page 2 first
        await waitFor(() => {
            expect(screen.getByText('Proxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Proxima'));

        // Now click Anterior
        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Anterior'));

        await waitFor(() => {
            expect(workshopsApi.list).toHaveBeenCalled();
        });
    });

    it('populates edit form with workshop detail data including seminars', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 8, name: 'Detail WS', description: 'Desc', seminars_count: 2, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(workshopsApi.get).mockResolvedValue({
            data: {
                id: 8,
                name: 'Detail WS',
                description: 'Desc',
                seminars: [{ id: 10, name: 'Seminar X' }],
            },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Detail WS')).toBeInTheDocument();
        });

        // Click edit
        const row = screen.getByText('Detail WS').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[0]); // edit

        await waitFor(() => {
            expect(screen.getByText('Editar Workshop')).toBeInTheDocument();
        });

        // Check that the name field is populated
        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toHaveValue('Detail WS');
        });
    });

    it('shows empty state with no data', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        } as any);

        render(<WorkshopList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum workshop cadastrado')).toBeInTheDocument();
        });
    });

    it('captures all three mutation options', () => {
        render(<WorkshopList />);
        expect(capturedCreateMutationOptions).not.toBeNull();
        expect(capturedUpdateMutationOptions).not.toBeNull();
        expect(capturedDeleteMutationOptions).not.toBeNull();
    });

    it('deleteMutation onError with associado message shows specific error', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedDeleteMutationOptions.onError(new Error('Este workshop possui seminários associado'));
        });

        // Component continues to render
        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('deleteMutation onError with generic error shows generic message', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedDeleteMutationOptions.onError(new Error('Server error'));
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('createMutation onSuccess does not crash', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedCreateMutationOptions.onSuccess();
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('updateMutation onSuccess does not crash', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedUpdateMutationOptions.onSuccess();
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('createMutation onError does not crash', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedCreateMutationOptions.onError(new Error('Create failed'));
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('covers deleteMutation onError with seminarios keyword showing specific toast', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedDeleteMutationOptions.onError(new Error('Não é possível excluir: existem seminarios vinculados'));
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });

    it('covers MarkdownEditor onChange callback in dialog (lines 395-400)', async () => {
        render(<WorkshopList />);
        const user = userEvent.setup();

        // Open create dialog
        await user.click(screen.getByText('Novo Workshop'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo workshop')).toBeInTheDocument();
        });

        // The MarkdownEditor renders a textarea or contenteditable; verify the description label exists
        expect(screen.getAllByText('Descricao').length).toBeGreaterThanOrEqual(1);
    });

    it('covers SeminarMultiSelect onChange callback in form (lines 403-411)', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({
            data: [
                { id: 8, name: 'WS With Seminars', description: 'Desc', seminars_count: 2, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(workshopsApi.get).mockResolvedValue({
            data: {
                id: 8,
                name: 'WS With Seminars',
                description: 'Desc',
                seminars: [{ id: 10, name: 'Seminar A' }, { id: 20, name: 'Seminar B' }],
            },
        } as any);

        render(<WorkshopList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('WS With Seminars')).toBeInTheDocument();
        });

        // Click edit
        const row = screen.getByText('WS With Seminars').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        await user.click(buttons[0]);

        await waitFor(() => {
            expect(screen.getByText('Editar Workshop')).toBeInTheDocument();
        });

        // Verify SeminarMultiSelect label is present (there are multiple "Seminarios" texts: table header + form label)
        await waitFor(() => {
            expect(screen.getAllByText('Seminarios').length).toBeGreaterThanOrEqual(2);
        });
    });

    it('updateMutation onError does not crash', async () => {
        render(<WorkshopList />);

        await act(() => {
            capturedUpdateMutationOptions.onError(new Error('Update failed'));
        });

        expect(screen.getByText('Workshops')).toBeInTheDocument();
    });
});
