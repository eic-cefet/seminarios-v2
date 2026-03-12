import { fireEvent, render, screen, waitFor, within, userEvent } from '@/test/test-utils';

if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

vi.mock('../../components/ui/select', async () => {
    const React = await vi.importActual<typeof import('react')>('react');

    const SelectContext = React.createContext<{
        onValueChange?: (value: string) => void;
        value?: string;
    }>({});

    function MockSelect({ children, value, onValueChange }: any) {
        return React.createElement(
            SelectContext.Provider,
            { value: { onValueChange, value } },
            React.createElement('div', null, children),
        );
    }

    function MockSelectTrigger({ children, id }: any) {
        return React.createElement('div', { role: 'combobox', id }, children);
    }

    function MockSelectValue({ placeholder }: any) {
        return React.createElement('span', null, placeholder);
    }

    function MockSelectItem({ children, value }: any) {
        return React.createElement('option', { value }, children);
    }

    function MockSelectContent({ children }: any) {
        const ctx = React.useContext(SelectContext);
        const options: any[] = [];

        React.Children.forEach(children, (child: any) => {
            if (child?.type === MockSelectItem) {
                options.push(child);
            }
        });

        return React.createElement(
            'select',
            {
                'data-testid': 'mock-native-select',
                value: ctx.value ?? 'all',
                onChange: (event: any) => ctx.onValueChange?.(event.target.value),
            },
            options,
        );
    }

    return {
        Select: MockSelect,
        SelectTrigger: MockSelectTrigger,
        SelectValue: MockSelectValue,
        SelectContent: MockSelectContent,
        SelectItem: MockSelectItem,
    };
});

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
        expect(screen.getAllByText('Positivo').length).toBeGreaterThan(0);
    });

    it('renders negative, mixed, and unlabeled sentiment badges with row fallbacks', async () => {
        vi.mocked(aiApi.ratingSentiments).mockResolvedValue({
            data: [
                {
                    id: 1,
                    score: 2,
                    comment: 'Poderia melhorar',
                    sentiment: 'Sentimento negativo.',
                    sentiment_label: 'negative',
                    sentiment_analyzed_at: '2026-03-10T14:00:00Z',
                    user: { id: 1, name: 'Ana' },
                    seminar: { id: 1, name: 'Seminário A', slug: 'a' },
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
                {
                    id: 2,
                    score: 3,
                    comment: 'Teve pontos bons e ruins',
                    sentiment: 'Sentimento misto.',
                    sentiment_label: 'mixed',
                    sentiment_analyzed_at: '2026-03-10T14:00:00Z',
                    user: { id: 2, name: 'Bruno' },
                    seminar: { id: 2, name: 'Seminário B', slug: 'b' },
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
                {
                    id: 3,
                    score: 4,
                    comment: null,
                    sentiment: null,
                    sentiment_label: null,
                    sentiment_analyzed_at: null,
                    user: undefined,
                    seminar: undefined,
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
            ],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 3,
                from: 1,
                to: 3,
            },
            summary: {
                total_ratings: 3,
                average_score: 3,
                low_score_count: 1,
            },
        });

        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(screen.getByText('Seminário A')).toBeInTheDocument();
        });

        const table = screen.getByRole('table');

        expect(within(table).getByText('Negativo')).toBeInTheDocument();
        expect(within(table).getByText('Misto')).toBeInTheDocument();
        expect(within(table).getByText('Sem rótulo')).toBeInTheDocument();
        expect(within(table).getByText('Seminário B')).toBeInTheDocument();
        expect(within(table).getByText('Ana')).toBeInTheDocument();
        expect(within(table).getByText('Bruno')).toBeInTheDocument();
        expect(within(table).getAllByText('-').length).toBeGreaterThan(0);
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

    it('passes score and sentiment filters to the API and can reset them', async () => {
        vi.mocked(aiApi.ratingSentiments).mockResolvedValue({
            data: [
                {
                    id: 1,
                    score: 4,
                    comment: 'Bom evento',
                    sentiment: 'Sentimento positivo.',
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
                last_page: 3,
                per_page: 10,
                total: 21,
                from: 1,
                to: 10,
            },
            summary: {
                total_ratings: 21,
                average_score: 4.2,
                low_score_count: 3,
            },
        });

        render(<FeedbackInsights />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(
                screen.getByRole('button', { name: /limpar filtros/i }),
            ).toBeDisabled();
        });

        await waitFor(() => {
            expect(screen.getByText('Página 1 de 3')).toBeInTheDocument();
        });

        await user.click(
            screen.getByRole('button', { name: /próxima página/i }),
        );

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenLastCalledWith({
                page: 2,
                per_page: 10,
                score: undefined,
                search: undefined,
                sentiment_label: undefined,
            });
        });

        const selects = screen.getAllByTestId('mock-native-select');

        fireEvent.change(selects[0], { target: { value: '5' } });

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenLastCalledWith({
                page: 1,
                per_page: 10,
                score: 5,
                search: undefined,
                sentiment_label: undefined,
            });
        });

        fireEvent.change(selects[1], { target: { value: 'mixed' } });

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenLastCalledWith({
                page: 1,
                per_page: 10,
                score: 5,
                search: undefined,
                sentiment_label: 'mixed',
            });
        });

        const resetButton = screen.getByRole('button', {
            name: /limpar filtros/i,
        });
        expect(resetButton).toBeEnabled();

        await user.click(resetButton);

        await waitFor(() => {
            expect(aiApi.ratingSentiments).toHaveBeenLastCalledWith({
                page: 1,
                per_page: 10,
                score: undefined,
                search: undefined,
                sentiment_label: undefined,
            });
        });

        expect(resetButton).toBeDisabled();
        expect(
            screen.getByPlaceholderText('Seminário, avaliador, comentário...'),
        ).toHaveValue('');
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

    it('falls back to the local page state when pagination meta is missing', async () => {
        vi.mocked(aiApi.ratingSentiments).mockResolvedValue({
            data: [
                {
                    id: 1,
                    score: 4,
                    comment: 'Bom evento',
                    sentiment: 'Sentimento positivo.',
                    sentiment_label: 'positive',
                    sentiment_analyzed_at: '2026-03-10T14:00:00Z',
                    user: { id: 1, name: 'Maria' },
                    seminar: { id: 1, name: 'Seminário de IA', slug: 'ia' },
                    created_at: '2026-03-10T14:00:00Z',
                    updated_at: '2026-03-10T14:00:00Z',
                },
            ],
            meta: undefined,
            summary: {
                total_ratings: 1,
                average_score: 4,
                low_score_count: 0,
            },
        } as any);

        render(<FeedbackInsights />);

        await waitFor(() => {
            expect(screen.getByText('Seminário de IA')).toBeInTheDocument();
        });

        expect(
            screen.queryByRole('navigation', { name: 'Paginação' }),
        ).not.toBeInTheDocument();
    });
});
