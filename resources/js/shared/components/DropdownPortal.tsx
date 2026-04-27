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
        setContainer(dialog ?? document.body);

        const updatePosition = () => {
            const anchor = anchorRef.current;
            if (!anchor) return;

            // Viewport-fixed positioning works regardless of which container we
            // portalled into and survives scrolling without recomputing offsets.
            const rect = anchor.getBoundingClientRect();
            setPosition({
                top: rect.bottom + 4,
                left: rect.left,
                width: rect.width,
            });
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
                position: "fixed",
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
