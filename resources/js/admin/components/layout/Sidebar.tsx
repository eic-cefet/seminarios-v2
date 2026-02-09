import { useState } from "react";
import { NavLink } from "react-router-dom";
import { ChevronDown, LogOut, ArrowLeft } from "lucide-react";
import * as Collapsible from "@radix-ui/react-collapsible";
import { cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { ROLES, hasRole } from "@shared/lib/roles";
import { Separator } from "../ui/separator";
import { adminNavigation, filterNavigation } from "../../config/navigation";

export function Sidebar() {
    const { user, logout } = useAuth();
    const isAdmin = hasRole(user?.roles, ROLES.ADMIN);
    const [openMenus, setOpenMenus] = useState<string[]>(["Seminários"]);

    const toggleMenu = (label: string) => {
        setOpenMenus((prev) =>
            prev.includes(label)
                ? prev.filter((l) => l !== label)
                : [...prev, label],
        );
    };

    const filteredNav = filterNavigation(adminNavigation, isAdmin ?? false);

    return (
        <aside className="flex h-screen w-64 flex-col border-r border-border bg-muted/50">
            {/* Logo */}
            <div className="flex h-16 items-center gap-2 px-6">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold text-sm">
                    EIC
                </div>
                <div>
                    <p className="font-semibold text-foreground text-sm">
                        CEFET-RJ
                    </p>
                    <p className="text-xs text-muted-foreground">Seminários</p>
                </div>
            </div>

            <Separator />

            {/* Navigation */}
            <nav className="flex-1 space-y-1 px-3 py-4">
                {filteredNav.map((item) => {
                    if (item.children) {
                        return (
                            <Collapsible.Root
                                key={item.label}
                                open={openMenus.includes(item.label)}
                                onOpenChange={() => toggleMenu(item.label)}
                            >
                                <Collapsible.Trigger className="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground">
                                    <span className="flex items-center gap-3">
                                        <item.icon className="h-5 w-5" />
                                        {item.label}
                                    </span>
                                    <ChevronDown
                                        className={cn(
                                            "h-4 w-4 transition-transform",
                                            openMenus.includes(item.label) &&
                                                "rotate-180",
                                        )}
                                    />
                                </Collapsible.Trigger>
                                <Collapsible.Content className="mt-1 space-y-1 pl-11">
                                    {item.children.map((child) => (
                                        <NavLink
                                            key={child.href}
                                            to={child.href}
                                            end={child.end}
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
                                </Collapsible.Content>
                            </Collapsible.Root>
                        );
                    }

                    return (
                        <NavLink
                            key={item.href}
                            to={item.href!}
                            end={item.href === "/"}
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

            <Separator />

            {/* User section */}
            <div className="p-4">
                <div className="flex items-center gap-3 mb-3">
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
                        className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Voltar ao Site
                    </a>
                    <button
                        onClick={() => logout()}
                        className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                    >
                        <LogOut className="h-4 w-4" />
                        Sair
                    </button>
                </div>
            </div>
        </aside>
    );
}
