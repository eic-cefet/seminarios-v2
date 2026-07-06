import { useRef, useState, type KeyboardEvent } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Check, ChevronDown, Loader2, Search } from "lucide-react";
import { cn, formatDateTime } from "@shared/lib/utils";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { seminarsApi, type AdminSeminar } from "../api/adminClient";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "./ui/popover";

export type SeminarOption = Pick<AdminSeminar, "id" | "name" | "scheduled_at">;

interface SeminarComboboxProps {
    value: SeminarOption | null;
    onChange: (seminar: SeminarOption | null) => void;
    id?: string;
    placeholder?: string;
    className?: string;
}

const SCROLL_THRESHOLD_PX = 50;

export function SeminarCombobox({
    value,
    onChange,
    id,
    placeholder = "Todos os seminarios",
    className,
}: SeminarComboboxProps) {
    const [open, setOpen] = useState(false);
    const listRef = useRef<HTMLDivElement>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);

    const {
        inputValue,
        debouncedValue: searchTerm,
        setInputValue,
        clear: clearSearch,
    } = useDebouncedSearch({ delay: 300 });

    const { data, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage } =
        useInfiniteQuery({
            queryKey: ["admin-seminars-combobox", { search: searchTerm }],
            queryFn: ({ pageParam }) =>
                seminarsApi.list({
                    page: pageParam,
                    search: searchTerm || undefined,
                }),
            initialPageParam: 1,
            getNextPageParam: (lastPage) =>
                lastPage.meta.current_page < lastPage.meta.last_page
                    ? lastPage.meta.current_page + 1
                    : undefined,
            enabled: open,
        });

    const seminars = data?.pages.flatMap((page) => page.data) ?? [];

    const handleScroll = () => {
        const el = listRef.current;
        if (!el || !hasNextPage || isFetchingNextPage) {
            return;
        }
        if (
            el.scrollHeight - el.scrollTop - el.clientHeight <
            SCROLL_THRESHOLD_PX
        ) {
            fetchNextPage();
        }
    };

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);
        if (!nextOpen) {
            clearSearch();
        }
    };

    const handleSelect = (seminar: SeminarOption | null) => {
        onChange(seminar);
        handleOpenChange(false);
    };

    const optionClassName = (isSelected: boolean) =>
        cn(
            "flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 hover:bg-accent focus:bg-accent focus:outline-none",
            isSelected && "bg-accent",
        );

    const optionKeyDown =
        (seminar: SeminarOption | null) =>
        (e: KeyboardEvent<HTMLDivElement>) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                handleSelect(seminar);
            }
        };

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-label={placeholder}
                    className={cn(
                        "w-full justify-between font-normal",
                        className,
                    )}
                >
                    {value ? (
                        <span className="truncate">{value.name}</span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                aria-label={placeholder}
                className="w-[var(--radix-popover-trigger-width)] min-w-[280px] p-0"
                align="start"
                onOpenAutoFocus={(e) => {
                    e.preventDefault();
                    searchInputRef.current?.focus();
                }}
            >
                <div className="border-b p-2">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            ref={searchInputRef}
                            placeholder="Buscar seminario..."
                            aria-label="Buscar seminario"
                            value={inputValue}
                            onChange={(e) => setInputValue(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                </div>
                <div
                    ref={listRef}
                    onScroll={handleScroll}
                    data-testid="seminar-combobox-list"
                    className="max-h-[300px] overflow-y-auto p-1"
                >
                    <div role="listbox" aria-label="Seminarios">
                        <div
                            role="option"
                            aria-selected={value === null}
                            tabIndex={0}
                            className={optionClassName(value === null)}
                            onClick={() => handleSelect(null)}
                            onKeyDown={optionKeyDown(null)}
                        >
                            <span className="text-sm">
                                Todos os seminarios
                            </span>
                            {value === null && (
                                <Check className="h-4 w-4 shrink-0" />
                            )}
                        </div>
                        {seminars.map((seminar) => {
                            const isSelected = value?.id === seminar.id;
                            return (
                                <div
                                    key={seminar.id}
                                    role="option"
                                    aria-selected={isSelected}
                                    tabIndex={0}
                                    className={optionClassName(isSelected)}
                                    onClick={() => handleSelect(seminar)}
                                    onKeyDown={optionKeyDown(seminar)}
                                >
                                    <span className="min-w-0 text-sm">
                                        <span className="block truncate">
                                            {seminar.name}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
                                            {formatDateTime(
                                                seminar.scheduled_at,
                                            )}
                                        </span>
                                    </span>
                                    {isSelected && (
                                        <Check className="h-4 w-4 shrink-0" />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    {isLoading && (
                        <div className="px-2 py-6 text-center text-sm text-muted-foreground">
                            Carregando...
                        </div>
                    )}
                    {!isLoading && seminars.length === 0 && (
                        <div className="px-2 py-6 text-center text-sm text-muted-foreground">
                            Nenhum seminario encontrado
                        </div>
                    )}
                    {isFetchingNextPage && (
                        <div className="flex items-center justify-center gap-2 px-2 py-3 text-sm text-muted-foreground">
                            <Loader2
                                className="h-4 w-4 animate-spin"
                                aria-hidden="true"
                            />
                            Carregando mais...
                        </div>
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
