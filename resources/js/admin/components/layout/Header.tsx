import { Menu } from "lucide-react";
import { Button } from "../ui/button";

interface HeaderProps {
    onMenuClick?: () => void;
    title?: string;
}

export function Header({ onMenuClick, title }: HeaderProps) {
    return (
        <header className="sticky top-0 z-40 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-6">
            <Button
                variant="ghost"
                size="icon"
                className="lg:hidden"
                onClick={onMenuClick}
            >
                <Menu className="h-5 w-5" />
                <span className="sr-only">Toggle menu</span>
            </Button>
            {title && (
                <h1 className="text-lg font-semibold text-gray-900">{title}</h1>
            )}
        </header>
    );
}
