import { useMutation, useQuery } from "@tanstack/react-query";
import { Check, Loader2, Plus, Sparkles, X } from "lucide-react";
import { useCallback, useRef, useState } from "react";
import { toast } from "sonner";
import { DropdownPortal } from "@shared/components/DropdownPortal";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { useDropdownNavigation } from "@shared/hooks/useDropdownNavigation";
import { aiApi, subjectsApi } from "../api/adminClient";
import { Badge } from "./ui/badge";
import { Button } from "./ui/button";
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

    const [aiSuggestions, setAiSuggestions] = useState<string[]>([]);

    const suggestMutation = useMutation({
        mutationFn: () => aiApi.suggestSubjectTags(value),
        onSuccess: (data) => {
            const filtered = data.data.suggestions.filter(
                (s) => !value.includes(s),
            );
            setAiSuggestions(filtered);
            if (filtered.length === 0) {
                toast.info("Nenhuma sugestão adicional encontrada.");
            }
        },
        onError: (error: any) => {
            if (error?.status === 503) {
                toast.error("IA não configurada. Defina AI_API_KEY no ambiente.");
            } else {
                toast.error("Erro ao buscar sugestões. Tente novamente.");
            }
        },
    });

    const addSuggestion = (name: string) => {
        /* v8 ignore next -- @preserve defensive guard: suggestions are pre-filtered */
        if (!value.includes(name)) {
            onChange([...value, name]);
        }
        setAiSuggestions((prev) => prev.filter((s) => s !== name));
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
                <DropdownPortal
                    anchorRef={wrapperRef}
                    isOpen={showSuggestions && suggestions.length > 0}
                >
                    <div
                        ref={dropdownRef}
                        className="bg-popover border border-border rounded-md shadow-md max-h-[200px] overflow-y-auto"
                    >
                        {suggestions.map((suggestion, index) => (
                            <div
                                key={suggestion}
                                role="option"
                                aria-selected={index === highlightedIndex}
                                tabIndex={-1}
                                // Use onMouseDown (not onClick) so the selection lands
                                // before any document-level pointerdown handler runs —
                                // Radix Dialog's onPointerDownOutside otherwise swallows
                                // the click when this dropdown is portalled outside the
                                // dialog content. preventDefault() also keeps the input
                                // focused so the dropdown stays open for further picks.
                                onMouseDown={(e) => {
                                    e.preventDefault();
                                    addSubject(suggestion);
                                    focusInput();
                                }}
                                onKeyDown={(e) => {
                                    if (e.key === "Enter" || e.key === " ") {
                                        e.preventDefault();
                                        addSubject(suggestion);
                                        focusInput();
                                    }
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
                </DropdownPortal>
            </div>
            {value.length > 0 && (
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => suggestMutation.mutate()}
                        disabled={suggestMutation.isPending}
                    >
                        {suggestMutation.isPending ? (
                            <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                        ) : (
                            <Sparkles className="mr-1 h-3 w-3" />
                        )}
                        {suggestMutation.isPending
                            ? "Buscando..."
                            : "Sugerir com IA"}
                    </Button>
                    {aiSuggestions.length > 0 && (
                        <button
                            type="button"
                            onClick={() => setAiSuggestions([])}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            Limpar sugestões
                        </button>
                    )}
                </div>
            )}

            {aiSuggestions.length > 0 && (
                <div className="space-y-1">
                    <Label className="text-xs text-muted-foreground">
                        Sugestões da IA:
                    </Label>
                    <div className="flex flex-wrap gap-1">
                        {aiSuggestions.map((suggestion) => (
                            <Badge
                                key={suggestion}
                                variant="outline"
                                className="gap-1 cursor-pointer border-dashed hover:bg-accent"
                                onClick={() => addSuggestion(suggestion)}
                            >
                                <Plus className="h-3 w-3" />
                                {suggestion}
                            </Badge>
                        ))}
                    </div>
                </div>
            )}

            {error && <p className="text-sm text-red-500">{error}</p>}
            <p className="text-xs text-muted-foreground">
                {showSuggestions && suggestions.length > 0
                    ? "Use ↑↓ para navegar, Enter para selecionar, ou continue digitando para criar nova."
                    : "Digite para buscar ou pressione Enter para adicionar novo tópico."}
            </p>
        </div>
    );
}
