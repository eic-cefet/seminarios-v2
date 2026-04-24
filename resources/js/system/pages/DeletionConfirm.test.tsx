import { StrictMode } from "react";
import { render, screen, waitFor } from "@/test/test-utils";
import DeletionConfirm from "./DeletionConfirm";

const mockConfirmDeletion = vi.fn();

vi.mock("@shared/api/client", () => ({
    ApiRequestError: class extends Error {
        constructor(
            public code: string,
            message: string,
            public status: number,
        ) {
            super(message);
            this.name = "ApiRequestError";
        }
    },
    dataPrivacyApi: {
        confirmDeletion: (token: string) => mockConfirmDeletion(token),
    },
}));

vi.mock("@shared/lib/errors", () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
}));

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: vi.fn(() => ({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        login: vi.fn(),
        logout: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => (
        <>{children}</>
    ),
}));

const TOKEN = "a".repeat(64);

const mockUseParams = vi.fn((): { token?: string } => ({ token: TOKEN }));

vi.mock("react-router-dom", async () => {
    const actual = await vi.importActual("react-router-dom");
    return { ...actual, useParams: () => mockUseParams() };
});

describe("DeletionConfirm", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockUseParams.mockReturnValue({ token: TOKEN });
    });

    it("shows loading state initially", () => {
        mockConfirmDeletion.mockReturnValue(new Promise(() => {}));
        render(<DeletionConfirm />);
        expect(
            screen.getByText(/confirmando sua solicitação/i),
        ).toBeInTheDocument();
    });

    it("shows success state with scheduled date on valid token", async () => {
        mockConfirmDeletion.mockResolvedValue({
            message: "Sua conta será excluída.",
            scheduled_for: "2026-05-23T00:00:00Z",
        });
        render(<DeletionConfirm />);
        await waitFor(() =>
            expect(
                screen.getByText(/exclusão agendada/i),
            ).toBeInTheDocument(),
        );
        expect(screen.getByText(/2026-05-23/)).toBeInTheDocument();
        expect(
            screen.getByRole("link", { name: /voltar ao perfil/i }),
        ).toBeInTheDocument();
    });

    it("shows error state when token is invalid", async () => {
        mockConfirmDeletion.mockRejectedValue(
            new Error("O link de confirmação é inválido ou expirou."),
        );
        render(<DeletionConfirm />);
        await waitFor(() =>
            expect(
                screen.getByText(/não foi possível confirmar/i),
            ).toBeInTheDocument(),
        );
        expect(
            screen.getByText(/o link de confirmação é inválido ou expirou/i),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("link", { name: /voltar ao perfil/i }),
        ).toBeInTheDocument();
    });

    it("shows error state when token param is missing", async () => {
        mockUseParams.mockReturnValue({ token: undefined } as { token?: string });
        render(<DeletionConfirm />);
        await waitFor(() =>
            expect(
                screen.getByText(/não foi possível confirmar/i),
            ).toBeInTheDocument(),
        );
        expect(screen.getByText(/link inválido/i)).toBeInTheDocument();
    });

    it("calls confirmDeletion with the token from route params", async () => {
        mockConfirmDeletion.mockResolvedValue({
            message: "ok",
            scheduled_for: "2026-05-23T00:00:00Z",
        });
        render(<DeletionConfirm />);
        await waitFor(() => expect(mockConfirmDeletion).toHaveBeenCalledWith(TOKEN));
    });

    it("does not double-invoke the API when rendered inside StrictMode", async () => {
        mockConfirmDeletion.mockResolvedValue({
            message: "ok",
            scheduled_for: "2026-05-23T00:00:00Z",
        });
        render(
            <StrictMode>
                <DeletionConfirm />
            </StrictMode>,
        );
        await waitFor(() =>
            expect(screen.getByText(/exclusão agendada/i)).toBeInTheDocument(),
        );
        // StrictMode invokes effects twice; the guard must ensure only one API call is made
        expect(mockConfirmDeletion).toHaveBeenCalledTimes(1);
    });
});
