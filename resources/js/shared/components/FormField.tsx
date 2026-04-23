import { forwardRef, InputHTMLAttributes, ReactNode } from "react";
import { cn } from "@shared/lib/utils";

interface FormFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    id: string;
    label: ReactNode;
    error?: string;
    hint?: string;
    labelClassName?: string;
    wrapperClassName?: string;
}

export const FormField = forwardRef<HTMLInputElement, FormFieldProps>(
    function FormField(
        { id, label, error, hint, required, className, labelClassName, wrapperClassName, ...inputProps },
        ref,
    ) {
        const errorId = error ? `${id}-error` : undefined;
        const hintId = hint ? `${id}-hint` : undefined;
        const describedBy = [hintId, errorId].filter(Boolean).join(" ") || undefined;

        return (
            <div className={wrapperClassName}>
                <label
                    htmlFor={id}
                    className={cn("block text-sm font-medium text-gray-700", labelClassName)}
                >
                    {label}
                    {required && <span aria-hidden="true" className="text-red-600"> *</span>}
                </label>
                {hint && (
                    <p id={hintId} className="mt-1 text-xs text-gray-600">
                        {hint}
                    </p>
                )}
                <input
                    ref={ref}
                    id={id}
                    required={required}
                    aria-required={required || undefined}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    className={cn(
                        "mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm",
                        "focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:border-primary-500",
                        error && "border-red-500 focus-visible:ring-red-500 focus-visible:border-red-500",
                        className,
                    )}
                    {...inputProps}
                />
                {error && (
                    <p id={errorId} role="alert" className="mt-1 text-xs text-red-600">
                        {error}
                    </p>
                )}
            </div>
        );
    },
);
