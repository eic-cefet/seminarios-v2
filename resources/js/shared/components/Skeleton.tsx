import type { HTMLAttributes } from "react";
import { cn } from "@shared/lib/utils";

interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
    tone?: "default" | "onPrimary";
}

export function Skeleton({ className, tone = "default", ...props }: SkeletonProps) {
    const base = tone === "onPrimary" ? "skeleton-on-primary" : "skeleton";
    return (
        <div
            aria-hidden="true"
            className={cn(base, "rounded-md", className)}
            {...props}
        />
    );
}
