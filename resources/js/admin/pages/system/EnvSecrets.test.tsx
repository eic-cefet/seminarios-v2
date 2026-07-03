import { render, screen, waitFor } from '@/test/test-utils';
import userEvent from '@testing-library/user-event';
import { describe, it, vi, expect, beforeEach } from 'vitest';

vi.mock('../../api/adminClient', async (importOriginal) => {
    const original = await importOriginal<typeof import('../../api/adminClient')>();
    return {
        ...original,
        envSecretsApi: {
            get: vi.fn(),
            update: vi.fn(),
        },
    };
});

import EnvSecrets from './EnvSecrets';
import { AdminApiError, envSecretsApi } from '../../api/adminClient';

const statusPayload = {
    data: {
        secret_id: 'current-secret',
        region: 'us-east-1',
        access_key_id_set: true,
        secret_access_key_set: false,
        applied: true,
    },
};

beforeEach(() => {
    vi.clearAllMocks();
});

describe('EnvSecrets', () => {
    it('shows a loading state while fetching', () => {
        vi.mocked(envSecretsApi.get).mockReturnValue(new Promise(() => {}));

        render(<EnvSecrets />);

        expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('renders the not-found page when the feature flag is off (404)', async () => {
        vi.mocked(envSecretsApi.get).mockRejectedValue(
            new AdminApiError('not_found', 'Recurso não encontrado', 404),
        );

        render(<EnvSecrets />);

        await waitFor(() => {
            expect(screen.getByText(/404/)).toBeInTheDocument();
        });
    });

    it('renders a generic error message on other failures', async () => {
        vi.mocked(envSecretsApi.get).mockRejectedValue(new Error('boom'));

        render(<EnvSecrets />);

        await waitFor(() => {
            expect(screen.getByText(/Não foi possível carregar/)).toBeInTheDocument();
        });
    });

    it('shows the current state with masked credentials', async () => {
        vi.mocked(envSecretsApi.get).mockResolvedValue(statusPayload);

        render(<EnvSecrets />);

        await waitFor(() => {
            expect(screen.getByDisplayValue('current-secret')).toBeInTheDocument();
        });
        expect(screen.getByDisplayValue('us-east-1')).toBeInTheDocument();
        expect(screen.getByText(/Access key configurada/)).toBeInTheDocument();
    });

    it('validates that the secret id is required', async () => {
        vi.mocked(envSecretsApi.get).mockResolvedValue({
            data: { ...statusPayload.data, secret_id: null, region: null },
        });

        render(<EnvSecrets />);
        await waitFor(() => expect(screen.getByLabelText(/Secret ID/)).toBeInTheDocument());

        await userEvent.click(screen.getByRole('button', { name: /Validar e aplicar/ }));

        await waitFor(() => {
            expect(screen.getByText(/Informe o ID do secret/)).toBeInTheDocument();
        });
        expect(envSecretsApi.update).not.toHaveBeenCalled();
    });

    it('requires confirmation before submitting, then shows the applied keys', async () => {
        vi.mocked(envSecretsApi.get).mockResolvedValue(statusPayload);
        vi.mocked(envSecretsApi.update).mockResolvedValue({
            data: { applied: true, keys: ['APP_KEY', 'DB_PASSWORD'], count: 2 },
        });

        render(<EnvSecrets />);
        await waitFor(() => expect(screen.getByLabelText(/Secret ID/)).toBeInTheDocument());

        await userEvent.click(screen.getByRole('button', { name: /Validar e aplicar/ }));

        expect(envSecretsApi.update).not.toHaveBeenCalled();
        expect(screen.getByText(/reescrever o \.env/)).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: /Confirmar/ }));

        await waitFor(() => {
            expect(screen.getByText('APP_KEY')).toBeInTheDocument();
        });
        expect(screen.getByText('DB_PASSWORD')).toBeInTheDocument();
        expect(envSecretsApi.update).toHaveBeenCalledWith(
            expect.objectContaining({ secret_id: 'current-secret', region: 'us-east-1' }),
        );
    });

    it('shows the backend message when the update fails', async () => {
        vi.mocked(envSecretsApi.get).mockResolvedValue(statusPayload);
        vi.mocked(envSecretsApi.update).mockRejectedValue(
            new AdminApiError('validation_error', 'Secret inválido', 422, {
                secret_id: ["Secret 'x' must be a JSON object mapping env names to values."],
            }),
        );

        render(<EnvSecrets />);
        await waitFor(() => expect(screen.getByLabelText(/Secret ID/)).toBeInTheDocument());

        await userEvent.click(screen.getByRole('button', { name: /Validar e aplicar/ }));
        await userEvent.click(screen.getByRole('button', { name: /Confirmar/ }));

        await waitFor(() => {
            expect(screen.getByText(/must be a JSON object/)).toBeInTheDocument();
        });
    });

    it('lets the user cancel the confirmation step', async () => {
        vi.mocked(envSecretsApi.get).mockResolvedValue(statusPayload);

        render(<EnvSecrets />);
        await waitFor(() => expect(screen.getByLabelText(/Secret ID/)).toBeInTheDocument());

        await userEvent.click(screen.getByRole('button', { name: /Validar e aplicar/ }));
        await userEvent.click(screen.getByRole('button', { name: /Cancelar/ }));

        expect(screen.queryByText(/reescrever o \.env/)).not.toBeInTheDocument();
        expect(envSecretsApi.update).not.toHaveBeenCalled();
    });
});
