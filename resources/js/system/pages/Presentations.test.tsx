import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createSeminar, createPaginatedResponse } from '@/test/factories';
import Presentations from './Presentations';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/api/client', () => ({
    seminarsApi: { list: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 12, total: 0 } }) },
    seminarTypesApi: { list: vi.fn().mockResolvedValue({ data: [{ id: 1, name: 'Palestra' }, { id: 2, name: 'Workshop' }] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { seminarsApi, seminarTypesApi } from '@shared/api/client';

describe('Presentations', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse([]));
        vi.mocked(seminarTypesApi.list).mockResolvedValue({ data: [{ id: 1, name: 'Palestra' }, { id: 2, name: 'Workshop' }] });
    });

    it('renders "Apresentações" heading', () => {
        render(<Presentations />);
        expect(screen.getByRole('heading', { name: /^apresentações$/i })).toBeInTheDocument();
    });

    it('renders subtitle text', () => {
        render(<Presentations />);
        expect(screen.getByText(/todas as apresentações e seminários realizados/i)).toBeInTheDocument();
    });

    it('renders seminar cards after loading', async () => {
        const seminars = [
            createSeminar({ name: 'Talk on AI' }),
            createSeminar({ name: 'Talk on Security' }),
        ];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByText('Talk on AI')).toBeInTheDocument();
            expect(screen.getByText('Talk on Security')).toBeInTheDocument();
        });
    });

    it('shows empty state when no seminars', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse([]));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByText(/nenhuma apresentação encontrada/i)).toBeInTheDocument();
        });
    });

    it('shows empty state helper text', async () => {
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse([]));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByText(/tente ajustar os filtros/i)).toBeInTheDocument();
        });
    });

    it('renders time filter tabs', () => {
        render(<Presentations />);
        expect(screen.getByRole('tab', { name: /todos/i })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: /próximos/i })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: /encerrados/i })).toBeInTheDocument();
    });

    it('switches to "Próximos" tab and calls API with upcoming filter', async () => {
        const user = userEvent.setup();
        render(<Presentations />);

        await user.click(screen.getByRole('tab', { name: /próximos/i }));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ upcoming: true, direction: 'asc' })
            );
        });
    });

    it('switches to "Encerrados" tab and calls API with expired filter', async () => {
        const user = userEvent.setup();
        render(<Presentations />);

        await user.click(screen.getByRole('tab', { name: /encerrados/i }));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ expired: true, direction: 'desc' })
            );
        });
    });

    it('shows "Filtrar por" label', () => {
        render(<Presentations />);
        expect(screen.getByText(/filtrar por/i)).toBeInTheDocument();
    });

    it('renders pagination when there are multiple pages', async () => {
        const seminars = Array.from({ length: 3 }, (_, i) => createSeminar({ name: `Talk ${i + 1}` }));
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars, {
            current_page: 1,
            last_page: 3,
            per_page: 12,
            total: 36,
        }));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByText(/página 1 de 3/i)).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /anterior/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /próxima/i })).toBeInTheDocument();
        });
    });

    it('disables "Anterior" button on first page', async () => {
        const seminars = [createSeminar({ name: 'Talk 1' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars, {
            current_page: 1,
            last_page: 3,
            per_page: 12,
            total: 36,
        }));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /anterior/i })).toBeDisabled();
        });
    });

    it('navigates to next page when "Próxima" is clicked', async () => {
        const seminars = [createSeminar({ name: 'Talk 1' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars, {
            current_page: 1,
            last_page: 3,
            per_page: 12,
            total: 36,
        }));
        const user = userEvent.setup();

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /próxima/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /próxima/i }));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ page: 2 })
            );
        });
    });

    it('does not show pagination when only one page', async () => {
        const seminars = [createSeminar({ name: 'Talk 1' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars, {
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 1,
        }));

        render(<Presentations />);

        await waitFor(() => {
            expect(screen.getByText('Talk 1')).toBeInTheDocument();
        });

        expect(screen.queryByRole('button', { name: /anterior/i })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /próxima/i })).not.toBeInTheDocument();
    });

    it('shows loading skeleton while data is loading', () => {
        vi.mocked(seminarsApi.list).mockImplementation(() => new Promise(() => {}));

        render(<Presentations />);

        // The skeleton shows animated divs, not the empty state
        expect(screen.queryByText(/nenhuma apresentação encontrada/i)).not.toBeInTheDocument();
    });

    it('calls seminarsApi.list with "all" filter by default', () => {
        render(<Presentations />);

        expect(seminarsApi.list).toHaveBeenCalledWith(
            expect.objectContaining({
                upcoming: undefined,
                expired: undefined,
                page: 1,
                per_page: 12,
                direction: 'desc',
            })
        );
    });

    it('shows "Limpar filtro" button and calls API with type filter when a type is selected', async () => {
        const seminars = [createSeminar({ name: 'Talk 1' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars));
        const user = userEvent.setup();

        // Polyfill pointer capture methods for Radix Select
        const origHasPointerCapture = HTMLElement.prototype.hasPointerCapture;
        const origSetPointerCapture = HTMLElement.prototype.setPointerCapture;
        const origReleasePointerCapture = HTMLElement.prototype.releasePointerCapture;
        HTMLElement.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
        HTMLElement.prototype.setPointerCapture = vi.fn();
        HTMLElement.prototype.releasePointerCapture = vi.fn();

        render(<Presentations />);

        // The "Limpar filtro" button should not appear initially
        expect(screen.queryByText(/limpar filtro/i)).not.toBeInTheDocument();

        // Open the Radix Select by clicking the combobox trigger
        const selectTrigger = screen.getByRole('combobox');
        await user.click(selectTrigger);

        // Select "Palestra" from the dropdown options
        const palestraOption = await screen.findByRole('option', { name: /palestra/i });
        await user.click(palestraOption);

        // Verify the API was called with the type filter (covers branch 30:0)
        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'Palestra' })
            );
        });

        // Verify the "Limpar filtro" button now appears (covers branch 151:1)
        expect(screen.getByText(/limpar filtro/i)).toBeInTheDocument();

        // Restore original methods
        HTMLElement.prototype.hasPointerCapture = origHasPointerCapture;
        HTMLElement.prototype.setPointerCapture = origSetPointerCapture;
        HTMLElement.prototype.releasePointerCapture = origReleasePointerCapture;
    });

    it('clears type filter when "Limpar filtro" button is clicked', async () => {
        const seminars = [createSeminar({ name: 'Talk 1' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars));
        const user = userEvent.setup();

        // Polyfill pointer capture methods for Radix Select
        const origHasPointerCapture = HTMLElement.prototype.hasPointerCapture;
        const origSetPointerCapture = HTMLElement.prototype.setPointerCapture;
        const origReleasePointerCapture = HTMLElement.prototype.releasePointerCapture;
        HTMLElement.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
        HTMLElement.prototype.setPointerCapture = vi.fn();
        HTMLElement.prototype.releasePointerCapture = vi.fn();

        render(<Presentations />);

        // Open the Radix Select and pick a type
        const selectTrigger = screen.getByRole('combobox');
        await user.click(selectTrigger);
        const palestraOption = await screen.findByRole('option', { name: /palestra/i });
        await user.click(palestraOption);

        // Wait for the "Limpar filtro" button to appear
        const clearButton = await screen.findByText(/limpar filtro/i);

        // Click "Limpar filtro" to reset the filter (covers fn at line 153)
        await user.click(clearButton);

        // Verify the "Limpar filtro" button disappears
        await waitFor(() => {
            expect(screen.queryByText(/limpar filtro/i)).not.toBeInTheDocument();
        });

        // Verify the API was called again with type: undefined (back to "all")
        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ type: undefined })
            );
        });

        // Restore original methods
        HTMLElement.prototype.hasPointerCapture = origHasPointerCapture;
        HTMLElement.prototype.setPointerCapture = origSetPointerCapture;
        HTMLElement.prototype.releasePointerCapture = origReleasePointerCapture;
    });

    it('navigates to previous page when "Anterior" is clicked', async () => {
        // Start on page 2
        const seminars = [createSeminar({ name: 'Talk on Page 2' })];
        vi.mocked(seminarsApi.list).mockResolvedValue(createPaginatedResponse(seminars, {
            current_page: 1,
            last_page: 3,
            per_page: 12,
            total: 36,
        }));
        const user = userEvent.setup();

        render(<Presentations />);

        // Wait for pagination to appear
        await waitFor(() => {
            expect(screen.getByRole('button', { name: /próxima/i })).toBeInTheDocument();
        });

        // Go to page 2 first
        await user.click(screen.getByRole('button', { name: /próxima/i }));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ page: 2 })
            );
        });

        // Now go back to page 1
        await user.click(screen.getByRole('button', { name: /anterior/i }));

        await waitFor(() => {
            expect(seminarsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ page: 1 })
            );
        });
    });
});
