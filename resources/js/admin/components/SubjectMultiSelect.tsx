import { useState, useRef, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { debounce } from "lodash";
import { X, Check } from "lucide-react";
import { Input } from "./ui/input";
import { Badge } from "./ui/badge";
import { Label } from "./ui/label";
import { subjectsApi } from "../api/adminClient";

interface SubjectMultiSelectProps {
    value: string[]; // Array of subject names
    onChange: (values: string[]) => void;
    label?: string;
    error?: string;
}

export function SubjectMultiSelect({
    value,
    onChange,
    label,
    error,
}: SubjectMultiSelectProps) {
    const [inputValue, setInputValue] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const inputRef = useRef<HTMLInputElement>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);

    // Debounced search
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

    // Fetch subjects for suggestions
    const { data: subjectsData } = useQuery({
        queryKey: ["admin-subjects-search", { search: searchTerm }],
        queryFn: () => subjectsApi.list({ search: searchTerm || undefined }),
        enabled: searchTerm.length > 0,
    });

    const allSubjects = subjectsData?.data ?? [];
    const suggestions = allSubjects
        .filter((subject) => !value.includes(subject.name))
        .map((subject) => subject.name);

    const handleInputChange = (newValue: string) => {
        setInputValue(newValue);
        debouncedSearch(newValue);
        setShowSuggestions(newValue.trim().length > 0);
        setHighlightedIndex(-1);
    };

    const addSubject = (subjectName: string) => {
        if (subjectName.trim() && !value.includes(subjectName.trim())) {
            onChange([...value, subjectName.trim()]);
        }
        setInputValue("");
        setSearchTerm("");
        setShowSuggestions(false);
        setHighlightedIndex(-1);
        inputRef.current?.focus();
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === "Enter") {
            e.preventDefault();
            if (
                highlightedIndex >= 0 &&
                highlightedIndex < suggestions.length
            ) {
                // Add highlighted suggestion
                addSubject(suggestions[highlightedIndex]);
            } else if (inputValue.trim()) {
                // Add new subject (will be auto-created on backend)
                addSubject(inputValue);
            }
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
            // Remove last item when backspace is pressed on empty input
            onChange(value.slice(0, -1));
        }
    };

    const removeSubject = (subject: string) => {
        onChange(value.filter((s) => s !== subject));
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

    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <div className="relative">
                <div className="flex flex-wrap gap-2 p-2 border border-border rounded-md min-h-[42px] bg-background focus-within:ring-2 focus-within:ring-ring">
                    {value.map((subject) => (
                        <Badge
                            key={subject}
                            variant="secondary"
                            className="gap-1 flex items-center"
                        >
                            {subject}
                            <X
                                className="h-3 w-3 cursor-pointer hover:text-foreground"
                                onClick={() => removeSubject(subject)}
                            />
                        </Badge>
                    ))}
                    <Input
                        ref={inputRef}
                        value={inputValue}
                        onChange={(e) => handleInputChange(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={
                            value.length === 0
                                ? "Digite e pressione Enter..."
                                : ""
                        }
                        className="flex-1 min-w-[150px] border-0 focus-visible:ring-0 p-0 h-6"
                    />
                </div>

                {/* Suggestions Dropdown */}
                {showSuggestions && suggestions.length > 0 && (
                    <div
                        ref={dropdownRef}
                        className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-md shadow-md max-h-[200px] overflow-y-auto"
                    >
                        {suggestions.map((suggestion, index) => (
                            <div
                                key={suggestion}
                                onClick={() => addSubject(suggestion)}
                                className={`px-3 py-2 cursor-pointer flex items-center justify-between ${
                                    index === highlightedIndex
                                        ? "bg-accent text-accent-foreground"
                                        : "hover:bg-accent hover:text-accent-foreground"
                                }`}
                            >
                                <span className="text-sm">{suggestion}</span>
                                {index === highlightedIndex && (
                                    <Check className="h-4 w-4" />
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
            {error && <p className="text-sm text-red-500">{error}</p>}
            <p className="text-xs text-muted-foreground">
                {showSuggestions && suggestions.length > 0
                    ? "Use ↑↓ para navegar, Enter para selecionar, ou continue digitando para criar nova."
                    : "Digite para buscar ou pressione Enter para adicionar nova disciplina."}
            </p>
        </div>
    );
}
