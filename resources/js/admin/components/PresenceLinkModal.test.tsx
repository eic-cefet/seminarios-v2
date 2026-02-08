import { render, screen, waitFor, userEvent } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    presenceLinkApi: {
        get: vi.fn().mockResolvedValue({ data: null }),
        create: vi.fn(),
        toggle: vi.fn(),
    },
}));

vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

import { PresenceLinkModal } from './PresenceLinkModal';

describe('PresenceLinkModal', () => {
    const defaultProps = {
        open: true,
        onClose: vi.fn(),
        seminarId: 1,
        seminarName: 'Test Seminar',
    };

    it('renders the dialog title when open', () => {
        render(<PresenceLinkModal {...defaultProps} />);
        expect(screen.getByText('Link de Presença')).toBeInTheDocument();
    });

    it('renders the seminar name in the description', () => {
        render(<PresenceLinkModal {...defaultProps} />);
        expect(screen.getByText('Test Seminar')).toBeInTheDocument();
    });

    it('shows the create button when no link exists', async () => {
        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Criar Link de Presença')).toBeInTheDocument();
        });
    });

    it('shows message about no link created', async () => {
        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(
                screen.getByText('Nenhum link de presença foi criado para este seminário ainda.'),
            ).toBeInTheDocument();
        });
    });

    it('does not render the dialog when closed', () => {
        render(<PresenceLinkModal {...defaultProps} open={false} />);
        expect(screen.queryByText('Link de Presença')).not.toBeInTheDocument();
    });

    it('shows link details when a presence link exists', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,test',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Status do Link')).toBeInTheDocument();
        });
        expect(screen.getByText('Link da Página')).toBeInTheDocument();
        expect(screen.getByText('https://example.com/presence/test-uuid')).toBeInTheDocument();
    });

    it('shows QR code when link is active', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('QR Code')).toBeInTheDocument();
        });
        expect(screen.getByAltText('QR Code de Presença')).toBeInTheDocument();
    });

    it('shows PNG URL section', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Link da Imagem PNG')).toBeInTheDocument();
        });
        expect(screen.getByText('https://example.com/presence/test-uuid.png')).toBeInTheDocument();
    });

    it('shows valid status when link is valid', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Válido')).toBeInTheDocument();
        });
    });

    it('shows inactive status when link is not valid and not expired', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: false,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: false,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Inativo')).toBeInTheDocument();
        });
    });

    it('copies page link to clipboard when copy button is clicked', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        const { toast } = await import('sonner');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        // Mock clipboard in jsdom
        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText: vi.fn().mockResolvedValue(undefined) },
            writable: true,
            configurable: true,
        });

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Link da Página')).toBeInTheDocument();
        });

        // The page URL text is inside a flex-1 div. Its parent div contains the copy button.
        const pageLinkText = screen.getByText('https://example.com/presence/test-uuid');
        const copyButton = pageLinkText.parentElement!.querySelector('button')!;
        await user.click(copyButton);
        expect(toast.success).toHaveBeenCalledWith('Link copiado para a área de transferência');
    });

    it('shows active status description when link is active', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Link ativo - usuários podem registrar presença')).toBeInTheDocument();
        });
    });

    it('shows inactive status description when link is not active', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: false,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: false,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Link inativo - presença não pode ser registrada')).toBeInTheDocument();
        });
    });

    it('shows expired status when link is expired', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: false,
                expires_at: '2020-01-01T00:00:00Z',
                is_expired: true,
                is_valid: false,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Expirado')).toBeInTheDocument();
        });
    });

    it('does not show QR code section when link is inactive', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: false,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: false,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Status do Link')).toBeInTheDocument();
        });
        expect(screen.queryByText('QR Code')).not.toBeInTheDocument();
        expect(screen.queryByAltText('QR Code de Presença')).not.toBeInTheDocument();
    });

    it('shows loading state while fetching data', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        // Create a promise that never resolves during the test
        vi.mocked(presenceLinkApi.get).mockReturnValue(new Promise(() => {}));

        render(<PresenceLinkModal {...defaultProps} />);

        expect(screen.getByText('Carregando...')).toBeInTheDocument();
    });

    it('calls create mutation when create button is clicked', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({ data: null });
        vi.mocked(presenceLinkApi.create).mockResolvedValue({
            message: 'Created',
            data: {
                id: 1,
                uuid: 'new-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/new-uuid',
                png_url: 'https://example.com/presence/new-uuid.png',
                qr_code: 'data:image/png;base64,newqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Criar Link de Presença')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Criar Link de Presença'));

        expect(presenceLinkApi.create).toHaveBeenCalledWith(1);
    });

    it('calls toggle mutation when switch is toggled', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });
        vi.mocked(presenceLinkApi.toggle).mockResolvedValue({
            message: 'Toggled',
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: false,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: false,
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Status do Link')).toBeInTheDocument();
        });

        const switchEl = screen.getByRole('switch');
        await user.click(switchEl);

        expect(presenceLinkApi.toggle).toHaveBeenCalledWith(1);
    });

    it('copies PNG URL to clipboard when PNG copy button is clicked', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        const { toast } = await import('sonner');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText: vi.fn().mockResolvedValue(undefined) },
            writable: true,
            configurable: true,
        });

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Link da Imagem PNG')).toBeInTheDocument();
        });

        const pngLinkText = screen.getByText('https://example.com/presence/test-uuid.png');
        const pngCopyButton = pngLinkText.parentElement!.querySelector('button')!;
        await user.click(pngCopyButton);
        expect(toast.success).toHaveBeenCalledWith('Link copiado para a área de transferência');
    });

    it('formats expiration date correctly', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-06-15T14:30:00Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Expira em:')).toBeInTheDocument();
        });
        // The date should be formatted in pt-BR locale, not "N/A"
        expect(screen.queryByText('N/A')).not.toBeInTheDocument();
    });

    it('shows N/A when expires_at is undefined', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: undefined as any,
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('N/A')).toBeInTheDocument();
        });
    });

    it('shows the QR code scan instruction text when active', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Usuários podem escanear este QR code para registrar presença')).toBeInTheDocument();
        });
    });

    it('handles create mutation error', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({ data: null });
        vi.mocked(presenceLinkApi.create).mockRejectedValue(new Error('Create failed'));

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Criar Link de Presença')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Criar Link de Presença'));

        // The mutation should have been called
        expect(presenceLinkApi.create).toHaveBeenCalledWith(1);
    });

    it('handles toggle mutation error', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });
        vi.mocked(presenceLinkApi.toggle).mockRejectedValue(new Error('Toggle failed'));

        render(<PresenceLinkModal {...defaultProps} />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Status do Link')).toBeInTheDocument();
        });

        const switchEl = screen.getByRole('switch');
        await user.click(switchEl);

        expect(presenceLinkApi.toggle).toHaveBeenCalledWith(1);
    });

    it('shows helper text for page link', async () => {
        const { presenceLinkApi } = await import('../api/adminClient');
        vi.mocked(presenceLinkApi.get).mockResolvedValue({
            data: {
                id: 1,
                uuid: 'test-uuid',
                active: true,
                expires_at: '2026-12-31T23:59:59Z',
                is_expired: false,
                is_valid: true,
                url: 'https://example.com/presence/test-uuid',
                png_url: 'https://example.com/presence/test-uuid.png',
                qr_code: 'data:image/png;base64,testqr',
            },
        });

        render(<PresenceLinkModal {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('Compartilhe este link para a página de registro')).toBeInTheDocument();
        });
        expect(screen.getByText('Use este link para baixar o QR code em PNG')).toBeInTheDocument();
    });
});
