import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { toast } from 'sonner';
import { analytics } from '@shared/lib/analytics';
import { createTestQueryClient } from '@/test/test-utils';

vi.mock('sonner', () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('../api/adminClient', () => ({
    registrationsApi: {
        store: vi.fn(),
    },
    seminarsApi: {
        list: vi.fn(),
    },
    usersApi: {
        list: vi.fn(),
    },
}));

import { AddRegistrationsModal } from './AddRegistrationsModal';
import { registrationsApi, seminarsApi, usersApi } from '../api/adminClient';

const page = (items: object[], extra = {}) => ({
    data: items,
    meta: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: items.length,
        from: items.length ? 1 : 0,
        to: items.length,
        ...extra,
    },
    links: { first: '', last: '', prev: null, next: null },
});

const seminarX = { id: 10, name: 'Seminar X', scheduled_at: '2026-06-15T14:00:00Z' };

beforeEach(() => {
    vi.mocked(registrationsApi.store).mockReset();
    vi.mocked(seminarsApi.list).mockResolvedValue(page([seminarX]) as any);
    vi.mocked(usersApi.list).mockResolvedValue(
        page([
            { id: 1, name: 'Ana Silva', email: 'ana@cefet-rj.br' },
            { id: 2, name: 'Bruno Costa', email: 'bruno@cefet-rj.br' },
        ]) as any,
    );
});

describe('AddRegistrationsModal', () => {
    it('renders title and presence notice when open', () => {
        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={null} />,
        );
        expect(screen.getByText('Adicionar inscricoes')).toBeInTheDocument();
        expect(
            screen.getByText(/serao marcados como presentes/),
        ).toBeInTheDocument();
    });

    it('renders nothing when closed', () => {
        render(
            <AddRegistrationsModal
                open={false}
                onClose={vi.fn()}
                initialSeminar={null}
            />,
        );
        expect(screen.queryByText('Adicionar inscricoes')).not.toBeInTheDocument();
    });

    it('disables submit until seminar and users are chosen', () => {
        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={null} />,
        );
        expect(screen.getByRole('button', { name: /^Adicionar$/ })).toBeDisabled();
    });

    it('prefills the seminar from initialSeminar', () => {
        render(
            <AddRegistrationsModal
                open
                onClose={vi.fn()}
                initialSeminar={seminarX}
            />,
        );
        expect(screen.getByText('Seminar X')).toBeInTheDocument();
    });

    it('submits selected seminar and users, toasts the summary and closes', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        vi.mocked(registrationsApi.store).mockResolvedValue({
            message: 'ok',
            data: { created: 2, already_registered: 0, marked_present: 0 },
        } as any);

        render(
            <AddRegistrationsModal open onClose={onClose} initialSeminar={seminarX} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.click(await screen.findByRole('option', { name: /Bruno Costa/ }));
        await user.keyboard('{Escape}');

        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(registrationsApi.store).toHaveBeenCalledWith({
                seminar_id: 10,
                user_ids: [1, 2],
            });
        });
        expect(toast.success).toHaveBeenCalledWith('2 inscricoes criadas');
        expect(onClose).toHaveBeenCalled();
        expect(analytics.event).toHaveBeenCalledWith('admin_registrations_added', {
            seminar_id: 10,
            count: 2,
        });
    });

    it('includes already-registered count in the toast', async () => {
        const user = userEvent.setup();
        vi.mocked(registrationsApi.store).mockResolvedValue({
            message: 'ok',
            data: { created: 1, already_registered: 1, marked_present: 1 },
        } as any);

        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={seminarX} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(toast.success).toHaveBeenCalledWith(
                '1 inscricao criada · 1 ja inscrito',
            );
        });
    });

    it('invalidates the registrations query on success', async () => {
        const user = userEvent.setup();
        const queryClient = createTestQueryClient();
        const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
        vi.mocked(registrationsApi.store).mockResolvedValue({
            message: 'ok',
            data: { created: 1, already_registered: 0, marked_present: 0 },
        } as any);

        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={seminarX} />,
            { queryClient },
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(invalidateSpy).toHaveBeenCalledWith({
                queryKey: ['admin-registrations'],
            });
        });
    });

    it('pluralizes ja inscritos for multiple already-registered users', async () => {
        const user = userEvent.setup();
        vi.mocked(registrationsApi.store).mockResolvedValue({
            message: 'ok',
            data: { created: 1, already_registered: 2, marked_present: 0 },
        } as any);

        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={seminarX} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(toast.success).toHaveBeenCalledWith(
                '1 inscricao criada · 2 ja inscritos',
            );
        });
    });

    it('toasts an error and stays open on failure', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        vi.mocked(registrationsApi.store).mockRejectedValue(new Error('fail'));

        render(
            <AddRegistrationsModal open onClose={onClose} initialSeminar={seminarX} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(toast.error).toHaveBeenCalledWith('Erro ao adicionar inscricoes');
        });
        expect(onClose).not.toHaveBeenCalled();
    });

    it('resets selections when reopened', async () => {
        const user = userEvent.setup();
        const { rerender } = render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={null} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        expect(screen.getByText('Ana Silva')).toBeInTheDocument();

        rerender(
            <AddRegistrationsModal
                open={false}
                onClose={vi.fn()}
                initialSeminar={null}
            />,
        );
        rerender(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={seminarX} />,
        );

        expect(screen.queryByText('Ana Silva')).not.toBeInTheDocument();
        expect(screen.getByText('Seminar X')).toBeInTheDocument();
    });

    it('calls onClose via the Cancelar button', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        render(
            <AddRegistrationsModal open onClose={onClose} initialSeminar={null} />,
        );

        await user.click(screen.getByRole('button', { name: 'Cancelar' }));
        expect(onClose).toHaveBeenCalled();
    });

    it('disables buttons while the request is pending', async () => {
        const user = userEvent.setup();
        vi.mocked(registrationsApi.store).mockReturnValue(
            new Promise(() => {}) as any,
        );

        render(
            <AddRegistrationsModal open onClose={vi.fn()} initialSeminar={seminarX} />,
        );

        const comboboxes = screen.getAllByRole('combobox');
        await user.click(comboboxes[1]);
        await user.click(await screen.findByRole('option', { name: /Ana Silva/ }));
        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: /^Adicionar$/ }));

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /Adicionar/ })).toBeDisabled();
        });
        expect(screen.getByRole('button', { name: 'Cancelar' })).toBeDisabled();
    });
});
