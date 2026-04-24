import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@/test/test-utils";
import DataRights from "./DataRights";

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: vi.fn(() => ({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
        completeTwoFactor: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

describe("DataRights", () => {
    it("describes how to export data, delete account, and contact DPO", () => {
        render(<DataRights />);
        expect(screen.getByText(/exportar seus dados/i)).toBeInTheDocument();
        expect(screen.getByText(/excluir sua conta/i)).toBeInTheDocument();
        expect(screen.getByText(/encarregado/i)).toBeInTheDocument();
    });
});
