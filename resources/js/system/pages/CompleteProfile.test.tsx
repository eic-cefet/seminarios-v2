import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";

vi.mock("@shared/api/client", () => ({
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
}));

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
});
