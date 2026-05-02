import * as React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { axe } from "jest-axe";
import { describe, it, expect } from "vitest";
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
    InputOTPSeparator,
} from "./InputOTP";

function Harness({
    value = "",
    maxLength = 4,
}: {
    value?: string;
    maxLength?: number;
}) {
    const [v, setV] = React.useState(value);
    return (
        <InputOTP
            maxLength={maxLength}
            value={v}
            onChange={setV}
            containerClassName="custom-container"
        >
            <InputOTPGroup>
                {Array.from({ length: maxLength }).map((_, i) => (
                    <InputOTPSlot key={i} index={i} />
                ))}
            </InputOTPGroup>
            <InputOTPSeparator />
        </InputOTP>
    );
}

describe("InputOTP", () => {
    it("renders an input with the configured maxLength", () => {
        const { container } = render(<Harness maxLength={6} />);
        const input = container.querySelector("input");
        expect(input).not.toBeNull();
        expect(input!.maxLength).toBe(6);
    });

    it("merges containerClassName with default classes", () => {
        const { container } = render(<Harness />);
        const wrapper = container.querySelector(".custom-container");
        expect(wrapper).not.toBeNull();
        expect(wrapper!.className).toContain("flex");
    });

    it("renders provided slot characters from value", () => {
        render(<Harness value="AB" maxLength={3} />);
        expect(screen.getByText("A")).toBeInTheDocument();
        expect(screen.getByText("B")).toBeInTheDocument();
    });

    it("shows the fake caret on the active empty slot when focused", async () => {
        const user = userEvent.setup();
        const { container } = render(<Harness maxLength={4} />);
        await user.click(container.querySelector("input")!);
        expect(container.querySelector(".animate-caret-blink")).not.toBeNull();
    });

    it("updates the slot character when the user types", async () => {
        const user = userEvent.setup();
        const { container } = render(<Harness maxLength={3} />);
        await user.click(container.querySelector("input")!);
        await user.keyboard("Q");
        expect(await screen.findByText("Q")).toBeInTheDocument();
    });
});

describe("InputOTPGroup", () => {
    it("renders a flex container with merged className and children", () => {
        const { container } = render(
            <InputOTPGroup className="extra">
                <span>kid</span>
            </InputOTPGroup>,
        );
        const root = container.firstChild as HTMLElement;
        expect(root.className).toContain("flex");
        expect(root.className).toContain("extra");
        expect(root.textContent).toBe("kid");
    });
});

describe("InputOTPSeparator", () => {
    it("exposes the separator role and an icon", () => {
        const { getByRole, container } = render(<InputOTPSeparator />);
        expect(getByRole("separator")).toBeInTheDocument();
        expect(container.querySelector("svg")).not.toBeNull();
    });

    it("has no axe violations on its own", async () => {
        const { container } = render(<InputOTPSeparator />);
        expect(await axe(container)).toHaveNoViolations();
    });
});
