import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createUser } from '@/test/factories';
import AlertPreferences from './AlertPreferences';

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
import { alertPreferencesApi, seminarTypesApi, subjectsApi } from '@shared/api/client';

const authedUser = () => ({
    user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
    login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
});

describe('AlertPreferences', () => {
    beforeEach(() => {
        vi.mocked(alertPreferencesApi.get).mockResolvedValue({
            optedIn: false,
            seminarTypeIds: [],
            subjectIds: [],
        });
        vi.mocked(alertPreferencesApi.update).mockImplementation(async (payload) => ({
            optedIn: payload.opted_in,
            seminarTypeIds: payload.seminar_type_ids,
            subjectIds: payload.subject_ids,
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
            expect(alertPreferencesApi.update).toHaveBeenCalledWith({
                opted_in: true,
                seminar_type_ids: [1],
                subject_ids: [10],
            });
        });
    });

    it('allows opt-in with no filters (ALLOW ALL)', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        await waitFor(() => {
            expect(alertPreferencesApi.update).toHaveBeenCalledWith({
                opted_in: true,
                seminar_type_ids: [],
                subject_ids: [],
            });
        });
    });

    it('shows success message after saving', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        const user = userEvent.setup();

        render(<AlertPreferences />);

        await user.click(await screen.findByLabelText(/quero receber alertas por e-mail/i));
        await user.click(screen.getByRole('button', { name: /salvar preferências/i }));

        expect(await screen.findByText(/preferências salvas com sucesso/i)).toBeInTheDocument();
    });

    it('hydrates form from existing preferences', async () => {
        vi.mocked(useAuth).mockReturnValue(authedUser());
        vi.mocked(alertPreferencesApi.get).mockResolvedValue({
            optedIn: true,
            seminarTypeIds: [2],
            subjectIds: [20],
        });

        render(<AlertPreferences />);

        await waitFor(() => {
            expect(screen.getByLabelText(/quero receber alertas por e-mail/i)).toBeChecked();
        });
    });

    it('redirects when unauthenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<AlertPreferences />);

        expect(screen.queryByRole('heading', { name: /alertas de novos seminários/i })).not.toBeInTheDocument();
    });
});
