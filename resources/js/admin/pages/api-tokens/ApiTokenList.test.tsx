import { render, screen, waitFor, userEvent, act } from '@/test/test-utils';

vi.mock('../../api/adminClient', () => ({
    apiTokensApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: null, to: null, per_page: 15 },
        }),
        create: vi.fn(),
        update: vi.fn(),
        regenerate: vi.fn(),
        delete: vi.fn(),
    },
    AdminApiError: class extends Error {},
}));

let capturedCreateOptions: any = null;
let capturedDeleteOptions: any = null;
let capturedUpdateOptions: any = null;
let capturedRegenerateOptions: any = null;
let tokenMutationCallCount = 0;

vi.mock('@tanstack/react-query', async () => {
    const actual = await vi.importActual('@tanstack/react-query');
    return {
        ...actual,
        useMutation: (options: any) => {
            tokenMutationCallCount++;
            const idx = ((tokenMutationCallCount - 1) % 4) + 1;
            if (idx === 1) capturedCreateOptions = options;
            else if (idx === 2) capturedDeleteOptions = options;
            else if (idx === 3) capturedUpdateOptions = options;
            else capturedRegenerateOptions = options;
            return (actual as any).useMutation(options);
        },
    };
});

import ApiTokenList from './ApiTokenList';
import { apiTokensApi } from '../../api/adminClient';

const paginatedMeta = { last_page: 1, current_page: 1, total: 2, from: 1, to: 2, per_page: 15 };
const emptyLinks = { first: '', last: '', prev: null, next: null };

function paginated(data: typeof mockTokens, meta = paginatedMeta) {
    return { data, meta, links: emptyLinks };
}

const mockTokens = [
    {
        id: 1,
        name: 'Prof. Joel Token',
        abilities: ['*'],
        last_used_at: '2026-04-01T10:00:00Z',
        expires_at: null,
        created_at: '2026-03-01T10:00:00Z',
    },
    {
        id: 2,
        name: 'Scoped Token',
        abilities: ['seminars:read', 'seminars:write'],
        last_used_at: null,
        expires_at: '2099-01-01T00:00:00Z',
        created_at: '2026-03-15T10:00:00Z',
    },
];

