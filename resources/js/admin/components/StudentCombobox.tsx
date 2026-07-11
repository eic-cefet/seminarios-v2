import { useRef, useState, type KeyboardEvent, type MouseEvent } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Check, ChevronsUpDown, Loader2, Search, SearchX, X } from "lucide-react";
import { cn } from "@shared/lib/utils";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { studentsApi, type AdminStudentListItem } from "../api/adminClient";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "./ui/popover";
import { Skeleton } from "./ui/skeleton";

export type StudentOption = Pick<AdminStudentListItem, "id" | "name" | "email">;

interface StudentComboboxProps {
    semester: string;
    value: StudentOption | null;
    onChange: (student: StudentOption | null) => void;
    id?: string;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
}

const SCROLL_THRESHOLD_PX = 50;

export function StudentCombobox({
    semester,
    value,
    onChange,
    id,
    placeholder = "Selecionar aluno",
    className,
    disabled = false,
}: StudentComboboxProps) {
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
            queryKey: ["admin-students-combobox", { semester, search: searchTerm }],
            queryFn: ({ pageParam }) =>
                studentsApi.list({
                    semester,
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

    const students = data?.pages.flatMap((page) => page.data) ?? [];

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

    const handleSelect = (student: StudentOption) => {
        onChange(student);
        handleOpenChange(false);
    };

    const clearSelection = () => onChange(null);

    const handleClearClick = (e: MouseEvent) => {
        e.stopPropagation();
        clearSelection();
    };

    const handleClearKeyDown = (e: KeyboardEvent<SVGSVGElement>) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            e.stopPropagation();
            clearSelection();
        }
    };

    const optionClassName = (isSelected: boolean) =>
        cn(
            "flex cursor-pointer items-center justify-between gap-3 rounded-md px-2.5 py-2 transition-colors hover:bg-accent focus:bg-accent focus:outline-none",
            isSelected && "bg-accent",
        );

    const optionKeyDown =
        (student: StudentOption) => (e: KeyboardEvent<HTMLDivElement>) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                handleSelect(student);
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
                    disabled={disabled}
                    className={cn(
                        "w-full justify-between font-normal",
                        className,
                    )}
                >
                    {value ? (
                        <span className="truncate font-medium">
                            {value.name}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <span className="flex shrink-0 items-center gap-1">
                        {value && (
                            <X
                                className="h-3.5 w-3.5 shrink-0 cursor-pointer rounded-sm text-muted-foreground hover:text-destructive focus:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                aria-label="Limpar seleção"
                                role="button"
                                tabIndex={0}
                                onClick={handleClearClick}
                                onKeyDown={handleClearKeyDown}
                            />
                        )}
                        <ChevronsUpDown className="h-4 w-4 text-muted-foreground" />
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent
                aria-label={placeholder}
                className="w-[var(--radix-popover-trigger-width)] min-w-[280px] overflow-hidden rounded-lg p-0 shadow-lg"
                align="start"
                sideOffset={6}
                onOpenAutoFocus={(e) => {
                    e.preventDefault();
                    searchInputRef.current?.focus();
                }}
            >
                <div className="relative border-b border-border">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground/70" />
                    <Input
                        ref={searchInputRef}
                        placeholder="Buscar aluno..."
                        aria-label="Buscar aluno"
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        className="h-10 rounded-none border-0 bg-transparent pl-9 shadow-none focus-visible:ring-0"
                    />
                </div>
                <div className="relative">
                    <div
                        ref={listRef}
                        onScroll={handleScroll}
                        data-testid="student-combobox-list"
                        className="max-h-[300px] overflow-y-auto overscroll-contain p-1.5"
                    >
                        <div role="listbox" aria-label="Alunos">
                            {students.map((student) => {
                                const isSelected = value?.id === student.id;
                                return (
                                    <div
                                        key={student.id}
                                        role="option"
                                        aria-selected={isSelected}
                                        tabIndex={0}
                                        className={optionClassName(isSelected)}
                                        onClick={() => handleSelect(student)}
                                        onKeyDown={optionKeyDown(student)}
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium">
                                                {student.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {student.email}
                                            </span>
                                        </span>
                                        {isSelected && (
                                            <Check className="h-4 w-4 shrink-0 text-primary" />
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                        {isLoading && (
                            <div
                                role="status"
                                className="space-y-3 px-2.5 pb-2 pt-3"
                            >
                                <span className="sr-only">Carregando...</span>
                                {[0, 1, 2].map((i) => (
                                    <div key={i} className="space-y-1.5">
                                        <Skeleton className="h-3.5 w-3/4" />
                                        <Skeleton className="h-3 w-2/5" />
                                    </div>
                                ))}
                            </div>
                        )}
                        {!isLoading && students.length === 0 && (
                            <div className="flex flex-col items-center gap-2 px-2 py-8 text-center">
                                <SearchX
                                    className="h-5 w-5 text-muted-foreground/60"
                                    aria-hidden="true"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Nenhum aluno encontrado
                                </p>
                            </div>
                        )}
                        {isFetchingNextPage && (
                            <div className="flex items-center justify-center gap-2 py-2.5 text-xs text-muted-foreground">
                                <Loader2
                                    className="h-3.5 w-3.5 animate-spin"
                                    aria-hidden="true"
                                />
                                Carregando mais...
                            </div>
                        )}
                    </div>
                    {hasNextPage && !isFetchingNextPage && (
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-popover to-transparent"
                        />
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
