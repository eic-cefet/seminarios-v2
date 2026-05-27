import { useAuth } from "@shared/contexts/AuthContext";
import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router-dom";

interface RequireCompleteProfileProps {
    children: ReactNode;
}

export function RequireCompleteProfile({
    children,
}: RequireCompleteProfileProps) {
    const { user } = useAuth();
    const location = useLocation();

    if (
        user?.needs_profile_completion &&
        location.pathname !== "/completar-perfil"
    ) {
        return <Navigate to="/completar-perfil" replace />;
    }

    return <>{children}</>;
}
