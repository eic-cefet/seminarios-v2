import { ReactNode } from "react";
import { Favicon } from "@shared/components/Favicon";
import { Navbar } from "./Navbar";
import { Footer } from "./Footer";
import { SkipLink } from "./SkipLink";

interface LayoutProps {
    children: ReactNode;
}

export function Layout({ children }: LayoutProps) {
    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Favicon />
            <SkipLink />
            <Navbar />
            <main id="main-content" tabIndex={-1} className="flex-1">
                {children}
            </main>
            <Footer />
        </div>
    );
}
