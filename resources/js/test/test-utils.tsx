import { render, type RenderOptions } from '@testing-library/react';
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { HelmetProvider } from 'react-helmet-async';
import type { ReactElement, ReactNode } from 'react';

interface CustomRenderOptions extends Omit<RenderOptions, 'wrapper'> {
    routerProps?: MemoryRouterProps;
    queryClient?: QueryClient;
    authUser?: import('@shared/types').User | null;
}

function createTestQueryClient() {
    return new QueryClient({
        defaultOptions: {
            queries: { retry: false, gcTime: 0 },
            mutations: { retry: false },
        },
    });
}

function AllProviders({
    children,
    routerProps,
    queryClient,
}: {
    children: ReactNode;
    routerProps?: MemoryRouterProps;
    queryClient?: QueryClient;
}) {
    const client = queryClient ?? createTestQueryClient();

    return (
        <QueryClientProvider client={client}>
            <HelmetProvider>
                <MemoryRouter {...routerProps}>{children}</MemoryRouter>
            </HelmetProvider>
        </QueryClientProvider>
    );
}

function customRender(
    ui: ReactElement,
    options: CustomRenderOptions = {},
) {
    const { routerProps, queryClient, ...renderOptions } = options;

    return render(ui, {
        wrapper: ({ children }) => (
            <AllProviders routerProps={routerProps} queryClient={queryClient}>
                {children}
            </AllProviders>
        ),
        ...renderOptions,
    });
}

export { customRender as render, createTestQueryClient };
export { screen, waitFor, within, act, fireEvent, cleanup } from '@testing-library/react';
export { default as userEvent } from '@testing-library/user-event';
