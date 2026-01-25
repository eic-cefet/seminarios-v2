import { useCallback, useState } from "react";

interface UseCRUDListStateOptions<T, F> {
    initialFormData: F;
    populateForm: (item: T) => F;
}

interface UseCRUDListStateReturn<T, F> {
    isDialogOpen: boolean;
    setIsDialogOpen: (open: boolean) => void;
    isDeleteDialogOpen: boolean;
    setIsDeleteDialogOpen: (open: boolean) => void;
    editingItem: T | null;
    deletingItem: T | null;
    formData: F;
    setFormData: React.Dispatch<React.SetStateAction<F>>;
    openCreateDialog: () => void;
    openEditDialog: (item: T) => void;
    openDeleteDialog: (item: T) => void;
    closeDialog: () => void;
    closeDeleteDialog: () => void;
    isEditing: boolean;
}

/**
 * Hook for managing common CRUD list state patterns.
 * Handles dialog open/close, editing/deleting items, and form data.
 *
 * @example
 * const {
 *   isDialogOpen,
 *   isDeleteDialogOpen,
 *   editingItem,
 *   deletingItem,
 *   formData,
 *   setFormData,
 *   openCreateDialog,
 *   openEditDialog,
 *   openDeleteDialog,
 *   closeDialog,
 *   closeDeleteDialog,
 *   isEditing,
 * } = useCRUDListState({
 *   initialFormData: { name: "" },
 *   populateForm: (item) => ({ name: item.name }),
 * });
 */
export function useCRUDListState<T, F>({
    initialFormData,
    populateForm,
}: UseCRUDListStateOptions<T, F>): UseCRUDListStateReturn<T, F> {
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<T | null>(null);
    const [deletingItem, setDeletingItem] = useState<T | null>(null);
    const [formData, setFormData] = useState<F>(initialFormData);

    const openCreateDialog = useCallback(() => {
        setEditingItem(null);
        setFormData(initialFormData);
        setIsDialogOpen(true);
    }, [initialFormData]);

    const openEditDialog = useCallback(
        (item: T) => {
            setEditingItem(item);
            setFormData(populateForm(item));
            setIsDialogOpen(true);
        },
        [populateForm],
    );

    const openDeleteDialog = useCallback((item: T) => {
        setDeletingItem(item);
        setIsDeleteDialogOpen(true);
    }, []);

    const closeDialog = useCallback(() => {
        setIsDialogOpen(false);
        setEditingItem(null);
        setFormData(initialFormData);
    }, [initialFormData]);

    const closeDeleteDialog = useCallback(() => {
        setIsDeleteDialogOpen(false);
        setDeletingItem(null);
    }, []);

    return {
        isDialogOpen,
        setIsDialogOpen,
        isDeleteDialogOpen,
        setIsDeleteDialogOpen,
        editingItem,
        deletingItem,
        formData,
        setFormData,
        openCreateDialog,
        openEditDialog,
        openDeleteDialog,
        closeDialog,
        closeDeleteDialog,
        isEditing: editingItem !== null,
    };
}
