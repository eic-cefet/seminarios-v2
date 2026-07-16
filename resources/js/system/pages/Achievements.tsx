import { profileApi } from "@shared/api/client";
import { Button } from "@shared/components/Button";
import { PageTitle } from "@shared/components/PageTitle";
import { Skeleton } from "@shared/components/Skeleton";
import { formatDate } from "@shared/lib/utils";
import { useQuery } from "@tanstack/react-query";
import { Award, RotateCcw, Sparkles } from "lucide-react";
import { Layout } from "../components/Layout";
import { ProtectedRoute } from "../components/ProtectedRoute";
import { BadgeGallery } from "../components/gamification/BadgeGallery";
import { LevelProgress } from "../components/gamification/LevelProgress";

function AchievementsLoading() {
    return (
        <div role="status" aria-label="Carregando conquistas" className="space-y-8">
            <Skeleton className="h-52 w-full rounded-2xl" />
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {Array.from({ length: 6 }, (_, index) => (
                    <Skeleton key={index} className="h-64 w-full rounded-xl" />
                ))}
            </div>
        </div>
    );
}

function AchievementsContent() {
    const { data, isLoading, isError, refetch } = useQuery({
        queryKey: ["profile", "gamification"],
        queryFn: () => profileApi.gamification(),
    });
    const profile = data?.data;

    return (
        <>
            <PageTitle title="Conquistas" />
            <Layout>
                <div className="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <Award className="h-8 w-8 text-primary-600 dark:text-primary-300" aria-hidden="true" />
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    Minhas conquistas
                                </h1>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Acompanhe seu progresso na comunidade EIC.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-gray-50 dark:bg-gray-950">
                    <div className="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
                        {isLoading ? (
                            <AchievementsLoading />
                        ) : isError || !profile ? (
                            <div role="alert" className="rounded-xl border border-red-200 bg-white px-6 py-12 text-center dark:border-red-900 dark:bg-gray-900">
                                <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Não foi possível carregar suas conquistas.
                                </h2>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Verifique sua conexão e tente novamente.
                                </p>
                                <Button type="button" variant="primary" className="mt-6" onClick={() => void refetch()}>
                                    <RotateCcw aria-hidden="true" />
                                    Tentar novamente
                                </Button>
                            </div>
                        ) : (
                            <>
                                <LevelProgress progress={profile.progress} />

                                <section aria-labelledby="achievement-summary-heading" className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h2 id="achievement-summary-heading" className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                                Seu resumo
                                            </h2>
                                            <p className="mt-1 text-lg font-semibold text-primary-700 dark:text-primary-300">
                                                {profile.summary.earned_badges} de {profile.summary.total_badges} conquistas
                                            </p>
                                        </div>
                                        <Sparkles className="h-9 w-9 text-primary-500" aria-hidden="true" />
                                    </div>

                                    <h3 className="mt-6 text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Conquistas recentes
                                    </h3>
                                    {profile.summary.earned_badges === 0 ? (
                                        <p className="mt-3 rounded-lg bg-gray-50 px-4 py-5 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            Você ainda não conquistou nenhuma conquista.
                                        </p>
                                    ) : profile.recent_badges.length === 0 ? (
                                        <p className="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                            Nenhuma conquista recente.
                                        </p>
                                    ) : (
                                        <ul className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                            {profile.recent_badges.slice(0, 5).map((badge) => (
                                                <li key={badge.key} className="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                                    <p className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {badge.name}
                                                    </p>
                                                    {badge.earned_at && (
                                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {formatDate(badge.earned_at)}
                                                        </p>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>

                                <BadgeGallery categories={profile.categories} />
                            </>
                        )}
                    </div>
                </div>
            </Layout>
        </>
    );
}

export default function Achievements() {
    return (
        <ProtectedRoute>
            <AchievementsContent />
        </ProtectedRoute>
    );
}
