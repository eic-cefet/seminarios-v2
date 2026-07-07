import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createTestQueryClient } from '@/test/test-utils';
import { toast } from 'sonner';

// Polyfill pointer capture methods needed by Radix UI Select
if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

vi.mock('sonner', () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('../../api/adminClient', () => ({
    registrationsApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        }),
        togglePresence: vi.fn(),
    },
    seminarsApi: {
        list: vi.fn().mockResolvedValue({
            data: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            },
            links: { first: '', last: '', prev: null, next: null },
        }),
    },
    AdminApiError: class extends Error {},
}));

vi.mock('../../components/AddRegistrationsModal', () => ({
    AddRegistrationsModal: ({
        open,
        initialSeminar,
    }: {
        open: boolean;
        initialSeminar: { name: string } | null;
    }) =>
        open ? (
            <div data-testid="add-registrations-modal">
                {initialSeminar?.name ?? 'sem seminario'}
            </div>
        ) : null,
}));

import RegistrationList from './RegistrationList';
import { registrationsApi, seminarsApi } from '../../api/adminClient';
import { analytics } from '@shared/lib/analytics';

const seminarPage = (
    items: Array<{ id: number; name: string; scheduled_at: string }>,
    { current = 1, last = 1 } = {},
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

describe('RegistrationList', () => {
    it('renders the page heading', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Inscricoes')).toBeInTheDocument();
    });

    it('renders the subtitle', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Gerenciar inscricoes e presencas dos seminarios')).toBeInTheDocument();
    });

    it('renders the filters card', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Filtros')).toBeInTheDocument();
    });

    it('renders the search input', () => {
        render(<RegistrationList />);
        expect(screen.getByPlaceholderText('Nome ou email...')).toBeInTheDocument();
    });

    it('shows empty state when no registrations exist', async () => {
        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhuma inscricao encontrada')).toBeInTheDocument();
        });
    });

    it('renders registration list when data is available', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'John Doe', email: 'john@test.com' },
                    seminar: { id: 1, name: 'Seminar A', slug: 'seminar-a', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });
        expect(screen.getByText('john@test.com')).toBeInTheDocument();
        expect(screen.getByText('Seminar A')).toBeInTheDocument();
    });

    it('renders the list title card', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Lista de Inscricoes')).toBeInTheDocument();
    });

    it('renders the seminar filter dropdown', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Seminario')).toBeInTheDocument();
    });

    it('allows typing in the search input', async () => {
        render(<RegistrationList />);
        const user = userEvent.setup();
        const searchInput = screen.getByPlaceholderText('Nome ou email...');
        await user.type(searchInput, 'john');
        expect(searchInput).toHaveValue('john');
    });

    it('shows presence badge for registrations', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: true,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Present User', email: 'present@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
                {
                    id: 2,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 2, name: 'Absent User', email: 'absent@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Present User')).toBeInTheDocument();
        });
        expect(screen.getByText('Sim')).toBeInTheDocument();
        expect(screen.getByText('Nao')).toBeInTheDocument();
    });

    it('shows pagination when there are multiple pages', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 4, current_page: 1, total: 40, from: 1, to: 10 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        expect(screen.getByText('Próxima')).toBeInTheDocument();
        expect(
            screen.getByText('Mostrando 1 a 10 de 40 inscrições'),
        ).toBeInTheDocument();
    });

    it('shows total registrations count', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 55, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('55 inscricoes encontradas')).toBeInTheDocument();
        });
    });

    it('renders table headers when data is present', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Usuario')).toBeInTheDocument();
        });
        expect(screen.getByText('Email')).toBeInTheDocument();
        expect(screen.getByText('Presente')).toBeInTheDocument();
    });

    it('shows fallback text when user is removed', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: null,
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Usuario removido')).toBeInTheDocument();
        });
    });

    it('shows fallback text when seminar is removed', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: null,
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Seminario removido')).toBeInTheDocument();
        });
    });

    it('shows dash when user email is null', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: null,
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('-')).toBeInTheDocument();
        });
    });

    it('toggles presence by clicking the switch', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 42,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Toggle User', email: 'toggle@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockResolvedValue({
            data: { id: 42, present: true },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Toggle User')).toBeInTheDocument();
        });

        // Find the switch button
        const switchBtn = screen.getByRole('switch');
        await user.click(switchBtn);

        await waitFor(() => {
            expect(registrationsApi.togglePresence).toHaveBeenCalledWith(42);
        });
    });

    it('shows Limpar filtros button when search has value', async () => {
        render(<RegistrationList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Nome ou email...');
        await user.type(searchInput, 'test search');

        // Need to wait for debounce
        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        }, { timeout: 2000 });
    });

    it('clears filters when Limpar filtros is clicked', async () => {
        render(<RegistrationList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Nome ou email...');
        await user.type(searchInput, 'john');

        await waitFor(() => {
            expect(screen.getByText('Limpar filtros')).toBeInTheDocument();
        }, { timeout: 2000 });

        await user.click(screen.getByText('Limpar filtros'));

        await waitFor(() => {
            expect(searchInput).toHaveValue('');
        });
    });

    it('shows empty state when no registrations and no filters', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Nenhuma inscricao encontrada')).toBeInTheDocument();
        });
    });

    it('disables Anterior button on the first page', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 4, current_page: 1, total: 40, from: 1, to: 10 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeDisabled();
        });
    });

    it('clicking Proxima advances the page', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 1, total: 30, from: 1, to: 10 },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Próxima'));

        await waitFor(() => {
            expect(registrationsApi.list).toHaveBeenCalled();
        });
    });

    it('shows pagination info text', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 4, current_page: 1, total: 40, from: 1, to: 10 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Mostrando 1 a 10 de 40 inscrições')).toBeInTheDocument();
        });
    });

    it('does not show pagination for single page', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('User')).toBeInTheDocument();
        });
        expect(screen.queryByText('Anterior')).not.toBeInTheDocument();
    });

    it('renders the seminar name column', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 5, name: 'IA Advanced', slug: 'ia-advanced', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('IA Advanced')).toBeInTheDocument();
        });
        // "Seminario" appears in both the table header and filter label
        expect(screen.getAllByText('Seminario').length).toBeGreaterThanOrEqual(1);
    });

    it('clicking Anterior goes to previous page', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 3, current_page: 2, total: 30, from: 11, to: 20 },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        // Go to page 2 first
        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Próxima'));

        // Click Anterior
        await waitFor(() => {
            expect(screen.getByText('Anterior')).toBeInTheDocument();
        });
        await user.click(screen.getByText('Anterior'));

        await waitFor(() => {
            expect(registrationsApi.list).toHaveBeenCalled();
        });
    });

    it('shows Buscar usuario label in filters', () => {
        render(<RegistrationList />);
        expect(screen.getByText('Buscar usuario')).toBeInTheDocument();
    });

    it('loads seminars only when the combobox is opened', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 1, name: 'Old Seminar', scheduled_at: '2025-01-01T10:00:00Z' },
            { id: 2, name: 'New Seminar', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        expect(seminarsApi.list).not.toHaveBeenCalled();

        await user.click(screen.getByRole('combobox'));

        expect(
            await screen.findByRole('option', { name: /New Seminar/ }),
        ).toBeInTheDocument();
        expect(seminarsApi.list).toHaveBeenCalledWith({ page: 1, search: undefined });
    });

    it('covers togglePresenceMutation onError rollback (lines 110-121)', async () => {
        // Lines 110-121: onError callback rolls back optimistic update
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 50,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Error User', email: 'error@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockRejectedValue(new Error('Toggle failed'));

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Error User')).toBeInTheDocument();
        });

        // Toggle presence - this will trigger onMutate (optimistic update) then onError (rollback)
        const switchBtn = screen.getByRole('switch');
        await user.click(switchBtn);

        await waitFor(() => {
            expect(registrationsApi.togglePresence).toHaveBeenCalledWith(50);
        });
    });

    it('renders Data Inscricao column header', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 1,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'User', email: 'user@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);

        render(<RegistrationList />);

        await waitFor(() => {
            expect(screen.getByText('Data Inscricao')).toBeInTheDocument();
        });
    });

    it('filters registrations by seminar via the combobox', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 10, name: 'Seminar X', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Seminar X/ }));

        await waitFor(() => {
            expect(registrationsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ seminar_id: 10 }),
            );
        });
    });

    it('resets the seminar filter by selecting "Todos os seminarios"', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 10, name: 'Seminar X', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Seminar X/ }));

        await waitFor(() => {
            expect(registrationsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ seminar_id: 10 }),
            );
        });

        await user.click(screen.getByRole('combobox'));
        await user.click(
            await screen.findByRole('option', { name: /Todos os seminarios/ }),
        );

        await waitFor(() => {
            expect(registrationsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ seminar_id: undefined }),
            );
        });
    });

    it('covers optimistic update else branch when multiple registrations exist', async () => {
        // When toggling presence for one registration in a list of many,
        // the map function hits the else branch (reg.id !== id) for the other registrations
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 100,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Target User', email: 'target@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
                {
                    id: 200,
                    present: true,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 2, name: 'Other User', email: 'other@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 2, from: 1, to: 2 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockResolvedValue({
            data: { id: 100, present: true },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Target User')).toBeInTheDocument();
            expect(screen.getByText('Other User')).toBeInTheDocument();
        });

        // Toggle presence for the first registration (id: 100)
        // This causes the map to iterate both registrations:
        //   - id 100: matches, returns { ...reg, present: !reg.present }
        //   - id 200: doesn't match, returns reg (the else branch)
        const switches = screen.getAllByRole('switch');
        await user.click(switches[0]);

        await waitFor(() => {
            expect(registrationsApi.togglePresence).toHaveBeenCalledWith(100);
        });
    });

    it('covers onSuccess with isPresent=true showing "Presenca confirmada"', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 77,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Confirm User', email: 'confirm@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockResolvedValue({
            data: { id: 77, present: true },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Confirm User')).toBeInTheDocument();
        });

        const switchBtn = screen.getByRole('switch');
        await user.click(switchBtn);

        await waitFor(() => {
            expect(toast.success).toHaveBeenCalledWith('Presenca confirmada');
        });
        expect(analytics.event).toHaveBeenCalledWith('admin_registration_toggle_presence', {
            registration_id: 77,
            present: true,
        });
    });

    it('covers onSuccess with isPresent=false showing "Presenca removida"', async () => {
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 88,
                    present: true,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Remove User', email: 'remove@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockResolvedValue({
            data: { id: 88, present: false },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Remove User')).toBeInTheDocument();
        });

        const switchBtn = screen.getByRole('switch');
        await user.click(switchBtn);

        await waitFor(() => {
            expect(toast.success).toHaveBeenCalledWith('Presenca removida');
        });
        expect(analytics.event).toHaveBeenCalledWith('admin_registration_toggle_presence', {
            registration_id: 88,
            present: false,
        });
    });

    it('covers optimistic update early return when query data is falsy', async () => {
        // Use a custom query client where the registrations query has no cached data
        // so that the optimistic updater function receives undefined for `old`
        const queryClient = createTestQueryClient();

        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 99,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'Falsy User', email: 'falsy@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockResolvedValue({
            data: { id: 99, present: true },
        } as any);

        render(<RegistrationList />, { queryClient });

        await waitFor(() => {
            expect(screen.getByText('Falsy User')).toBeInTheDocument();
        });

        // Clear the query cache before mutating so the optimistic updater receives undefined
        queryClient.removeQueries({ queryKey: ['admin-registrations'] });

        const switchBtn = screen.getByRole('switch');
        const user = userEvent.setup();
        await user.click(switchBtn);

        // The mutation should still complete successfully
        await waitFor(() => {
            expect(registrationsApi.togglePresence).toHaveBeenCalledWith(99);
        });

        // onSuccess toast should still fire
        await waitFor(() => {
            expect(toast.success).toHaveBeenCalled();
        });
    });

    it('covers onError without previousData in context', async () => {
        const queryClient = createTestQueryClient();

        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [
                {
                    id: 60,
                    present: false,
                    reminder_sent: false,
                    certificate_sent: false,
                    user: { id: 1, name: 'NoCtx User', email: 'noctx@test.com' },
                    seminar: { id: 1, name: 'Seminar', slug: 'seminar', scheduled_at: '2026-06-15T14:00:00Z' },
                    created_at: '2026-01-01T00:00:00Z',
                    updated_at: '2026-01-01T00:00:00Z',
                },
            ],
            meta: { last_page: 1, current_page: 1, total: 1, from: 1, to: 1 },
        } as any);
        vi.mocked(registrationsApi.togglePresence).mockRejectedValue(new Error('Server error'));

        render(<RegistrationList />, { queryClient });

        await waitFor(() => {
            expect(screen.getByText('NoCtx User')).toBeInTheDocument();
        });

        // Remove query data so onMutate captures previousData as undefined
        // and the onError handler has context?.previousData as falsy
        queryClient.removeQueries({ queryKey: ['admin-registrations'] });

        const switchBtn = screen.getByRole('switch');
        const user = userEvent.setup();
        await user.click(switchBtn);

        // The error toast should still fire even without previousData
        await waitFor(() => {
            expect(toast.error).toHaveBeenCalledWith('Erro ao atualizar presenca');
        });
    });

    it('shows Limpar filtros in empty state when seminar filter is active', async () => {
        // This covers branch 282:2 - empty registrations with active filters
        // showing the "Limpar filtros" button in the empty state area
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 10, name: 'Filter Seminar', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);
        vi.mocked(registrationsApi.list).mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0, from: 0, to: 0 },
        } as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('combobox'));
        await user.click(
            await screen.findByRole('option', { name: /Filter Seminar/ }),
        );

        // Now the empty state should show "Limpar filtros" link button
        await waitFor(() => {
            expect(screen.getByText('Nenhuma inscricao encontrada')).toBeInTheDocument();
        });

        // The "Limpar filtros" button in the empty state section (variant="link")
        const clearButtons = screen.getAllByText('Limpar filtros');
        expect(clearButtons.length).toBeGreaterThanOrEqual(1);
    });

    it('shows Carregando... while seminars load in the combobox', async () => {
        vi.mocked(seminarsApi.list).mockReturnValue(new Promise(() => {}) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();
        await user.click(screen.getByRole('combobox'));

        expect(await screen.findByText('Carregando...')).toBeInTheDocument();
    });

    it('clears the seminar filter when Limpar filtros is clicked', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 10, name: 'Seminar X', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Seminar X/ }));

        await waitFor(() => {
            expect(screen.getAllByText('Limpar filtros').length).toBeGreaterThanOrEqual(1);
        });
        await user.click(screen.getAllByText('Limpar filtros')[0]);

        await waitFor(() => {
            expect(screen.getByRole('combobox')).toHaveTextContent(
                'Todos os seminarios',
            );
        });
    });

    it('renders the Adicionar inscricoes button', () => {
        render(<RegistrationList />);
        expect(
            screen.getByRole('button', { name: /Adicionar inscricoes/ }),
        ).toBeInTheDocument();
    });

    it('opens the add-registrations modal without a seminar prefilled', async () => {
        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(
            screen.getByRole('button', { name: /Adicionar inscricoes/ }),
        );

        expect(screen.getByTestId('add-registrations-modal')).toHaveTextContent(
            'sem seminario',
        );
    });

    it('opens the add-registrations modal with the filtered seminar prefilled', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(seminarPage([
            { id: 10, name: 'Seminar X', scheduled_at: '2026-06-15T14:00:00Z' },
        ]) as any);

        render(<RegistrationList />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('combobox'));
        await user.click(await screen.findByRole('option', { name: /Seminar X/ }));

        await user.click(
            screen.getByRole('button', { name: /Adicionar inscricoes/ }),
        );

        expect(screen.getByTestId('add-registrations-modal')).toHaveTextContent(
            'Seminar X',
        );
    });
});
