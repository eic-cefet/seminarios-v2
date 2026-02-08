import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { PasswordSection } from './PasswordSection';
import { profileApi } from '@shared/api/client';
import { analytics } from '@shared/lib/analytics';

vi.mock('@shared/api/client', () => ({
    profileApi: { update: vi.fn(), updatePassword: vi.fn() },
    ApiRequestError: class extends Error {
        code: string;
        errors?: Record<string, string[]>;
        constructor(code: string, message: string, status: number, errors?: Record<string, string[]>) {
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

describe('PasswordSection', () => {
    it('renders section heading', () => {
        render(<PasswordSection />);

        expect(screen.getByRole('heading', { name: /senha/i })).toBeInTheDocument();
    });

    it('renders change password button in display mode', () => {
        render(<PasswordSection />);

        expect(screen.getByRole('button', { name: /alterar senha/i })).toBeInTheDocument();
    });

    it('renders password hint text in display mode', () => {
        render(<PasswordSection />);

        expect(screen.getByText(/use uma senha forte com pelo menos 8 caracteres/i)).toBeInTheDocument();
    });

    it('shows password fields after clicking change password', async () => {
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        expect(screen.getByLabelText('Senha atual')).toBeInTheDocument();
        expect(screen.getByLabelText('Nova senha')).toBeInTheDocument();
        expect(screen.getByLabelText('Confirmar nova senha')).toBeInTheDocument();
    });

    it('shows submit and cancel buttons in editing mode', async () => {
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        expect(screen.getByRole('button', { name: /alterar senha/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /cancelar/i })).toBeInTheDocument();
    });

    it('has empty password fields when editing starts', async () => {
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        expect(screen.getByLabelText('Senha atual')).toHaveValue('');
        expect(screen.getByLabelText('Nova senha')).toHaveValue('');
        expect(screen.getByLabelText('Confirmar nova senha')).toHaveValue('');
    });

    it('allows typing in all password fields', async () => {
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass');

        expect(screen.getByLabelText('Senha atual')).toHaveValue('oldpass');
        expect(screen.getByLabelText('Nova senha')).toHaveValue('newpass');
        expect(screen.getByLabelText('Confirmar nova senha')).toHaveValue('newpass');
    });

    it('submits password change and shows success message', async () => {
        vi.mocked(profileApi.updatePassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(profileApi.updatePassword).toHaveBeenCalledWith({
                current_password: 'oldpass',
                password: 'newpass123',
                password_confirmation: 'newpass123',
            });
        });

        await waitFor(() => {
            expect(screen.getByText('Senha atualizada com sucesso!')).toBeInTheDocument();
        });

        expect(analytics.event).toHaveBeenCalledWith('profile_password_change');
    });

    it('shows error message on failed password change', async () => {
        vi.mocked(profileApi.updatePassword).mockRejectedValueOnce(new Error('Senha atual incorreta.'));
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'wrongpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(screen.getByText('Senha atual incorreta.')).toBeInTheDocument();
        });
    });

    it('shows field-level errors on validation failure', async () => {
        const errorWithFields = new Error('Verifique os dados informados.');
        (errorWithFields as any).fieldErrors = { current_password: 'A senha atual está incorreta', password: 'A senha deve ter pelo menos 8 caracteres' };
        vi.mocked(profileApi.updatePassword).mockRejectedValueOnce(errorWithFields);
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'wrong');
        await user.type(screen.getByLabelText('Nova senha'), 'short');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'short');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(screen.getByText('A senha atual está incorreta')).toBeInTheDocument();
        });

        expect(screen.getByText('A senha deve ter pelo menos 8 caracteres')).toBeInTheDocument();
    });

    it('shows loading state while submitting', async () => {
        let resolveUpdate: () => void;
        vi.mocked(profileApi.updatePassword).mockImplementation(() => new Promise<any>((resolve) => { resolveUpdate = resolve; }));
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        expect(screen.getByRole('button', { name: /salvando/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /salvando/i })).toBeDisabled();

        resolveUpdate!();

        await waitFor(() => {
            expect(screen.getByText('Senha atualizada com sucesso!')).toBeInTheDocument();
        });
    });

    it('returns to display mode and clears fields after cancel', async () => {
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'some text');
        await user.type(screen.getByLabelText('Nova senha'), 'some text');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'some text');

        await user.click(screen.getByRole('button', { name: /cancelar/i }));

        // Back in display mode
        expect(screen.getByText(/use uma senha forte com pelo menos 8 caracteres/i)).toBeInTheDocument();
        expect(screen.queryByLabelText('Senha atual')).not.toBeInTheDocument();
    });

    it('returns to display mode after successful submission', async () => {
        vi.mocked(profileApi.updatePassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(screen.getByText('Senha atualizada com sucesso!')).toBeInTheDocument();
        });

        // The form should no longer be visible, and password hint should not appear while success is shown
        expect(screen.queryByLabelText('Senha atual')).not.toBeInTheDocument();
    });

    it('hides password hint text when success message is shown', async () => {
        vi.mocked(profileApi.updatePassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(screen.getByText('Senha atualizada com sucesso!')).toBeInTheDocument();
        });

        // The hint text should NOT show when success is visible
        expect(screen.queryByText(/use uma senha forte com pelo menos 8 caracteres/i)).not.toBeInTheDocument();
    });

    it('clears password fields after successful submission', async () => {
        vi.mocked(profileApi.updatePassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<PasswordSection />);

        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await user.type(screen.getByLabelText('Senha atual'), 'oldpass');
        await user.type(screen.getByLabelText('Nova senha'), 'newpass123');
        await user.type(screen.getByLabelText('Confirmar nova senha'), 'newpass123');
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        await waitFor(() => {
            expect(screen.getByText('Senha atualizada com sucesso!')).toBeInTheDocument();
        });

        // Now click edit again - fields should be empty
        await user.click(screen.getByRole('button', { name: /alterar senha/i }));

        expect(screen.getByLabelText('Senha atual')).toHaveValue('');
        expect(screen.getByLabelText('Nova senha')).toHaveValue('');
        expect(screen.getByLabelText('Confirmar nova senha')).toHaveValue('');
    });
});
