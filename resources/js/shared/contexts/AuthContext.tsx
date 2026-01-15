import {
    createContext,
    useContext,
    useState,
    useCallback,
    useEffect,
    type ReactNode,
} from "react";
import { useQueryClient } from "@tanstack/react-query";
import { authApi, ApiRequestError } from "@shared/api/client";
import type { User } from "@shared/types";

interface RegisterData {
    name: string;
    email: string;
    password: string;
    passwordConfirmation: string;
    courseSituation: "studying" | "graduated";
    courseRole: "Aluno" | "Professor" | "Outro";
    courseId?: number;
}

interface AuthContextValue {
    user: User | null;
    isLoading: boolean;
    isAuthenticated: boolean;
    login: (
        email: string,
        password: string,
        remember?: boolean
    ) => Promise<void>;
    register: (data: RegisterData) => Promise<void>;
    logout: () => Promise<void>;
    exchangeCode: (code: string) => Promise<void>;
    refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const queryClient = useQueryClient();

    const refreshUser = useCallback(async () => {
        try {
            const response = await authApi.me();
            setUser(response.user);
        } catch (error) {
            if (error instanceof ApiRequestError && error.status === 401) {
                setUser(null);
            }
        }
    }, []);

    useEffect(() => {
        refreshUser().finally(() => setIsLoading(false));
    }, [refreshUser]);

    const login = useCallback(
        async (email: string, password: string, remember = false) => {
            const response = await authApi.login({ email, password, remember });
            setUser(response.user);
            queryClient.invalidateQueries();
        },
        [queryClient]
    );

    const register = useCallback(
        async (data: RegisterData) => {
            const response = await authApi.register({
                name: data.name,
                email: data.email,
                password: data.password,
                password_confirmation: data.passwordConfirmation,
                course_situation: data.courseSituation,
                course_role: data.courseRole,
                course_id: data.courseId,
            });
            setUser(response.user);
            queryClient.invalidateQueries();
        },
        [queryClient]
    );

    const logout = useCallback(async () => {
        await authApi.logout();
        setUser(null);
        queryClient.clear();
    }, [queryClient]);

    const exchangeCode = useCallback(
        async (code: string) => {
            // Exchange the code to establish the session
            await authApi.exchangeCode(code);
            // Fetch user data via /me to ensure consistent data shape
            await refreshUser();
            queryClient.invalidateQueries();
        },
        [queryClient, refreshUser]
    );

    return (
        <AuthContext.Provider
            value={{
                user,
                isLoading,
                isAuthenticated: !!user,
                login,
                register,
                logout,
                exchangeCode,
                refreshUser,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error("useAuth must be used within an AuthProvider");
    }
    return context;
}
