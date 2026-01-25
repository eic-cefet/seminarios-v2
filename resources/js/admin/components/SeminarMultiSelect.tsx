import { useQuery } from "@tanstack/react-query";
import { Calendar, Check, X } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { DropdownPortal } from "@shared/components/DropdownPortal";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { useDropdownNavigation } from "@shared/hooks/useDropdownNavigation";
import { formatDateTime } from "@shared/lib/utils";
import { workshopsApi, type SeminarSearchResult } from "../api/adminClient";
import { Badge } from "./ui/badge";
import { Input } from "./ui/input";
import { Label } from "./ui/label";

interface SeminarInfo {
    id: number;
    name: string;
    scheduled_at?: string;
}

interface SeminarMultiSelectProps {
    value: number[];
    onChange: (ids: number[]) => void;
    workshopId?: number;
    initialSeminars?: SeminarInfo[];
    label?: string;
    error?: string;
}

export function SeminarMultiSelect({
    value,
    onChange,
    workshopId,
    initialSeminars = [],
    label,
    error,
}: SeminarMultiSelectProps) {
    const [selectedSeminars, setSelectedSeminars] = useState<SeminarInfo[]>(
        () => initialSeminars,
    );

    const {
        inputValue,
        debouncedValue: searchTerm,
        setInputValue,
        clear: clearSearch,
    } = useDebouncedSearch({ delay: 300 });

    // Sync selectedSeminars when initialSeminars changes (e.g., when editing)
    useEffect(() => {
        if (initialSeminars.length > 0) {
            setSelectedSeminars(initialSeminars);
        }
    }, [initialSeminars]);

    // Fetch seminars for suggestions
    const { data: seminarsData } = useQuery({
        queryKey: [
            "admin-seminars-search",
            { search: searchTerm, workshopId },
        ],
        queryFn: () =>
            workshopsApi.searchSeminars({
                search: searchTerm || undefined,
                workshop_id: workshopId,
            }),
        enabled: true,
    });

    const allSeminars = seminarsData?.data ?? [];
    const suggestions = allSeminars.filter(
        (seminar) => !value.includes(seminar.id),
    );

    const addSeminar = useCallback(
        (seminar: SeminarSearchResult) => {
            if (!value.includes(seminar.id)) {
                onChange([...value, seminar.id]);
                setSelectedSeminars((prev) => [
                    ...prev,
                    {
                        id: seminar.id,
                        name: seminar.name,
                        scheduled_at: seminar.scheduled_at,
                    },
                ]);
            }
            clearSearch();
        },
        [value, onChange, clearSearch],
    );

    const removeSeminar = useCallback(
        (seminarId: number) => {
            onChange(value.filter((id) => id !== seminarId));
            setSelectedSeminars((prev) => prev.filter((s) => s.id !== seminarId));
        },
        [value, onChange],
    );

    const {
        isOpen: showSuggestions,
        setIsOpen: setShowSuggestions,
        highlightedIndex,
        setHighlightedIndex,
        inputRef,
        dropdownRef,
        handleKeyDown,
        focusInput,
    } = useDropdownNavigation({
        onSelect: (index) => {
            addSeminar(suggestions[index]);
            focusInput();
        },
        onClose: clearSearch,
    });

    const handleInputChange = (newValue: string) => {
        setInputValue(newValue);
        setShowSuggestions(true);
        setHighlightedIndex(-1);
    };

    // Get selected seminar names for display
    const getSelectedName = (id: number) => {
        const seminar = selectedSeminars.find((s) => s.id === id);
        return seminar?.name ?? `Seminário #${id}`;
    };

    const wrapperRef = useRef<HTMLDivElement>(null);

    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <div>
                <div
                    ref={wrapperRef}
                    className="flex flex-wrap gap-2 p-2 border border-border rounded-md min-h-[42px] bg-background focus-within:ring-2 focus-within:ring-ring"
                >
                    {value.map((seminarId) => (
                        <Badge
                            key={seminarId}
                            variant="secondary"
                            className="gap-1 flex items-center"
                        >
                            {getSelectedName(seminarId)}
                            <X
                                className="h-3 w-3 cursor-pointer hover:text-foreground"
                                onClick={() => removeSeminar(seminarId)}
                            />
                        </Badge>
                    ))}
                    <Input
                        ref={inputRef}
                        value={inputValue}
                        onChange={(e) => handleInputChange(e.target.value)}
                        onKeyDown={(e) =>
                            handleKeyDown(e, suggestions.length, {
                                onBackspaceEmpty: () => {
                                    if (value.length > 0) {
                                        removeSeminar(value[value.length - 1]);
                                    }
                                },
                            })
                        }
                        onFocus={() => setShowSuggestions(true)}
                        placeholder={
                            value.length === 0 ? "Buscar seminários..." : ""
                        }
                        className="flex-1 min-w-[150px] border-0 focus-visible:ring-0 p-0 h-6"
                    />
                </div>

                {/* Suggestions Dropdown */}
                <DropdownPortal
                    anchorRef={wrapperRef}
                    isOpen={showSuggestions && suggestions.length > 0}
                >
                    <div
                        ref={dropdownRef}
                        className="bg-popover border border-border rounded-md shadow-md max-h-[250px] overflow-y-auto"
                    >
                        {suggestions.map((seminar, index) => (
                            <div
                                key={seminar.id}
                                onClick={() => {
                                    addSeminar(seminar);
                                    focusInput();
                                }}
                                className={`px-3 py-2 cursor-pointer ${
                                    index === highlightedIndex
                                        ? "bg-accent text-accent-foreground"
                                        : "hover:bg-accent hover:text-accent-foreground"
                                }`}
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        {seminar.name}
                                    </span>
                                    {index === highlightedIndex && (
                                        <Check className="h-4 w-4" />
                                    )}
                                </div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                                    <Calendar className="h-3 w-3" />
                                    {formatDateTime(seminar.scheduled_at)}
                                </div>
                            </div>
                        ))}
                    </div>
                </DropdownPortal>

                <DropdownPortal
                    anchorRef={wrapperRef}
                    isOpen={
                        showSuggestions &&
                        suggestions.length === 0 &&
                        allSeminars.length === 0
                    }
                >
                    <div
                        ref={dropdownRef}
                        className="bg-popover border border-border rounded-md shadow-md p-3"
                    >
                        <p className="text-sm text-muted-foreground text-center">
                            Nenhum seminário disponível
                        </p>
                    </div>
                </DropdownPortal>
            </div>
            {error && <p className="text-sm text-red-500">{error}</p>}
            <p className="text-xs text-muted-foreground">
                Use setas para navegar, Enter para selecionar.
            </p>
        </div>
    );
}
