import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import * as Tabs from "@radix-ui/react-tabs";
import * as Select from "@radix-ui/react-select";
import { ChevronDown, Check, Presentation, Filter } from "lucide-react";
import { Layout } from "../components/Layout";
import { SeminarCard } from "../components/SeminarCard";
import { PageTitle } from "@shared/components/PageTitle";
import { seminarsApi, seminarTypesApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";

type TimeFilter = "all" | "upcoming" | "expired";

export default function Presentations() {
    const [timeFilter, setTimeFilter] = useState<TimeFilter>("all");
    const [typeFilter, setTypeFilter] = useState<string>("all");
    const [page, setPage] = useState(1);

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
                direction: timeFilter === "upcoming" ? "asc" : "desc",
            }),
    });

    const types = typesData?.data ?? [];
    const seminars = seminarsData?.data ?? [];
    const pagination = seminarsData?.meta;

    return (
        <>
            <PageTitle title="Apresentações" />
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
                    {/* Time Filter Tabs */}
                    <Tabs.Root
                        value={timeFilter}
                        onValueChange={(v) => setTimeFilter(v as TimeFilter)}
                    >
                        <Tabs.List className="flex border-b border-gray-200">
                            <Tabs.Trigger
                                value="all"
                                className={cn(
                                    "px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors",
                                    timeFilter === "all"
                                        ? "border-primary-600 text-primary-600"
                                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300",
                                )}
                            >
                                Todos
                            </Tabs.Trigger>
                            <Tabs.Trigger
                                value="upcoming"
                                className={cn(
                                    "px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors",
                                    timeFilter === "upcoming"
                                        ? "border-primary-600 text-primary-600"
                                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300",
                                )}
                            >
                                Próximos
                            </Tabs.Trigger>
                            <Tabs.Trigger
                                value="expired"
                                className={cn(
                                    "px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors",
                                    timeFilter === "expired"
                                        ? "border-primary-600 text-primary-600"
                                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300",
                                )}
                            >
                                Encerrados
                            </Tabs.Trigger>
                        </Tabs.List>
                    </Tabs.Root>

                    {/* Filters */}
                    <div className="mt-6 flex flex-wrap items-center gap-4">
                        <div className="flex items-center gap-2 text-sm text-gray-500">
                            <Filter className="h-4 w-4" />
                            Filtrar por:
                        </div>

                        {/* Type Filter */}
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

                        {/* Clear Filters */}
                        {typeFilter !== "all" && (
                            <button
                                onClick={() => setTypeFilter("all")}
                                className="text-sm text-primary-600 hover:text-primary-700"
                            >
                                Limpar filtro
                            </button>
                        )}
                    </div>

                    {/* Results */}
                    <div className="mt-8">
                        {isLoading ? (
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

                                {/* Pagination */}
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
