import { fireEvent, render, screen, waitFor, userEvent } from '@/test/test-utils';

if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

vi.mock('../../components/ui/select', async () => {
    const React = await vi.importActual<typeof import('react')>('react');

    const SelectContext = React.createContext<{
        onValueChange?: (value: string) => void;
        value?: string;
    }>({});

    function MockSelect({ children, value, onValueChange }: any) {
        return React.createElement(
            SelectContext.Provider,
            { value: { onValueChange, value } },
            React.createElement('div', null, children),
        );
    }

    function MockSelectTrigger({ children, id }: any) {
        return React.createElement('div', { role: 'combobox', id }, children);
    }

    function MockSelectValue({ placeholder }: any) {
        return React.createElement('span', null, placeholder);
    }

    function MockSelectItem({ children, value }: any) {
        return React.createElement('option', { value }, children);
    }

    function MockSelectContent({ children }: any) {
        const ctx = React.useContext(SelectContext);
        const options: any[] = [];

        React.Children.forEach(children, (child: any) => {
            if (child?.type === MockSelectItem) {
                options.push(child);
            }
        });

        return React.createElement(
            'select',
            {
                'data-testid': 'mock-native-select',
                value: ctx.value ?? 'all',
                onChange: (event: any) => ctx.onValueChange?.(event.target.value),
            },
            options,
        );
    }

    return {
        Select: MockSelect,
        SelectTrigger: MockSelectTrigger,
        SelectValue: MockSelectValue,
        SelectContent: MockSelectContent,
        SelectItem: MockSelectItem,
    };
});

vi.mock('../../api/adminClient', () => ({
    auditLogsApi: {
        summary: vi.fn().mockResolvedValue({
            data: {
                total: 0,
                manual_count: 0,
                system_count: 0,
                top_events: {},
            },
        }),
        export: vi.fn().mockResolvedValue({
            message: 'Relatório sendo gerado. Você receberá um e-mail em breve.',
        }),
        eventNames: vi.fn().mockResolvedValue({
            data: ['user.login', 'user.logout', 'certificate.generated'],
        }),
    },
}));

import AuditLogReport from './AuditLogReport';
import { auditLogsApi } from '../../api/adminClient';

describe('AuditLogReport', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the page heading and summary cards', async () => {
        render(<AuditLogReport />);

        expect(screen.getByRole('heading', { level: 1, name: /logs de auditoria/i })).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('Total de eventos')).toBeInTheDocument();
        });
        expect(screen.getByText('Eventos manuais')).toBeInTheDocument();
        expect(screen.getByText('Eventos do sistema')).toBeInTheDocument();
        expect(screen.getByText('Eventos mais frequentes')).toBeInTheDocument();
    });

    it('renders summary cards with correct values', async () => {
        vi.mocked(auditLogsApi.summary).mockResolvedValue({
            data: {
                total: 150,
                manual_count: 80,
                system_count: 70,
                top_events: { 'user.login': 50, 'user.logout': 30 },
            },
        });

        render(<AuditLogReport />);

        await waitFor(() => {
            expect(screen.getByText('150')).toBeInTheDocument();
        });
        expect(screen.getByText('80')).toBeInTheDocument();
        expect(screen.getByText('70')).toBeInTheDocument();
    });

    it('renders export button', async () => {
        render(<AuditLogReport />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /exportar por e-mail/i })).toBeInTheDocument();
        });
    });

    it('calls export API with current filters when export button is clicked', async () => {
        render(<AuditLogReport />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalled();
        });

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalledWith({
                days: 30,
                event_type: undefined,
                event_name: undefined,
                search: undefined,
            });
        });
    });

    it('passes changed days filter to summary and export', async () => {
        render(<AuditLogReport />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalledWith({ days: 30 });
        });

        const selects = screen.getAllByTestId('mock-native-select');

        // Change days filter (first select)
        fireEvent.change(selects[0], { target: { value: '7' } });

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalledWith({ days: 7 });
        });

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalledWith({
                days: 7,
                event_type: undefined,
                event_name: undefined,
                search: undefined,
            });
        });
    });

    it('passes event_type filter to export', async () => {
        render(<AuditLogReport />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalled();
        });

        const selects = screen.getAllByTestId('mock-native-select');

        // Change event type filter (second select)
        fireEvent.change(selects[1], { target: { value: 'manual' } });

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalledWith({
                days: 30,
                event_type: 'manual',
                event_name: undefined,
                search: undefined,
            });
        });
    });

    it('passes event_name filter to export', async () => {
        render(<AuditLogReport />);
        const user = userEvent.setup();

        // Wait for both summary and event names to load
        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalled();
            expect(auditLogsApi.eventNames).toHaveBeenCalled();
        });

        // Wait for the event name options to render in the select
        await waitFor(() => {
            expect(screen.getAllByTestId('mock-native-select').length).toBeGreaterThanOrEqual(3);
        });

        const selects = screen.getAllByTestId('mock-native-select');

        // Change event name filter (third select)
        fireEvent.change(selects[2], { target: { value: 'user.login' } });

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalledWith({
                days: 30,
                event_type: undefined,
                event_name: 'user.login',
                search: undefined,
            });
        });
    });

    it('shows error toast when export fails', async () => {
        vi.mocked(auditLogsApi.export).mockRejectedValue(new Error('fail'));

        render(<AuditLogReport />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalled();
        });

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalled();
        });
    });

    it('passes search filter to export', async () => {
        render(<AuditLogReport />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(auditLogsApi.summary).toHaveBeenCalled();
        });

        await user.type(
            screen.getByPlaceholderText('Usuário, evento, origem, IP...'),
            'jorge',
        );

        await user.click(screen.getByRole('button', { name: /exportar por e-mail/i }));

        await waitFor(() => {
            expect(auditLogsApi.export).toHaveBeenCalledWith({
                days: 30,
                event_type: undefined,
                event_name: undefined,
                search: 'jorge',
            });
        });
    });
});
