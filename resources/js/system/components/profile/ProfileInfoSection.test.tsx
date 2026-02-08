import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { createUser } from '@/test/factories';
import { ProfileInfoSection } from './ProfileInfoSection';
import { profileApi } from '@shared/api/client';
import { analytics } from '@shared/lib/analytics';

vi.mock('@shared/api/client', () => ({
    profileApi: { update: vi.fn(), updatePassword: vi.fn() },
    ApiRequestError: class extends Error {
        code: string;
        errors?: Record<string, string[]>;
        constructor(code: string, message: string, _status: number, errors?: Record<string, string[]>) {
            super(message);
            this.code = code;
            this.errors = errors;
        }
    },
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
    getFieldErrors: vi.fn((err: any) => err?.fieldErrors),
}));

describe('ProfileInfoSection', () => {
    const user = createUser({ name: 'Maria Silva', email: 'maria@example.com' });
    const onUpdate = vi.fn();

    it('renders user name in display mode', () => {
        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Maria Silva')).toBeInTheDocument();
    });

    it('renders user email in display mode', () => {
        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('maria@example.com')).toBeInTheDocument();
    });

    it('renders section heading', () => {
        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByRole('heading', { name: /informações pessoais/i })).toBeInTheDocument();
    });

    it('renders edit button in display mode', () => {
        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByRole('button', { name: /editar/i })).toBeInTheDocument();
    });

    it('shows form with name and email inputs after clicking edit', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByLabelText(/nome/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument();
    });

    it('shows save and cancel buttons in editing mode', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByRole('button', { name: /salvar/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /cancelar/i })).toBeInTheDocument();
    });

    it('populates form inputs with current user values', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByLabelText(/nome/i)).toHaveValue('Maria Silva');
        expect(screen.getByLabelText(/e-mail/i)).toHaveValue('maria@example.com');
    });

    it('hides edit button when in editing mode', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        // The "Editar" button should not be visible in editing mode
        expect(screen.queryByRole('button', { name: /^editar$/i })).not.toBeInTheDocument();
    });

    it('allows typing in name and email fields', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        const nameInput = screen.getByLabelText(/nome/i);
        const emailInput = screen.getByLabelText(/e-mail/i);

        await user_action.clear(nameInput);
        await user_action.type(nameInput, 'New Name');

        await user_action.clear(emailInput);
        await user_action.type(emailInput, 'new@example.com');

        expect(nameInput).toHaveValue('New Name');
        expect(emailInput).toHaveValue('new@example.com');
    });

    it('submits profile update and shows success message', async () => {
        vi.mocked(profileApi.update).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        await user_action.clear(screen.getByLabelText(/nome/i));
        await user_action.type(screen.getByLabelText(/nome/i), 'Maria Updated');
        await user_action.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(profileApi.update).toHaveBeenCalledWith({ name: 'Maria Updated', email: 'maria@example.com' });
        });

        await waitFor(() => {
            expect(screen.getByText('Perfil atualizado com sucesso!')).toBeInTheDocument();
        });

        expect(analytics.event).toHaveBeenCalledWith('profile_info_update');
        expect(onUpdate).toHaveBeenCalled();
    });

    it('shows error message on failed profile update', async () => {
        vi.mocked(profileApi.update).mockRejectedValueOnce(new Error('E-mail já está em uso.'));
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        await user_action.clear(screen.getByLabelText(/e-mail/i));
        await user_action.type(screen.getByLabelText(/e-mail/i), 'taken@example.com');
        await user_action.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('E-mail já está em uso.')).toBeInTheDocument();
        });
    });

    it('shows field-level errors on validation failure', async () => {
        const errorWithFields = new Error('Verifique os dados informados.');
        (errorWithFields as any).fieldErrors = { name: 'O nome é obrigatório', email: 'O e-mail é inválido' };
        vi.mocked(profileApi.update).mockRejectedValueOnce(errorWithFields);
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        await user_action.clear(screen.getByLabelText(/nome/i));
        await user_action.type(screen.getByLabelText(/nome/i), ' ');
        await user_action.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('O nome é obrigatório')).toBeInTheDocument();
        });

        expect(screen.getByText('O e-mail é inválido')).toBeInTheDocument();
    });

    it('shows loading state while submitting', async () => {
        let resolveUpdate: (value?: any) => void;
        vi.mocked(profileApi.update).mockImplementation(() => new Promise<any>((resolve) => { resolveUpdate = resolve; }));
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));
        await user_action.click(screen.getByRole('button', { name: /salvar/i }));

        expect(screen.getByRole('button', { name: /salvando/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /salvando/i })).toBeDisabled();

        resolveUpdate!();

        await waitFor(() => {
            expect(screen.getByText('Perfil atualizado com sucesso!')).toBeInTheDocument();
        });
    });

    it('resets form values and returns to display mode on cancel', async () => {
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));

        await user_action.clear(screen.getByLabelText(/nome/i));
        await user_action.type(screen.getByLabelText(/nome/i), 'Changed Name');

        await user_action.click(screen.getByRole('button', { name: /cancelar/i }));

        // Back in display mode, should show original values
        expect(screen.getByText('Maria Silva')).toBeInTheDocument();
        expect(screen.getByText('maria@example.com')).toBeInTheDocument();
        expect(screen.queryByLabelText(/nome/i)).not.toBeInTheDocument();
    });

    it('returns to display mode after successful submission', async () => {
        vi.mocked(profileApi.update).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);
        const user_action = userEvent.setup();

        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        await user_action.click(screen.getByRole('button', { name: /editar/i }));
        await user_action.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('Perfil atualizado com sucesso!')).toBeInTheDocument();
        });

        // Form should no longer be visible
        expect(screen.queryByLabelText(/nome/i)).not.toBeInTheDocument();
        // Display data should be visible
        expect(screen.getByText('Maria Silva')).toBeInTheDocument();
    });

    it('displays Nome and E-mail labels in display mode', () => {
        render(<ProfileInfoSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Nome')).toBeInTheDocument();
        expect(screen.getByText('E-mail')).toBeInTheDocument();
    });
});
