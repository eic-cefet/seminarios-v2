import * as DropdownMenu from "@radix-ui/react-dropdown-menu";
import { useAuth } from "@shared/contexts/AuthContext";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import {
    ChevronDown,
    FileText,
    LogOut,
    Menu,
    Shield,
    Star,
    User,
    X,
} from "lucide-react";
import { useState } from "react";
import { Link } from "react-router-dom";
import { LoginModal } from "./LoginModal";

const navigation = [
    { name: "Início", href: "/" },
    { name: "Tópicos", href: "/topicos" },
    { name: "Apresentações", href: "/apresentacoes" },
    { name: "Workshops", href: "/workshops" },
];

export function Navbar() {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [loginModalOpen, setLoginModalOpen] = useState(false);
    const { user, logout } = useAuth();

    const isAdmin =
        user?.roles?.includes("admin") || user?.roles?.includes("teacher");

    return (
        <>
            <nav className="bg-white border-b border-gray-200">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        {/* Logo and Desktop Navigation */}
                        <div className="flex">
                            <Link to="/" className="flex items-center">
                                <span className="text-xl font-bold text-primary-600">
                                    Seminários EIC
                                </span>
                            </Link>
                            <div className="hidden sm:ml-8 sm:flex sm:space-x-8">
                                {navigation.map((item) => (
                                    <Link
                                        key={item.name}
                                        to={item.href}
                                        className="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-300 hover:text-gray-700 transition-colors"
                                    >
                                        {item.name}
                                    </Link>
                                ))}
                                {isAdmin && (
                                    <a
                                        href="/admin"
                                        className="inline-flex items-center gap-1.5 border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-primary-600 hover:border-primary-300 hover:text-primary-700 transition-colors"
                                    >
                                        <Shield className="h-4 w-4" />
                                        Admin
                                    </a>
                                )}
                            </div>
                        </div>

                        {/* Desktop Auth Section */}
                        <div className="hidden sm:ml-6 sm:flex sm:items-center">
                            {user ? (
                                <UserDropdown user={user} onLogout={logout} />
                            ) : (
                                <AuthDropdown
                                    onLoginClick={() => {
                                        analytics.event("login_modal_open");
                                        setLoginModalOpen(true);
                                    }}
                                />
                            )}
                        </div>

                        {/* Mobile menu button */}
                        <div className="flex items-center sm:hidden">
                            <button
                                type="button"
                                onClick={() => {
                                    if (!mobileMenuOpen) {
                                        analytics.event("navbar_menu_open");
                                    }
                                    setMobileMenuOpen(!mobileMenuOpen);
                                }}
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 cursor-pointer"
                            >
                                <span className="sr-only">Abrir menu</span>
                                {mobileMenuOpen ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile menu */}
                <div
                    className={cn(
                        "sm:hidden",
                        mobileMenuOpen ? "block" : "hidden",
                    )}
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                to={item.href}
                                className="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-primary-300 hover:bg-gray-50 hover:text-gray-700"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                {item.name}
                            </Link>
                        ))}
                        {isAdmin && (
                            <a
                                href="/admin"
                                className="flex items-center gap-2 border-l-4 border-primary-600 bg-primary-50 py-2 pl-3 pr-4 text-base font-medium text-primary-600"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                <Shield className="h-5 w-5" />
                                Admin
                            </a>
                        )}
                    </div>
                    <div className="border-t border-gray-200 pb-3 pt-4">
                        {user ? (
                            <MobileUserMenu user={user} onLogout={logout} />
                        ) : (
                            <div className="space-y-1 px-4">
                                <button
                                    onClick={() => {
                                        setMobileMenuOpen(false);
                                        analytics.event("login_modal_open", {
                                            source: "mobile_menu",
                                        });
                                        setLoginModalOpen(true);
                                    }}
                                    className="block w-full text-left py-2 text-base font-medium text-gray-500 hover:text-gray-700 cursor-pointer"
                                >
                                    Entrar
                                </button>
                                <Link
                                    to="/cadastro"
                                    className="block py-2 text-base font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                                    onClick={() => setMobileMenuOpen(false)}
                                >
                                    Criar conta
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </nav>

            <LoginModal
                open={loginModalOpen}
                onOpenChange={setLoginModalOpen}
            />
        </>
    );
}

