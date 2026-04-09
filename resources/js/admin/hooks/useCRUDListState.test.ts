import { renderHook, act } from '@testing-library/react';
import { useCRUDListState } from './useCRUDListState';

interface TestItem {
    id: number;
    name: string;
}

interface TestFormData {
    name: string;
}

const defaultOptions = {
    initialFormData: { name: '' } as TestFormData,
    populateForm: (item: TestItem) => ({ name: item.name }),
};

describe('useCRUDListState', () => {
    it('returns initial state', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));

        expect(result.current.isDialogOpen).toBe(false);
        expect(result.current.isDeleteDialogOpen).toBe(false);
        expect(result.current.editingItem).toBeNull();
        expect(result.current.deletingItem).toBeNull();
        expect(result.current.formData).toEqual({ name: '' });
        expect(result.current.isEditing).toBe(false);
    });

    it('openCreateDialog opens dialog with initial form data', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));

        act(() => {
            result.current.openCreateDialog();
        });

        expect(result.current.isDialogOpen).toBe(true);
        expect(result.current.editingItem).toBeNull();
        expect(result.current.formData).toEqual({ name: '' });
        expect(result.current.isEditing).toBe(false);
    });

    it('openEditDialog opens dialog with item data', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));
        const item: TestItem = { id: 1, name: 'Test Item' };

        act(() => {
            result.current.openEditDialog(item);
        });

        expect(result.current.isDialogOpen).toBe(true);
        expect(result.current.editingItem).toEqual(item);
        expect(result.current.formData).toEqual({ name: 'Test Item' });
        expect(result.current.isEditing).toBe(true);
    });

    it('openDeleteDialog opens delete confirmation', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));
        const item: TestItem = { id: 1, name: 'Test' };

        act(() => {
            result.current.openDeleteDialog(item);
        });

        expect(result.current.isDeleteDialogOpen).toBe(true);
        expect(result.current.deletingItem).toEqual(item);
    });

    it('closeDialog resets dialog state', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));

        act(() => {
            result.current.openEditDialog({ id: 1, name: 'Test' });
        });

        act(() => {
            result.current.closeDialog();
        });

        expect(result.current.isDialogOpen).toBe(false);
        expect(result.current.editingItem).toBeNull();
        expect(result.current.formData).toEqual({ name: '' });
    });

    it('closeDeleteDialog resets delete state', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));

        act(() => {
            result.current.openDeleteDialog({ id: 1, name: 'Test' });
        });

        act(() => {
            result.current.closeDeleteDialog();
        });

        expect(result.current.isDeleteDialogOpen).toBe(false);
        expect(result.current.deletingItem).toBeNull();
    });

    it('setFormData updates form data', () => {
        const { result } = renderHook(() => useCRUDListState(defaultOptions));

        act(() => {
            result.current.setFormData({ name: 'Updated' });
        });

        expect(result.current.formData).toEqual({ name: 'Updated' });
    });
});
