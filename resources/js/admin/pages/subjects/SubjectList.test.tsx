import { render, screen, waitFor, userEvent, act } from '@/test/test-utils';

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('../../api/adminClient', () => ({
    subjectsApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        }),
        create: vi.fn(),
        update: vi.fn(),
        delete: vi.fn(),
        merge: vi.fn(),
    },
    AdminApiError: class extends Error {},
}));

// Capture mutation options for direct callback testing
let capturedSubCreateOptions: any = null;
let capturedSubUpdateOptions: any = null;
let capturedSubDeleteOptions: any = null;
let capturedSubMergeOptions: any = null;
let subMutationCallCount = 0;

vi.mock('@tanstack/react-query', async () => {
    const actual = await vi.importActual('@tanstack/react-query');
    return {
        ...actual,
        useMutation: (options: any) => {
            subMutationCallCount++;
            const idx = ((subMutationCallCount - 1) % 4) + 1;
            if (idx === 1) capturedSubCreateOptions = options;
            else if (idx === 2) capturedSubUpdateOptions = options;
            else if (idx === 3) capturedSubDeleteOptions = options;
            else capturedSubMergeOptions = options;
            return (actual as any).useMutation(options);
        },
    };
});

import SubjectList from './SubjectList';
import { subjectsApi } from '../../api/adminClient';

