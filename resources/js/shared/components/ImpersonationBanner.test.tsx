import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { ImpersonationBanner } from './ImpersonationBanner';
import { useAuth } from '@shared/contexts/AuthContext';
import { authApi } from '@shared/api/client';
import { toast } from 'sonner';

vi.mock('@shared/contexts/AuthContext', () => ({ useAuth: vi.fn() }));
vi.mock('@shared/api/client', () => ({ authApi: { stopImpersonation: vi.fn() } }));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

beforeEach(() => {
    vi.mocked(useAuth).mockReturnValue({ user: { id: 2, name: 'Maria Silva', email: 'maria@example.com', is_impersonating: true } } as ReturnType<typeof useAuth>);
});

afterEach(() => vi.unstubAllGlobals());

it('shows the current account and a way back to the administrator', () => {
    render(<ImpersonationBanner />);
    expect(screen.getByRole('region', { name: 'Acesso como usuário' })).toHaveTextContent('Maria Silva');
    expect(screen.getByRole('button', { name: 'Voltar ao administrador' })).toBeVisible();
});

it('is absent during ordinary sessions', () => {
    vi.mocked(useAuth).mockReturnValue({ user: { id: 1, name: 'Admin' } } as ReturnType<typeof useAuth>);
    render(<ImpersonationBanner />);
    expect(screen.queryByRole('region')).not.toBeInTheDocument();
});

it('ends impersonation and reloads the admin user list', async () => {
    vi.stubGlobal('location', { href: '' });
    vi.mocked(authApi.stopImpersonation).mockResolvedValue({ user: { id: 1, name: 'Admin', email: 'admin@example.com' } });
    render(<ImpersonationBanner />);
    await userEvent.setup().click(screen.getByRole('button', { name: 'Voltar ao administrador' }));
    await waitFor(() => expect(window.location.href).toBe('/admin/users'));
    expect(authApi.stopImpersonation).toHaveBeenCalledOnce();
});

it('keeps the return action available after a network failure', async () => {
    vi.mocked(authApi.stopImpersonation).mockRejectedValue(new Error('offline'));
    render(<ImpersonationBanner />);
    await userEvent.setup().click(screen.getByRole('button', { name: 'Voltar ao administrador' }));
    await waitFor(() => expect(toast.error).toHaveBeenCalled());
    expect(screen.getByRole('button', { name: 'Voltar ao administrador' })).toBeEnabled();
});
