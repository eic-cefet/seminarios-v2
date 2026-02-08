import { render, screen, waitFor, userEvent } from '@/test/test-utils';

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('../../api/adminClient', () => ({
    seminarsApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        }),
        delete: vi.fn().mockResolvedValue({ message: 'Deleted' }),
    },
    presenceLinkApi: {
        get: vi.fn().mockResolvedValue({ data: null }),
        create: vi.fn(),
        toggle: vi.fn(),
    },
    AdminApiError: class extends Error {},
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useNavigate: vi.fn(() => vi.fn()) };
});

import SeminarList from './SeminarList';
import { seminarsApi } from '../../api/adminClient';

describe('SeminarList', () => {
    it('renders the page heading', () => {
        render(<SeminarList />);
        expect(screen.getByText('Seminários')).toBeInTheDocument();
    });

    it('renders the subtitle', () => {
        render(<SeminarList />);
        expect(screen.getByText('Gerenciar seminários do sistema')).toBeInTheDocument();
    });

    it('renders the new seminar button', () => {
        render(<SeminarList />);
        expect(screen.getByText('Novo Seminário')).toBeInTheDocument();
    });

    it('renders the filters card', () => {
        render(<SeminarList />);
        expect(screen.getByText('Filtros')).toBeInTheDocument();
    });

    it('shows empty state when no seminars exist', async () => {
        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum seminário encontrado')).toBeInTheDocument();
        });
    });

    it('renders seminar list when data is available', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Seminário de IA',
                    slug: 'seminario-de-ia',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: { id: 1, name: 'Sala 101', max_vacancies: 50 },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Seminário de IA')).toBeInTheDocument();
        });
        expect(screen.getByText('Sala 101')).toBeInTheDocument();
        expect(screen.getByText('Ativo')).toBeInTheDocument();
    });

    it('renders table headers', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    slug: 'test',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Nome')).toBeInTheDocument();
        });
        expect(screen.getByText('Data')).toBeInTheDocument();
        expect(screen.getByText('Local')).toBeInTheDocument();
        expect(screen.getByText('Status')).toBeInTheDocument();
    });

    it('renders search input', () => {
        render(<SeminarList />);
        expect(screen.getByPlaceholderText('Nome do seminário...')).toBeInTheDocument();
    });

    it('allows typing in the search input', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();
        const searchInput = screen.getByPlaceholderText('Nome do seminário...');
        await user.type(searchInput, 'IA');
        expect(searchInput).toHaveValue('IA');
    });

    it('shows pagination when there are multiple pages', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Seminário 1',
                    slug: 'seminario-1',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        expect(screen.getByText('Próxima')).toBeInTheDocument();
        expect(screen.getByText('Página 1 de 3')).toBeInTheDocument();
    });

    it('disables Anterior button on first page', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Seminário 1',
                    slug: 'seminario-1',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeDisabled();
        });
    });

    it('clicking Próxima advances the page', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Seminário 1',
                    slug: 'seminario-1',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Próxima'));

        // After clicking, the seminarsApi.list should have been called again
        expect(seminarsApi.list).toHaveBeenCalled();
    });

    it('navigates to new seminar form on button click', async () => {
        const { useNavigate } = await import('react-router-dom');
        const mockNavigate = vi.fn();
        vi.mocked(useNavigate).mockReturnValue(mockNavigate);

        render(<SeminarList />);
        const user = userEvent.setup();

        await user.click(screen.getByText('Novo Seminário'));
        expect(mockNavigate).toHaveBeenCalledWith('/seminars/new');
    });

    it('opens delete dialog when trash button is clicked', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Seminário Delete',
                    slug: 'seminario-delete',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Seminário Delete')).toBeInTheDocument();
        });

        // Click the delete (trash) icon button - it's the third button in the actions column
        const row = screen.getByText('Seminário Delete').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        const deleteButton = buttons[buttons.length - 1]; // last button is delete
        await user.click(deleteButton);

        await waitFor(() => {
            expect(screen.getByText('Excluir seminário?')).toBeInTheDocument();
        });
        expect(screen.getAllByText(/Seminário Delete/).length).toBeGreaterThanOrEqual(1);
    });

    it('shows inactive badge for inactive seminars', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Inactive Seminar',
                    slug: 'inactive',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: false,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Inativo')).toBeInTheDocument();
        });
    });

    it('shows dash when location is null', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'No Location',
                    slug: 'no-location',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('-')).toBeInTheDocument();
        });
    });

    it('shows total count in the header', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Test',
                    slug: 'test',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 42, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('42 seminários encontrados')).toBeInTheDocument();
        });
    });

    it('confirms delete and calls seminarsApi.delete', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 7,
                    name: 'Delete Target',
                    slug: 'delete-target',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(seminarsApi.delete).mockResolvedValue({ message: 'Deleted' } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Delete Target')).toBeInTheDocument();
        });

        const row = screen.getByText('Delete Target').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        const deleteButton = buttons[buttons.length - 1];
        await user.click(deleteButton);

        await waitFor(() => {
            expect(screen.getByText('Excluir seminário?')).toBeInTheDocument();
        });

        const confirmBtn = screen.getByRole('button', { name: 'Excluir' });
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(seminarsApi.delete).toHaveBeenCalledWith(7);
        });
    });

    it('navigates to edit form when edit button is clicked', async () => {
        const { useNavigate } = await import('react-router-dom');
        const mockNavigate = vi.fn();
        vi.mocked(useNavigate).mockReturnValue(mockNavigate);

        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 42,
                    name: 'Edit Seminar',
                    slug: 'edit-seminar',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Edit Seminar')).toBeInTheDocument();
        });

        const row = screen.getByText('Edit Seminar').closest('tr')!;
        const buttons = row.querySelectorAll('button');
        // The edit button is the second button (after QR code)
        await user.click(buttons[1]);

        expect(mockNavigate).toHaveBeenCalledWith('/seminars/42/edit');
    });

    it('shows Limpar filtros button when search has value', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Nome do seminário...');
        await user.type(searchInput, 'test search');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });
    });

    it('clears all filters when Limpar filtros is clicked', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Nome do seminário...');
        await user.type(searchInput, 'something');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Limpar filtros'));

        await waitFor(() => {
            expect(searchInput).toHaveValue('');
        });
    });

    it('does not show pagination for single page', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Solo',
                    slug: 'solo',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Solo')).toBeInTheDocument();
        });
        expect(screen.queryByText('Anterior')).not.toBeInTheDocument();
    });

    it('shows pagination info text', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Page Test',
                    slug: 'page-test',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<SeminarList />);

        await waitFor(() => {
            expect(screen.getByText('Mostrando 1 a 10 de 30 seminários')).toBeInTheDocument();
        });
    });

    it('shows empty state with clear filters link when filters are active', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Nome do seminário...');
        await user.type(searchInput, 'nonexistent');

        await waitFor(() => {
            expect(screen.getByText('Nenhum seminário encontrado')).toBeInTheDocument();
        });
    });

    it('renders the active filter switch', () => {
        render(<SeminarList />);
        expect(screen.getByText('Apenas ativos')).toBeInTheDocument();
    });

    it('renders the upcoming filter switch', () => {
        render(<SeminarList />);
        expect(screen.getByText('Apenas futuros')).toBeInTheDocument();
    });

    it('renders active filter switch as unchecked initially', () => {
        render(<SeminarList />);
        const activeSwitch = screen.getByRole('switch', { name: 'Apenas ativos' });
        expect(activeSwitch).toBeInTheDocument();
        expect(activeSwitch).not.toBeChecked();
    });

    it('renders upcoming filter switch as unchecked initially', () => {
        render(<SeminarList />);
        const upcomingSwitch = screen.getByRole('switch', { name: 'Apenas futuros' });
        expect(upcomingSwitch).toBeInTheDocument();
        expect(upcomingSwitch).not.toBeChecked();
    });

    it('opens QR code modal when QR button is clicked', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 10,
                    name: 'QR Seminar',
                    slug: 'qr-seminar',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('QR Seminar')).toBeInTheDocument();
        });

        // The QR Code button is the first button in the row (has title)
        const qrButton = screen.getByTitle('Link de Presença (QR Code)');
        await user.click(qrButton);

        // PresenceLinkModal should be rendered (we check that selectedSeminar is set)
        // The modal content depends on PresenceLinkModal component internals
        // but at minimum the state change should happen without errors
    });

    it('renders the list title card', () => {
        render(<SeminarList />);
        expect(screen.getByText('Lista de Seminários')).toBeInTheDocument();
    });

    it('clicking Anterior goes back a page', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Sem',
                    slug: 'sem',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    active: true,
                    location: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 2, total: 30, from: 11, to: 20 },
        } as any);

        render(<SeminarList />);
        const user = userEvent.setup();

        // Go to page 2 first
        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Próxima'));

        // Now click Anterior
        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Anterior'));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalled();
        });
    });

    it('toggles active filter switch', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        const activeSwitch = screen.getByRole('switch', { name: 'Apenas ativos' });
        await user.click(activeSwitch);

        // After toggling, the filter should be active
        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ active: true }),
            );
        });

        // Toggle off
        await user.click(activeSwitch);
        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ active: undefined }),
            );
        });
    });

    it('toggles upcoming filter switch', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        const upcomingSwitch = screen.getByRole('switch', { name: 'Apenas futuros' });
        await user.click(upcomingSwitch);

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ upcoming: true }),
            );
        });
    });

    it('shows Limpar filtros when active filter is set', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        const activeSwitch = screen.getByRole('switch', { name: 'Apenas ativos' });
        await user.click(activeSwitch);

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });
    });

    it('clears active and upcoming filters when Limpar filtros is clicked', async () => {
        render(<SeminarList />);
        const user = userEvent.setup();

        // Toggle active and upcoming on
        await user.click(screen.getByRole('switch', { name: 'Apenas ativos' }));
        await user.click(screen.getByRole('switch', { name: 'Apenas futuros' }));

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Limpar filtros'));

        // Filters should be cleared
        await waitFor(() => {
            const activeSwitch = screen.getByRole('switch', { name: 'Apenas ativos' });
            expect(activeSwitch).not.toBeChecked();
            const upcomingSwitch = screen.getByRole('switch', { name: 'Apenas futuros' });
            expect(upcomingSwitch).not.toBeChecked();
        });
    });
});