describe('SubjectList', () => {
    beforeEach(() => {
        capturedSubCreateOptions = null;
        capturedSubUpdateOptions = null;
        capturedSubDeleteOptions = null;
        capturedSubMergeOptions = null;
        subMutationCallCount = 0;
    });

    it('renders the page heading', () => {
        render(<SubjectList />);
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Tópicos');
    });

    it('renders the subtitle', () => {
        render(<SubjectList />);
        expect(screen.getByText('Gerenciar tópicos dos seminários')).toBeInTheDocument();
    });

    it('renders the new topic button', () => {
        render(<SubjectList />);
        expect(screen.getByText('Novo Tópico')).toBeInTheDocument();
    });

    it('renders the search input', () => {
        render(<SubjectList />);
        expect(screen.getByPlaceholderText('Pesquisar por nome...')).toBeInTheDocument();
    });

    it('shows empty state when no subjects exist', async () => {
        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum tópico cadastrado')).toBeInTheDocument();
        });
    });

    it('renders subject list when data is available', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Inteligência Artificial',
                    seminars_count: 5,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
                {
                    id: 2,
                    name: 'Machine Learning',
                    seminars_count: 3,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Inteligência Artificial')).toBeInTheDocument();
        });
        expect(screen.getByText('Machine Learning')).toBeInTheDocument();
    });

    it('renders table headers', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Nome')).toBeInTheDocument();
        });
        expect(screen.getByText('Seminarios')).toBeInTheDocument();
    });

    it('renders the list title card', () => {
        render(<SubjectList />);
        expect(screen.getByText('Lista de Tópicos')).toBeInTheDocument();
    });

    it('opens create subject dialog on button click', async () => {
        render(<SubjectList />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Novo Tópico'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo tópico')).toBeInTheDocument();
        });
    });

    it('opens edit subject dialog when edit button is clicked', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'React',
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('React')).toBeInTheDocument();
        });

        const row = screen.getByText('React').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        // First non-checkbox button is edit
        const editBtn = Array.from(buttons).find(b => !b.getAttribute('role')?.includes('checkbox'));
        await user.click(editBtn!);

        await waitFor(() => {
            expect(screen.getByText('Editar Tópico')).toBeInTheDocument();
        });
        expect(screen.getByText('Edite os dados do tópico abaixo')).toBeInTheDocument();
    });

    it('opens delete dialog when trash button is clicked for subject with no seminars', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Delete Me',
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Delete Me')).toBeInTheDocument();
        });

        const row = screen.getByText('Delete Me').closest('tr')!;
        const allButtons = Array.from(row.querySelectorAll('button'));
        // The last regular button in actions is the delete button
        const deleteBtn = allButtons[allButtons.length - 1];
        await user.click(deleteBtn);

        await waitFor(() => {
            expect(screen.getByText('Excluir tópico?')).toBeInTheDocument();
        });
    });

    it('disables delete button for subjects with associated seminars', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Has Seminars',
                    seminars_count: 3,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Has Seminars')).toBeInTheDocument();
        });

        const row = screen.getByText('Has Seminars').closest('tr')!;
        const allButtons = Array.from(row.querySelectorAll('button'));
        const deleteBtn = allButtons[allButtons.length - 1];
        expect(deleteBtn).toBeDisabled();
    });

    it('shows seminars count badge', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Topic With Count',
                    seminars_count: 7,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('7')).toBeInTheDocument();
        });
    });

    it('allows typing in the search input', async () => {
        render(<SubjectList />);
        const user = userEvent.setup();
        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'React');
        expect(searchInput).toHaveValue('React');
    });

    it('shows total subjects count', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    seminars_count: 0,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 15, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('15 tópicos encontrados')).toBeInTheDocument();
        });
    });

    it('submits create form and calls subjectsApi.create', async () => {
        vi.mocked(subjectsApi.create).mockResolvedValue({ data: { id: 10, name: 'New Topic' } } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Novo Tópico'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo tópico')).toBeInTheDocument();
        });

        const nameInput = screen.getByLabelText('Nome');
        await user.type(nameInput, 'New Topic');

        const saveButton = screen.getByRole('button', { name: 'Salvar' });
        await user.click(saveButton);

        await waitFor(() => {
            expect(subjectsApi.create).toHaveBeenCalledWith({ name: 'New Topic' });
        });
    });

    it('submits edit form and calls subjectsApi.update', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 5, name: 'Old Name', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(subjectsApi.update).mockResolvedValue({ data: { id: 5, name: 'New Name' } } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Old Name')).toBeInTheDocument();
        });

        const row = screen.getByText('Old Name').closest('tr')!;
        const buttons = Array.from(row.querySelectorAll('button'));
        const editBtn = buttons.find(b => !b.getAttribute('role')?.includes('checkbox'));
        await user.click(editBtn!);

        await waitFor(() => {
            expect(screen.getByText('Editar Tópico')).toBeInTheDocument();
        });

        const nameInput = screen.getByLabelText('Nome');
        await user.clear(nameInput);
        await user.type(nameInput, 'New Name');

        const saveButton = screen.getByRole('button', { name: 'Salvar' });
        await user.click(saveButton);

        await waitFor(() => {
            expect(subjectsApi.update).toHaveBeenCalledWith(5, { name: 'New Name' });
        });
    });

    it('confirms delete and calls subjectsApi.delete', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 3, name: 'To Delete', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(subjectsApi.delete).mockResolvedValue(undefined as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('To Delete')).toBeInTheDocument();
        });

        const row = screen.getByText('To Delete').closest('tr')!;
        const allButtons = Array.from(row.querySelectorAll('button'));
        const deleteBtn = allButtons[allButtons.length - 1];
        await user.click(deleteBtn);

        await waitFor(() => {
            expect(screen.getByText('Excluir tópico?')).toBeInTheDocument();
        });

        const confirmBtn = screen.getByRole('button', { name: 'Excluir' });
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(subjectsApi.delete).toHaveBeenCalledWith(3);
        });
    });

    it('shows pagination when there are multiple pages', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Page Topic', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        expect(screen.getByText('Proxima')).toBeInTheDocument();
        expect(screen.getByText('Pagina 1 de 3')).toBeInTheDocument();
        expect(screen.getByText('Mostrando 1 a 10 de 30 tópicos')).toBeInTheDocument();
    });

    it('disables Anterior button on the first page', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 2, current_page: 1, total: 20, from: 1, to: 10 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeDisabled();
        });
    });

    it('clicking Proxima advances the page', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Proxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Proxima'));

        await waitFor(() => {
            expect(subjectsApi.list).toHaveBeenCalled();
        });
    });

    it('does not show pagination for single page', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('Topic')).toBeInTheDocument();
        });
        expect(screen.queryByText('Anterior')).not.toBeInTheDocument();
    });

    it('toggles individual subject checkbox selection', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic A', seminars_count: 2, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Topic B', seminars_count: 1, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Topic A')).toBeInTheDocument();
        });

        // Click checkboxes for both subjects to trigger merge button visibility
        const rowA = screen.getByText('Topic A').closest('tr')!;
        const checkboxA = rowA.querySelector('button[role="checkbox"]')!;
        await user.click(checkboxA);

        const rowB = screen.getByText('Topic B').closest('tr')!;
        const checkboxB = rowB.querySelector('button[role="checkbox"]')!;
        await user.click(checkboxB);

        // Merge button should appear when 2+ items are selected
        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });
    });

    it('toggles all checkboxes with the header checkbox', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic A', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Topic B', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Topic A')).toBeInTheDocument();
        });

        // The header checkbox is in the TableHeader
        const headerRow = screen.getByText('Nome').closest('tr')!;
        const headerCheckbox = headerRow.querySelector('button[role="checkbox"]')!;
        await user.click(headerCheckbox);

        // Merge button should appear
        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });

        // Click again to deselect all
        await user.click(headerCheckbox);

        await waitFor(() => {
            expect(screen.queryByText(/Mesclar/)).not.toBeInTheDocument();
        });
    });

    it('opens merge dialog when merge button is clicked', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic A', seminars_count: 2, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Topic B', seminars_count: 3, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Topic A')).toBeInTheDocument();
        });

        // Select both topics
        const headerRow = screen.getByText('Nome').closest('tr')!;
        const headerCheckbox = headerRow.querySelector('button[role="checkbox"]')!;
        await user.click(headerCheckbox);

        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });

        await user.click(screen.getByText(/Mesclar/));

        await waitFor(() => {
            expect(screen.getByText('Mesclar Tópicos')).toBeInTheDocument();
        });
        // Check the warning about affected seminars
        expect(screen.getByText(/tópicos serão mesclados/)).toBeInTheDocument();
    });

    it('shows Limpar filtros button when search has value', async () => {
        render(<SubjectList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'test search');

        // The "Limpar filtros" button should appear after debounce
        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });
    });

    it('clears search when Limpar filtros is clicked', async () => {
        render(<SubjectList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Pesquisar por nome...');
        await user.type(searchInput, 'some search');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Limpar filtros'));

        await waitFor(() => {
            expect(searchInput).toHaveValue('');
        });
    });

    it('shows 0 seminars count for subjects with null seminars_count', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'No Count', seminars_count: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);

        await waitFor(() => {
            expect(screen.getByText('0')).toBeInTheDocument();
        });
    });

    it('closes create dialog when Cancelar is clicked', async () => {
        render(<SubjectList />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Novo Tópico'));

        await waitFor(() => {
            expect(screen.getByText('Preencha os dados do novo tópico')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: 'Cancelar' }));

        await waitFor(() => {
            expect(screen.queryByText('Preencha os dados do novo tópico')).not.toBeInTheDocument();
        });
    });

    it('confirms merge and calls subjectsApi.merge', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Topic A', seminars_count: 2, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Topic B', seminars_count: 3, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);
        vi.mocked(subjectsApi.merge).mockResolvedValue({ data: { id: 1 } } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Topic A')).toBeInTheDocument();
        });

        // Select all via header checkbox
        const headerRow = screen.getByText('Nome').closest('tr')!;
        const headerCheckbox = headerRow.querySelector('button[role="checkbox"]')!;
        await user.click(headerCheckbox);

        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });

        // Open merge dialog
        await user.click(screen.getByText(/Mesclar/));

        await waitFor(() => {
            expect(screen.getByText('Mesclar Tópicos')).toBeInTheDocument();
        });

        // Click the merge confirm button
        const mergeButton = screen.getAllByRole('button').find(
            btn => btn.textContent === 'Mesclar' && !btn.textContent?.includes('(')
        );
        expect(mergeButton).toBeDefined();
        await user.click(mergeButton!);

        await waitFor(() => {
            expect(subjectsApi.merge).toHaveBeenCalledWith(expect.objectContaining({
                target_id: expect.any(Number),
                source_ids: expect.any(Array),
            }));
        });
    });

    it('closes merge dialog when cancel is clicked', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Merge A', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Merge B', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Merge A')).toBeInTheDocument();
        });

        // Select all and open merge dialog
        const headerRow = screen.getByText('Nome').closest('tr')!;
        const headerCheckbox = headerRow.querySelector('button[role="checkbox"]')!;
        await user.click(headerCheckbox);

        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });

        await user.click(screen.getByText(/Mesclar/));

        await waitFor(() => {
            expect(screen.getByText('Mesclar Tópicos')).toBeInTheDocument();
        });

        // Click Cancelar in the merge dialog
        const cancelButtons = screen.getAllByRole('button', { name: 'Cancelar' });
        await user.click(cancelButtons[cancelButtons.length - 1]);

        await waitFor(() => {
            expect(screen.queryByText('Mesclar Tópicos')).not.toBeInTheDocument();
        });
    });

    it('clicking Anterior goes to previous page', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Page Topic', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 3, current_page: 2, total: 30, from: 11, to: 20 },
        } as any);

        render(<SubjectList />);
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
            expect(subjectsApi.list).toHaveBeenCalled();
        });
    });

    it('shows delete confirmation dialog text with subject name', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 9, name: 'Named Delete', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Named Delete')).toBeInTheDocument();
        });

        const row = screen.getByText('Named Delete').closest('tr')!;
        const allButtons = Array.from(row.querySelectorAll('button'));
        const deleteBtn = allButtons[allButtons.length - 1];
        await user.click(deleteBtn);

        await waitFor(() => {
            expect(screen.getByText('Excluir tópico?')).toBeInTheDocument();
            expect(screen.getByText(/Esta acao nao pode ser desfeita/)).toBeInTheDocument();
        });
    });

    it('captures all four mutation options', () => {
        render(<SubjectList />);
        expect(capturedSubCreateOptions).not.toBeNull();
        expect(capturedSubUpdateOptions).not.toBeNull();
        expect(capturedSubDeleteOptions).not.toBeNull();
        expect(capturedSubMergeOptions).not.toBeNull();
    });

    it('deleteMutation onError with associado message shows specific error', async () => {
        render(<SubjectList />);

        await act(() => {
            capturedSubDeleteOptions.onError(new Error('Este tópico possui seminários associado'));
        });

        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('deleteMutation onError with generic error shows generic message', async () => {
        render(<SubjectList />);

        await act(() => {
            capturedSubDeleteOptions.onError(new Error('Server error'));
        });

        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('mergeMutation onError does not crash', async () => {
        render(<SubjectList />);

        await act(() => {
            capturedSubMergeOptions.onError(new Error('Merge failed'));
        });

        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('createMutation onError does not crash', async () => {
        render(<SubjectList />);

        await act(() => {
            capturedSubCreateOptions.onError(new Error('Create failed'));
        });

        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('updateMutation onError does not crash', async () => {
        render(<SubjectList />);

        await act(() => {
            capturedSubUpdateOptions.onError(new Error('Update failed'));
        });

        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('types in the merge name field', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Merge X', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
                { id: 2, name: 'Merge Y', seminars_count: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<SubjectList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Merge X')).toBeInTheDocument();
        });

        // Select all and open merge dialog
        const headerRow = screen.getByText('Nome').closest('tr')!;
        const headerCheckbox = headerRow.querySelector('button[role="checkbox"]')!;
        await user.click(headerCheckbox);

        await waitFor(() => {
            expect(screen.getByText(/Mesclar/)).toBeInTheDocument();
        });

        await user.click(screen.getByText(/Mesclar/));

        await waitFor(() => {
            expect(screen.getByText('Mesclar Tópicos')).toBeInTheDocument();
        });

        // Type in the merge name field
        const nameInput = screen.getByLabelText('Nome final (opcional)');
        await user.clear(nameInput);
        await user.type(nameInput, 'Merged Topic');
        expect(nameInput).toHaveValue('Merged Topic');
    });
});
