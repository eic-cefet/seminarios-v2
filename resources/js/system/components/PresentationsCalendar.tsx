import { Link } from "react-router-dom";
import {
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameMonth,
    isToday,
    startOfMonth,
    startOfWeek,
    subMonths,
} from "date-fns";
import { ptBR } from "date-fns/locale";
import { ChevronLeft, ChevronRight } from "lucide-react";
import type { Seminar } from "@shared/types";
import { ROUTES } from "@shared/config/routes";
import { cn, toSaoPaulo } from "@shared/lib/utils";
import { Skeleton } from "@shared/components/Skeleton";

interface PresentationsCalendarProps {
    seminars: Seminar[];
    month: Date;
    total?: number;
    isLoading?: boolean;
    onMonthChange: (month: Date) => void;
}

const WEEKDAY_LABELS = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sab"];

function formatMonthLabel(month: Date): string {
    const label = format(month, "MMMM 'de' yyyy", { locale: ptBR });
    return label.charAt(0).toUpperCase() + label.slice(1);
}

export function PresentationsCalendar({
    seminars,
    month,
    total,
    isLoading = false,
    onMonthChange,
}: PresentationsCalendarProps) {
    const monthStart = startOfMonth(month);
    const monthEnd = endOfMonth(monthStart);
    const intervalStart = startOfWeek(monthStart);
    const intervalEnd = endOfWeek(monthEnd);
    const calendarDays = eachDayOfInterval({
        start: intervalStart,
        end: intervalEnd,
    });

    const seminarsByDay = seminars.reduce<Record<string, Seminar[]>>(
        (grouped, seminar) => {
            const seminarDate = toSaoPaulo(seminar.scheduledAt);
            const key = format(seminarDate, "yyyy-MM-dd");
            grouped[key] ??= [];
            grouped[key].push(seminar);
            grouped[key].sort(
                (a, b) =>
                    new Date(a.scheduledAt).getTime() -
                    new Date(b.scheduledAt).getTime(),
            );

            return grouped;
        },
        {},
    );

    const monthlySeminars = seminars.filter((seminar) =>
        isSameMonth(toSaoPaulo(seminar.scheduledAt), monthStart),
    );

    const totalLabel =
        typeof total === "number" && total > seminars.length
            ? `Exibindo ${seminars.length} de ${total} apresentações neste filtro.`
            : `${monthlySeminars.length} apresentação${monthlySeminars.length === 1 ? "" : "ões"} neste mês.`;

    return (
        <div className="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">
                        {formatMonthLabel(monthStart)}
                    </h2>
                    <p className="mt-1 text-sm text-gray-500">{totalLabel}</p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() =>
                            onMonthChange(subMonths(monthStart, 1))
                        }
                        aria-label="Mês anterior"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-primary-300 hover:text-primary-700"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => onMonthChange(startOfMonth(new Date()))}
                        aria-label="Mês atual"
                        className="rounded-full border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-primary-300 hover:text-primary-700"
                    >
                        Hoje
                    </button>
                    <button
                        type="button"
                        onClick={() =>
                            onMonthChange(addMonths(monthStart, 1))
                        }
                        aria-label="Próximo mês"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-primary-300 hover:text-primary-700"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                </div>
            </div>

            {isLoading ? (
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {Array.from({ length: 6 }).map((_, index) => (
                        <Skeleton key={index} className="h-40 rounded-md" />
                    ))}
                </div>
            ) : seminars.length > 0 ? (
                <div className="overflow-x-auto">
                    <div className="grid min-w-[56rem] grid-cols-7 gap-px bg-gray-200">
                        {WEEKDAY_LABELS.map((label) => (
                            <div
                                key={label}
                                className="bg-gray-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500"
                            >
                                {label}
                            </div>
                        ))}

                        {calendarDays.map((day) => {
                            const dayKey = format(day, "yyyy-MM-dd");
                            const daySeminars = seminarsByDay[dayKey] ?? [];
                            const isCurrentMonth = isSameMonth(day, monthStart);

                            return (
                                <div
                                    key={dayKey}
                                    className={cn(
                                        "min-h-44 bg-white px-3 py-3 align-top",
                                        !isCurrentMonth && "bg-gray-50/80",
                                    )}
                                >
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={cn(
                                                "inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold",
                                                isToday(day)
                                                    ? "bg-primary-600 text-white"
                                                    : isCurrentMonth
                                                      ? "text-gray-900"
                                                      : "text-gray-500",
                                            )}
                                        >
                                            {format(day, "d")}
                                        </span>
                                        {daySeminars.length > 0 && (
                                            <span className="text-xs font-medium text-gray-600">
                                                {daySeminars.length}
                                            </span>
                                        )}
                                    </div>

                                    <div className="mt-3 space-y-2">
                                        {daySeminars.slice(0, 3).map((seminar) => (
                                            <Link
                                                key={seminar.id}
                                                to={ROUTES.SYSTEM.SEMINAR_DETAILS(seminar.slug)}
                                                className={cn(
                                                    "block rounded-xl border px-3 py-2 text-sm transition",
                                                    seminar.isExpired
                                                        ? "border-gray-200 bg-gray-50 text-gray-500 hover:border-gray-300"
                                                        : "border-primary-100 bg-primary-50/70 text-gray-800 hover:border-primary-300 hover:bg-primary-50",
                                                )}
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className="font-semibold line-clamp-2">
                                                        {seminar.name}
                                                    </span>
                                                    <span className="shrink-0 text-xs font-medium uppercase tracking-wide text-primary-700">
                                                        {format(
                                                            toSaoPaulo(
                                                                seminar.scheduledAt,
                                                            ),
                                                            "HH:mm",
                                                        )}
                                                    </span>
                                                </div>
                                                {seminar.seminarType && (
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        {seminar.seminarType.name}
                                                    </p>
                                                )}
                                            </Link>
                                        ))}

                                        {daySeminars.length > 3 && (
                                            <p className="px-1 text-xs font-medium text-gray-500">
                                                +{daySeminars.length - 3} outras
                                                apresentações
                                            </p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            ) : (
                <div className="px-6 py-12 text-center">
                    <p className="text-lg font-medium text-gray-900">
                        Nenhuma apresentação encontrada
                    </p>
                    <p className="mt-2 text-sm text-gray-500">
                        Ajuste os filtros para preencher o calendário.
                    </p>
                </div>
            )}

            {!isLoading && monthlySeminars.length > 0 && (
                <div className="border-t border-gray-200 px-5 py-3 text-sm text-gray-500">
                    {monthlySeminars.length} apresentação
                    {monthlySeminars.length === 1 ? "" : "ões"} neste mês com
                    os filtros atuais.
                </div>
            )}
        </div>
    );
}
