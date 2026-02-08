import { render, screen, userEvent, waitFor, fireEvent } from '@/test/test-utils';
import Register from './Register';

const mockRegister = vi.fn();
const mockNavigate = vi.fn();

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: mockRegister, logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/api/client', () => ({
    coursesApi: { list: vi.fn().mockResolvedValue({ data: [{ id: 1, name: 'Ciência da Computação' }, { id: 2, name: 'Engenharia' }] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

vi.mock('react-google-recaptcha', () => ({
    default: ({ onChange, onExpired }: any) => (
        <>
            <button onClick={() => onChange?.('token')}>ReCaptcha</button>
            <button onClick={() => onExpired?.()}>ExpireCaptcha</button>
        </>
    ),
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useNavigate: () => mockNavigate };
});

import { useAuth } from '@shared/contexts/AuthContext';
import { analytics } from '@shared/lib/analytics';

describe('Register', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRegister.mockResolvedValue(undefined);
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: mockRegister, logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
    });
    it('renders "Criar conta" heading', () => {
        render(<Register />);
        expect(screen.getByRole('heading', { name: /criar conta/i })).toBeInTheDocument();
    });

    it('renders form fields (name, email, password, confirm password)', () => {
        render(<Register />);
        expect(screen.getByLabelText(/nome completo/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^senha$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/confirmar senha/i)).toBeInTheDocument();
    });

    it('renders social login buttons', () => {
        render(<Register />);
        expect(screen.getByRole('button', { name: /google/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /github/i })).toBeInTheDocument();
    });

    it('renders link back to login', () => {
        render(<Register />);
        const link = screen.getByRole('link', { name: /entrar/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/login');
    });

    it('renders situation and role select fields', () => {
        render(<Register />);
        expect(screen.getByLabelText(/situação/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/vínculo/i)).toBeInTheDocument();
    });

    it('shows password mismatch error on submit', async () => {
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'differentpass');
        // Complete the captcha first
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(screen.getByText(/as senhas não coincidem/i)).toBeInTheDocument();
        });
    });

    it('shows short password error on submit', async () => {
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'short');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'short');
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(screen.getByText(/a senha deve ter pelo menos 8 caracteres/i)).toBeInTheDocument();
        });
    });

    it('has required attribute on situation and role selects', () => {
        render(<Register />);
        expect(screen.getByLabelText(/situação/i)).toBeRequired();
        expect(screen.getByLabelText(/vínculo/i)).toBeRequired();
    });

    it('renders submit button with correct text', () => {
        render(<Register />);
        expect(screen.getByRole('button', { name: /criar conta/i })).toBeInTheDocument();
    });

    it('renders course options from API', async () => {
        render(<Register />);

        await waitFor(() => {
            expect(screen.getByText('Ciência da Computação')).toBeInTheDocument();
            expect(screen.getByText('Engenharia')).toBeInTheDocument();
        });
    });

    it('shows error when courseSituation or courseRole is empty', async () => {
        const user = userEvent.setup();
        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'password123');
        // Leave courseSituation and courseRole empty
        await user.click(screen.getByText('ReCaptcha'));

        // Use fireEvent.submit to bypass HTML5 required validation
        const form = screen.getByRole('button', { name: /criar conta/i }).closest('form')!;
        fireEvent.submit(form);

        await waitFor(() => {
            expect(screen.getByText(/preencha todos os campos obrigatórios/i)).toBeInTheDocument();
        });
    });

    it('calls register and navigates on successful registration', async () => {
        mockRegister.mockResolvedValue(undefined);
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'password123');
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(mockRegister).toHaveBeenCalledWith({
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123',
                passwordConfirmation: 'password123',
                courseSituation: 'studying',
                courseRole: 'Aluno',
                courseId: undefined,
            });
        });

        await waitFor(() => {
            expect(mockNavigate).toHaveBeenCalledWith('/');
        });
    });

    it('fires analytics event on successful registration', async () => {
        mockRegister.mockResolvedValue(undefined);
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'password123');
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(analytics.event).toHaveBeenCalledWith('register_account', {
                course_situation: 'studying',
                course_role: 'Aluno',
            });
        });
    });

    it('shows error message when registration fails', async () => {
        mockRegister.mockRejectedValue(new Error('Email already taken'));
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test User');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'password123');
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(screen.getByText('Email already taken')).toBeInTheDocument();
        });
    });

    it('handles social login by calling analytics and redirecting', async () => {
        const user = userEvent.setup();
        // Mock window.location.href
        const originalLocation = window.location;
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { ...originalLocation, href: '' },
        });

        render(<Register />);

        await user.click(screen.getByRole('button', { name: /google/i }));

        expect(analytics.event).toHaveBeenCalledWith('register_social', { provider: 'google' });

        // Restore
        Object.defineProperty(window, 'location', {
            writable: true,
            value: originalLocation,
        });
    });

    it('handles GitHub social login', async () => {
        const user = userEvent.setup();
        const originalLocation = window.location;
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { ...originalLocation, href: '' },
        });

        render(<Register />);

        await user.click(screen.getByRole('button', { name: /github/i }));

        expect(analytics.event).toHaveBeenCalledWith('register_social', { provider: 'github' });

        Object.defineProperty(window, 'location', {
            writable: true,
            value: originalLocation,
        });
    });

    it('redirects to home when already authenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Test', email: 'test@test.com' }, isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Register />);

        expect(mockNavigate).toHaveBeenCalledWith('/', { replace: true });
    });

    it('renders course select options with values from API', async () => {
        render(<Register />);

        await waitFor(() => {
            expect(screen.getByText('Ciência da Computação')).toBeInTheDocument();
            expect(screen.getByText('Engenharia')).toBeInTheDocument();
        });

        // Verify the select has the correct options
        const courseSelect = screen.getByLabelText(/curso/i) as HTMLSelectElement;
        const options = Array.from(courseSelect.options);
        expect(options).toHaveLength(3); // "Selecione um curso (opcional)" + 2 courses
        expect(options[1].value).toBe('1');
        expect(options[1].text).toBe('Ciência da Computação');
        expect(options[2].value).toBe('2');
        expect(options[2].text).toBe('Engenharia');
    });

    it('sends courseId undefined when no course is selected', async () => {
        mockRegister.mockResolvedValue(undefined);
        const user = userEvent.setup();

        render(<Register />);

        await user.type(screen.getByLabelText(/nome completo/i), 'Test');
        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.selectOptions(screen.getByLabelText(/situação/i), 'studying');
        await user.selectOptions(screen.getByLabelText(/vínculo/i), 'Aluno');
        await user.type(screen.getByLabelText(/^senha$/i), 'password123');
        await user.type(screen.getByLabelText(/confirmar senha/i), 'password123');
        await user.click(screen.getByText('ReCaptcha'));
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(mockRegister).toHaveBeenCalled();
        });

        // courseId should be undefined when no course is selected
        const call = mockRegister.mock.calls[0][0];
        expect(call.courseId).toBeUndefined();
    });

    it('disables submit when captcha expires', async () => {
        const user = userEvent.setup();
        render(<Register />);

        // Complete the captcha first
        await user.click(screen.getByText('ReCaptcha'));

        // Submit button should be enabled
        expect(screen.getByRole('button', { name: /criar conta/i })).not.toBeDisabled();

        // Expire the captcha
        await user.click(screen.getByText('ExpireCaptcha'));

        // Submit button should be disabled again
        expect(screen.getByRole('button', { name: /criar conta/i })).toBeDisabled();
    });
});
