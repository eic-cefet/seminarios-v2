import * as React from "react";
import { Check, ChevronDown, X } from "lucide-react";
import { cn } from "@shared/lib/utils";
import { Button } from "./button";
import { Popover, PopoverContent, PopoverTrigger } from "./popover";
import { Badge } from "./badge";
import { ScrollArea } from "./scroll-area";

export interface MultiSelectOption {
    value: string | number;
    label: string;
}

interface MultiSelectProps {
    options: MultiSelectOption[];
    selected: (string | number)[];
    onChange: (selected: (string | number)[]) => void;
    placeholder?: string;
    className?: string;
}

export function MultiSelect({
    options,
    selected,
    onChange,
    placeholder = "Selecione...",
    className,
}: MultiSelectProps) {
    const [open, setOpen] = React.useState(false);

    const handleSelect = (value: string | number) => {
        if (selected.includes(value)) {
            onChange(selected.filter((item) => item !== value));
        } else {
            onChange([...selected, value]);
        }
    };

    const handleRemove = (value: string | number, e: React.MouseEvent) => {
        e.stopPropagation();
        onChange(selected.filter((item) => item !== value));
    };

    const selectedLabels = selected
        .map((value) => options.find((opt) => opt.value === value)?.label)
        .filter(Boolean);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-label={placeholder}
                    className={cn(
                        "w-full justify-between min-h-10 h-auto",
                        className,
                    )}
                >
                    <div className="flex flex-wrap gap-1 flex-1">
                        {selected.length === 0 ? (
                            <span className="text-muted-foreground">
                                {placeholder}
                            </span>
                        ) : (
                            selectedLabels.map((label, index) => (
                                <Badge
                                    key={index}
                                    variant="secondary"
                                    className="gap-1"
                                >
                                    {label}
                                    <X
                                        className="h-3 w-3 cursor-pointer hover:text-destructive"
                                        onClick={(e) =>
                                            handleRemove(selected[index], e)
                                        }
                                    />
                                </Badge>
                            ))
                        )}
                    </div>
                    <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-full p-0" align="start">
                <ScrollArea className="h-72">
                    <div className="p-1" role="listbox" aria-multiselectable>
                        {options.map((option) => {
                            const isSelected = selected.includes(option.value);
                            return (
                                <div
                                    key={option.value}
                                    role="option"
                                    aria-selected={isSelected}
                                    tabIndex={0}
                                    className={cn(
                                        "flex items-center space-x-2 rounded-sm px-2 py-1.5 cursor-pointer hover:bg-accent focus:bg-accent focus:outline-none",
                                        isSelected && "bg-accent",
                                    )}
                                    onClick={() => handleSelect(option.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === "Enter" || e.key === " ") {
                                            e.preventDefault();
                                            handleSelect(option.value);
                                        }
                                    }}
                                >
                                    <div
                                        className={cn(
                                            "flex h-4 w-4 items-center justify-center rounded-sm border border-primary",
                                            isSelected
                                                ? "bg-primary text-primary-foreground"
                                                : "opacity-50",
                                        )}
                                    >
                                        {isSelected && (
                                            <Check className="h-3 w-3" />
                                        )}
                                    </div>
                                    <span className="text-sm">
                                        {option.label}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </ScrollArea>
            </PopoverContent>
        </Popover>
    );
}
