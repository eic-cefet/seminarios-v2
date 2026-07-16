import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { toast } from 'sonner';

vi.mock('sonner', () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

vi.mock('../../api/adminClient', () => ({
    systemInfoApi: { resetDatabase: vi.fn() },
}));

import { systemInfoApi } from '../../api/adminClient';
import { DatabaseResetDangerZone } from './DatabaseResetDangerZone';

const originalLocation = window.location;

describe('DatabaseResetDangerZone', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
        Object.defineProperty(window, 'location', {
            writable: true,
            value: originalLocation,
        });
    });

    it('shows the destructive warning and requires the exact confirmation phrase', async () => {
        const user = userEvent.setup();
        render(<DatabaseResetDangerZone />);

        await user.click(screen.getByRole('button', { name: /recriar banco de dados/i }));

        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            /todas as tabelas, usuários, sessões, auditorias e dados da aplicação serão apagados e substituídos/i,
        );

        const confirm = screen.getByRole('button', { name: /^recriar banco$/i });
        const input = screen.getByLabelText(/digite apagar banco/i);
        expect(confirm).toBeDisabled();

        await user.type(input, 'apagar banco');
        expect(confirm).toBeDisabled();

        await user.clear(input);
        await user.type(input, 'APAGAR BANCO');
        expect(confirm).toBeEnabled();
    });

    it('keeps controls disabled and submits only once while pending', async () => {
        const user = userEvent.setup();
        const resetPromise: Promise<{ message: string }> = new Promise(() => {});
        vi.mocked(systemInfoApi.resetDatabase).mockReturnValue(resetPromise);
        render(<DatabaseResetDangerZone />);

        await user.click(screen.getByRole('button', { name: /recriar banco de dados/i }));
        const input = screen.getByLabelText(/digite apagar banco/i);
        await user.type(input, 'APAGAR BANCO');
        await user.click(screen.getByRole('button', { name: /^recriar banco$/i }));

        const pendingAction = screen.getByRole('button', { name: /recriando banco/i });
        expect(pendingAction).toBeDisabled();
        expect(screen.getByRole('button', { name: /cancelar/i })).toBeDisabled();
        expect(input).toBeDisabled();
        expect(screen.getByRole('alertdialog')).toBeInTheDocument();

        await user.click(pendingAction);

        expect(systemInfoApi.resetDatabase).toHaveBeenCalledTimes(1);
        expect(systemInfoApi.resetDatabase).toHaveBeenCalledWith('APAGAR BANCO');
    });

    it('keeps the modal open and shows the API error', async () => {
        const user = userEvent.setup();
        vi.mocked(systemInfoApi.resetDatabase).mockRejectedValue(new Error('Falha controlada'));
        render(<DatabaseResetDangerZone />);

        await user.click(screen.getByRole('button', { name: /recriar banco de dados/i }));
        await user.type(screen.getByLabelText(/digite apagar banco/i), 'APAGAR BANCO');
        await user.click(screen.getByRole('button', { name: /^recriar banco$/i }));

        await waitFor(() => expect(toast.error).toHaveBeenCalledWith('Falha controlada'));
        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    });

    it('shows success and redirects to login after one second', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { ...originalLocation, href: '' },
        });
        vi.mocked(systemInfoApi.resetDatabase).mockResolvedValue({ message: 'Banco recriado' });
        render(<DatabaseResetDangerZone />);

        await user.click(screen.getByRole('button', { name: /recriar banco de dados/i }));
        await user.type(screen.getByLabelText(/digite apagar banco/i), 'APAGAR BANCO');
        await user.click(screen.getByRole('button', { name: /^recriar banco$/i }));

        await waitFor(() => expect(toast.success).toHaveBeenCalledWith('Banco recriado'));
        vi.advanceTimersByTime(1_000);

        expect(window.location.href).toContain('/login');
    });
});