interface AuthDropdownProps {
    onLoginClick: () => void;
}

function AuthDropdown({ onLoginClick }: AuthDropdownProps) {
    return (
        <div className="flex items-center gap-3">
            <button
                onClick={onLoginClick}
                className="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors cursor-pointer"
            >
                Entrar
            </button>
            <Link
                to="/cadastro"
                className="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
            >
                Criar conta
            </Link>
        </div>
    );
}

interface UserDropdownProps {
    user: { name: string; email: string };
    onLogout: () => void;
}

function UserDropdown({ user, onLogout }: UserDropdownProps) {
    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild>
                <button className="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors cursor-pointer">
                    <User className="h-4 w-4" />
                    <span>{user.name}</span>
                    <ChevronDown className="h-4 w-4" />
                </button>
            </DropdownMenu.Trigger>

            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    className="min-w-[200px] bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 p-1 z-50"
                    sideOffset={5}
                    align="end"
                >
                    <div className="px-3 py-2 border-b border-gray-100">
                        <p className="text-sm font-medium text-gray-900">
                            {user.name}
                        </p>
                        <p className="text-xs text-gray-500">{user.email}</p>
                    </div>
                    <DropdownMenu.Item asChild>
                        <Link
                            to="/perfil"
                            className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded cursor-pointer outline-none"
                        >
                            <User className="h-4 w-4" />
                            Meu perfil
                        </Link>
                    </DropdownMenu.Item>
                    <DropdownMenu.Item asChild>
                        <Link
                            to="/avaliar"
                            className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded cursor-pointer outline-none"
                        >
                            <Star className="h-4 w-4" />
                            Avaliar seminarios
                        </Link>
                    </DropdownMenu.Item>
                    <DropdownMenu.Item asChild>
                        <Link
                            to="/certificados"
                            className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded cursor-pointer outline-none"
                        >
                            <FileText className="h-4 w-4" />
                            Meus certificados
                        </Link>
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator className="h-px bg-gray-100 my-1" />
                    <DropdownMenu.Item asChild>
                        <button
                            onClick={() => {
                                analytics.event("logout");
                                onLogout();
                            }}
                            className="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded cursor-pointer outline-none"
                        >
                            <LogOut className="h-4 w-4" />
                            Sair
                        </button>
                    </DropdownMenu.Item>
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
}

interface MobileUserMenuProps {
    user: { name: string; email: string };
    onLogout: () => void;
}

function MobileUserMenu({ user, onLogout }: MobileUserMenuProps) {
    return (
        <div>
            <div className="flex items-center px-4">
                <div className="flex-shrink-0">
                    <div className="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                        <User className="h-5 w-5 text-primary-600" />
                    </div>
                </div>
                <div className="ml-3">
                    <div className="text-base font-medium text-gray-800">
                        {user.name}
                    </div>
                    <div className="text-sm font-medium text-gray-500">
                        {user.email}
                    </div>
                </div>
            </div>
            <div className="mt-3 space-y-1 px-4">
                <Link
                    to="/perfil"
                    className="block py-2 text-base font-medium text-gray-500 hover:text-gray-700 cursor-pointer"
                >
                    Meu perfil
                </Link>
                <Link
                    to="/avaliar"
                    className="flex items-center gap-2 py-2 text-base font-medium text-gray-500 hover:text-gray-700 cursor-pointer"
                >
                    <Star className="h-4 w-4" />
                    Avaliar seminarios
                </Link>
                <Link
                    to="/certificados"
                    className="flex items-center gap-2 py-2 text-base font-medium text-gray-500 hover:text-gray-700 cursor-pointer"
                >
                    <FileText className="h-4 w-4" />
                    Meus certificados
                </Link>
                <button
                    onClick={() => {
                        analytics.event("logout", { source: "mobile_menu" });
                        onLogout();
                    }}
                    className="block w-full text-left py-2 text-base font-medium text-red-600 hover:text-red-700 cursor-pointer"
                >
                    Sair
                </button>
            </div>
        </div>
    );
}
