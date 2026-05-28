import { render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: vi.fn(),
}));

import { useAuth } from "@shared/contexts/AuthContext";
import { RequireCompleteProfile } from "./RequireCompleteProfile";

const useAuthMock = vi.mocked(useAuth);

const renderAt = (initialPath: string) =>
    render(
        <MemoryRouter initialEntries={[initialPath]}>
            <Routes>
                <Route
                    path="/topicos"
                    element={
                        <RequireCompleteProfile>
                            <div>SUBJECTS</div>
                        </RequireCompleteProfile>
                    }
                />
                <Route
                    path="/completar-perfil"
                    element={<div>COMPLETE_PROFILE</div>}
                />
            </Routes>
        </MemoryRouter>,
    );

describe("RequireCompleteProfile", () => {
    it("redirects to /completar-perfil when the user has needs_profile_completion=true", () => {
        useAuthMock.mockReturnValue({
            user: {
                id: 1,
                name: "Maria",
                email: "maria@example.test",
                needs_profile_completion: true,
            },
        } as never);

        renderAt("/topicos");

        expect(screen.getByText("COMPLETE_PROFILE")).toBeInTheDocument();
        expect(screen.queryByText("SUBJECTS")).not.toBeInTheDocument();
    });

    it("renders children when the user has a complete profile", () => {
        useAuthMock.mockReturnValue({
            user: {
                id: 1,
                name: "Maria Silva",
                email: "maria@example.test",
                needs_profile_completion: false,
            },
        } as never);

        renderAt("/topicos");

        expect(screen.getByText("SUBJECTS")).toBeInTheDocument();
    });

    it("renders children when there is no authenticated user", () => {
        useAuthMock.mockReturnValue({ user: null } as never);

        renderAt("/topicos");

        expect(screen.getByText("SUBJECTS")).toBeInTheDocument();
    });

    it("does not redirect when already on /completar-perfil", () => {
        useAuthMock.mockReturnValue({
            user: {
                id: 1,
                name: "Maria",
                email: "maria@example.test",
                needs_profile_completion: true,
            },
        } as never);

        render(
            <MemoryRouter initialEntries={["/completar-perfil"]}>
                <Routes>
                    <Route
                        path="/completar-perfil"
                        element={
                            <RequireCompleteProfile>
                                <div>COMPLETE_FORM</div>
                            </RequireCompleteProfile>
                        }
                    />
                </Routes>
            </MemoryRouter>,
        );

        expect(screen.getByText("COMPLETE_FORM")).toBeInTheDocument();
    });
});
