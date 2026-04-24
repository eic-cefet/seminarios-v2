import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { TwoFactorSection } from './TwoFactorSection';

const mockRefreshUser = vi.fn();

let mockUser: { two_factor_enabled?: boolean } | null = { two_factor_enabled: false };

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: mockUser,
        isLoading: false,
        isAuthenticated: true,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: mockRefreshUser,
        completeTwoFactor: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/api/twoFactorApi', () => ({
    twoFactorApi: {
        enable: vi.fn(),
        confirm: vi.fn(),
        disable: vi.fn(),
        regenerateRecoveryCodes: vi.fn(),
        listDevices: vi.fn(async () => ({ devices: [] })),
        revokeDevice: vi.fn(),
    },
}));

import { twoFactorApi } from '@shared/api/twoFactorApi';

describe('TwoFactorSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockUser = { two_factor_enabled: false };
    });

    it('shows an enable button when 2FA is off', async () => {
        vi.mocked(twoFactorApi.enable).mockResolvedValueOnce({
            secret: 'SECRET',
            qr_code_svg: '<svg data-testid="qr"/>',
            recovery_codes: ['code-a', 'code-b'],
        });

        render(<TwoFactorSection />);

        const button = screen.getByRole('button', { name: /^ativar$/i });
        await userEvent.click(button);

        await waitFor(() => {
            expect(screen.getByText(/escaneie o qr code/i)).toBeInTheDocument();
            expect(screen.getByText('code-a')).toBeInTheDocument();
        });
    });

    it('confirms 2FA with a 6-digit code', async () => {
        vi.mocked(twoFactorApi.enable).mockResolvedValueOnce({
            secret: 'S',
            qr_code_svg: '<svg/>',
            recovery_codes: ['a'],
        });
        vi.mocked(twoFactorApi.confirm).mockResolvedValueOnce({ message: 'ok' });

        render(<TwoFactorSection />);
        await userEvent.click(screen.getByRole('button', { name: /^ativar$/i }));

        const input = await screen.findByLabelText(/código do app/i);
        await userEvent.type(input, '123456');
        await userEvent.click(screen.getByRole('button', { name: /confirmar/i }));

        await waitFor(() => {
            expect(twoFactorApi.confirm).toHaveBeenCalledWith('123456');
            expect(mockRefreshUser).toHaveBeenCalled();
        });
    });

    it('shows the disable button once 2FA is enabled', async () => {
        mockUser = { two_factor_enabled: true };
        vi.mocked(twoFactorApi.disable).mockResolvedValueOnce({ message: 'ok' });

        render(<TwoFactorSection />);

        const disableBtn = screen.getByRole('button', { name: /desativar/i });
        await userEvent.click(disableBtn);

        await waitFor(() => {
            expect(twoFactorApi.disable).toHaveBeenCalled();
        });
    });

    it('lets the user regenerate recovery codes', async () => {
        mockUser = { two_factor_enabled: true };
        vi.mocked(twoFactorApi.regenerateRecoveryCodes).mockResolvedValueOnce({
            recovery_codes: ['new-1', 'new-2'],
        });

        render(<TwoFactorSection />);
        await userEvent.click(
            screen.getByRole('button', { name: /gerar novos códigos de recuperação/i }),
        );

        await waitFor(() => {
            expect(screen.getByText('new-1')).toBeInTheDocument();
        });
    });

    it('lists trusted devices and supports revocation', async () => {
        mockUser = { two_factor_enabled: true };
        vi.mocked(twoFactorApi.listDevices).mockResolvedValueOnce({
            devices: [
                {
                    id: 7,
                    label: 'My Laptop',
                    ip: '127.0.0.1',
                    last_used_at: null,
                    expires_at: '2027-01-01T00:00:00Z',
                    created_at: '2026-04-23T00:00:00Z',
                },
            ],
        });
        vi.mocked(twoFactorApi.revokeDevice).mockResolvedValueOnce({ message: 'ok' });

        render(<TwoFactorSection />);

        const item = await screen.findByText('My Laptop');
        expect(item).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: /revogar/i }));

        await waitFor(() => {
            expect(twoFactorApi.revokeDevice).toHaveBeenCalledWith(7);
        });
    });
});
