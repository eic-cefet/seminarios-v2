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

    useLayoutEffect(() => {
        if (!isOpen || !anchorRef.current) return;

        const updatePosition = () => {
            const anchor = anchorRef.current;
            if (!anchor) return;

            const rect = anchor.getBoundingClientRect();
            setPosition({
                top: rect.bottom + window.scrollY + 4,
                left: rect.left + window.scrollX,
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

    if (!isOpen) return null;

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
        document.body,
    );
}
