import { fireEvent, render, screen, within } from '@/test/test-utils';
import { createSeminar } from '@/test/factories';
import { PresentationsCalendar } from './PresentationsCalendar';

describe('PresentationsCalendar', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-20T12:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('sorts seminars within the same day and shows the filtered total label', () => {
        render(
            <PresentationsCalendar
                seminars={[
                    createSeminar({
                        id: 0,
                        name: 'Expired Talk',
                        slug: 'expired-talk',
                        scheduled_at: '2026-06-15T13:00:00Z',
                        is_expired: true,
                    }),
                    createSeminar({
                        id: 1,
                        name: 'Late Talk',
                        slug: 'late-talk',
                        scheduled_at: '2026-06-15T17:00:00Z',
                    }),
                    createSeminar({
                        id: 2,
                        name: 'Early Talk',
                        slug: 'early-talk',
                        scheduled_at: '2026-06-15T14:00:00Z',
                    }),
                    createSeminar({
                        id: 3,
                        name: 'Extra Talk 1',
                        slug: 'extra-talk-1',
                        scheduled_at: '2026-06-15T18:00:00Z',
                    }),
                    createSeminar({
                        id: 4,
                        name: 'Extra Talk 2',
                        slug: 'extra-talk-2',
                        scheduled_at: '2026-06-15T19:00:00Z',
                    }),
                ]}
                month={new Date('2026-06-01T12:00:00Z')}
                total={8}
                onMonthChange={vi.fn()}
            />,
        );

        expect(
            screen.getByText('Exibindo 5 de 8 apresentações neste filtro.'),
        ).toBeInTheDocument();

        const seminarLinks = screen.getAllByRole('link');
        expect(seminarLinks[0]).toHaveTextContent('Expired Talk');
        expect(seminarLinks[0]).toHaveClass('border-gray-200');
        expect(seminarLinks[1]).toHaveTextContent('Early Talk');
        expect(seminarLinks[2]).toHaveTextContent('Late Talk');
        expect(screen.getByText('+2 outras apresentações')).toBeInTheDocument();
    });

    it('opens a modal with the full day schedule when clicking the overflow button', () => {
        render(
            <PresentationsCalendar
                seminars={[
                    createSeminar({
                        id: 1,
                        name: 'Talk One',
                        slug: 'talk-one',
                        scheduled_at: '2026-06-15T13:00:00Z',
                    }),
                    createSeminar({
                        id: 2,
                        name: 'Talk Two',
                        slug: 'talk-two',
                        scheduled_at: '2026-06-15T14:00:00Z',
                    }),
                    createSeminar({
                        id: 3,
                        name: 'Talk Three',
                        slug: 'talk-three',
                        scheduled_at: '2026-06-15T15:00:00Z',
                    }),
                    createSeminar({
                        id: 4,
                        name: 'Talk Four',
                        slug: 'talk-four',
                        scheduled_at: '2026-06-15T16:00:00Z',
                    }),
                    createSeminar({
                        id: 5,
                        name: 'Talk Five',
                        slug: 'talk-five',
                        scheduled_at: '2026-06-15T17:00:00Z',
                    }),
                ]}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={vi.fn()}
            />,
        );

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: '+2 outras apresentações' }),
        );

        const dialog = screen.getByRole('dialog');
        expect(dialog).toHaveTextContent('Segunda-feira, 15 de junho de 2026');
        expect(dialog).toHaveTextContent('5 apresentações neste dia.');
        expect(dialog).toHaveTextContent('Talk Four');
        expect(dialog).toHaveTextContent('Talk Five');

        fireEvent.click(screen.getByRole('button', { name: 'Fechar' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('keeps the day schedule modal stable when the seminars change while it is open', () => {
        const seminars = Array.from({ length: 4 }, (_, index) =>
            createSeminar({
                id: index + 1,
                name: `Talk ${index + 1}`,
                slug: `talk-${index + 1}`,
                scheduled_at: `2026-06-15T1${index + 3}:00:00Z`,
            }),
        );

        const { rerender } = render(
            <PresentationsCalendar
                seminars={seminars}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={vi.fn()}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: '+1 outras apresentações' }),
        );
        expect(screen.getByRole('dialog')).toHaveTextContent(
            '4 apresentações neste dia.',
        );

        rerender(
            <PresentationsCalendar
                seminars={[]}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={vi.fn()}
            />,
        );

        expect(screen.getByRole('dialog')).toHaveTextContent(
            '0 apresentações neste dia.',
        );
    });

    it('closes the day schedule modal when a seminar link is clicked', () => {
        render(
            <PresentationsCalendar
                seminars={Array.from({ length: 4 }, (_, index) =>
                    createSeminar({
                        id: index + 1,
                        name: `Talk ${index + 1}`,
                        slug: `talk-${index + 1}`,
                        scheduled_at: `2026-06-15T1${index + 3}:00:00Z`,
                    }),
                )}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={vi.fn()}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: '+1 outras apresentações' }),
        );

        const dialog = screen.getByRole('dialog');
        expect(dialog).toHaveTextContent('4 apresentações neste dia.');

        fireEvent.click(within(dialog).getByRole('link', { name: /Talk 4/ }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('navigates between previous month, current month, and next month', async () => {
        const onMonthChange = vi.fn();

        render(
            <PresentationsCalendar
                seminars={[
                    createSeminar({
                        id: 1,
                        name: 'Single Talk',
                        slug: 'single-talk',
                        scheduled_at: '2026-06-15T14:00:00Z',
                    }),
                ]}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={onMonthChange}
            />,
        );

        expect(screen.getByText('1 apresentação neste mês.')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /mês anterior/i }));
        const previousMonth = onMonthChange.mock.calls[0][0] as Date;
        expect(previousMonth).toBeInstanceOf(Date);
        expect(previousMonth.getFullYear()).toBe(2026);
        expect(previousMonth.getMonth()).toBe(4);
        expect(previousMonth.getDate()).toBe(1);

        fireEvent.click(screen.getByRole('button', { name: /mês atual/i }));
        const currentMonth = onMonthChange.mock.calls[1][0] as Date;
        expect(currentMonth).toBeInstanceOf(Date);
        expect(currentMonth.getFullYear()).toBe(2026);
        expect(currentMonth.getMonth()).toBe(5);
        expect(currentMonth.getDate()).toBe(1);

        fireEvent.click(screen.getByRole('button', { name: /próximo mês/i }));
        const nextMonth = onMonthChange.mock.calls[2][0] as Date;
        expect(nextMonth).toBeInstanceOf(Date);
        expect(nextMonth.getFullYear()).toBe(2026);
        expect(nextMonth.getMonth()).toBe(6);
        expect(nextMonth.getDate()).toBe(1);
    });

    it('shows the empty state when there are no seminars', () => {
        render(
            <PresentationsCalendar
                seminars={[]}
                month={new Date('2026-06-01T12:00:00Z')}
                onMonthChange={vi.fn()}
            />,
        );

        expect(screen.getByText('Nenhuma apresentação encontrada')).toBeInTheDocument();
        expect(screen.getByText('Ajuste os filtros para preencher o calendário.')).toBeInTheDocument();
    });
});
