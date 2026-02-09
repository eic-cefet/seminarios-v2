import { useState } from "react";
import { NavLink } from "react-router-dom";
import { Menu, X, ChevronDown, LogOut, ArrowLeft } from "lucide-react";
import { cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { ROLES, hasRole } from "@shared/lib/roles";
import { adminNavigation, filterNavigation } from "../../config/navigation";

export function MobileHeader() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [expandedMenus, setExpandedMenus] = useState<string[]>(["Seminários"]);
    const { user, logout } = useAuth();
    const isAdmin = hasRole(user?.roles, ROLES.ADMIN);

    const filteredNav = filterNavigation(adminNavigation, isAdmin ?? false);

    const toggleExpanded = (label: string) => {
        setExpandedMenus((prev) =>
            prev.includes(label)
                ? prev.filter((l) => l !== label)
                : [...prev, label],
        );
    };

    const closeMenu = () => setMenuOpen(false);

    return (
        <header className="lg:hidden bg-muted/50 border-b border-border">
            <div className="flex h-14 items-center justify-between px-4">
                {/* Logo */}
                <div className="flex items-center gap-2">
                    <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold text-xs">
                        EIC
                    </div>
                    <div>
                        <p className="font-semibold text-foreground text-sm">
                            CEFET-RJ
                        </p>
                    </div>
                </div>

                {/* Menu button */}
                <button
                    type="button"
                    onClick={() => setMenuOpen(!menuOpen)}
                    className="inline-flex items-center justify-center rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                >
                    <span className="sr-only">Abrir menu</span>
                    {menuOpen ? (
                        <X className="h-6 w-6" />
                    ) : (
                        <Menu className="h-6 w-6" />
                    )}
                </button>
            </div>

            {/* Mobile menu */}
            <div
                className={cn(
                    "border-t border-border",
                    menuOpen ? "block" : "hidden",
                )}
            >
                <nav className="space-y-1 px-3 py-4">
                    {filteredNav.map((item) => {
                        if (item.children) {
                            const isExpanded = expandedMenus.includes(item.label);

                            return (
                                <div key={item.label}>
                                    <button
                                        onClick={() => toggleExpanded(item.label)}
                                        className="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <span className="flex items-center gap-3">
                                            <item.icon className="h-5 w-5" />
                                            {item.label}
                                        </span>
                                        <ChevronDown
                                            className={cn(
                                                "h-4 w-4 transition-transform",
                                                isExpanded && "rotate-180",
                                            )}
                                        />
                                    </button>
                                    {isExpanded && (
                                        <div className="mt-1 space-y-1 pl-11">
                                            {item.children.map((child) => (
                                                <NavLink
                                                    key={child.href}
                                                    to={child.href}
                                                    end={child.end}
                                                    onClick={closeMenu}
                                                    className={({ isActive }) =>
                                                        cn(
                                                            "block rounded-lg px-3 py-2 text-sm",
                                                            isActive
                                                                ? "bg-accent text-accent-foreground font-medium"
                                                                : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
                                                        )
                                                    }
                                                >
                                                    {child.label}
                                                </NavLink>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        return (
                            <NavLink
                                key={item.href}
                                to={item.href!}
                                end={item.href === "/"}
                                onClick={closeMenu}
                                className={({ isActive }) =>
                                    cn(
                                        "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium",
                                        isActive
                                            ? "bg-accent text-accent-foreground"
                                            : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
                                    )
                                }
                            >
                                <item.icon className="h-5 w-5" />
                                {item.label}
                            </NavLink>
                        );
                    })}
                </nav>

                {/* User section */}
                <div className="border-t border-border px-3 py-4">
                    <div className="flex items-center gap-3 mb-3 px-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-muted-foreground font-medium text-sm">
                            {user?.name?.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-foreground truncate">
                                {user?.name}
                            </p>
                            <p className="text-xs text-muted-foreground truncate">
                                {user?.email}
                            </p>
                        </div>
                    </div>
                    <div className="space-y-1">
                        <a
                            href={app.BASE_PATH || "/"}
                            onClick={closeMenu}
                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Voltar ao Site
                        </a>
                        <button
                            onClick={() => {
                                closeMenu();
                                logout();
                            }}
                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        >
                            <LogOut className="h-4 w-4" />
                            Sair
                        </button>
                    </div>
                </div>
            </div>
        </header>
    );
}
