import { render, screen, waitFor } from '@/test/test-utils';
import { describe, it, vi, expect, beforeEach } from 'vitest';

vi.mock('../../api/adminClient', () => ({
    systemInfoApi: {
        get: vi.fn(),
    },
}));

import SystemInfo from './SystemInfo';
import { systemInfoApi } from '../../api/adminClient';

const mockPayload = {
    data: {
        runtime: {
            php_version: '8.3.30',
            laravel_version: '12.0.0',
            app_version: 'v1.4.2',
            environment: 'testing',
            debug: true,
            timezone: 'UTC',
            locale: 'pt_BR',
        },
        server: {
            os_family: 'Darwin',
            os_release: '25.4.0',
            hostname: 'test-host',
            server_software: 'cli',
            sapi: 'cli',
            architecture: 'arm64',
        },
        memory: {
            limit_bytes: 134_217_728,
            current_bytes: 16_777_216,
            peak_bytes: 33_554_432,
        },
        database: {
            driver: 'sqlite',
            database: ':memory:',
            host: null,
            version: '3.45.0',
        },
        drivers: {
            cache: 'array',
            queue: 'sync',
            session: 'array',
            mail: 'array',
            filesystem: 'local',
        },
        storage: {
            free_bytes: 50 * 1024 ** 3,
            total_bytes: 200 * 1024 ** 3,
        },
        extensions: ['Core', 'PDO', 'json'],
        php_config: {
            max_execution_time: 120,
            post_max_size: '40M',
            upload_max_filesize: '40M',
            opcache_enabled: false,
        },
        scheduler: [
            {
                command: 'artisan reminders:seminars --days=1',
                description: null,
                expression: '0 10 * * *',
                timezone: 'America/Sao_Paulo',
                without_overlapping: true,
                on_one_server: true,
                next_run_at: '2026-04-27T10:00:00-03:00',
            },
            {
                command: 'artisan audit:prune --days=365',
                description: null,
                expression: '0 4 * * *',
                timezone: 'America/Sao_Paulo',
                without_overlapping: false,
                on_one_server: true,
                next_run_at: '2026-04-27T04:00:00-03:00',
            },
        ],
    },
};

describe('SystemInfo page', function () {
    beforeEach(() => {
        vi.mocked(systemInfoApi.get).mockResolvedValue(mockPayload);
    });

    it('renders a loading skeleton while fetching', () => {
        vi.mocked(systemInfoApi.get).mockReturnValue(new Promise(() => {}));

        render(<SystemInfo />);

        expect(screen.getByRole('status', { name: /carregando/i })).toBeInTheDocument();
    });

    it('renders runtime details when loaded', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('8.3.30')).toBeInTheDocument();
        });

        expect(screen.getByText('12.0.0')).toBeInTheDocument();
        expect(screen.getByText('testing')).toBeInTheDocument();
        expect(screen.getByText('pt_BR')).toBeInTheDocument();
    });

    it('renders the app version from the runtime payload', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('v1.4.2')).toBeInTheDocument();
        });

        expect(screen.getByText(/versão do app/i)).toBeInTheDocument();
    });

    it('renders database driver and version', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('sqlite')).toBeInTheDocument();
        });

        expect(screen.getByText('3.45.0')).toBeInTheDocument();
    });

    it('renders storage usage as human-readable sizes', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText(/200(\.|,)0+\s?GB/)).toBeInTheDocument();
        });

        expect(screen.getByText(/50(\.|,)0+\s?GB/)).toBeInTheDocument();
    });

    it('renders the loaded extension count and list', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText(/3 extensões/i)).toBeInTheDocument();
        });

        expect(screen.getByText('Core')).toBeInTheDocument();
        expect(screen.getByText('PDO')).toBeInTheDocument();
        expect(screen.getByText('json')).toBeInTheDocument();
    });

    it('renders the scheduler table with command, expression and next run', async () => {
        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText(/Agendador/i)).toBeInTheDocument();
        });

        expect(
            screen.getByText('artisan reminders:seminars --days=1'),
        ).toBeInTheDocument();
        expect(screen.getByText('0 10 * * *')).toBeInTheDocument();
        expect(screen.getAllByText('America/Sao_Paulo').length).toBeGreaterThan(0);
    });

    it('renders edge-case formatting (no memory limit, zero bytes, empty scheduler, optional fields)', async () => {
        vi.mocked(systemInfoApi.get).mockResolvedValue({
            data: {
                ...mockPayload.data,
                memory: {
                    limit_bytes: -1,
                    current_bytes: 0,
                    peak_bytes: 0,
                },
                database: {
                    driver: 'mysql',
                    database: null,
                    host: null,
                    version: '8.0.36',
                },
                scheduler: [],
            },
        });

        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('Sem limite')).toBeInTheDocument();
        });

        // formatBytes(0) → "0 B"
        expect(screen.getAllByText('0 B').length).toBeGreaterThan(0);
        // empty scheduler branch
        expect(screen.getByText(/nenhuma rotina agendada/i)).toBeInTheDocument();
        // null host/database fall back to em-dash
        expect(screen.getAllByText('—').length).toBeGreaterThanOrEqual(2);
    });

    it('renders scheduler tasks with missing next_run and without_overlapping=false', async () => {
        vi.mocked(systemInfoApi.get).mockResolvedValue({
            data: {
                ...mockPayload.data,
                scheduler: [
                    {
                        command: 'artisan one-off:task',
                        description: null,
                        expression: '*/5 * * * *',
                        timezone: 'UTC',
                        without_overlapping: false,
                        on_one_server: false,
                        next_run_at: null,
                    },
                ],
            },
        });

        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('artisan one-off:task')).toBeInTheDocument();
        });

        expect(screen.queryByText(/sem sobreposição/i)).not.toBeInTheDocument();
        expect(screen.queryByText(/servidor único/i)).not.toBeInTheDocument();
    });

    it('renders inverse boolean labels for debug=false and opcache_enabled=true', async () => {
        vi.mocked(systemInfoApi.get).mockResolvedValue({
            data: {
                ...mockPayload.data,
                runtime: { ...mockPayload.data.runtime, debug: false },
                php_config: { ...mockPayload.data.php_config, opcache_enabled: true },
            },
        });

        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText('Desativado')).toBeInTheDocument();
        });

        expect(screen.getByText('Ativado')).toBeInTheDocument();
    });

    it('renders an error state when the request fails', async () => {
        vi.mocked(systemInfoApi.get).mockRejectedValue(new Error('boom'));

        render(<SystemInfo />);

        await waitFor(() => {
            expect(screen.getByText(/não foi possível/i)).toBeInTheDocument();
        });
    });
});
