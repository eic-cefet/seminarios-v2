import { useState, useRef, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { debounce } from "lodash";
import { X, Check, Calendar } from "lucide-react";
import { Input } from "./ui/input";
import { Badge } from "./ui/badge";
import { Label } from "./ui/label";
import { workshopsApi, type SeminarSearchResult } from "../api/adminClient";
import { formatDateTime } from "@shared/lib/utils";

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
    const [inputValue, setInputValue] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const [selectedSeminars, setSelectedSeminars] = useState<SeminarInfo[]>(
        () => initialSeminars,
    );
    const inputRef = useRef<HTMLInputElement>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);

    // Sync selectedSeminars when initialSeminars changes (e.g., when editing)
    useEffect(() => {
        if (initialSeminars.length > 0) {
            setSelectedSeminars(initialSeminars);
        }
    }, [initialSeminars]);

    // Debounced search (300ms as specified)
    const debouncedSearch = useRef(
        debounce((value: string) => {
            setSearchTerm(value);
        }, 300),
    ).current;

    useEffect(() => {
        return () => {
            debouncedSearch.cancel();
        };
    }, [debouncedSearch]);

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
        enabled: showSuggestions,
    });

    const allSeminars = seminarsData?.data ?? [];
    const suggestions = allSeminars.filter(
        (seminar) => !value.includes(seminar.id),
    );

    const handleInputChange = (newValue: string) => {
        setInputValue(newValue);
        debouncedSearch(newValue);
        setShowSuggestions(true);
        setHighlightedIndex(-1);
    };

    const addSeminar = (seminar: SeminarSearchResult) => {
        if (!value.includes(seminar.id)) {
            onChange([...value, seminar.id]);
            setSelectedSeminars([
                ...selectedSeminars,
                { id: seminar.id, name: seminar.name, scheduled_at: seminar.scheduled_at },
            ]);
        }
        setInputValue("");
        setSearchTerm("");
        setShowSuggestions(false);
        setHighlightedIndex(-1);
        inputRef.current?.focus();
    };

    const removeSeminar = (seminarId: number) => {
        onChange(value.filter((id) => id !== seminarId));
        setSelectedSeminars(selectedSeminars.filter((s) => s.id !== seminarId));
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === "Enter") {
            e.preventDefault();
            if (
                highlightedIndex >= 0 &&
                highlightedIndex < suggestions.length
            ) {
                addSeminar(suggestions[highlightedIndex]);
            }
            // No free-text creation - only select from suggestions
        } else if (e.key === "Escape") {
            setShowSuggestions(false);
            setHighlightedIndex(-1);
        } else if (e.key === "ArrowDown") {
            e.preventDefault();
            setHighlightedIndex((prev) =>
                prev < suggestions.length - 1 ? prev + 1 : prev,
            );
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : -1));
        } else if (e.key === "Backspace" && !inputValue && value.length > 0) {
            const lastId = value[value.length - 1];
            removeSeminar(lastId);
        }
    };

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target as Node) &&
                inputRef.current &&
                !inputRef.current.contains(event.target as Node)
            ) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    // Get selected seminar names for display
    const getSelectedName = (id: number) => {
        const seminar = selectedSeminars.find((s) => s.id === id);
        return seminar?.name ?? `Seminário #${id}`;
    };

    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <div className="relative">
                <div className="flex flex-wrap gap-2 p-2 border border-border rounded-md min-h-[42px] bg-background focus-within:ring-2 focus-within:ring-ring">
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
                        onKeyDown={handleKeyDown}
                        onFocus={() => setShowSuggestions(true)}
                        placeholder={
                            value.length === 0 ? "Buscar seminários..." : ""
                        }
                        className="flex-1 min-w-[150px] border-0 focus-visible:ring-0 p-0 h-6"
                    />
                </div>

                {/* Suggestions Dropdown */}
                {showSuggestions && suggestions.length > 0 && (
                    <div
                        ref={dropdownRef}
                        className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-md shadow-md max-h-[250px] overflow-y-auto"
                    >
                        {suggestions.map((seminar, index) => (
                            <div
                                key={seminar.id}
                                onClick={() => addSeminar(seminar)}
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
                )}

                {showSuggestions &&
                    suggestions.length === 0 &&
                    allSeminars.length === 0 && (
                        <div
                            ref={dropdownRef}
                            className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-md shadow-md p-3"
                        >
                            <p className="text-sm text-muted-foreground text-center">
                                Nenhum seminário disponível
                            </p>
                        </div>
                    )}
            </div>
            {error && <p className="text-sm text-red-500">{error}</p>}
            <p className="text-xs text-muted-foreground">
                Use setas para navegar, Enter para selecionar.
            </p>
        </div>
    );
}
