import { formatDate, cn } from "@shared/lib/utils";
import type { BadgeTier, GamificationBadge } from "@shared/types";
import {
    AudioLines,
    Award,
    BadgeCheck,
    Brain,
    CalendarDays,
    CalendarRange,
    Compass,
    CopyPlus,
    Crown,
    Flame,
    Footprints,
    Gauge,
    GraduationCap,
    Hammer,
    HeartHandshake,
    Landmark,
    Medal,
    MessageCircleMore,
    MessageSquare,
    MessagesSquare,
    Milestone,
    Network,
    Repeat2,
    Route,
    Search,
    Shapes,
    Sparkles,
    Trophy,
    Users,
    Wrench,
    type LucideIcon,
} from "lucide-react";

interface BadgeCardProps {
    badge: GamificationBadge;
}

const BADGE_ICONS: Record<string, LucideIcon> = {
    Footprints,
    Flame,
    Medal,
    Landmark,
    Crown,
    Trophy,
    Search,
    Compass,
    Network,
    Brain,
    Shapes,
    Users,
    AudioLines,
    CopyPlus,
    Gauge,
    CalendarDays,
    CalendarRange,
    GraduationCap,
    Sparkles,
    Repeat2,
    Route,
    Milestone,
    Wrench,
    Hammer,
    BadgeCheck,
    MessageSquare,
    MessagesSquare,
    MessageCircleMore,
    HeartHandshake,
    Award,
};

const tierDetails: Record<BadgeTier, { label: string; styles: string }> = {
    bronze: {
        label: "Bronze",
        styles: "border-amber-700 bg-amber-50 text-amber-950 dark:border-amber-500 dark:bg-amber-950 dark:text-amber-100",
    },
    silver: {
        label: "Prata",
        styles: "border-slate-500 bg-slate-100 text-slate-900 dark:border-slate-400 dark:bg-slate-800 dark:text-slate-100",
    },
    gold: {
        label: "Ouro",
        styles: "border-yellow-600 bg-yellow-50 text-yellow-950 dark:border-yellow-400 dark:bg-yellow-950 dark:text-yellow-100",
    },
    platinum: {
        label: "Platina",
        styles: "border-cyan-700 bg-cyan-50 text-cyan-950 dark:border-cyan-400 dark:bg-cyan-950 dark:text-cyan-100",
    },
    special: {
        label: "Especial",
        styles: "border-violet-700 bg-violet-50 text-violet-950 dark:border-violet-400 dark:bg-violet-950 dark:text-violet-100",
    },
};

export function BadgeCard({ badge }: BadgeCardProps) {
    const Icon = BADGE_ICONS[badge.icon] ?? Award;
    const tier = tierDetails[badge.tier];
    const status = badge.earned ? "Conquistada" : "Bloqueada";

    return (
        <article
            aria-label={`${badge.name}, ${status}`}
            className={cn(
                "flex h-full flex-col rounded-xl border bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:bg-gray-900",
                badge.earned
                    ? "border-gray-200 dark:border-gray-700"
                    : "border-dashed border-gray-300 dark:border-gray-700",
            )}
        >
            <div className="flex items-start justify-between gap-4">
                <div
                    className={cn(
                        "flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2",
                        tier.styles,
                        !badge.earned && "grayscale opacity-50",
                    )}
                >
                    <Icon className="h-6 w-6" aria-hidden="true" />
                </div>
                <div className="flex flex-wrap justify-end gap-2">
                    <span className={cn("rounded-full border px-2.5 py-1 text-xs font-bold", tier.styles)}>
                        {tier.label}
                    </span>
                    <span
                        className={cn(
                            "rounded-full px-2.5 py-1 text-xs font-semibold",
                            badge.earned
                                ? "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
                                : "bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100",
                        )}
                    >
                        {status}
                    </span>
                </div>
            </div>

            <h3 className="mt-4 text-lg font-bold text-gray-900 dark:text-gray-100">
                {badge.name}
            </h3>
            <p className="mt-2 flex-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                {badge.description}
            </p>

            {badge.earned && badge.earned_at ? (
                <p className="mt-4 border-t border-gray-100 pt-4 text-sm font-medium text-emerald-800 dark:border-gray-800 dark:text-emerald-300">
                    Conquistada em {formatDate(badge.earned_at)}
                </p>
            ) : (
                <p className="mt-4 border-t border-gray-100 pt-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:text-gray-300">
                    Requisito: {badge.description}
                </p>
            )}
        </article>
    );
}
