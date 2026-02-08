import { render, screen, waitFor, userEvent, fireEvent } from '@/test/test-utils';
import { createUser, createPendingEvaluation } from '@/test/factories';
import Evaluations from './Evaluations';

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
    profileApi: {
        pendingEvaluations: vi.fn().mockResolvedValue({ data: [] }),
        submitRating: vi.fn().mockResolvedValue({ message: 'ok', rating: { id: 1, score: 5, comment: null } }),
    },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { useAuth } from '@shared/contexts/AuthContext';
import { profileApi } from '@shared/api/client';
import { analytics } from '@shared/lib/analytics';

const singleEvaluation = () => [
    createPendingEvaluation({
        id: 1,
        seminar: {
            id: 10, name: 'Test Seminar', slug: 'test-seminar',
            scheduled_at: '2026-06-15T14:00:00Z',
            seminar_type: { id: 1, name: 'Palestra' },
            location: { id: 1, name: 'Room 101' },
        },
    }),
];

describe('Evaluations', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
    });

    it('renders "Avaliar Seminarios" heading', () => {
        render(<Evaluations />);
        expect(screen.getByRole('heading', { name: /avaliar seminarios/i })).toBeInTheDocument();
    });

    it('renders pending evaluations list', async () => {
        const evaluations = [
            createPendingEvaluation({ seminar: { id: 1, name: 'AI Workshop', slug: 'ai-workshop', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Workshop' }, location: { id: 1, name: 'Room 101' } } }),
            createPendingEvaluation({ seminar: { id: 2, name: 'Web Talk', slug: 'web-talk', scheduled_at: '2026-07-10T10:00:00Z', seminar_type: { id: 1, name: 'Palestra' }, location: { id: 1, name: 'Room 202' } } }),
        ];
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: evaluations });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText('AI Workshop')).toBeInTheDocument();
            expect(screen.getByText('Web Talk')).toBeInTheDocument();
        });
    });

    it('shows empty state when no pending evaluations', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: [] });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText(/nenhuma avaliacao pendente/i)).toBeInTheDocument();
        });
    });

    it('shows profile link in empty state', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: [] });

        render(<Evaluations />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /ir para o perfil/i });
            expect(link).toHaveAttribute('href', '/perfil');
        });
    });

    it('renders "Avaliar" button for each evaluation item', async () => {
        const evaluations = [
            createPendingEvaluation({ seminar: { id: 1, name: 'Test Seminar', slug: 'test', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Palestra' }, location: { id: 1, name: 'Room 101' } } }),
        ];
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: evaluations });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });
    });

    it('redirects to login when user is not authenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Evaluations />, {
            routerProps: { initialEntries: ['/avaliacoes'] },
        });

        // Navigate component renders nothing visible, but should redirect
        expect(screen.queryByRole('heading', { name: /avaliar seminarios/i })).not.toBeInTheDocument();
    });

    it('shows loading state when auth is loading', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: true, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Evaluations />);

        // Should not show the heading (loading spinner is shown instead)
        expect(screen.queryByRole('heading', { name: /avaliar seminarios/i })).not.toBeInTheDocument();
    });

    it('shows evaluation count in the header', async () => {
        const evaluations = [
            createPendingEvaluation({ seminar: { id: 1, name: 'Seminar A', slug: 'a', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Palestra' }, location: { id: 1, name: 'Room 101' } } }),
            createPendingEvaluation({ seminar: { id: 2, name: 'Seminar B', slug: 'b', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Palestra' }, location: { id: 1, name: 'Room 101' } } }),
        ];
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: evaluations });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText('2 seminarios')).toBeInTheDocument();
        });
    });

    it('shows singular count for 1 seminar', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText('1 seminario')).toBeInTheDocument();
        });
    });

    it('expands evaluation form when "Avaliar" button is clicked', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        expect(screen.getByText(/como voce avalia este seminario/i)).toBeInTheDocument();
    });

    it('shows "Cancelar" button after expanding the form', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        expect(screen.getByRole('button', { name: /cancelar/i })).toBeInTheDocument();
    });

    it('collapses form when "Cancelar" is clicked', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));
        expect(screen.getByText(/como voce avalia este seminario/i)).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /cancelar/i }));
        expect(screen.queryByText(/como voce avalia este seminario/i)).not.toBeInTheDocument();
    });

    it('shows error when submitting without selecting a score', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        // The submit button is disabled when score is 0, so submit via form
        const submitButton = screen.getByRole('button', { name: /enviar avaliacao/i });
        expect(submitButton).toBeDisabled();
    });

    it('shows star rating labels when score is selected', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        // Star buttons are inside a div following the label "Como voce avalia este seminario?"
        // They have class "p-1 cursor-pointer" and type="button"
        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        expect(starButtons.length).toBe(5);

        await user.click(starButtons[4]); // 5th star = Excelente
        expect(screen.getByText('Excelente')).toBeInTheDocument();
    });

    it('shows comment field as optional for scores > 3', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5
        expect(screen.getByText('(opcional)')).toBeInTheDocument();
    });

    it('shows comment as required for scores <= 3', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[1]); // score = 2
        expect(screen.getByText('(obrigatorio)')).toBeInTheDocument();
    });

    it('shows error when submitting low score without comment', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[1]); // score = 2

        // Submit without comment
        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByText(/conte-nos o que podemos melhorar/i)).toBeInTheDocument();
        });
    });

    it('shows success message after submitting a valid evaluation', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        });
    });

    it('fires analytics event on successful submission', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(analytics.event).toHaveBeenCalledWith('evaluation_submit', {
                seminar_id: 10,
                score: 5,
            });
        });
    });

    it('shows error when API submission fails', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockRejectedValue(new Error('Server error'));
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByText('Server error')).toBeInTheDocument();
        });
    });

    it('shows seminar type badge when available', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText('Palestra')).toBeInTheDocument();
        });
    });

    it('shows location when available', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByText('Room 101')).toBeInTheDocument();
        });
    });

    it('shows character count for comment field', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        expect(screen.getByText('0/1000')).toBeInTheDocument();
    });

    it('displays seminar link to details page', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });

        render(<Evaluations />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: 'Test Seminar' });
            expect(link).toHaveAttribute('href', '/seminario/test-seminar');
        });
    });

    it('renders description text', () => {
        render(<Evaluations />);
        expect(screen.getByText(/avalie os seminarios que voce participou/i)).toBeInTheDocument();
    });

    it('shows loading spinner while evaluations are loading', () => {
        vi.mocked(profileApi.pendingEvaluations).mockImplementation(() => new Promise(() => {}));

        render(<Evaluations />);

        // The page heading is rendered, but no evaluation content yet (loading state)
        expect(screen.getByRole('heading', { name: /avaliar seminarios/i })).toBeInTheDocument();
        // Should not show empty state or evaluation items
        expect(screen.queryByText(/nenhuma avaliacao pendente/i)).not.toBeInTheDocument();
    });

    it('shows error when submitting with score 0 via form submit', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        // The submit button is disabled with score 0, so we simulate form submit directly
        const form = screen.getByRole('button', { name: /enviar avaliacao/i }).closest('form')!;
        fireEvent.submit(form);

        await waitFor(() => {
            expect(screen.getByText(/selecione uma nota de 1 a 5 estrelas/i)).toBeInTheDocument();
        });
    });

    it('updates comment text when user types', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const textarea = screen.getByRole('textbox');
        await user.type(textarea, 'Great seminar!');

        expect(textarea).toHaveValue('Great seminar!');
        expect(screen.getByText('14/1000')).toBeInTheDocument();
    });

    it('submits with comment when score <= 3 and comment is provided', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', rating: { id: 1, score: 2, comment: 'Needs improvement' } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[1]); // score = 2

        const textarea = screen.getByRole('textbox');
        await user.type(textarea, 'Needs improvement');

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(profileApi.submitRating).toHaveBeenCalledWith(10, {
                score: 2,
                comment: 'Needs improvement',
            });
        });

        await waitFor(() => {
            expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        });
    });

    it('calls onRated (refetch) after successful submission via setTimeout', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        });

        // Record call count before the timer
        const callsBefore = vi.mocked(profileApi.pendingEvaluations).mock.calls.length;

        // Advance timer to trigger the onRated callback (setTimeout 1500ms)
        await vi.advanceTimersByTimeAsync(1600);

        // The refetch would be called via onRated -> refetchEvaluations
        await waitFor(() => {
            expect(vi.mocked(profileApi.pendingEvaluations).mock.calls.length).toBeGreaterThan(callsBefore);
        });

        vi.useRealTimers();
    });

    it('shows star score labels for all ratings', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como voce avalia este seminario/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');

        await user.click(starButtons[0]); // score = 1
        expect(screen.getByText('Muito ruim')).toBeInTheDocument();

        await user.click(starButtons[2]); // score = 3
        expect(screen.getByText('Regular')).toBeInTheDocument();

        await user.click(starButtons[3]); // score = 4
        expect(screen.getByText('Bom')).toBeInTheDocument();
    });
});
