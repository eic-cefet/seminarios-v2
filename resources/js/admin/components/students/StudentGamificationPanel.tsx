import type { BadgeTier, GamificationBadge } from "@shared/types";
import { formatDate } from "@shared/lib/utils";
import { useQuery } from "@tanstack/react-query";
import { Award, CheckCircle2, LockKeyhole, RotateCcw, Sparkles } from "lucide-react";
import { AdminApiError, studentsApi } from "../../api/adminClient";
import { Badge } from "../ui/badge";
import { Button } from "../ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "../ui/card";
import { Skeleton } from "../ui/skeleton";

interface StudentGamificationPanelProps {
    userId: number;
}

const numberFormatter = new Intl.NumberFormat("pt-BR");

const tierDetails: Record<BadgeTier, { label: string; className: string }> = {
    bronze: {
        label: "Bronze",
        className: "border-amber-700 text-amber-800 dark:border-amber-500 dark:text-amber-300",
    },
    silver: {
        label: "Prata",
        className: "border-slate-500 text-slate-700 dark:border-slate-400 dark:text-slate-200",
    },
    gold: {
        label: "Ouro",
        className: "border-yellow-600 text-yellow-700 dark:border-yellow-400 dark:text-yellow-300",
    },
    platinum: {
        label: "Platina",
        className: "border-cyan-700 text-cyan-800 dark:border-cyan-400 dark:text-cyan-300",
    },
    special: {
        label: "Especial",
        className: "border-violet-700 text-violet-800 dark:border-violet-400 dark:text-violet-300",
    },
};

function LoadingPanel() {
    return (
        <div
            role="status"
            aria-label="Carregando conquistas do aluno"
            className="space-y-6"
        >
            <Skeleton className="h-44 w-full" />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {Array.from({ length: 6 }, (_, index) => (
                    <Skeleton key={index} className="h-40 w-full" />
                ))}
            </div>
        </div>
    );
}

function AchievementCard({ badge }: { badge: GamificationBadge }) {
    const tier = tierDetails[badge.tier];
    const status = badge.earned ? "Conquistada" : "Bloqueada";

    return (
        <article aria-label={`${badge.name}, ${status}`}>
            <Card className={badge.earned ? "h-full" : "h-full border-dashed"}>
                <CardHeader className="gap-3 p-4 pb-2">
                    <div className="flex items-start justify-between gap-3">
                        <div
                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border ${tier.className} ${badge.earned ? "" : "opacity-50 grayscale"}`}
                        >
                            {badge.earned ? (
                                <CheckCircle2 className="h-5 w-5" aria-hidden="true" />
                            ) : (
                                <LockKeyhole className="h-5 w-5" aria-hidden="true" />
                            )}
                        </div>
                        <div className="flex flex-wrap justify-end gap-2">
                            <Badge variant="outline" className={tier.className}>
                                {tier.label}
                            </Badge>
                            <Badge variant={badge.earned ? "success" : "secondary"}>
                                {status}
                            </Badge>
                        </div>
                    </div>
                    <CardTitle className="text-base leading-5">{badge.name}</CardTitle>
                    <CardDescription className="leading-5">{badge.description}</CardDescription>
                </CardHeader>
                <CardContent className="p-4 pt-2 text-xs font-medium text-muted-foreground">
                    {badge.earned && badge.earned_at
                        ? `Conquistada em ${formatDate(badge.earned_at)}`
                        : `Requisito: ${badge.description}`}
                </CardContent>
            </Card>
        </article>
    );
}

export function StudentGamificationPanel({ userId }: StudentGamificationPanelProps) {
    const { data, error, isLoading, refetch } = useQuery({
        queryKey: ["admin-student-gamification", userId],
        queryFn: () => studentsApi.gamification(userId),
        staleTime: 1000 * 60 * 5,
        refetchOnWindowFocus: false,
        retry: false,
    });

    if (isLoading) {
        return <LoadingPanel />;
    }

    const profile = data?.data;
    if (!profile) {
        const isForbidden = error instanceof AdminApiError && error.status === 403;

        return (
            <Card role="alert">
                <CardContent className="py-10 text-center">
                    <p className="font-semibold text-foreground">
                        {isForbidden
                            ? "Você não tem permissão para visualizar as conquistas deste aluno."
                            : "Não foi possível carregar as conquistas do aluno."}
                    </p>
                    {!isForbidden && (
                        <Button
                            type="button"
                            variant="outline"
                            className="mt-4"
                            onClick={() => void refetch()}
                        >
                            <RotateCcw aria-hidden="true" />
                            Tentar novamente
                        </Button>
                    )}
                </CardContent>
            </Card>
        );
    }

    const progressPercent = Math.max(0, Math.min(100, profile.progress.progress_percent));
    const nextLevel = profile.progress.level + 1;

    return (
        <section aria-labelledby="student-achievements-heading" className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <CardDescription className="font-semibold uppercase tracking-wide">
                                Progresso vitalício
                            </CardDescription>
                            <CardTitle>
                                <h3 id="student-achievements-heading" className="mt-1 text-2xl">
                                    Nível {profile.progress.level}
                                </h3>
                            </CardTitle>
                            <p className="mt-1 text-sm font-medium text-muted-foreground">
                                {profile.progress.rank}
                            </p>
                        </div>
                        <div className="text-left sm:text-right">
                            <p className="text-lg font-bold text-foreground">
                                {numberFormatter.format(profile.progress.total_xp)} XP no total
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Próximo nível: {numberFormatter.format(profile.progress.next_level_xp)} XP
                            </p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="mb-2 flex items-center justify-between gap-4 text-sm text-muted-foreground">
                        <span>{numberFormatter.format(profile.progress.current_level_xp)} XP</span>
                        <span>{progressPercent}%</span>
                    </div>
                    <div
                        role="progressbar"
                        aria-label={`Progresso para o nível ${nextLevel}`}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={progressPercent}
                        aria-valuetext={`${progressPercent}% concluído`}
                        className="h-3 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            className="h-full rounded-full bg-primary transition-[width] motion-reduce:transition-none"
                            style={{ width: `${progressPercent}%` }}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                    <div>
                        <CardTitle className="text-lg">Resumo de conquistas</CardTitle>
                        <CardDescription className="mt-1 text-base font-semibold text-foreground">
                            {profile.summary.earned_badges} de {profile.summary.total_badges} conquistas
                        </CardDescription>
                    </div>
                    <Sparkles className="h-6 w-6 text-primary" aria-hidden="true" />
                </CardHeader>
                {profile.summary.earned_badges === 0 && (
                    <CardContent>
                        <p className="rounded-lg bg-muted px-4 py-3 text-sm text-muted-foreground">
                            Este aluno ainda não conquistou nenhuma conquista.
                        </p>
                    </CardContent>
                )}
            </Card>

            <div className="space-y-6">
                {profile.categories.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Nenhuma conquista disponível.
                        </CardContent>
                    </Card>
                ) : (
                    profile.categories.map((category) => (
                        <section key={category.key} aria-labelledby={`achievement-category-${category.key}`}>
                            <div className="mb-3 flex items-center gap-2">
                                <Award className="h-5 w-5 text-primary" aria-hidden="true" />
                                <h3
                                    id={`achievement-category-${category.key}`}
                                    className="text-lg font-semibold text-foreground"
                                >
                                    {category.label}
                                </h3>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {category.badges.map((badge) => (
                                    <AchievementCard key={badge.key} badge={badge} />
                                ))}
                            </div>
                        </section>
                    ))
                )}
            </div>
        </section>
    );
}
