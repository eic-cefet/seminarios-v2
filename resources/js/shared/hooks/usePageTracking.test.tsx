import { renderHook } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { usePageTracking } from './usePageTracking';
import { analytics } from '../lib/analytics';
import type { ReactNode } from 'react';

vi.mock('../lib/analytics', () => ({
    analytics: {
        pageview: vi.fn(),
        event: vi.fn(),
    },
}));

describe('usePageTracking', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('calls analytics.pageview on mount with current location', () => {
        const wrapper = ({ children }: { children: ReactNode }) => (
            <MemoryRouter initialEntries={['/test?q=1']}>{children}</MemoryRouter>
        );
        renderHook(() => usePageTracking(), { wrapper });
        expect(analytics.pageview).toHaveBeenCalledWith('/test?q=1', expect.any(String));
    });

    it('calls analytics.pageview at least once', () => {
        const wrapper = ({ children }: { children: ReactNode }) => (
            <MemoryRouter initialEntries={['/']}>{children}</MemoryRouter>
        );
        renderHook(() => usePageTracking(), { wrapper });
        expect(analytics.pageview).toHaveBeenCalled();
    });
});
