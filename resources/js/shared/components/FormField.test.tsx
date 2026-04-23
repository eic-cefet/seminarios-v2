import { render, screen } from "@testing-library/react";
import { axe } from "jest-axe";
import { describe, it, expect } from "vitest";
import { FormField } from "./FormField";

describe("FormField", () => {
    it("associates label with input via htmlFor/id", () => {
        render(<FormField id="email" label="E-mail" type="email" />);
        const input = screen.getByLabelText("E-mail");
        expect(input).toHaveAttribute("id", "email");
    });

    it("marks required fields with aria-required", () => {
        render(<FormField id="email" label="E-mail" required />);
        expect(screen.getByLabelText(/e-mail/i)).toHaveAttribute("aria-required", "true");
    });

    it("exposes aria-invalid and links error via aria-describedby", () => {
        render(<FormField id="email" label="E-mail" error="E-mail inválido" />);
        const input = screen.getByLabelText("E-mail");
        expect(input).toHaveAttribute("aria-invalid", "true");
        const errorId = input.getAttribute("aria-describedby");
        expect(errorId).toBeTruthy();
        expect(screen.getByText("E-mail inválido")).toHaveAttribute("id", errorId!);
        expect(screen.getByText("E-mail inválido")).toHaveAttribute("role", "alert");
    });

    it("links help text via aria-describedby", () => {
        render(<FormField id="pw" label="Senha" hint="Mínimo de 8 caracteres" />);
        const input = screen.getByLabelText("Senha");
        const describedBy = input.getAttribute("aria-describedby");
        expect(describedBy).toContain("pw-hint");
    });

    it("has no axe violations when valid", async () => {
        const { container } = render(<FormField id="email" label="E-mail" />);
        expect(await axe(container)).toHaveNoViolations();
    });
});
