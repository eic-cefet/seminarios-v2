import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { UserActionsMenu } from './UserActionsMenu';
import { usersApi } from '../../api/adminClient';
import { useAuth } from '@shared/contexts/AuthContext';
import { toast } from 'sonner';

vi.mock('@shared/contexts/AuthContext', () => ({ useAuth: vi.fn() }));
vi.mock('../../api/adminClient', () => ({ usersApi: { impersonate: vi.fn() } }));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

const target = { id: 2, name: 'Maria Silva', email: 'maria@example.com', roles: [], created_at: '', updated_at: '' };
const callbacks = { onEdit: vi.fn(), onLgpd: vi.fn(), onDelete: vi.fn(), onRestore: vi.fn() };

beforeEach(() => {
    vi.mocked(useAuth).mockReturnValue({ user: { id: 1, roles: ['admin'] } } as ReturnType<typeof useAuth>);
});

async function openMenu() {
    const user = userEvent.setup();
    await user.click(screen.getByRole('button', { name: 'Ações de Maria Silva' }));
    return user;
}

it('keeps actions behind one accessible trigger with red Excluir', async () => {
    render(<UserActionsMenu user={target} {...callbacks} />);
    expect(screen.queryByRole('menuitem')).not.toBeInTheDocument();
    await openMenu();
    expect(screen.getAllByRole('menuitem')).toHaveLength(4);
    expect(screen.getByRole('menuitem', { name: 'Excluir' })).toHaveClass('text-red-600');
});

it.each([['Editar', 'onEdit'], ['Dados LGPD', 'onLgpd'], ['Excluir', 'onDelete']] as const)(
    'preserves %s behavior', async (label, callback) => {
        render(<UserActionsMenu user={target} {...callbacks} />);
        const user = await openMenu();
        await user.click(screen.getByRole('menuitem', { name: label }));
        expect(callbacks[callback]).toHaveBeenCalledWith(target);
    },
);

it('supports keyboard opening and Escape with focus returned to the trigger', async () => {
    render(<UserActionsMenu user={target} {...callbacks} />);
    const user = userEvent.setup();
    const trigger = screen.getByRole('button', { name: 'Ações de Maria Silva' });
    trigger.focus();
    await user.keyboard('{Enter}');
    expect(screen.getByRole('menu')).toBeInTheDocument();
    await user.keyboard('{Escape}');
    await waitFor(() => expect(trigger).toHaveFocus());
});

it('requires confirmation showing the selected account before impersonation', async () => {
    render(<UserActionsMenu user={target} {...callbacks} />);
    const user = await openMenu();
    await user.click(screen.getByRole('menuitem', { name: 'Acessar como usuário' }));
    expect(screen.getByRole('alertdialog')).toHaveTextContent(target.name);
    expect(screen.getByRole('alertdialog')).toHaveTextContent(target.email);
    expect(usersApi.impersonate).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', { name: 'Cancelar' }));
    expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
});

it('starts impersonation and navigates to the public app using its base path', async () => {
    vi.mocked(usersApi.impersonate).mockResolvedValue({ user: { id: 2, name: target.name, email: target.email } });
    vi.stubGlobal('location', { href: '' });
    const originalBase = window.app.ROUTER_BASE;
    window.app.ROUTER_BASE = '/eic';
    render(<UserActionsMenu user={target} {...callbacks} />);
    const user = await openMenu();
    await user.click(screen.getByRole('menuitem', { name: 'Acessar como usuário' }));
    await user.click(screen.getByRole('button', { name: 'Acessar como usuário' }));
    await waitFor(() => expect(window.location.href).toBe('/eic/'));
    expect(usersApi.impersonate).toHaveBeenCalledWith(2);
    window.app.ROUTER_BASE = originalBase;
    vi.unstubAllGlobals();
});

it('keeps the confirmation open and reports a failed request', async () => {
    vi.mocked(usersApi.impersonate).mockRejectedValue(new Error('Request failed'));
    render(<UserActionsMenu user={target} {...callbacks} />);
    const user = await openMenu();
    await user.click(screen.getByRole('menuitem', { name: 'Acessar como usuário' }));
    await user.click(screen.getByRole('button', { name: 'Acessar como usuário' }));
    await waitFor(() => expect(toast.error).toHaveBeenCalled());
    expect(screen.getByRole('alertdialog')).toBeInTheDocument();
});

it.each(['teacher', 'user'])('hides impersonation for a %s actor', async (role) => {
    vi.mocked(useAuth).mockReturnValue({ user: { id: 1, roles: [role] } } as ReturnType<typeof useAuth>);
    render(<UserActionsMenu user={target} {...callbacks} />);
    await openMenu();
    expect(screen.queryByRole('menuitem', { name: 'Acessar como usuário' })).not.toBeInTheDocument();
});

it('hides impersonation for administrator targets', async () => {
    render(<UserActionsMenu user={{ ...target, roles: ['admin'] }} {...callbacks} />);
    await openMenu();
    expect(screen.queryByRole('menuitem', { name: 'Acessar como usuário' })).not.toBeInTheDocument();
});

it('offers only restore for archived users', async () => {
    render(<UserActionsMenu user={target} archived {...callbacks} />);
    const user = await openMenu();
    expect(screen.getAllByRole('menuitem')).toHaveLength(1);
    await user.click(screen.getByRole('menuitem', { name: 'Restaurar' }));
    expect(callbacks.onRestore).toHaveBeenCalledWith(target);
});
