import { useCallback, useMemo, useState } from "react";

export interface ApiTokenFormValues {
    name: string;
    expiry: string;
    abilities: string[];
    fullAccess: boolean;
}

interface UseApiTokenFormOptions {
    mode: "create" | "edit";
}

interface TokenLike {
    name: string;
    abilities: string[];
}

// Defaults are intentionally duplicated per mode: edit mode currently mirrors
// create defaults, but the two are kept separate so future divergence (e.g. a
// different default expiry for edits) does not require restructuring the hook.
const CREATE_DEFAULTS: ApiTokenFormValues = {
    name: "",
    expiry: "90",
    abilities: [],
    fullAccess: true,
};

const EDIT_DEFAULTS: ApiTokenFormValues = {
    name: "",
    expiry: "90",
    abilities: [],
    fullAccess: true,
};

export function useApiTokenForm({ mode }: UseApiTokenFormOptions) {
    const initial = useMemo(
        () => (mode === "create" ? CREATE_DEFAULTS : EDIT_DEFAULTS),
        [mode],
    );
    const [values, setValues] = useState<ApiTokenFormValues>(initial);

    const setName = useCallback(
        (name: string) => setValues((v) => ({ ...v, name })),
        [],
    );
    const setExpiry = useCallback(
        (expiry: string) => setValues((v) => ({ ...v, expiry })),
        [],
    );
    const setAbilities = useCallback(
        (abilities: string[]) => setValues((v) => ({ ...v, abilities })),
        [],
    );
    const setFullAccess = useCallback(
        (fullAccess: boolean) => setValues((v) => ({ ...v, fullAccess })),
        [],
    );

    const reset = useCallback(() => setValues(initial), [initial]);

    const loadFrom = useCallback((token: TokenLike) => {
        const isFullAccess = token.abilities.includes("*");
        setValues((v) => ({
            ...v,
            name: token.name,
            abilities: isFullAccess ? [] : [...token.abilities],
            fullAccess: isFullAccess,
        }));
    }, []);

    return {
        values,
        setName,
        setExpiry,
        setAbilities,
        setFullAccess,
        reset,
        loadFrom,
    };
}
