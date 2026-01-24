import { useEffect } from "react";
import { Outlet } from "react-router-dom";
import { useAuth } from "@shared/contexts/AuthContext";
import { Favicon } from "@shared/components/Favicon";
import { Sidebar } from "./Sidebar";
import { MobileHeader } from "./MobileHeader";
import { Toaster } from "sonner";

export function AdminLayout() {
    const { user, isLoading, isAuthenticated } = useAuth();

    // Check if user has admin or teacher role
    const hasAdminAccess =
        user?.roles?.includes("admin") || user?.roles?.includes("teacher");

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            // Redirect to system SPA login (full page redirect since it's a different SPA)
            window.location.href = "/login";
        } else if (!isLoading && isAuthenticated && !hasAdminAccess) {
            // User is authenticated but doesn't have admin access
            window.location.href = "/";
        }
    }, [isLoading, isAuthenticated, hasAdminAccess]);

    if (isLoading) {
        return (
            <div className="flex h-screen items-center justify-center bg-background">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            </div>
        );
    }

    if (!isAuthenticated || !hasAdminAccess) {
        // Show loading while redirect happens
        return (
            <div className="flex h-screen items-center justify-center bg-background">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            </div>
        );
    }

    return (
        <div className="flex h-screen flex-col bg-background">
            <Favicon />
            {/* Mobile header - visible on mobile only */}
            <MobileHeader />

            <div className="flex flex-1 overflow-hidden">
                {/* Sidebar - hidden on mobile */}
                <div className="hidden lg:block">
                    <Sidebar />
                </div>

                {/* Main content */}
                <main className="flex-1 min-w-0 overflow-auto">
                    <div className="w-full max-w-7xl mx-auto p-6">
                        <Outlet />
                    </div>
                </main>
            </div>

            <Toaster richColors position="top-right" theme="light" />
        </div>
    );
}
