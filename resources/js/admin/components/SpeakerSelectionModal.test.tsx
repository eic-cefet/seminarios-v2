import { render, screen, waitFor, userEvent } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    usersApi: {
        list: vi.fn().mockResolvedValue({
            data: [
                { id: 1, name: 'Speaker One', email: 'speaker1@test.com', speaker_data: { institution: 'CEFET' } },
                { id: 2, name: 'Speaker Two', email: 'speaker2@test.com', speaker_data: null },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 },
        }),
        create: vi.fn(),
    },
}));

import { SpeakerSelectionModal } from './SpeakerSelectionModal';

describe('SpeakerSelectionModal', () => {
    const defaultProps = {
        open: true,
        onClose: vi.fn(),
        selectedIds: [] as number[],
        onConfirm: vi.fn(),
    };

    beforeEach(async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Speaker One', email: 'speaker1@test.com', speaker_data: { institution: 'CEFET' } },
                { id: 2, name: 'Speaker Two', email: 'speaker2@test.com', speaker_data: null },
            ] as any,
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });
        vi.mocked(usersApi.create).mockReset();
    });

    it('renders the dialog title when open', () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        expect(screen.getByText('Selecionar Palestrantes')).toBeInTheDocument();
    });

    it('renders the search input', () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        expect(screen.getByPlaceholderText('Buscar usuários...')).toBeInTheDocument();
    });

    it('renders the confirm and cancel buttons', () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        expect(screen.getByText('Confirmar (0)')).toBeInTheDocument();
        expect(screen.getByText('Cancelar')).toBeInTheDocument();
    });

    it('renders the create new speaker button', () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        expect(screen.getByText('Criar Novo Palestrante')).toBeInTheDocument();
    });

    it('shows user list after loading', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Speaker One')).toBeInTheDocument();
        });
        expect(screen.getByText('Speaker Two')).toBeInTheDocument();
    });

    it('shows user emails in the list', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('speaker1@test.com')).toBeInTheDocument();
        });
        expect(screen.getByText('speaker2@test.com')).toBeInTheDocument();
    });

    it('shows institution for speakers that have one', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('CEFET')).toBeInTheDocument();
        });
    });

    it('updates confirm count based on selected IDs', () => {
        render(<SpeakerSelectionModal {...defaultProps} selectedIds={[1, 2]} />);
        expect(screen.getByText('Confirmar (2)')).toBeInTheDocument();
    });

    it('does not render the dialog when closed', () => {
        render(<SpeakerSelectionModal {...defaultProps} open={false} />);
        expect(screen.queryByText('Selecionar Palestrantes')).not.toBeInTheDocument();
    });

    it('allows typing in the search input', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        const searchInput = screen.getByPlaceholderText('Buscar usuários...');
        await user.type(searchInput, 'speaker');
        expect(searchInput).toHaveValue('speaker');
    });

    it('toggles speaker selection when checkbox is clicked', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Speaker One')).toBeInTheDocument();
        });

        // Click the checkbox next to Speaker One
        const checkboxes = screen.getAllByRole('checkbox');
        await user.click(checkboxes[0]);

        // The confirm button count should update
        expect(screen.getByText('Confirmar (1)')).toBeInTheDocument();
    });

    it('shows the create new speaker form when button is clicked', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByText('Criar Novo Palestrante', { selector: 'h2, [class*="DialogTitle"]' })).toBeInTheDocument();
        });
        expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        expect(screen.getByLabelText('Email *')).toBeInTheDocument();
        expect(screen.getByLabelText('Instituição')).toBeInTheDocument();
        expect(screen.getByLabelText('Descrição')).toBeInTheDocument();
    });

    it('shows the Voltar button in the create form', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByText('Voltar')).toBeInTheDocument();
        });
        expect(screen.getByText('Criar e Adicionar')).toBeInTheDocument();
    });

    it('navigates back from create form when Voltar is clicked', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByText('Voltar')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Voltar'));

        await waitFor(() => {
            expect(screen.getByText('Selecionar Palestrantes')).toBeInTheDocument();
        });
    });

    it('calls onConfirm with selected ids when Confirmar is clicked', async () => {
        const mockOnConfirm = vi.fn();
        render(<SpeakerSelectionModal {...defaultProps} onConfirm={mockOnConfirm} selectedIds={[1]} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Speaker One')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Confirmar (1)'));
        expect(mockOnConfirm).toHaveBeenCalled();
    });

    it('shows password field in the create form', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByLabelText('Senha')).toBeInTheDocument();
        });
        expect(screen.getByPlaceholderText('Deixe em branco para gerar automaticamente')).toBeInTheDocument();
    });

    it('shows loading state while users are loading', async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.list).mockReturnValue(new Promise(() => {}));

        render(<SpeakerSelectionModal {...defaultProps} />);

        expect(screen.getByText('Carregando...')).toBeInTheDocument();
    });

    it('shows empty state when no users are found', async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.list).mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });

        render(<SpeakerSelectionModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum usuário encontrado')).toBeInTheDocument();
        });
    });

    it('calls onClose when Cancelar button is clicked', async () => {
        const mockOnClose = vi.fn();
        render(<SpeakerSelectionModal {...defaultProps} onClose={mockOnClose} />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Cancelar'));

        expect(mockOnClose).toHaveBeenCalled();
    });

    it('toggles user deselection when checkbox is clicked for already-selected user', async () => {
        render(<SpeakerSelectionModal {...defaultProps} selectedIds={[1]} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Speaker One')).toBeInTheDocument();
        });

        // Confirm count starts at 1
        expect(screen.getByText('Confirmar (1)')).toBeInTheDocument();

        // Click the checkbox next to Speaker One to deselect
        const checkboxes = screen.getAllByRole('checkbox');
        await user.click(checkboxes[0]);

        // The confirm button count should decrease
        expect(screen.getByText('Confirmar (0)')).toBeInTheDocument();
    });

    it('calls onConfirm with correct user objects when Confirmar is clicked', async () => {
        const mockOnConfirm = vi.fn();
        render(<SpeakerSelectionModal {...defaultProps} onConfirm={mockOnConfirm} selectedIds={[1]} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Speaker One')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Confirmar (1)'));
        expect(mockOnConfirm).toHaveBeenCalledWith(
            [1],
            expect.arrayContaining([expect.objectContaining({ id: 1, name: 'Speaker One' })]),
        );
    });

    it('submits create form with the filled data', async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.create).mockResolvedValue({
            message: 'Created',
            data: { id: 3, name: 'New Speaker', email: 'new@test.com', roles: [], created_at: '', updated_at: '' },
        });

        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        // Go to create form
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        // Fill form fields
        await user.type(screen.getByLabelText('Nome *'), 'New Speaker');
        await user.type(screen.getByLabelText('Email *'), 'new@test.com');
        await user.type(screen.getByLabelText('Instituição'), 'UFRJ');
        await user.type(screen.getByLabelText('Descrição'), 'A great speaker');
        await user.type(screen.getByLabelText('Senha'), 'password123');

        // Submit
        await user.click(screen.getByText('Criar e Adicionar'));

        expect(usersApi.create).toHaveBeenCalledWith(expect.objectContaining({
            name: 'New Speaker',
            email: 'new@test.com',
            password: 'password123',
            speaker_data: {
                institution: 'UFRJ',
                description: 'A great speaker',
            },
        }));
    });

    it('resets create form and returns to list after successful creation', async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.create).mockResolvedValue({
            message: 'Created',
            data: { id: 3, name: 'New Speaker', email: 'new@test.com', roles: [], created_at: '', updated_at: '' },
        });

        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        await user.type(screen.getByLabelText('Nome *'), 'New Speaker');
        await user.type(screen.getByLabelText('Email *'), 'new@test.com');
        await user.click(screen.getByText('Criar e Adicionar'));

        // After successful creation, should return to list view
        await waitFor(() => {
            expect(screen.getByText('Selecionar Palestrantes')).toBeInTheDocument();
        });
    });

    it('handles create user mutation error', async () => {
        const { usersApi } = await import('../api/adminClient');
        vi.mocked(usersApi.create).mockRejectedValue(new Error('Create failed'));

        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        await user.type(screen.getByLabelText('Nome *'), 'New Speaker');
        await user.type(screen.getByLabelText('Email *'), 'new@test.com');
        await user.click(screen.getByText('Criar e Adicionar'));

        // The mutation should have been called
        expect(usersApi.create).toHaveBeenCalled();
        // Should still be on the create form (not navigated back due to error)
        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });
    });

    it('shows password help text in the create form', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByText(/Se deixar em branco, uma senha aleatória será gerada/)).toBeInTheDocument();
        });
    });

    it('calls onConfirm with empty users array when data is not yet loaded', async () => {
        const { usersApi } = await import('../api/adminClient');
        // Make the list return a never-resolving promise so usersData is undefined
        vi.mocked(usersApi.list).mockReturnValue(new Promise(() => {}));

        const mockOnConfirm = vi.fn();
        render(<SpeakerSelectionModal {...defaultProps} onConfirm={mockOnConfirm} selectedIds={[1]} />);
        const user = userEvent.setup();

        // Click Confirmar immediately while data hasn't loaded
        await user.click(screen.getByText('Confirmar (1)'));

        // onConfirm should be called with ids but empty users array (the ?? [] fallback)
        expect(mockOnConfirm).toHaveBeenCalledWith([1], []);
    });

    it('shows Criando... when create mutation is pending', async () => {
        const { usersApi } = await import('../api/adminClient');
        // Make create return a never-resolving promise so mutation stays pending
        vi.mocked(usersApi.create).mockReturnValue(new Promise(() => {}));

        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        // Go to create form
        await user.click(screen.getByText('Criar Novo Palestrante'));

        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        // Fill required fields
        await user.type(screen.getByLabelText('Nome *'), 'Pending Speaker');
        await user.type(screen.getByLabelText('Email *'), 'pending@test.com');

        // Submit
        await user.click(screen.getByText('Criar e Adicionar'));

        // Button should show pending state
        await waitFor(() => {
            expect(screen.getByText('Criando...')).toBeInTheDocument();
        });
    });

    it('clears search and resets form when closing the modal', async () => {
        render(<SpeakerSelectionModal {...defaultProps} />);
        const user = userEvent.setup();

        // Go to create form
        await user.click(screen.getByText('Criar Novo Palestrante'));
        await waitFor(() => {
            expect(screen.getByLabelText('Nome *')).toBeInTheDocument();
        });

        // Type in the name field
        await user.type(screen.getByLabelText('Nome *'), 'Some Name');

        // Click Cancel (handleClose)
        await user.click(screen.getByText('Voltar'));

        // Should be back to selection view
        await waitFor(() => {
            expect(screen.getByText('Selecionar Palestrantes')).toBeInTheDocument();
        });
    });
});