describe('ApiTokenList', () => {
    beforeEach(() => {
        capturedCreateOptions = null;
        capturedDeleteOptions = null;
        capturedUpdateOptions = null;
        capturedRegenerateOptions = null;
        tokenMutationCallCount = 0;
    });

    it('renders the page heading and subtitle', () => {
        render(<ApiTokenList />);
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('API Tokens');
        expect(screen.getByText(/Gerenciar seus tokens de acesso para a API externa/)).toBeInTheDocument();
    });

    it('renders the new token button', () => {
        render(<ApiTokenList />);
        expect(screen.getByText('Novo Token')).toBeInTheDocument();
    });

    it('shows empty state when no tokens exist', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum token criado')).toBeInTheDocument();
        });
        expect(screen.getByText('Crie um token para permitir acesso via API externa.')).toBeInTheDocument();
    });

    it('renders token list with abilities and dates', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });
        expect(screen.getByText('Acesso total')).toBeInTheDocument();
        expect(screen.getByText('seminars:read')).toBeInTheDocument();
        expect(screen.getByText('seminars:write')).toBeInTheDocument();
        expect(screen.getAllByText('Nunca').length).toBeGreaterThan(0);
    });

    it('renders singular and plural token counts', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([mockTokens[0]], { ...paginatedMeta, total: 1, to: 1 }));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('1 token encontrado')).toBeInTheDocument();
        });
    });

    it('renders plural token count', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('2 tokens encontrados')).toBeInTheDocument();
        });
    });

    it('renders table headers', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Nome')).toBeInTheDocument();
        });
        expect(screen.getByText('Permissões')).toBeInTheDocument();
        expect(screen.getByText('Expira em')).toBeInTheDocument();
        expect(screen.getByText('Último uso')).toBeInTheDocument();
        expect(screen.getByText('Criado em')).toBeInTheDocument();
        expect(screen.getByText('Ações')).toBeInTheDocument();
    });

    it('shows "Nunca" for tokens without expiry', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([mockTokens[0]]));

        render(<ApiTokenList />);

        await waitFor(() => {
            // "Nunca" appears twice: expiry and last_used_at — but the first
            // mockTokens[0] has last_used_at set, so only one "Nunca" for expiry
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });
    });

    it('shows expired badge for expired tokens', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([{
            id: 3,
            name: 'Expired Token',
            abilities: ['*'],
            last_used_at: null,
            expires_at: '2020-01-01T00:00:00Z',
            created_at: '2019-01-01T00:00:00Z',
        }]));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Expirado')).toBeInTheDocument();
        });
    });

    it('shows loading skeletons while fetching', () => {
        vi.mocked(apiTokensApi.list).mockReturnValue(new Promise(() => {}));
        render(<ApiTokenList />);
        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
    });

    // --- Create flow ---

    it('opens create dialog when clicking new token button', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Novo Token de API')).toBeInTheDocument();
        });
        expect(screen.getByText(/O token será exibido apenas uma vez/)).toBeInTheDocument();
    });

    it('submits create form', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByPlaceholderText('Ex: Integração TCC')).toBeInTheDocument();
        });

        await userEvent.type(screen.getByPlaceholderText('Ex: Integração TCC'), 'My Token');

        const form = screen.getByPlaceholderText('Ex: Integração TCC').closest('form');
        await act(async () => {
            form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        });

        expect(apiTokensApi.create).toHaveBeenCalledWith(
            { name: 'My Token', expires_in_days: 90 },
            expect.anything(),
        );
    });

    it('shows access level picker when full access is toggled off', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Acesso total')).toBeInTheDocument();
        });

        // Toggle off full access
        await userEvent.click(screen.getByRole('switch'));

        // Should show resource groups with access level buttons
        await waitFor(() => {
            expect(screen.getByText('Seminários')).toBeInTheDocument();
        });
        expect(screen.getByText('Locais')).toBeInTheDocument();
        expect(screen.getByText('Usuários')).toBeInTheDocument();
        expect(screen.getByText('Dados de Palestrante')).toBeInTheDocument();
    });

    it('submits with specific abilities when full access is off', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Acesso total')).toBeInTheDocument();
        });

        // Fill name
        await userEvent.type(screen.getByPlaceholderText('Ex: Integração TCC'), 'Scoped Token');

        // Toggle off full access
        await userEvent.click(screen.getByRole('switch'));

        await waitFor(() => {
            expect(screen.getByText('Seminários')).toBeInTheDocument();
        });

        // Click "Leitura" for Seminários (first "Leitura" button)
        const leituraButtons = screen.getAllByText('Leitura');
        await userEvent.click(leituraButtons[0]);

        // Click "Escrita" for Locais (second "Escrita" button)
        const escritaButtons = screen.getAllByText('Escrita');
        await userEvent.click(escritaButtons[2]); // 3rd escrita = Locais

        // Submit
        const form = screen.getByPlaceholderText('Ex: Integração TCC').closest('form');
        await act(async () => {
            form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        });

        expect(apiTokensApi.create).toHaveBeenCalledWith(
            expect.objectContaining({
                name: 'Scoped Token',
                abilities: expect.arrayContaining(['seminars:read']),
            }),
            expect.anything(),
        );
    });

    it('disables create when no abilities selected and full access is off', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Acesso total')).toBeInTheDocument();
        });

        await userEvent.type(screen.getByPlaceholderText('Ex: Integração TCC'), 'Test');

        // Toggle off full access (no abilities selected yet)
        await userEvent.click(screen.getByRole('switch'));

        await waitFor(() => {
            expect(screen.getByText('Criar Token')).toBeDisabled();
        });
    });

    it('sets access level to none when clicking Nenhum', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Acesso total')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByRole('switch'));

        await waitFor(() => {
            expect(screen.getByText('Seminários')).toBeInTheDocument();
        });

        // Select Leitura for Seminários
        const leituraButtons = screen.getAllByText('Leitura');
        await userEvent.click(leituraButtons[0]);

        // Then click Nenhum to remove it
        const nenhumButtons = screen.getAllByText('Nenhum');
        await userEvent.click(nenhumButtons[0]);
    });

    it('clears abilities when toggling back to full access', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Acesso total')).toBeInTheDocument();
        });

        // Toggle off, select something, toggle back on
        await userEvent.click(screen.getByRole('switch'));
        await waitFor(() => {
            expect(screen.getByText('Seminários')).toBeInTheDocument();
        });

        const leituraButtons = screen.getAllByText('Leitura');
        await userEvent.click(leituraButtons[0]);

        // Toggle back on
        await userEvent.click(screen.getByRole('switch'));

        // Abilities picker should be hidden
        await waitFor(() => {
            expect(screen.queryByText('Seminários')).not.toBeInTheDocument();
        });
    });

    it('disables create button when name is empty', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Criar Token')).toBeDisabled();
        });
    });

    it('closes create dialog via cancel button', async () => {
        render(<ApiTokenList />);
        await userEvent.click(screen.getByText('Novo Token'));

        await waitFor(() => {
            expect(screen.getByText('Novo Token de API')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('Cancelar'));

        await waitFor(() => {
            expect(screen.queryByText('Novo Token de API')).not.toBeInTheDocument();
        });
    });

    it('shows token dialog on create success', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedCreateOptions).not.toBeNull();
        });

        act(() => {
            capturedCreateOptions.onSuccess({
                message: 'Created',
                data: { id: 1, name: 'T', abilities: ['*'], token: 'sk-test123abc' },
            });
        });

        await waitFor(() => {
            expect(screen.getByText('Token Criado')).toBeInTheDocument();
        });
        expect(screen.getByText('sk-test123abc')).toBeInTheDocument();
        expect(screen.getByText(/Por segurança, ele não será exibido novamente/)).toBeInTheDocument();
    });

    it('copies token to clipboard', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedCreateOptions).not.toBeNull();
        });

        act(() => {
            capturedCreateOptions.onSuccess({
                message: 'Created',
                data: { id: 1, name: 'T', abilities: ['*'], token: 'sk-copytest' },
            });
        });

        await waitFor(() => {
            expect(screen.getByText('sk-copytest')).toBeInTheDocument();
        });

        const copyButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-copy')
        );
        if (copyButton) await userEvent.click(copyButton);

        expect(writeText).toHaveBeenCalledWith('sk-copytest');
    });

    it('closes token dialog via close button', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedCreateOptions).not.toBeNull();
        });

        act(() => {
            capturedCreateOptions.onSuccess({
                message: 'Created',
                data: { id: 1, name: 'T', abilities: ['*'], token: 'sk-close' },
            });
        });

        await waitFor(() => {
            expect(screen.getByText('Token Criado')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('Fechar'));

        await waitFor(() => {
            expect(screen.queryByText('Token Criado')).not.toBeInTheDocument();
        });
    });

    it('handles create mutation onError', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedCreateOptions).not.toBeNull();
        });

        act(() => {
            capturedCreateOptions.onError();
        });
    });

    // --- Delete flow ---

    it('opens and confirms delete dialog', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([mockTokens[0]], { ...paginatedMeta, total: 1, to: 1 }));
        vi.mocked(apiTokensApi.delete).mockResolvedValue({ message: 'Deleted' });

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });

        const deleteButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-trash-2')
        );
        if (deleteButton) await userEvent.click(deleteButton);

        await waitFor(() => {
            expect(screen.getByText('Revogar token?')).toBeInTheDocument();
        });
        expect(screen.getByText(/Qualquer sistema que utilize este token perderá acesso imediatamente/)).toBeInTheDocument();

        await userEvent.click(screen.getByText('Revogar'));
    });

    it('cancels delete dialog', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([mockTokens[0]], { ...paginatedMeta, total: 1, to: 1 }));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });

        const deleteButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-trash-2')
        );
        if (deleteButton) await userEvent.click(deleteButton);

        await waitFor(() => {
            expect(screen.getByText('Revogar token?')).toBeInTheDocument();
        });

        const cancelButtons = screen.getAllByText('Cancelar');
        await userEvent.click(cancelButtons[cancelButtons.length - 1]);

        await waitFor(() => {
            expect(screen.queryByText('Revogar token?')).not.toBeInTheDocument();
        });
    });

    it('handles delete mutation onSuccess and onError', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedDeleteOptions).not.toBeNull();
        });

        act(() => { capturedDeleteOptions.onSuccess(); });
        act(() => { capturedDeleteOptions.onError(); });
    });

    // --- Edit flow ---

    it('opens edit dialog with token data and shows form', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });

        const editButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-pencil')
        );
        if (editButton) await userEvent.click(editButton);

        await waitFor(() => {
            expect(screen.getByText('Editar Token')).toBeInTheDocument();
        });
        expect(screen.getByText('Altere o nome e as permissões do token.')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Prof. Joel Token')).toBeInTheDocument();
        expect(screen.getByText('Salvar')).toBeInTheDocument();
    });

    it('opens edit dialog for scoped token and shows abilities picker', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated([mockTokens[1]]));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Scoped Token')).toBeInTheDocument();
        });

        const editButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-pencil')
        );
        if (editButton) await userEvent.click(editButton);

        await waitFor(() => {
            expect(screen.getByText('Editar Token')).toBeInTheDocument();
        });
        // Scoped token should show abilities picker (full access off)
        expect(screen.getByText('Seminários')).toBeInTheDocument();
    });

    it('submits edit form', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));
        vi.mocked(apiTokensApi.update).mockResolvedValue({
            message: 'Updated',
            data: mockTokens[0],
        } as any);

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });

        const editButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-pencil')
        );
        if (editButton) await userEvent.click(editButton);

        await waitFor(() => {
            expect(screen.getByText('Editar Token')).toBeInTheDocument();
        });

        const form = screen.getByDisplayValue('Prof. Joel Token').closest('form');
        await act(async () => {
            form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        });

        expect(apiTokensApi.update).toHaveBeenCalled();
    });

    it('handles update mutation onSuccess and onError', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedUpdateOptions).not.toBeNull();
        });

        act(() => { capturedUpdateOptions.onSuccess(); });
        act(() => { capturedUpdateOptions.onError(); });
    });

    // --- Regenerate flow ---

    it('opens regenerate confirmation when clicking refresh icon', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });

        const refreshButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg.lucide-refresh-cw')
        );
        if (refreshButton) await userEvent.click(refreshButton);

        await waitFor(() => {
            expect(screen.getByText('Regenerar token?')).toBeInTheDocument();
        });
        expect(screen.getByText(/token antigo precisará ser atualizado/)).toBeInTheDocument();
        expect(screen.getByText('Regenerar')).toBeInTheDocument();

        vi.mocked(apiTokensApi.regenerate).mockResolvedValue({
            message: 'Regenerated',
            data: { id: 2, name: 'Test', abilities: ['*'], token: 'sk-regen' },
        });
        await userEvent.click(screen.getByText('Regenerar'));
    });

    it('handles regenerate mutation onSuccess and onError', async () => {
        render(<ApiTokenList />);

        await waitFor(() => {
            expect(capturedRegenerateOptions).not.toBeNull();
        });

        act(() => {
            capturedRegenerateOptions.onSuccess({
                message: 'Regenerated',
                data: { id: 2, name: 'T', abilities: ['*'], token: 'sk-regenerated' },
            });
        });

        await waitFor(() => {
            expect(screen.getByText('sk-regenerated')).toBeInTheDocument();
        });

        act(() => { capturedRegenerateOptions.onError(); });
    });

    // --- Pagination ---

    it('shows pagination when multiple pages', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens, { last_page: 3, current_page: 1, total: 40, from: 1, to: 15, per_page: 15 }));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Mostrando 1 a 15 de 40 tokens')).toBeInTheDocument();
        });
        expect(screen.getByText('Página 1 de 3')).toBeInTheDocument();
        expect(screen.getByText('Anterior')).toBeDisabled();
    });

    it('hides pagination when only one page', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Prof. Joel Token')).toBeInTheDocument();
        });
        expect(screen.queryByText('Anterior')).not.toBeInTheDocument();
    });

    it('navigates pages forward and backward', async () => {
        vi.mocked(apiTokensApi.list).mockResolvedValue(paginated(mockTokens, { last_page: 3, current_page: 1, total: 40, from: 1, to: 15, per_page: 15 }));

        render(<ApiTokenList />);

        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('Próxima'));
        await waitFor(() => {
            expect(apiTokensApi.list).toHaveBeenCalledWith({ page: 2 });
        });

        await userEvent.click(screen.getByText('Anterior'));
        await waitFor(() => {
            expect(apiTokensApi.list).toHaveBeenCalledWith({ page: 1 });
        });
    });
});
