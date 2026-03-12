import { render, screen, waitFor, userEvent } from '@/test/test-utils';

if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

vi.mock('../../api/adminClient', () => ({
    aiApi: {
        ratingSentiments: vi.fn().mockResolvedValue({
            data: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
                from: null,
                to: null,
            },
            summary: {
                total_ratings: 0,
                average_score: null,
                low_score_count: 0,
            },
        }),
    },
}));

import FeedbackInsights from './FeedbackInsights';
import { aiApi } from '../../api/adminClient';

describe('FeedbackInsights', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the page heading and summary cards', async () => {
        render(<FeedbackInsights />);

        expect(screen.getByRole('heading', { level: 1, name: 'Feedback IA' })).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getAllByText('Avaliações analisadas').length).toBeGreaterThan(0);
        });
        expect(screen.getByText('Média das notas')).toBeInTheDocument();
        expect(screen.getByText('Notas até 3')).toBeInTheDocument();
    });

    it('renders empty state when there are no analyzed ratings', async () => {
        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(
                screen.getByText(/nenhuma avaliação analisada encontrada/i),
            ).toBeInTheDocument();
        });
    });

    it('renders analyzed ratings with sentiment badges', async () => {
        vi.mocked(aiApi.ratingSentiments).mockResolvedValue({
            data: [
                {
                    id: 1,
                    score: 5,
                    comment: 'Ótimo evento',
                    sentiment: 'Sentimento positivo. Aluno gostou da clareza.',
                    sentiment_label: 'positive',
                    sentiment_analyzed_at: '2026-03-10T14:00:00Z',
                    user: { id: 1, name: 'Maria' },
                    seminar: { id: 1, name: 'Seminário de IA', slug: 'ia' },
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
            ],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 1,
                from: 1,
                to: 1,
            },
            summary: {
                total_ratings: 1,
                average_score: 5,
                low_score_count: 0,
            },
        });

        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(screen.getByText('Seminário de IA')).toBeInTheDocument();
        });
        expect(screen.getByText('Maria')).toBeInTheDocument();
        expect(screen.getByText('Ótimo evento')).toBeInTheDocument();
        expect(screen.getByText('Positivo')).toBeInTheDocument();
    });

    it('shows error state when the request fails', async () => {
        vi.mocked(aiApi.ratingSentiments).mockRejectedValue(new Error('boom'));

        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(
                screen.getByText(/erro ao carregar feedback analisado/i),
            ).toBeInTheDocument();
        });
    });

    it('passes filters to the API', async () => {
        render(<FeedbackInsights />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenCalledWith({
                page: 1,
                per_page: 10,
                score: undefined,
                search: undefined,
                sentiment_label: undefined,
            });
        });

        await user.type(
            screen.getByPlaceholderText(
                'Seminário, avaliador, comentário...',
            ),
            'maria',
        );

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenLastCalledWith({
                page: 1,
                per_page: 10,
                score: undefined,
                search: 'maria',
                sentiment_label: undefined,
            });
        });
    });

    it('renders pagination when more than one page exists', async () => {
        vi.mocked(aiApi.ratingSentiments).mockResolvedValue({
            data: [
                {
                    id: 1,
                    score: 3,
                    comment: 'Regular',
                    sentiment: 'Sentimento neutro.',
                    sentiment_label: 'neutral',
                    sentiment_analyzed_at: '2026-03-10T14:00:00Z',
                    user: { id: 1, name: 'Maria' },
                    seminar: { id: 1, name: 'Seminário de IA', slug: 'ia' },
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
            ],
            meta: {
                current_page: 1,
                last_page: 3,
                per_page: 10,
                total: 21,
                from: 1,
                to: 10,
            },
            summary: {
                total_ratings: 21,
                average_score: 3,
                low_score_count: 7,
            },
        });

        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(screen.getByText('Página 1 de 3')).toBeInTheDocument();
        });
    });
});
