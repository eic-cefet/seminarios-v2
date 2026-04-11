import { useAuth } from "@shared/contexts/AuthContext";
import { Loader2 } from "lucide-react";
import { type ReactNode } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Layout } from "./Layout";

export function ProtectedRoute({ children }: { children: ReactNode }) {
    const { user, isLoading } = useAuth();
    const location = useLocation();

    if (!isLoading && !user) {
        if (location.pathname === "/login") {
            return null;
        }
        return (
            <Navigate
                to="/login"
                state={{ from: location.pathname + location.search }}
                replace
            />
        );
    }

    if (isLoading) {
        return (
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary-600" />
                </div>
            </Layout>
        );
    }

    return children;
}
