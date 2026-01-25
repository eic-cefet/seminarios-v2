import { useQuery } from "@tanstack/react-query";
import { Check, X } from "lucide-react";
import { useCallback } from "react";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { useDropdownNavigation } from "@shared/hooks/useDropdownNavigation";
import { subjectsApi } from "../api/adminClient";
import { Badge } from "./ui/badge";
import { Input } from "./ui/input";
import { Label } from "./ui/label";

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
    const {
        inputValue,
        debouncedValue: searchTerm,
        setInputValue,
        clear: clearSearch,
    } = useDebouncedSearch({ delay: 300 });

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

    const addSubject = useCallback(
        (subjectName: string) => {
            if (subjectName.trim() && !value.includes(subjectName.trim())) {
                onChange([...value, subjectName.trim()]);
            }
            clearSearch();
        },
        [value, onChange, clearSearch],
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
            addSubject(suggestions[index]);
            focusInput();
        },
        onClose: clearSearch,
    });

    const handleInputChange = (newValue: string) => {
        setInputValue(newValue);
        setShowSuggestions(newValue.trim().length > 0);
        setHighlightedIndex(-1);
    };

    const removeSubject = (subject: string) => {
        onChange(value.filter((s) => s !== subject));
    };

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
                        onKeyDown={(e) =>
                            handleKeyDown(e, suggestions.length, {
                                onEnterWithNoSelection: () => {
                                    if (inputValue.trim()) {
                                        addSubject(inputValue);
                                    }
                                },
                                onBackspaceEmpty: () => {
                                    if (value.length > 0) {
                                        onChange(value.slice(0, -1));
                                    }
                                },
                            })
                        }
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
                                onClick={() => {
                                    addSubject(suggestion);
                                    focusInput();
                                }}
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
                    : "Digite para buscar ou pressione Enter para adicionar novo tópico."}
            </p>
        </div>
    );
}
