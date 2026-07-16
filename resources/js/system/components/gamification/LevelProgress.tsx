import type { GamificationProgress } from "@shared/types";

interface LevelProgressProps {
    progress: GamificationProgress;
}

const numberFormatter = new Intl.NumberFormat("pt-BR");

export function LevelProgress({ progress }: LevelProgressProps) {
    const progressPercent = Math.max(0, Math.min(100, progress.progress_percent));
    const nextLevel = progress.level + 1;

    return (
        <section
            aria-labelledby="level-progress-heading"
            className="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-700 to-primary-900 p-6 text-white shadow-sm dark:border-primary-700 dark:from-primary-900 dark:to-gray-950"
        >
            <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-primary-100">
                        Sua jornada
                    </p>
                    <h2 id="level-progress-heading" className="mt-1 text-3xl font-bold">
                        Nível {progress.level}
                    </h2>
                    <p className="mt-1 text-base font-medium text-primary-100">
                        {progress.rank}
                    </p>
                </div>
                <div className="text-left sm:text-right">
                    <p className="text-xl font-bold">
                        {numberFormatter.format(progress.total_xp)} XP no total
                    </p>
                    <p className="mt-1 text-sm text-primary-100">
                        Próximo nível: {numberFormatter.format(progress.next_level_xp)} XP
                    </p>
                </div>
            </div>

            <div className="mt-6">
                <div className="mb-2 flex items-center justify-between gap-4 text-sm text-primary-100">
                    <span>{numberFormatter.format(progress.current_level_xp)} XP</span>
                    <span>{progressPercent}%</span>
                </div>
                <div
                    role="progressbar"
                    aria-label={`Progresso para o nível ${nextLevel}`}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={progressPercent}
                    aria-valuetext={`${progressPercent}% concluído`}
                    className="h-3 overflow-hidden rounded-full bg-primary-950/60 ring-1 ring-white/20"
                >
                    <div
                        className="h-full rounded-full bg-white transition-[width] motion-reduce:transition-none"
                        style={{ width: `${progressPercent}%` }}
                    />
                </div>
            </div>
        </section>
    );
}
