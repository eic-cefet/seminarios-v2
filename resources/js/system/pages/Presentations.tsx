import { useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import * as Tabs from "@radix-ui/react-tabs";
import * as Select from "@radix-ui/react-select";
import {
    ChevronDown,
    Check,
    Presentation,
    Filter,
    List,
    CalendarDays,
} from "lucide-react";
import { Layout } from "../components/Layout";
import { SeminarCard } from "../components/SeminarCard";
import { PresentationsCalendar } from "../components/PresentationsCalendar";
import { PageTitle } from "@shared/components/PageTitle";
import { seminarsApi, seminarTypesApi } from "@shared/api/client";
import { toSaoPaulo } from "@shared/lib/date";
import {
    buildCollectionPage,
    buildItemList,
} from "@shared/lib/structuredData";
import { buildAbsoluteUrl, cn } from "@shared/lib/utils";

type TimeFilter = "all" | "upcoming" | "expired";
type ViewMode = "list" | "calendar";

function tabTriggerClass(active: boolean): string {
    return cn(
        "px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors",
        active
            ? "border-primary-600 text-primary-600"
            : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300",
    );
}

export default function Presentations() {
    const [timeFilter, setTimeFilter] = useState<TimeFilter>("all");
    const [typeFilter, setTypeFilter] = useState<string>("all");
    const [viewMode, setViewMode] = useState<ViewMode>("list");
    const [page, setPage] = useState(1);
    const [calendarMonth, setCalendarMonth] = useState<Date>(new Date());

    const direction = timeFilter === "upcoming" ? "asc" : "desc";

    const { data: typesData } = useQuery({
        queryKey: ["seminarTypes"],
        queryFn: () => seminarTypesApi.list(),
    });

    const { data: seminarsData, isLoading } = useQuery({
        queryKey: ["seminars", timeFilter, typeFilter, page],
        queryFn: () =>
            seminarsApi.list({
                upcoming: timeFilter === "upcoming" ? true : undefined,
                expired: timeFilter === "expired" ? true : undefined,
                type: typeFilter !== "all" ? typeFilter : undefined,
                page,
                per_page: 12,
                direction,
            }),
    });

    const { data: calendarSeminarsData, isLoading: isCalendarLoading } =
        useQuery({
            queryKey: ["seminars-calendar", timeFilter, typeFilter],
            queryFn: () =>
                seminarsApi.list({
                    upcoming: timeFilter === "upcoming" ? true : undefined,
                    expired: timeFilter === "expired" ? true : undefined,
                    type: typeFilter !== "all" ? typeFilter : undefined,
                    page: 1,
                    per_page: 200,
                    direction,
                }),
            enabled: viewMode === "calendar",
        });

    useEffect(() => {
        setPage(1);
    }, [timeFilter, typeFilter]);

    useEffect(() => {
        if (viewMode !== "calendar") {
            return;
        }

        const firstSeminar = calendarSeminarsData?.data[0];
        setCalendarMonth(
            firstSeminar
                ? toSaoPaulo(firstSeminar.scheduledAt)
                : new Date(),
        );
    }, [
        viewMode,
        timeFilter,
        typeFilter,
        calendarSeminarsData?.data,
    ]);

    const types = typesData?.data ?? [];
    const seminars = seminarsData?.data ?? [];
    const pagination = seminarsData?.meta;
    const calendarSeminars = calendarSeminarsData?.data ?? [];
    const calendarTotal = calendarSeminarsData?.meta?.total;
    const pageDescription =
        "Veja a agenda de apresentações e seminários da Escola de Informática e Computação do CEFET-RJ.";
    const pageOffset =
        ((pagination?.current_page ?? 1) - 1) *
        (pagination?.per_page ?? seminars.length);
    const itemList = buildItemList(
        seminars.map((s, i) => ({
            name: s.name,
            url: buildAbsoluteUrl(`/seminario/${s.slug}`),
            position: pageOffset + i + 1,
        })),
        {
            itemListOrder: "https://schema.org/ItemListOrderAscending",
            numberOfItems: pagination?.total ?? seminars.length,
        },
    );
    const structuredData = [
        buildCollectionPage({
            name: "Apresentações e Seminários",
            description: pageDescription,
            path: "/apresentacoes",
        }),
        ...(itemList ? [itemList] : []),
    ];

    return (
        <>
            <PageTitle
                title="Apresentações"
                description={pageDescription}
                canonicalPath="/apresentacoes"
                structuredData={structuredData}
            />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold text-gray-900">
                            Apresentações
                        </h1>
                        <p className="mt-2 text-gray-500">
                            Todas as apresentações e seminários realizados
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <Tabs.Root
                        value={timeFilter}
                        onValueChange={(v) => setTimeFilter(v as TimeFilter)}
                    >
                        <Tabs.List className="flex border-b border-gray-200">
                            <Tabs.Trigger
                                value="all"
                                className={tabTriggerClass(timeFilter === "all")}
                            >
                                Todos
                            </Tabs.Trigger>
                            <Tabs.Trigger
                                value="upcoming"
                                className={tabTriggerClass(timeFilter === "upcoming")}
                            >
                                Próximos
                            </Tabs.Trigger>
                            <Tabs.Trigger
                                value="expired"
                                className={tabTriggerClass(timeFilter === "expired")}
                            >
                                Encerrados
                            </Tabs.Trigger>
                        </Tabs.List>
                    </Tabs.Root>

                    <div className="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                <Filter className="h-4 w-4" />
                                Filtrar por:
                            </div>

                            <Select.Root
                                value={typeFilter}
                                onValueChange={setTypeFilter}
                            >
                                <Select.Trigger className="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <Select.Value placeholder="Tipo" />
                                    <Select.Icon>
                                        <ChevronDown className="h-4 w-4 text-gray-400" />
                                    </Select.Icon>
                                </Select.Trigger>
                                <Select.Portal>
                                    <Select.Content className="rounded-md border border-gray-200 bg-white shadow-lg z-50">
                                        <Select.Viewport className="p-1">
                                            <Select.Item
                                                value="all"
                                                className="relative flex items-center px-8 py-2 text-sm rounded cursor-pointer hover:bg-gray-100 outline-none data-[highlighted]:bg-gray-100"
                                            >
                                                <Select.ItemText>
                                                    Todos os tipos
                                                </Select.ItemText>
                                                <Select.ItemIndicator className="absolute left-2">
                                                    <Check className="h-4 w-4 text-primary-600" />
                                                </Select.ItemIndicator>
                                            </Select.Item>
                                            {types.map((type) => (
                                                <Select.Item
                                                    key={type.id}
                                                    value={type.name}
                                                    className="relative flex items-center px-8 py-2 text-sm rounded cursor-pointer hover:bg-gray-100 outline-none data-[highlighted]:bg-gray-100"
                                                >
                                                    <Select.ItemText>
                                                        {type.name}
                                                    </Select.ItemText>
                                                    <Select.ItemIndicator className="absolute left-2">
                                                        <Check className="h-4 w-4 text-primary-600" />
                                                    </Select.ItemIndicator>
                                                </Select.Item>
                                            ))}
                                        </Select.Viewport>
                                    </Select.Content>
                                </Select.Portal>
                            </Select.Root>

                            {typeFilter !== "all" && (
                                <button
                                    onClick={() => setTypeFilter("all")}
                                    className="text-sm text-primary-600 hover:text-primary-700"
                                >
                                    Limpar filtro
                                </button>
                            )}
                        </div>

                        <div className="inline-flex w-full rounded-full border border-gray-200 bg-gray-50 p-1 sm:w-auto">
                            <button
                                type="button"
                                onClick={() => setViewMode("list")}
                                aria-label="Vista em lista"
                                className={cn(
                                    "inline-flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition sm:flex-none",
                                    viewMode === "list"
                                        ? "bg-white text-gray-900 shadow-sm"
                                        : "text-gray-500 hover:text-gray-700",
                                )}
                            >
                                <List className="h-4 w-4" />
                                Lista
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode("calendar")}
                                aria-label="Vista em calendário"
                                className={cn(
                                    "inline-flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition sm:flex-none",
                                    viewMode === "calendar"
                                        ? "bg-white text-gray-900 shadow-sm"
                                        : "text-gray-500 hover:text-gray-700",
                                )}
                            >
                                <CalendarDays className="h-4 w-4" />
                                Calendário
                            </button>
                        </div>
                    </div>

                    <div className="mt-8">
                        {viewMode === "calendar" ? (
                            <PresentationsCalendar
                                seminars={calendarSeminars}
                                month={calendarMonth}
                                total={calendarTotal}
                                isLoading={isCalendarLoading}
                                onMonthChange={setCalendarMonth}
                            />
                        ) : isLoading ? (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {[1, 2, 3, 4, 5, 6].map((i) => (
                                    <div
                                        key={i}
                                        className="h-48 animate-pulse rounded-lg bg-gray-200"
                                    />
                                ))}
                            </div>
                        ) : seminars.length > 0 ? (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {seminars.map((seminar) => (
                                        <SeminarCard
                                            key={seminar.id}
                                            seminar={seminar}
                                        />
                                    ))}
                                </div>

                                {pagination && pagination.last_page > 1 && (
                                    <div className="mt-8 flex items-center justify-center gap-2">
                                        <button
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.max(1, p - 1),
                                                )
                                            }
                                            disabled={page === 1}
                                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                        >
                                            Anterior
                                        </button>
                                        <span className="text-sm text-gray-500">
                                            Página {pagination.current_page} de{" "}
                                            {pagination.last_page}
                                        </span>
                                        <button
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.min(
                                                        pagination.last_page,
                                                        p + 1,
                                                    ),
                                                )
                                            }
                                            disabled={
                                                page === pagination.last_page
                                            }
                                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                        >
                                            Próxima
                                        </button>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                                <Presentation className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-4 text-lg font-medium text-gray-900">
                                    Nenhuma apresentação encontrada
                                </h3>
                                <p className="mt-2 text-gray-500">
                                    Tente ajustar os filtros para encontrar o
                                    que procura.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </Layout>
        </>
    );
}
