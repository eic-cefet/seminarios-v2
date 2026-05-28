import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";

vi.mock("@shared/api/client", () => {
    class ApiRequestError extends Error {
        public readonly code: string;
        public readonly status: number;
        public readonly errors?: Record<string, string[]>;
        constructor(
            code: string,
            message: string,
            status: number,
            errors?: Record<string, string[]>,
        ) {
            super(message);
            this.name = "ApiRequestError";
            this.code = code;
            this.status = status;
            this.errors = errors;
        }
    }

    return {
        profileApi: {
            update: vi.fn().mockResolvedValue({
                user: {
                    id: 1,
                    name: "Maria Silva",
                    email: "maria@example.test",
                    needs_profile_completion: false,
                },
            }),
        },
        ApiRequestError,
    };
});

const refreshUser = vi.fn().mockResolvedValue(undefined);

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: () => ({
        user: {
            id: 1,
            name: "Maria",
            email: "maria@example.test",
            needs_profile_completion: true,
        },
        refreshUser,
    }),
}));

import { CompleteProfile } from "./CompleteProfile";

const renderPage = () =>
    render(
        <QueryClientProvider client={new QueryClient()}>
            <MemoryRouter initialEntries={["/completar-perfil"]}>
                <Routes>
                    <Route
                        path="/completar-perfil"
                        element={<CompleteProfile />}
                    />
                    <Route path="/" element={<div>HOME</div>} />
                </Routes>
            </MemoryRouter>
        </QueryClientProvider>,
    );

describe("CompleteProfile", () => {
    it("requires a full name before submitting", async () => {
        const { profileApi } = await import("@shared/api/client");
        vi.mocked(profileApi.update).mockClear();
        renderPage();

        fireEvent.change(screen.getByLabelText(/nome completo/i), {
            target: { value: "Maria" },
        });
        fireEvent.click(
            screen.getByRole("button", { name: /continuar|salvar/i }),
        );

        expect(
            await screen.findByText(/Informe seu nome completo/i),
        ).toBeInTheDocument();
        expect(profileApi.update).not.toHaveBeenCalled();
    });

    it("submits a valid full name and navigates home", async () => {
        const { profileApi } = await import("@shared/api/client");
        vi.mocked(profileApi.update).mockClear();
        renderPage();

        fireEvent.change(screen.getByLabelText(/nome completo/i), {
            target: { value: "Maria Silva" },
        });
        fireEvent.click(
            screen.getByRole("button", { name: /continuar|salvar/i }),
        );

        await waitFor(() =>
            expect(profileApi.update).toHaveBeenCalledWith(
                expect.objectContaining({ name: "Maria Silva" }),
            ),
        );
        await screen.findByText("HOME");
    });

    it("surfaces a server-side validation error from the name field", async () => {
        const { profileApi, ApiRequestError } = await import(
            "@shared/api/client"
        );
        vi.mocked(profileApi.update).mockClear();
        vi.mocked(profileApi.update).mockRejectedValueOnce(
            new ApiRequestError("validation_failed", "Invalid", 422, {
                name: ["O nome completo é inválido."],
            }),
        );
        renderPage();

        fireEvent.change(screen.getByLabelText(/nome completo/i), {
            target: { value: "Maria Silva" },
        });
        fireEvent.click(
            screen.getByRole("button", { name: /continuar|salvar/i }),
        );

        expect(
            await screen.findByText(/O nome completo é inválido\./),
        ).toBeInTheDocument();
    });

    it("falls back to a generic error message on non-validation failures", async () => {
        const { profileApi } = await import("@shared/api/client");
        vi.mocked(profileApi.update).mockClear();
        vi.mocked(profileApi.update).mockRejectedValueOnce(
            new Error("Network down"),
        );
        renderPage();

        fireEvent.change(screen.getByLabelText(/nome completo/i), {
            target: { value: "Maria Silva" },
        });
        fireEvent.click(
            screen.getByRole("button", { name: /continuar|salvar/i }),
        );

        expect(await screen.findByText(/Network down/)).toBeInTheDocument();
    });
});
