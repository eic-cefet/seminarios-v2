import { getErrorMessage, getFieldErrors } from "@shared/lib/errors";
import { useState } from "react";

interface UseProfileFormOptions {
    onSuccess?: () => void | Promise<void>;
    onCancel?: () => void;
}

export function useProfileForm({ onSuccess, onCancel }: UseProfileFormOptions = {}) {
    const [isEditing, setIsEditing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);

    const mutationCallbacks = {
        onSuccess: async () => {
            setError(null);
            setFieldErrors({});
            setSuccess(true);
            setIsEditing(false);
            await onSuccess?.();
            setTimeout(() => setSuccess(false), 3000);
        },
        onError: (err: Error) => {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err) || {});
        },
    };

    const handleCancel = () => {
        onCancel?.();
        setError(null);
        setFieldErrors({});
        setIsEditing(false);
    };

    return {
        isEditing,
        startEditing: () => setIsEditing(true),
        error,
        fieldErrors,
        success,
        mutationCallbacks,
        handleCancel,
    };
}
