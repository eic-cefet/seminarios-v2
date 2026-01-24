import { NavLink } from "react-router-dom";
import {
    LayoutDashboard,
    Users,
    Presentation,
    ClipboardList,
    ChevronDown,
    LogOut,
    ArrowLeft,
    FileBarChart,
} from "lucide-react";
import { cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { Separator } from "../ui/separator";
import * as Collapsible from "@radix-ui/react-collapsible";
import { useState } from "react";

interface NavItem {
    label: string;
    href?: string;
    icon: React.ComponentType<{ className?: string }>;
    adminOnly?: boolean;
    children?: { label: string; href: string; adminOnly?: boolean }[];
}

const navigation: NavItem[] = [
    { label: "Dashboard", href: "/", icon: LayoutDashboard },
    { label: "Usuários", href: "/users", icon: Users, adminOnly: true },
    {
        label: "Seminários",
        icon: Presentation,
        children: [
            { label: "Lista", href: "/seminars" },
            { label: "Locais", href: "/locations", adminOnly: true },
            { label: "Tópicos", href: "/subjects", adminOnly: true },
        ],
    },
    {
        label: "Inscrições",
        href: "/registrations",
        icon: ClipboardList,
        adminOnly: true,
    },
    {
        label: "Relatórios",
        icon: FileBarChart,
        adminOnly: true,
        children: [
            {
                label: "Relatório Semestral",
                href: "/reports/semestral",
                adminOnly: true,
            },
        ],
    },
];

export function Sidebar() {
    const { user, logout } = useAuth();
    const isAdmin = user?.roles?.includes("admin");
    const [openMenus, setOpenMenus] = useState<string[]>(["Seminários"]);

    const toggleMenu = (label: string) => {
        setOpenMenus((prev) =>
            prev.includes(label)
                ? prev.filter((l) => l !== label)
                : [...prev, label],
        );
    };

    const filteredNav = navigation.filter((item) => {
        if (item.adminOnly && !isAdmin) return false;
        return true;
    });

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
                        const visibleChildren = item.children.filter(
                            (child) => !child.adminOnly || isAdmin,
                        );
                        if (visibleChildren.length === 0) return null;

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
                                    {visibleChildren.map((child) => (
                                        <NavLink
                                            key={child.href}
                                            to={child.href}
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
