import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createUser } from '@/test/factories';
import AlertPreferences from './AlertPreferences';

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
    alertPreferencesApi: {
        get: vi.fn(),
        update: vi.fn(),
    },
    seminarTypesApi: {
        list: vi.fn(),
    },
    subjectsApi: {
        list: vi.fn(),
    },
    ApiRequestError: class extends Error {},
}));

import { useAuth } from '@shared/contexts/AuthContext';
import { alertPreferencesApi, seminarTypesApi, subjectsApi, type AlertPreference } from '@shared/api/client';

const prefs = (overrides: Partial<AlertPreference> = {}): AlertPreference => ({
    newSeminarAlert: false,
    seminarTypeIds: [],
    subjectIds: [],
    seminarReminder7d: true,
    seminarReminder24h: true,
    evaluationPrompt: true,
    announcements: true,
    certificateReady: true,
    seminarRescheduled: true,
    ...overrides,
});

const authedUser = () => ({
    user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
    login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
});

describe('AlertPreferences', () => {
    beforeEach(() => {
        vi.mocked(alertPreferencesApi.get).mockResolvedValue(prefs());
        vi.mocked(alertPreferencesApi.update).mockImplementation(async (payload) => prefs({
            newSeminarAlert: payload.new_seminar_alert,
            seminarTypeIds: payload.seminar_type_ids,
            subjectIds: payload.subject_ids,
            seminarReminder7d: payload.seminar_reminder_7d,
            seminarReminder24h: payload.seminar_reminder_24h,
            evaluationPrompt: payload.evaluation_prompt,
            announcements: payload.announcements,
            certificateReady: payload.certificate_ready,
            seminarRescheduled: payload.seminar_rescheduled,
        }));
        vi.mocked(seminarTypesApi.list).mockResolvedValue({
            data: [{ id: 1, name: 'Palestra' }, { id: 2, name: 'Workshop' }],
        });
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [{ id: 10, name: 'IA', slug: 'ia' }, { id: 20, name: 'Segurança', slug: 'seguranca' }],
        });
    });

    it('renders the page heading when authenticated', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());

        render(<AlertPreferences />);

        expect(await screen.findByRole('heading', { name: /alertas de novos seminários/i })).toBeInTheDocument();
    });

    it('loads the current preference and shows opt-in unchecked by default', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());

        render(<AlertPreferences />);

        const checkbox = await screen.findByLabelText(/quero receber alertas por e-mail/i);
        expect(checkbox).not.toBeChecked();
    });

    it('submits opt-in with selected filters', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(await screen.findByText('Palestra'));
        await user.click(await screen.findByText('IA'));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        await waitFor(() => {
            expect(alertPreferencesApi.update).toHaveBeenCalledWith(expect.objectContaining({
                new_seminar_alert: true,
                seminar_type_ids: [1],
                subject_ids: [10],
            }));
        });
    });

    it('allows opt-in with no filters (ALLOW ALL)', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        await waitFor(() => {
            expect(alertPreferencesApi.update).toHaveBeenCalledWith(expect.objectContaining({
                new_seminar_alert: true,
                seminar_type_ids: [],
                subject_ids: [],
            }));
        });
    });

    it('shows success message after saving and clears it after timeout', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        expect(await screen.findByText(/preferências salvas com sucesso/i)).toBeInTheDocument();

        vi.advanceTimersByTime(3500);

        await waitFor(() => {
            expect(screen.queryByText(/preferências salvas com sucesso/i)).not.toBeInTheDocument();
        });

        vi.useRealTimers();
    });

    it('hydrates form from existing preferences', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        vi.mocked(alertPreferencesApi.get).mockResolvedValue(prefs({
            newSeminarAlert: true,
            seminarTypeIds: [2],
            subjectIds: [20],
        }));

        render(<AlertPreferences />);

        await waitFor(() => {
            expect(screen.getByLabelText(/quero receber alertas por e-mail/i)).toBeChecked();
        });
    });

    it('toggles a filter off when already selected', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        vi.mocked(alertPreferencesApi.get).mockResolvedValue(prefs({
            newSeminarAlert: true,
            seminarTypeIds: [1],
            subjectIds: [],
        }));
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await waitFor(() => {
            expect(screen.getByLabelText(/quero receber alertas por e-mail/i)).toBeChecked();
        });

        await user.click(await screen.findByText('Palestra'));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        await waitFor(() => {
            expect(alertPreferencesApi.update).toHaveBeenCalledWith(expect.objectContaining({
                new_seminar_alert: true,
                seminar_type_ids: [],
                subject_ids: [],
            }));
        });
    });

    it('shows error alert when save fails', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        vi.mocked(alertPreferencesApi.update).mockRejectedValue(new Error('boom'));
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        expect(await screen.findByText(/não foi possível salvar/i)).toBeInTheDocument();
    });

    it('shows saving state on submit button during pending mutation', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        let resolveUpdate: (v: AlertPreference) => void = () => {};
        vi.mocked(alertPreferencesApi.update).mockImplementation(
            () => new Promise((resolve) => { resolveUpdate = resolve; }),
        );
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        expect(await screen.findByRole('button', { name: /salvando/i })).toBeInTheDocument();

        resolveUpdate(prefs({ newSeminarAlert: true }));
    });

    it('opts out of a transactional reminder', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup();

        render(<AlertPreferences />);

        const toggle = await screen.findByLabelText(/lembrete 24h antes do seminário/i);
        expect(toggle).toBeChecked();
        await user.click(toggle);
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        await waitFor(() => {
            expect(alertPreferencesApi.update).toHaveBeenCalledWith(expect.objectContaining({
                seminar_reminder_24h: false,
                seminar_reminder_7d: true,
                evaluation_prompt: true,
                certificate_ready: true,
                seminar_rescheduled: true,
                announcements: true,
            }));
        });
    });

    it('redirects when unauthenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<AlertPreferences />);

        expect(screen.queryByRole('heading', { name: /alertas de novos seminários/i })).not.toBeInTheDocument();
    });
});
