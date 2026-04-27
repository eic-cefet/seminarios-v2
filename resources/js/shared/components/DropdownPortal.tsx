import { useLayoutEffect, useState, type ReactNode, type RefObject } from "react";
import { createPortal } from "react-dom";

interface DropdownPortalProps {
    children: ReactNode;
    anchorRef: RefObject<HTMLElement | null>;
    isOpen: boolean;
}

export function DropdownPortal({
    children,
    anchorRef,
    isOpen,
}: DropdownPortalProps) {
    const [position, setPosition] = useState({ top: 0, left: 0, width: 0 });
    const [container, setContainer] = useState<HTMLElement | null>(null);

    useLayoutEffect(() => {
        if (!isOpen || !anchorRef.current) return;

        // When the anchor lives inside a Radix Dialog (modal), portalling to
        // document.body lands outside the dialog's interaction zone — Radix's
        // modal pointer-events lock and onPointerDownOutside then swallow clicks,
        // and scrolling the dropdown is blocked. Portalling into the dialog
        // itself keeps the dropdown inside the modal's allowed surface.
        const dialog =
            anchorRef.current.closest<HTMLElement>('[role="dialog"]');
        const target = dialog ?? document.body;
        setContainer(target);

        const updatePosition = () => {
            const anchor = anchorRef.current;
            if (!anchor) return;

            const rect = anchor.getBoundingClientRect();

            if (target === document.body) {
                // Page-relative coordinates for absolute positioning in the body.
                setPosition({
                    top: rect.bottom + window.scrollY + 4,
                    left: rect.left + window.scrollX,
                    width: rect.width,
                });
            } else {
                // Radix Dialog uses transform: translate(...) for centering,
                // which makes the dialog the containing block for any absolute
                // descendant AND breaks position: fixed inside it. Compute
                // coordinates relative to the dialog's bounding box so
                // position: absolute lands correctly.
                const containerRect = target.getBoundingClientRect();
                setPosition({
                    top: rect.bottom - containerRect.top + 4,
                    left: rect.left - containerRect.left,
                    width: rect.width,
                });
            }
        };

        updatePosition();

        window.addEventListener("scroll", updatePosition, true);
        window.addEventListener("resize", updatePosition);

        return () => {
            window.removeEventListener("scroll", updatePosition, true);
            window.removeEventListener("resize", updatePosition);
        };
    }, [isOpen, anchorRef]);

    if (!isOpen || !container) return null;

    return createPortal(
        <div
            style={{
                position: "absolute",
                top: position.top,
                left: position.left,
                width: position.width,
                zIndex: 9999,
            }}
        >
            {children}
        </div>,
        container,
    );
}
