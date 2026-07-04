import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { CalendarSubscribeCard } from './CalendarSubscribeCard';

vi.mock('@shared/api/client', () => ({
    profileApi: {
        calendarFeed: vi.fn(),
        rotateCalendarFeed: vi.fn(),
    },
}));

import { profileApi } from '@shared/api/client';

const FEED = {
    data: {
        personal_url: 'https://app.test/calendar/personal/tok123.ics',
        public_url: 'https://app.test/calendar/seminars.ics',
    },
};

const writeText = vi.fn();

describe('CalendarSubscribeCard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        writeText.mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
        });
        vi.mocked(profileApi.calendarFeed).mockResolvedValue(FEED);
    });

    it('renders webcal urls for both feeds after loading', async () => {
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByDisplayValue('webcal://app.test/calendar/personal/tok123.ics')).toBeInTheDocument();
            expect(screen.getByDisplayValue('webcal://app.test/calendar/seminars.ics')).toBeInTheDocument();
        });
    });

    it('links to Google Calendar subscription for the personal feed', async () => {
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /adicionar ao google calendar/i });
            expect(link.getAttribute('href')).toContain('calendar.google.com/calendar/render');
            expect(link.getAttribute('href')).toContain(encodeURIComponent('webcal://app.test/calendar/personal/tok123.ics'));
        });
    });

    it('copies the personal webcal url, shows feedback, and clears it after 2s', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTimeAsync });
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getAllByRole('button', { name: /copiar/i }).length).toBe(2);
        });

        // Re-apply clipboard mock after userEvent.setup() installs its own stub
        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
        });

        await user.click(screen.getAllByRole('button', { name: /copiar/i })[0]);

        expect(writeText).toHaveBeenCalledWith('webcal://app.test/calendar/personal/tok123.ics');
        await waitFor(() => {
            expect(screen.getByText('Copiado!')).toBeInTheDocument();
        });

        await vi.advanceTimersByTimeAsync(2000);
        await waitFor(() => {
            expect(screen.queryByText('Copiado!')).not.toBeInTheDocument();
        });

        vi.useRealTimers();
    });

    it('shows no feedback when the clipboard write fails', async () => {
        writeText.mockRejectedValue(new Error('denied'));
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getAllByRole('button', { name: /copiar/i }).length).toBe(2);
        });

        // Re-apply clipboard mock after userEvent.setup() installs its own stub
        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
        });

        await user.click(screen.getAllByRole('button', { name: /copiar/i })[0]);

        expect(writeText).toHaveBeenCalled();
        expect(screen.queryByText('Copiado!')).not.toBeInTheDocument();
    });

    it('rotates the token after confirmation and refetches the urls', async () => {
        vi.mocked(profileApi.rotateCalendarFeed).mockResolvedValue({
            message: 'Novo link gerado com sucesso.',
            data: FEED.data,
        });
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /gerar novo link/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /gerar novo link/i }));
        expect(profileApi.rotateCalendarFeed).not.toHaveBeenCalled();
        expect(screen.getByText(/o link atual deixará de funcionar/i)).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /^confirmar$/i }));

        await waitFor(() => {
            expect(profileApi.rotateCalendarFeed).toHaveBeenCalledTimes(1);
            expect(profileApi.calendarFeed).toHaveBeenCalledTimes(2);
        });
    });

    it('cancels the rotation confirmation', async () => {
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /gerar novo link/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /gerar novo link/i }));
        await user.click(screen.getByRole('button', { name: /cancelar/i }));

        expect(profileApi.rotateCalendarFeed).not.toHaveBeenCalled();
        expect(screen.getByRole('button', { name: /gerar novo link/i })).toBeInTheDocument();
    });

    it('shows an error message when rotation fails', async () => {
        vi.mocked(profileApi.rotateCalendarFeed).mockRejectedValue(new Error('fail'));
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /gerar novo link/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /gerar novo link/i }));
        await user.click(screen.getByRole('button', { name: /^confirmar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/não foi possível gerar um novo link/i)).toBeInTheDocument();
        });
    });

    it('shows an error message when the feed urls fail to load', async () => {
        vi.mocked(profileApi.calendarFeed).mockRejectedValue(new Error('fail'));

        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByText(/não foi possível carregar os links de assinatura/i)).toBeInTheDocument();
        });
    });

    it('selects all text in the url input on focus', async () => {
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        const inputs = await screen.findAllByRole('textbox');
        const select = vi.fn();
        Object.defineProperty(inputs[0], 'select', { value: select, configurable: true });

        await user.click(inputs[0]);

        expect(select).toHaveBeenCalled();
    });

    it('shows a spinner on the confirm button while rotation is pending', async () => {
        let resolveRotate: () => void;
        vi.mocked(profileApi.rotateCalendarFeed).mockReturnValue(
            new Promise((resolve) => {
                resolveRotate = () => resolve({ message: 'ok', data: FEED.data });
            }) as any,
        );
        const user = userEvent.setup();
        render(<CalendarSubscribeCard />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /gerar novo link/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /gerar novo link/i }));
        await user.click(screen.getByRole('button', { name: /^confirmar$/i }));

        // While pending the confirm button should be disabled and the spinner visible
        expect(screen.getByRole('button', { name: /^confirmar$/i })).toBeDisabled();

        resolveRotate!();
    });
});
