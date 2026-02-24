import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createSeminar, createSpeaker, createLocation, createUser, createWorkshop, createSubject, createSeminarType } from '@/test/factories';
import SeminarDetails from './SeminarDetails';

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
    seminarsApi: { get: vi.fn().mockResolvedValue({ data: null }) },
    registrationApi: {
        status: vi.fn().mockResolvedValue({ registered: false }),
        register: vi.fn().mockResolvedValue({ message: 'ok' }),
        unregister: vi.fn().mockResolvedValue({ message: 'ok' }),
    },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useParams: vi.fn(() => ({ slug: 'test-seminar' })), useNavigate: vi.fn(() => vi.fn()) };
});

vi.mock('../components/LoginModal', () => ({
    LoginModal: ({ open }: { open: boolean }) => open ? <div data-testid="login-modal">Login Modal</div> : null,
}));

import { seminarsApi, registrationApi } from '@shared/api/client';
import { useAuth } from '@shared/contexts/AuthContext';
import { analytics } from '@shared/lib/analytics';

describe('SeminarDetails', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders seminar name after loading', async () => {
        const seminar = createSeminar({ name: 'Intro to TypeScript' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: 'Intro to TypeScript' })).toBeInTheDocument();
        });
    });

    it('renders seminar details (date, location, speakers)', async () => {
        const speaker = createSpeaker({ name: 'Jane Doe' });
        const location = createLocation({ name: 'Room 202' });
        const seminar = createSeminar({
            name: 'Advanced React',
            speakers: [speaker],
            location,
            scheduledAt: '2026-06-15T14:00:00Z',
        });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Advanced React')).toBeInTheDocument();
        });
        expect(screen.getByText('Jane Doe')).toBeInTheDocument();
        // Location may appear more than once
        expect(screen.getAllByText('Room 202').length).toBeGreaterThan(0);
    });

    it('shows not found state when seminar does not exist', async () => {
        vi.mocked(seminarsApi.get).mockRejectedValue(new Error('Not found'));

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /seminário não encontrado/i })).toBeInTheDocument();
        });
    });

    it('shows "Voltar para apresentações" link in not found state', async () => {
        vi.mocked(seminarsApi.get).mockRejectedValue(new Error('Not found'));

        render(<SeminarDetails />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /voltar para apresentações/i });
            expect(link).toHaveAttribute('href', '/apresentacoes');
        });
    });

    it('shows loading skeleton initially', () => {
        vi.mocked(seminarsApi.get).mockImplementation(() => new Promise(() => {}));

        render(<SeminarDetails />);

        // The loading state shows pulse animations but no heading
        expect(screen.queryByRole('heading', { name: /seminário não encontrado/i })).not.toBeInTheDocument();
    });

    it('shows register button for unauthenticated user with non-expired seminar', async () => {
        const seminar = createSeminar({ name: 'Test Seminar', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /realizar inscrição/i })).toBeInTheDocument();
        });
    });

    it('opens login modal when unauthenticated user clicks register', async () => {
        const seminar = createSeminar({ name: 'Test Seminar', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /realizar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /realizar inscrição/i }));

        expect(screen.getByTestId('login-modal')).toBeInTheDocument();
    });

    it('fires analytics event when unauthenticated user tries to register', async () => {
        const seminar = createSeminar({ name: 'Test Seminar', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /realizar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /realizar inscrição/i }));

        expect(analytics.event).toHaveBeenCalledWith('seminar_register_attempt_unauthenticated', {
            seminar_slug: 'test-seminar',
        });
    });

    it('shows expired seminar state', async () => {
        const seminar = createSeminar({ name: 'Old Seminar', isExpired: true });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/este seminário já foi realizado/i)).toBeInTheDocument();
        });
    });

    it('shows "Encerrado" badge for expired seminar', async () => {
        const seminar = createSeminar({ name: 'Old Seminar', isExpired: true });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Encerrado')).toBeInTheDocument();
        });
    });

    it('shows registration count when available', async () => {
        const seminar = createSeminar({ name: 'Popular Seminar', registrationsCount: 42 });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('42 inscritos')).toBeInTheDocument();
        });
    });

    it('shows "Inscreva-se" heading for non-registered user', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/inscreva-se neste seminário/i)).toBeInTheDocument();
        });
    });

    it('registers successfully and shows registered state', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.register).mockResolvedValue({ message: 'ok', registration: { id: 1, seminar_id: 1, created_at: '2024-01-01' } });
        // After registration, the status query returns registered: true
        vi.mocked(registrationApi.status)
            .mockResolvedValueOnce({ registered: false })
            .mockResolvedValue({ registered: true });
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /realizar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /realizar inscrição/i }));

        await waitFor(() => {
            expect(registrationApi.register).toHaveBeenCalled();
        });
    });

    it('shows registered state with "Cancelar inscrição" button', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: true });
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/você está inscrito/i)).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /cancelar inscrição/i })).toBeInTheDocument();
        });
    });

    it('unregisters when "Cancelar inscrição" is clicked', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: true });
        vi.mocked(registrationApi.unregister).mockResolvedValue({ message: 'ok' });
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /cancelar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /cancelar inscrição/i }));

        await waitFor(() => {
            expect(registrationApi.unregister).toHaveBeenCalled();
        });
    });

    it('shows registration error when register fails', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: false });
        vi.mocked(registrationApi.register).mockRejectedValue(new Error('Already registered'));
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            const btn = screen.getByRole('button', { name: /realizar inscrição/i });
            expect(btn).toBeInTheDocument();
            expect(btn).not.toBeDisabled();
        });

        await user.click(screen.getByRole('button', { name: /realizar inscrição/i }));

        await waitFor(() => {
            expect(screen.getByText('Already registered')).toBeInTheDocument();
        });
    });

    it('shows seminar description', async () => {
        const seminar = createSeminar({ name: 'Test', description: 'This is a test description' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/sobre o seminário/i)).toBeInTheDocument();
        });
    });

    it('renders markdown description when not HTML', async () => {
        const seminar = createSeminar({ name: 'Test', description: 'Simple text description' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Simple text description')).toBeInTheDocument();
        });
    });

    it('renders HTML description when content contains HTML', async () => {
        const seminar = createSeminar({ name: 'Test', description: '<p>HTML content</p>' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('HTML content')).toBeInTheDocument();
        });
    });

    it('shows speaker details (position, company, bio)', async () => {
        const speaker = createSpeaker({
            name: 'Dr. Smith',
            speakerData: {
                position: 'Professor',
                company: 'MIT',
                bio: 'Expert in AI',
            },
        });
        const seminar = createSeminar({ speakers: [speaker] });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Dr. Smith')).toBeInTheDocument();
        });
        expect(screen.getByText(/professor/i)).toBeInTheDocument();
        expect(screen.getByText(/@ MIT/)).toBeInTheDocument();
        expect(screen.getByText('Expert in AI')).toBeInTheDocument();
    });

    it('shows "Palestrantes" section heading when speakers exist', async () => {
        const speaker = createSpeaker({ name: 'John Speaker' });
        const seminar = createSeminar({ speakers: [speaker] });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Palestrantes')).toBeInTheDocument();
        });
    });

    it('shows room link when available', async () => {
        const seminar = createSeminar({ roomLink: 'https://zoom.us/j/123' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /link do evento/i });
            expect(link).toHaveAttribute('href', 'https://zoom.us/j/123');
            expect(link).toHaveAttribute('target', '_blank');
        });
    });

    it('shows workshop info when available', async () => {
        const workshop = createWorkshop({ id: 5, name: 'Full Workshop' });
        const seminar = createSeminar({ workshop });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/parte do workshop/i)).toBeInTheDocument();
            const link = screen.getByRole('link', { name: 'Full Workshop' });
            expect(link).toHaveAttribute('href', '/workshop/5');
        });
    });

    it('shows location name when available', async () => {
        const location = createLocation({ name: 'Main Hall' });
        const seminar = createSeminar({ name: 'Test Seminar', location });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Test Seminar')).toBeInTheDocument();
        });
        expect(screen.getAllByText('Main Hall').length).toBeGreaterThan(0);
    });

    it('shows seminar type badge', async () => {
        const seminarType = createSeminarType({ name: 'Workshop' });
        const seminar = createSeminar({ seminarType });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Workshop')).toBeInTheDocument();
        });
    });

    it('shows subject badges', async () => {
        const subjects = [createSubject({ name: 'Machine Learning' }), createSubject({ name: 'Data Science' })];
        const seminar = createSeminar({ subjects });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText('Machine Learning')).toBeInTheDocument();
            expect(screen.getByText('Data Science')).toBeInTheDocument();
        });
    });

    it('shows "Todas as apresentações" back link', async () => {
        const seminar = createSeminar({ name: 'Test' });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /todas as apresentações/i });
            expect(link).toHaveAttribute('href', '/apresentacoes');
        });
    });

    it('shows speaker linkedin link when available', async () => {
        const speaker = createSpeaker({
            name: 'Social Speaker',
            speakerData: {
                linkedin: 'https://linkedin.com/in/speaker',
            },
        });
        const seminar = createSeminar({ speakers: [speaker] });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            const links = screen.getAllByRole('link');
            const linkedinLink = links.find((link) =>
                link.getAttribute('href') === 'https://linkedin.com/in/speaker'
            );
            expect(linkedinLink).toBeDefined();
        });
    });

    it('shows speaker github link when available', async () => {
        const speaker = createSpeaker({
            name: 'Dev Speaker',
            speakerData: {
                github: 'https://github.com/speaker',
            },
        });
        const seminar = createSeminar({ speakers: [speaker] });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            const links = screen.getAllByRole('link');
            const githubLink = links.find((link) =>
                link.getAttribute('href') === 'https://github.com/speaker'
            );
            expect(githubLink).toBeDefined();
        });
    });

    it('shows email reminder text for non-registered users', async () => {
        const seminar = createSeminar({ isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByText(/você receberá um lembrete por e-mail antes do evento/i)).toBeInTheDocument();
        });
    });

    it('shows "Processando..." on unregister button while mutation is pending', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: true });
        // Never-resolving promise keeps the mutation in pending state
        vi.mocked(registrationApi.unregister).mockImplementation(() => new Promise(() => {}));
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /cancelar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /cancelar inscrição/i }));

        await waitFor(() => {
            expect(screen.getByText('Processando...')).toBeInTheDocument();
        });
    });

    it('shows "Processando..." on register button while mutation is pending', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: false });
        // Never-resolving promise keeps the mutation in pending state
        vi.mocked(registrationApi.register).mockImplementation(() => new Promise(() => {}));
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /realizar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /realizar inscrição/i }));

        await waitFor(() => {
            expect(screen.getByText('Processando...')).toBeInTheDocument();
        });
    });

    it('calls unregisterMutation onError when unregister fails', async () => {
        const seminar = createSeminar({ name: 'Test', isExpired: false });
        vi.mocked(seminarsApi.get).mockResolvedValue({ data: seminar });
        vi.mocked(registrationApi.status).mockResolvedValue({ registered: true });
        vi.mocked(registrationApi.unregister).mockRejectedValue(new Error('Cannot unregister'));
        vi.mocked(useAuth).mockReturnValue({
            user: createUser(), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<SeminarDetails />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /cancelar inscrição/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /cancelar inscrição/i }));

        // The unregister call should have been made and failed, triggering the onError handler
        await waitFor(() => {
            expect(registrationApi.unregister).toHaveBeenCalled();
        });

        // The "Cancelar inscricao" button should still be visible (still registered)
        await waitFor(() => {
            expect(screen.getByRole('button', { name: /cancelar inscrição/i })).toBeInTheDocument();
        });
    });
});
