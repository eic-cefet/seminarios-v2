import { renderHook, act, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider, useAuth } from './AuthContext';
import { authApi, ApiRequestError } from '@shared/api/client';
import type { ReactNode } from 'react';

vi.mock('@shared/api/client', () => ({
    authApi: {
        me: vi.fn(),
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
    },
    ApiRequestError: class extends Error {
        code: string;
        status: number;
        constructor(code: string, message: string, status: number) {
            super(message);
            this.name = 'ApiRequestError';
            this.code = code;
            this.status = status;
        }
    },
}));

const mockAuthApi = authApi as {
    me: ReturnType<typeof vi.fn>;
    login: ReturnType<typeof vi.fn>;
    register: ReturnType<typeof vi.fn>;
    logout: ReturnType<typeof vi.fn>;
    exchangeCode: ReturnType<typeof vi.fn>;
};

function createWrapper() {
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });
    return ({ children }: { children: ReactNode }) => (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>{children}</AuthProvider>
        </QueryClientProvider>
    );
}

describe('AuthContext', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('useAuth', () => {
        it('throws when used outside AuthProvider', () => {
            expect(() => {
                renderHook(() => useAuth());
            }).toThrow('useAuth must be used within an AuthProvider');
        });
    });

    describe('AuthProvider', () => {
        it('starts in loading state then resolves', async () => {
            mockAuthApi.me.mockRejectedValue(new ApiRequestError('unauthenticated', '', 401));

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            expect(result.current.isLoading).toBe(true);

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.user).toBeNull();
            expect(result.current.isAuthenticated).toBe(false);
        });

        it('fetches user on mount', async () => {
            const user = { id: 1, name: 'Test', email: 'test@test.com' };
            mockAuthApi.me.mockResolvedValue({ user });

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.user).toEqual(user);
            expect(result.current.isAuthenticated).toBe(true);
        });

        it('login sets user', async () => {
            mockAuthApi.me.mockRejectedValue(new ApiRequestError('unauthenticated', '', 401));
            const user = { id: 1, name: 'Test', email: 'test@test.com' };
            mockAuthApi.login.mockResolvedValue({ user });

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                await result.current.login('test@test.com', 'password');
            });

            expect(result.current.user).toEqual(user);
            expect(result.current.isAuthenticated).toBe(true);
        });

        it('logout clears user', async () => {
            const user = { id: 1, name: 'Test', email: 'test@test.com' };
            mockAuthApi.me.mockResolvedValue({ user });
            mockAuthApi.logout.mockResolvedValue({ message: 'Logged out' });

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(result.current.user).toEqual(user);
            });

            await act(async () => {
                await result.current.logout();
            });

            expect(result.current.user).toBeNull();
            expect(result.current.isAuthenticated).toBe(false);
        });

        it('register sets user', async () => {
            mockAuthApi.me.mockRejectedValue(new ApiRequestError('unauthenticated', '', 401));
            const user = { id: 1, name: 'Test', email: 'test@test.com' };
            mockAuthApi.register.mockResolvedValue({ user });

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                await result.current.register({
                    name: 'Test',
                    email: 'test@test.com',
                    password: 'password',
                    passwordConfirmation: 'password',
                    courseSituation: 'studying',
                    courseRole: 'Aluno',
                });
            });

            expect(result.current.user).toEqual(user);
        });

        it('exchangeCode fetches user after exchange', async () => {
            const user = { id: 1, name: 'Test', email: 'test@test.com' };
            mockAuthApi.me
                .mockRejectedValueOnce(new ApiRequestError('unauthenticated', '', 401))
                .mockResolvedValueOnce({ user });
            mockAuthApi.exchangeCode.mockResolvedValue({});

            const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                await result.current.exchangeCode('code123');
            });

            expect(mockAuthApi.exchangeCode).toHaveBeenCalledWith('code123');
            expect(result.current.user).toEqual(user);
        });
    });
});
