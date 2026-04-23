import { ReactNode } from "react";

interface LoadingRegionProps {
    label: string;
    children: ReactNode;
    className?: string;
}

export function LoadingRegion({ label, children, className }: LoadingRegionProps) {
    return (
        <div
            role="status"
            aria-live="polite"
            aria-busy="true"
            aria-label={label}
            className={className}
        >
            {children}
        </div>
    );
}
