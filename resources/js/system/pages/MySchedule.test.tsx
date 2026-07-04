import { render, screen, waitFor } from '@/test/test-utils';
import { createUser, createUserRegistration } from '@/test/factories';
import MySchedule from './MySchedule';

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
        schedule: vi.fn().mockResolvedValue({ data: [] }),
        calendarFeed: vi.fn().mockResolvedValue({
            data: {
                personal_url: 'https://app.test/calendar/personal/tok.ics',
                public_url: 'https://app.test/calendar/seminars.ics',
            },
        }),
        rotateCalendarFeed: vi.fn(),
    },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { useAuth } from '@shared/contexts/AuthContext';
import { profileApi } from '@shared/api/client';

const authenticated = () => {
    vi.mocked(useAuth).mockReturnValue({
        user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
    });
};

describe('MySchedule', () => {
    it('renders "Minha Agenda" heading when authenticated', async () => {
        authenticated();
        vi.mocked(profileApi.schedule).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 } });

        render(<MySchedule />);

        expect(screen.getByRole('heading', { name: /minha agenda/i })).toBeInTheDocument();
    });

    it('renders upcoming registrations with date, location, and calendar menu', async () => {
        authenticated();
        vi.mocked(profileApi.schedule).mockResolvedValue({
            data: [
                createUserRegistration({ seminar: { id: 1, name: 'Intro to AI', slug: 'intro-ai', scheduled_at: '2026-08-15T14:00:00Z', ends_at: '2026-08-15T15:00:00Z', is_expired: false, seminar_type: { id: 1, name: 'Palestra' }, location: { id: 1, name: 'Sala 101' } } }),
            ],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<MySchedule />);

        await waitFor(() => {
            expect(screen.getByText('Intro to AI')).toBeInTheDocument();
            expect(screen.getByText('Sala 101')).toBeInTheDocument();
            expect(screen.getByText(/adicionar ao calendário/i)).toBeInTheDocument();
        });
        expect(screen.getByText('1 inscrição')).toBeInTheDocument();
    });

    it('does not render a calendar menu for a registration without scheduled_at', async () => {
        authenticated();
        vi.mocked(profileApi.schedule).mockResolvedValue({
            data: [
                createUserRegistration({ seminar: { id: 2, name: 'Sem Data', slug: 'sem-data', scheduled_at: null, is_expired: false, seminar_type: null, location: null } }),
            ],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<MySchedule />);

        await waitFor(() => {
            expect(screen.getByText('Sem Data')).toBeInTheDocument();
        });
        expect(screen.queryByText(/adicionar ao calendário/i)).not.toBeInTheDocument();
    });

    it('shows empty state when there are no upcoming registrations', async () => {
        authenticated();
        vi.mocked(profileApi.schedule).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 } });

        render(<MySchedule />);

        await waitFor(() => {
            expect(screen.getByText(/não está inscrito em nenhum seminário futuro/i)).toBeInTheDocument();
        });
        expect(screen.getByRole('link', { name: /ver apresentações/i })).toBeInTheDocument();
    });

    it('redirects to /login when not authenticated and not loading', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<MySchedule />);

        expect(screen.queryByRole('heading', { name: /minha agenda/i })).not.toBeInTheDocument();
    });

    it('renders pagination fallbacks when meta is undefined', async () => {
        authenticated();
        vi.mocked(profileApi.schedule).mockResolvedValue({
            data: [createUserRegistration({ seminar: { id: 3, name: 'Solo Event', slug: 'solo', scheduled_at: '2026-08-15T14:00:00Z', is_expired: false, seminar_type: null, location: null } })],
            meta: undefined as any,
        });

        render(<MySchedule />);

        await waitFor(() => {
            expect(screen.getByText('Solo Event')).toBeInTheDocument();
        });
    });
});
