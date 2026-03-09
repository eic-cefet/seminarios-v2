import { useState } from "react";
import {
    Sparkles,
    Undo2,
    Redo2,
    ChevronDown,
    Loader2,
    Type,
    Minimize2,
    BookOpen,
    GraduationCap,
    MessageCircle,
} from "lucide-react";
import { Button } from "./ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "./ui/popover";
import { aiApi, type AiAction } from "@admin/api/adminClient";
import { toast } from "sonner";

const AI_ACTIONS: { action: AiAction; label: string; icon: React.ReactNode }[] =
    [
        {
            action: "format_markdown",
            label: "Formatar em Markdown",
            icon: <Type className="size-4" />,
        },
        {
            action: "shorten",
            label: "Resumir",
            icon: <Minimize2 className="size-4" />,
        },
        {
            action: "explain",
            label: "Explicar melhor",
            icon: <BookOpen className="size-4" />,
        },
        {
            action: "formal",
            label: "Tom formal",
            icon: <GraduationCap className="size-4" />,
        },
        {
            action: "casual",
            label: "Tom casual",
            icon: <MessageCircle className="size-4" />,
        },
    ];

interface AiTextToolbarProps {
    value: string;
    onChange: (value: string) => void;
    onLoadingChange?: (loading: boolean) => void;
}

export function AiTextToolbar({ value, onChange, onLoadingChange }: AiTextToolbarProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    // Linear version stack: [original, ai_v1, ai_v2, ...]
    const [versions, setVersions] = useState<string[]>([]);
    const [versionIndex, setVersionIndex] = useState(-1);

    const hasHistory = versions.length > 0;
    const canUndo = hasHistory && versionIndex > 0;
    const canRedo = hasHistory && versionIndex < versions.length - 1;

    async function handleAction(action: AiAction) {
        if (!value.trim()) {
            toast.warning("Digite algum texto antes de usar a IA.");
            return;
        }

        setOpen(false);
        setLoading(true);
        onLoadingChange?.(true);

        try {
            const response = await aiApi.transformText(value, action);
            const newText = response.data.text;

            setVersions((prev) => {
                if (prev.length === 0) {
                    // First AI action: save original + result
                    return [value, newText];
                }
                // Truncate any "future" versions after current position, then append
                const base = prev.slice(0, versionIndex + 1);
                return [...base, newText];
            });
            setVersionIndex((prev) => (prev === -1 ? 1 : prev + 1));

            onChange(newText);
        } catch {
            toast.error("Erro ao processar texto com IA. Tente novamente.");
        } finally {
            setLoading(false);
            onLoadingChange?.(false);
        }
    }

    function handleUndo() {
        if (!canUndo) return;
        const newIndex = versionIndex - 1;

        setVersionIndex(newIndex);
        onChange(versions[newIndex]);
    }

    function handleRedo() {
        if (!canRedo) return;
        const newIndex = versionIndex + 1;

        setVersionIndex(newIndex);
        onChange(versions[newIndex]);
    }

    return (
        <div className="flex items-center gap-1">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={loading}
                        className="gap-1.5"
                    >
                        {loading ? (
                            <Loader2 className="size-3.5 animate-spin" />
                        ) : (
                            <Sparkles className="size-3.5" />
                        )}
                        IA
                        <ChevronDown className="size-3" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-56 p-1">
                    {AI_ACTIONS.map(({ action, label, icon }) => (
                        <button
                            key={action}
                            type="button"
                            onClick={() => handleAction(action)}
                            className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground transition-colors"
                        >
                            {icon}
                            {label}
                        </button>
                    ))}
                </PopoverContent>
            </Popover>

            {hasHistory && (
                <div className="flex items-center gap-0.5 ml-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        disabled={!canUndo || loading}
                        onClick={handleUndo}
                        title="Desfazer alteração da IA"
                    >
                        <Undo2 className="size-3.5" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        disabled={!canRedo || loading}
                        onClick={handleRedo}
                        title="Refazer alteração da IA"
                    >
                        <Redo2 className="size-3.5" />
                    </Button>
                    <span className="text-xs text-muted-foreground ml-1">
                        {versionIndex + 1}/{versions.length}
                    </span>
                </div>
            )}
        </div>
    );
}
