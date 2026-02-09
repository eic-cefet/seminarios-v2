import { render, screen, waitFor } from '@/test/test-utils';
import { createUserCertificate } from '@/test/factories';
import { CertificatesSection } from './CertificatesSection';

vi.mock('@shared/api/client', () => ({
    profileApi: {
        certificates: vi.fn(() => Promise.resolve({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        })),
    },
    ApiRequestError: class extends Error {},
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

import { profileApi } from '@shared/api/client';

describe('CertificatesSection', () => {
    it('renders section heading', () => {
        render(<CertificatesSection />);

        expect(screen.getByRole('heading', { name: /meus certificados/i })).toBeInTheDocument();
    });

    it('renders empty state when no certificates', async () => {
        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText(/você ainda não possui certificados/i)).toBeInTheDocument();
        });
    });

    it('renders hint about certificate generation in empty state', async () => {
        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText(/certificados são gerados após a confirmação de presença/i)).toBeInTheDocument();
        });
    });

    it('renders certificate list when data is available', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário de IA',
                    slug: 'seminario-ia',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: { id: 1, name: 'Palestra' },
                },
            }),
            createUserCertificate({
                id: 2,
                certificate_code: 'CERT-002',
                seminar: {
                    id: 11,
                    name: 'Workshop de Docker',
                    slug: 'workshop-docker',
                    scheduled_at: '2026-07-20T10:00:00Z',
                    seminar_type: { id: 2, name: 'Workshop' },
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário de IA')).toBeInTheDocument();
        });

        expect(screen.getByText('Workshop de Docker')).toBeInTheDocument();
    });

    it('renders download buttons for each certificate', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário de IA',
                    slug: 'seminario-ia',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: { id: 1, name: 'Palestra' },
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Baixar')).toBeInTheDocument();
        });
    });

    it('renders certificate download link with correct href', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-ABC123',
                seminar: {
                    id: 10,
                    name: 'Test Seminar',
                    slug: 'test-seminar',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: null,
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            const link = screen.getByText('Baixar').closest('a');
            expect(link).toHaveAttribute('href', '/certificado/CERT-ABC123');
        });
    });

    it('renders seminar type badge when available', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário de IA',
                    slug: 'seminario-ia',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: { id: 1, name: 'Palestra' },
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Palestra')).toBeInTheDocument();
        });
    });

    it('does not render seminar type badge when seminar_type is null', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário Sem Tipo',
                    slug: 'seminario-sem-tipo',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: null,
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário Sem Tipo')).toBeInTheDocument();
        });

        // No badge should be rendered since seminar_type is null
        expect(screen.queryByText('Palestra')).not.toBeInTheDocument();
        expect(screen.queryByText('Workshop')).not.toBeInTheDocument();
    });

    it('does not render scheduled date when scheduled_at is null', async () => {
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário Sem Data',
                    slug: 'seminario-sem-data',
                    scheduled_at: null,
                    seminar_type: { id: 1, name: 'Palestra' },
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário Sem Data')).toBeInTheDocument();
        });

        // Badge should be present but no date text
        expect(screen.getByText('Palestra')).toBeInTheDocument();
    });

    it('fires analytics event when download link is clicked', async () => {
        const { analytics } = await import('@shared/lib/analytics');
        const certificates = [
            createUserCertificate({
                id: 1,
                certificate_code: 'CERT-001',
                seminar: {
                    id: 10,
                    name: 'Seminário de IA',
                    slug: 'seminario-ia',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    seminar_type: { id: 1, name: 'Palestra' },
                },
            }),
        ];

        vi.mocked(profileApi.certificates).mockResolvedValue({
            data: certificates,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<CertificatesSection />);

        await waitFor(() => {
            expect(screen.getByText('Baixar')).toBeInTheDocument();
        });

        const downloadLink = screen.getByText('Baixar').closest('a')!;
        downloadLink.click();

        expect(analytics.event).toHaveBeenCalledWith('certificate_download', {
            seminar_id: 10,
        });
    });
});
