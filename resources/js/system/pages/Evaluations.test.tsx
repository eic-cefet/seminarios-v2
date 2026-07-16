import { createTestQueryClient, render, screen, waitFor, userEvent, fireEvent } from '@/test/test-utils';
import { createUser, createPendingEvaluation } from '@/test/factories';
import type { GamificationSyncDelta } from '@shared/types';
import Evaluations from './Evaluations';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/api/client', () => ({
    profileApi: {
        pendingEvaluations: vi.fn().mockResolvedValue({ data: [] }),
        submitRating: vi.fn().mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 5, comment: null } }),
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

const gamificationDelta = (overrides: Partial<GamificationSyncDelta> = {}): GamificationSyncDelta => ({
    xp_earned: 45,
    total_xp: 45,
    level: {
        level: 1,
        rank: 'Calouro',
        current_level_xp: 45,
        next_level_xp: 100,
        progress_percent: 45,
    },
    new_badges: [{
        key: 'first_evaluation',
        name: 'Voz Ativa',
        description: 'Avalie uma apresentação.',
        category: 'feedback',
        tier: 'bronze',
        icon: 'MessageSquare',
        earned: true,
        earned_at: '2026-07-15T12:00:00-03:00',
    }],
    ...overrides,
});

describe('Evaluations', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
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
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
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
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
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

        expect(screen.getByText(/como você avalia/i)).toBeInTheDocument();
    });

    it('names the real presentation type in the rating legend and placeholder', async () => {
        const evaluations = [
            createPendingEvaluation({
                id: 1,
                seminar: {
                    id: 10, name: 'Test Seminar', slug: 'test-seminar',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: { id: 1, name: 'Seminário', gender: 'm', noun: 'seminário' },
                    location: { id: 1, name: 'Room 101' },
                },
            }),
        ];
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: evaluations });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        expect(screen.getByText('Como você avalia este seminário?')).toBeInTheDocument();
        expect(screen.getByPlaceholderText('Deixe um comentário sobre o seminário...')).toBeInTheDocument();
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
        expect(screen.getByText(/como você avalia/i)).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /cancelar/i }));
        expect(screen.queryByText(/como você avalia/i)).not.toBeInTheDocument();
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
        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        });
    });

    it('keeps the evaluation success feedback and celebrates the returned gamification delta', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({
            message: 'ok',
            rating: { id: 1, score: 5, comment: null },
            gamification: gamificationDelta(),
        });
        const user = userEvent.setup();

        render(<Evaluations />);
        await user.click(await screen.findByRole('button', { name: /^avaliar$/i }));
        const stars = screen.getByText(/como você avalia/i).nextElementSibling!.querySelectorAll('button');
        await user.click(stars[4]);
        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        expect(
            await screen.findByRole('heading', { name: 'Conquista desbloqueada!' }),
        ).toBeInTheDocument();
        expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        expect(screen.getByText('+45 XP')).toBeInTheDocument();
    });

    it('keeps the celebration open after the rated item is removed and returns focus to the page heading', async () => {
        vi.mocked(profileApi.pendingEvaluations)
            .mockResolvedValueOnce({ data: singleEvaluation() })
            .mockResolvedValue({ data: [] });
        vi.mocked(profileApi.submitRating).mockResolvedValue({
            message: 'ok',
            rating: { id: 1, score: 5, comment: null },
            gamification: gamificationDelta(),
        });
        const user = userEvent.setup();

        render(<Evaluations />);
        const pageHeading = screen.getByRole('heading', {
            name: /avaliar seminarios/i,
            level: 1,
        });
        await user.click(await screen.findByRole('button', { name: /^avaliar$/i }));
        const stars = screen.getByText(/como você avalia/i).nextElementSibling!.querySelectorAll('button');
        await user.click(stars[4]);
        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        expect(
            await screen.findByRole('heading', { name: 'Conquista desbloqueada!' }),
        ).toBeInTheDocument();
        await waitFor(() => {
            expect(screen.getByText(/nenhuma avaliacao pendente/i)).toBeInTheDocument();
        });
        expect(screen.getByRole('dialog')).toHaveTextContent('Voz Ativa');

        await user.click(screen.getByRole('button', { name: 'Continuar' }));

        expect(pageHeading).toHaveFocus();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it.each([
        ['null', null],
        ['zero', gamificationDelta({ xp_earned: 0, new_badges: [] })],
    ])('suppresses a %s celebration but still invalidates gamification', async (_label, gamification) => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({
            message: 'ok',
            rating: { id: 1, score: 5, comment: null },
            gamification,
        });
        const queryClient = createTestQueryClient();
        const invalidateQueries = vi.spyOn(queryClient, 'invalidateQueries');
        const user = userEvent.setup();

        render(<Evaluations />, { queryClient });
        await user.click(await screen.findByRole('button', { name: /^avaliar$/i }));
        const stars = screen.getByText(/como você avalia/i).nextElementSibling!.querySelectorAll('button');
        await user.click(stars[4]);
        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await screen.findByText(/avaliacao enviada com sucesso/i);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(invalidateQueries).toHaveBeenCalledWith({
            queryKey: ['profile', 'gamification'],
        });
    });

    it('fires analytics event on successful submission', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 2, comment: 'Needs improvement' } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[1]); // score = 2

        const textarea = screen.getByRole('textbox');
        await user.type(textarea, 'Needs improvement');

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(profileApi.submitRating).toHaveBeenCalledWith(10, {
                score: 2,
                comment: 'Needs improvement',
                ai_analysis_consent: false,
            });
        });

        await waitFor(() => {
            expect(screen.getByText(/avaliacao enviada com sucesso/i)).toBeInTheDocument();
        });
    });

    it('calls onRated (refetch) after successful submission via setTimeout', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
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

    it('shows "Enviando..." button text while mutation is pending', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockImplementation(() => new Promise(() => {})); // never resolves
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /enviando/i })).toBeInTheDocument();
        });
    });

    it('shows star score labels for all ratings', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');

        await user.click(starButtons[0]); // score = 1
        expect(screen.getByText('Muito ruim')).toBeInTheDocument();

        await user.click(starButtons[2]); // score = 3
        expect(screen.getByText('Regular')).toBeInTheDocument();

        await user.click(starButtons[3]); // score = 4
        expect(screen.getByText('Bom')).toBeInTheDocument();
    });

    it('submits with ai_analysis_consent=true when the checkbox is checked', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        vi.mocked(profileApi.submitRating).mockResolvedValue({ message: 'ok', gamification: null, rating: { id: 1, score: 5, comment: null } });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const starsContainer = screen.getByText(/como você avalia/i).nextElementSibling!;
        const starButtons = starsContainer.querySelectorAll('button');
        await user.click(starButtons[4]); // score = 5

        const consentCheckbox = screen.getByRole('checkbox');
        await user.click(consentCheckbox);
        expect(consentCheckbox).toBeChecked();

        await user.click(screen.getByRole('button', { name: /enviar avaliacao/i }));

        await waitFor(() => {
            expect(profileApi.submitRating).toHaveBeenCalledWith(10, {
                score: 5,
                comment: undefined,
                ai_analysis_consent: true,
            });
        });
    });

    it('renders AI consent checkbox with link to cookie preferences', async () => {
        vi.mocked(profileApi.pendingEvaluations).mockResolvedValue({ data: singleEvaluation() });
        const user = userEvent.setup();

        render(<Evaluations />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^avaliar$/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /^avaliar$/i }));

        const checkbox = screen.getByRole('checkbox');
        expect(checkbox).toBeInTheDocument();
        expect(checkbox).not.toBeChecked();

        // Find the cookie-preferences link specifically within the consent label
        const consentLabel = checkbox.closest('label')!;
        const preferencesLink = consentLabel.querySelector('a');
        expect(preferencesLink).toHaveAttribute('href', '/preferencias-de-cookies');
    });
});
