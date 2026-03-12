import { render, screen } from '@/test/test-utils';

vi.mock('./components/layout/AdminLayout', async () => {
    const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
    return {
        AdminLayout: () => <actual.Outlet />,
    };
});

vi.mock('./pages/reports/FeedbackInsights', () => ({
    __esModule: true,
    default: () => <div>Feedback IA Route</div>,
}));

import { AppRoutes } from './app';

describe('AppRoutes', () => {
    it('renders the feedback insights route', () => {
        render(<AppRoutes />, {
            routerProps: { initialEntries: ['/reports/feedback'] },
        });

        expect(screen.getByText('Feedback IA Route')).toBeInTheDocument();
    });
});
