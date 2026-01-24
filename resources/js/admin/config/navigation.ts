import {
    LayoutDashboard,
    Users,
    Presentation,
    ClipboardList,
    FileBarChart,
} from "lucide-react";

export interface NavChild {
    label: string;
    href: string;
    adminOnly?: boolean;
}

export interface NavItem {
    label: string;
    href?: string;
    icon: React.ComponentType<{ className?: string }>;
    adminOnly?: boolean;
    children?: NavChild[];
}

export const adminNavigation: NavItem[] = [
    { label: "Dashboard", href: "/", icon: LayoutDashboard },
    { label: "Usuários", href: "/users", icon: Users, adminOnly: true },
    {
        label: "Seminários",
        icon: Presentation,
        children: [
            { label: "Lista", href: "/seminars" },
            { label: "Novo Seminário", href: "/seminars/new" },
            { label: "Workshops", href: "/workshops", adminOnly: true },
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

/**
 * Filters navigation items based on user's admin status
 */
export function filterNavigation(items: NavItem[], isAdmin: boolean): NavItem[] {
    return items
        .filter((item) => !item.adminOnly || isAdmin)
        .map((item) => {
            if (item.children) {
                const filteredChildren = item.children.filter(
                    (child) => !child.adminOnly || isAdmin,
                );
                if (filteredChildren.length === 0) {
                    return null;
                }
                return { ...item, children: filteredChildren };
            }
            return item;
        })
        .filter((item): item is NavItem => item !== null);
}
