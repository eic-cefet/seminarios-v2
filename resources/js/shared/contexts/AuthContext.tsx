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

export type LoginResult =
    | { status: "authenticated" }
    | { status: "two_factor_required"; challengeToken: string; remember: boolean };

interface RegisterData {
    name: string;
    email: string;
    password: string;
    passwordConfirmation: string;
    courseSituation: "studying" | "graduated";
    courseRole: "Aluno" | "Professor" | "Outro";
    courseId?: number;
    acceptedTerms: boolean;
    acceptedPrivacy: boolean;
}

interface AuthContextValue {
    user: User | null;
    isLoading: boolean;
    isAuthenticated: boolean;
    login: (
        email: string,
        password: string,
        remember?: boolean,
    ) => Promise<LoginResult>;
    register: (data: RegisterData) => Promise<void>;
    logout: () => Promise<void>;
    exchangeCode: (code: string) => Promise<void>;
    refreshUser: () => Promise<void>;
    completeTwoFactor: (user: User) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const queryClient = useQueryClient();

    const invalidateAuthQueries = useCallback(() => {
        queryClient.invalidateQueries({ queryKey: ["profile"] });
        queryClient.invalidateQueries({ queryKey: ["registration"] });
    }, [queryClient]);

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
        async (
            email: string,
            password: string,
            remember = false,
        ): Promise<LoginResult> => {
            const response = await authApi.login({ email, password, remember });
            if ("two_factor" in response) {
                return {
                    status: "two_factor_required",
                    challengeToken: response.two_factor.challenge_token,
                    remember,
                };
            }
            setUser(response.user);
            invalidateAuthQueries();
            return { status: "authenticated" };
        },
        [invalidateAuthQueries],
    );

    const completeTwoFactor = useCallback(
        (user: User) => {
            setUser(user);
            invalidateAuthQueries();
        },
        [invalidateAuthQueries],
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
                accepted_terms: data.acceptedTerms,
                accepted_privacy_policy: data.acceptedPrivacy,
            });
            setUser(response.user);
            invalidateAuthQueries();
        },
        [invalidateAuthQueries],
    );

    const logout = useCallback(async () => {
        await authApi.logout();
        setUser(null);
        queryClient.clear();
    }, [queryClient]);

    const exchangeCode = useCallback(
        async (code: string) => {
            await authApi.exchangeCode(code);
            await refreshUser();
            invalidateAuthQueries();
        },
        [invalidateAuthQueries, refreshUser],
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
                completeTwoFactor,
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
