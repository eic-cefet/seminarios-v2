import { useRef, useState, type KeyboardEvent, type MouseEvent } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import {
    Check,
    ChevronsUpDown,
    Loader2,
    Search,
    SearchX,
    X,
} from "lucide-react";
import { cn } from "@shared/lib/utils";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { usersApi, type AdminUser } from "../api/adminClient";
import { Badge } from "./ui/badge";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "./ui/popover";
import { Skeleton } from "./ui/skeleton";

export type UserOption = Pick<AdminUser, "id" | "name" | "email">;

interface UserMultiComboboxProps {
    value: UserOption[];
    onChange: (users: UserOption[]) => void;
    id?: string;
    placeholder?: string;
    className?: string;
    modal?: boolean;
}

const SCROLL_THRESHOLD_PX = 50;

export function UserMultiCombobox({
    value,
    onChange,
    id,
    placeholder = "Selecionar usuarios",
    className,
    modal = false,
}: UserMultiComboboxProps) {
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
            queryKey: ["admin-users-combobox", { search: searchTerm }],
            queryFn: ({ pageParam }) =>
                usersApi.list({
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

    const users = data?.pages.flatMap((page) => page.data) ?? [];

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

    const isSelected = (userId: number) =>
        value.some((selected) => selected.id === userId);

    const toggleUser = (user: UserOption) => {
        if (isSelected(user.id)) {
            onChange(value.filter((selected) => selected.id !== user.id));
        } else {
            onChange([
                ...value,
                { id: user.id, name: user.name, email: user.email },
            ]);
        }
    };

    const removeUser = (userId: number, e: MouseEvent) => {
        e.stopPropagation();
        onChange(value.filter((selected) => selected.id !== userId));
    };

    const optionClassName = (selected: boolean) =>
        cn(
            "flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 transition-colors hover:bg-accent focus:bg-accent focus:outline-none",
            selected && "bg-accent",
        );

    const optionKeyDown =
        (user: UserOption) => (e: KeyboardEvent<HTMLDivElement>) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                toggleUser(user);
            }
        };

    return (
        <Popover open={open} onOpenChange={handleOpenChange} modal={modal}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        "h-auto min-h-10 w-full justify-between font-normal",
                        className,
                    )}
                >
                    <span className="flex min-w-0 flex-1 flex-wrap gap-1">
                        {value.length === 0 ? (
                            <span className="text-muted-foreground">
                                {placeholder}
                            </span>
                        ) : (
                            value.map((user) => (
                                <Badge
                                    key={user.id}
                                    variant="secondary"
                                    className="gap-1 font-normal"
                                >
                                    {user.name}
                                    <X
                                        className="h-3 w-3 cursor-pointer hover:text-destructive"
                                        onClick={(e) => removeUser(user.id, e)}
                                    />
                                </Badge>
                            ))
                        )}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 text-muted-foreground" />
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
                        placeholder="Buscar usuario..."
                        aria-label="Buscar usuario"
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        className="h-10 rounded-none border-0 bg-transparent pl-9 shadow-none focus-visible:ring-0"
                    />
                </div>
                <div className="relative">
                    <div
                        ref={listRef}
                        onScroll={handleScroll}
                        data-testid="user-combobox-list"
                        className="max-h-[300px] overflow-y-auto overscroll-contain p-1.5"
                    >
                        <div
                            role="listbox"
                            aria-multiselectable="true"
                            aria-label="Usuarios"
                        >
                            {users.map((user) => {
                                const selected = isSelected(user.id);
                                return (
                                    <div
                                        key={user.id}
                                        role="option"
                                        aria-selected={selected}
                                        tabIndex={0}
                                        className={optionClassName(selected)}
                                        onClick={() => toggleUser(user)}
                                        onKeyDown={optionKeyDown(user)}
                                    >
                                        <span
                                            className={cn(
                                                "flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border border-primary",
                                                selected
                                                    ? "bg-primary text-primary-foreground"
                                                    : "opacity-50",
                                            )}
                                        >
                                            {selected && (
                                                <Check className="h-3 w-3" />
                                            )}
                                        </span>
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium">
                                                {user.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {user.email}
                                            </span>
                                        </span>
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
                        {!isLoading && users.length === 0 && (
                            <div className="flex flex-col items-center gap-2 px-2 py-8 text-center">
                                <SearchX
                                    className="h-5 w-5 text-muted-foreground/60"
                                    aria-hidden="true"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Nenhum usuario encontrado
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
