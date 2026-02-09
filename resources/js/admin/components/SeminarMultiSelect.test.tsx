import { render, screen, userEvent, waitFor } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    workshopsApi: {
        searchSeminars: vi.fn().mockResolvedValue({ data: [] }),
    },
}));

vi.mock('@shared/components/DropdownPortal', () => ({
    DropdownPortal: ({ children, isOpen }: { children: React.ReactNode; isOpen: boolean }) =>
        isOpen ? <div data-testid="dropdown-portal">{children}</div> : null,
}));

import { SeminarMultiSelect } from './SeminarMultiSelect';

describe('SeminarMultiSelect', () => {
    const defaultProps = {
        value: [] as number[],
        onChange: vi.fn(),
    };

    it('renders the component', () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        expect(screen.getByPlaceholderText('Buscar seminários...')).toBeInTheDocument();
    });

    it('renders the label when provided', () => {
        render(<SeminarMultiSelect {...defaultProps} label="Seminários" />);
        expect(screen.getByText('Seminários')).toBeInTheDocument();
    });

    it('renders the placeholder when no values are selected', () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        expect(screen.getByPlaceholderText('Buscar seminários...')).toBeInTheDocument();
    });

    it('renders selected seminars as badges', () => {
        const initialSeminars = [
            { id: 1, name: 'Seminar A', scheduled_at: '2026-01-01T10:00:00Z' },
            { id: 2, name: 'Seminar B', scheduled_at: '2026-02-01T10:00:00Z' },
        ];
        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1, 2]}
                initialSeminars={initialSeminars}
            />,
        );
        expect(screen.getByText('Seminar A')).toBeInTheDocument();
        expect(screen.getByText('Seminar B')).toBeInTheDocument();
    });

    it('hides placeholder when values are selected', () => {
        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1]}
                initialSeminars={[{ id: 1, name: 'Seminar A' }]}
            />,
        );
        expect(screen.getByPlaceholderText('')).toBeInTheDocument();
    });

    it('renders an error message when provided', () => {
        render(<SeminarMultiSelect {...defaultProps} error="Selecione pelo menos um seminário" />);
        expect(screen.getByText('Selecione pelo menos um seminário')).toBeInTheDocument();
    });

    it('renders the helper text', () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        expect(screen.getByText('Use setas para navegar, Enter para selecionar.')).toBeInTheDocument();
    });

    it('allows typing in the search input', async () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.type(input, 'test');
        expect(input).toHaveValue('test');
    });

    it('calls onChange when removing a selected seminar badge', async () => {
        const mockOnChange = vi.fn();
        const initialSeminars = [
            { id: 1, name: 'Seminar A', scheduled_at: '2026-01-01T10:00:00Z' },
            { id: 2, name: 'Seminar B', scheduled_at: '2026-02-01T10:00:00Z' },
        ];
        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1, 2]}
                initialSeminars={initialSeminars}
                onChange={mockOnChange}
            />,
        );
        const user = userEvent.setup();

        // Find the X button next to "Seminar A" text
        const seminarAText = screen.getByText('Seminar A');
        const removeButton = seminarAText.parentElement!.querySelector('svg')!;
        await user.click(removeButton);
        expect(mockOnChange).toHaveBeenCalledWith([2]);
    });

    it('shows fallback name for unknown seminar IDs', () => {
        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[999]}
                initialSeminars={[]}
            />,
        );
        expect(screen.getByText('Seminário #999')).toBeInTheDocument();
    });

    it('removes last seminar when pressing Backspace on empty input', async () => {
        const mockOnChange = vi.fn();
        const initialSeminars = [
            { id: 1, name: 'Seminar A' },
            { id: 2, name: 'Seminar B' },
        ];
        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1, 2]}
                initialSeminars={initialSeminars}
                onChange={mockOnChange}
            />,
        );
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('');
        await user.click(input);
        await user.keyboard('{Backspace}');
        expect(mockOnChange).toHaveBeenCalledWith([1]);
    });

    it('does not render label when not provided', () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        // Should not have any label element from our component
        expect(screen.queryByText('Seminários')).not.toBeInTheDocument();
    });

    it('does not render error when not provided', () => {
        render(<SeminarMultiSelect {...defaultProps} />);
        const errorElements = document.querySelectorAll('.text-red-500');
        expect(errorElements.length).toBe(0);
    });

    it('shows suggestions dropdown when seminar data is available and input is focused', async () => {
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 10, name: 'Available Seminar', slug: 'available', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(<SeminarMultiSelect {...defaultProps} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.click(input);

        await waitFor(() => {
            expect(screen.getByText('Available Seminar')).toBeInTheDocument();
        });
    });

    it('selects a seminar from dropdown suggestions', async () => {
        const mockOnChange = vi.fn();
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 10, name: 'Suggestion Seminar', slug: 'suggestion', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(<SeminarMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.click(input);

        await waitFor(() => {
            expect(screen.getByText('Suggestion Seminar')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Suggestion Seminar'));
        expect(mockOnChange).toHaveBeenCalledWith([10]);
    });

    it('filters out already selected seminars from suggestions', async () => {
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 1, name: 'Selected Seminar', slug: 'selected', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
                { id: 2, name: 'Unselected Seminar', slug: 'unselected', scheduled_at: '2026-04-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1]}
                initialSeminars={[{ id: 1, name: 'Selected Seminar' }]}
            />,
        );
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('');
        await user.click(input);

        await waitFor(() => {
            expect(screen.getByText('Unselected Seminar')).toBeInTheDocument();
        });
        // The "Selected Seminar" text appears as a badge, not in the dropdown
        const dropdownPortal = screen.getByTestId('dropdown-portal');
        expect(dropdownPortal).not.toHaveTextContent('Selected Seminar');
    });

    it('shows empty state when no seminars are available', async () => {
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({ data: [] });

        render(<SeminarMultiSelect {...defaultProps} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.click(input);

        await waitFor(() => {
            expect(screen.getByText('Nenhum seminário disponível')).toBeInTheDocument();
        });
    });

    it('does not call onChange when Backspace is pressed but no items selected', async () => {
        const mockOnChange = vi.fn();
        render(<SeminarMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.click(input);
        await user.keyboard('{Backspace}');
        expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('does not add a seminar that is already selected', async () => {
        const mockOnChange = vi.fn();
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 1, name: 'Seminar A', slug: 'a', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1]}
                initialSeminars={[{ id: 1, name: 'Seminar A' }]}
                onChange={mockOnChange}
            />,
        );

        // Since seminar id=1 is already selected, the dropdown should filter it out
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('');
        await user.click(input);

        // The dropdown portal for suggestions should not appear since all are filtered
        await waitFor(() => {
            expect(screen.queryByTestId('dropdown-portal')).not.toBeInTheDocument();
        });
    });

    it('does not duplicate when selecting an already-selected seminar via suggestion click', async () => {
        const mockOnChange = vi.fn();
        const { workshopsApi } = await import('../api/adminClient');
        // Return two seminars, one of which is already selected
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 1, name: 'Already Selected', slug: 'a', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
                { id: 2, name: 'Not Selected', slug: 'b', scheduled_at: '2026-04-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(
            <SeminarMultiSelect
                {...defaultProps}
                value={[1]}
                initialSeminars={[{ id: 1, name: 'Already Selected' }]}
                onChange={mockOnChange}
            />,
        );

        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('');
        await user.click(input);

        // Only "Not Selected" should appear in dropdown (already selected are filtered)
        await waitFor(() => {
            expect(screen.getByText('Not Selected')).toBeInTheDocument();
        });

        // Click "Not Selected" to add it
        await user.click(screen.getByText('Not Selected'));
        expect(mockOnChange).toHaveBeenCalledWith([1, 2]);
    });

    it('selects a seminar via keyboard navigation (ArrowDown + Enter)', async () => {
        const mockOnChange = vi.fn();
        const { workshopsApi } = await import('../api/adminClient');
        vi.mocked(workshopsApi.searchSeminars).mockResolvedValue({
            data: [
                { id: 10, name: 'Keyboard Seminar', slug: 'keyboard', scheduled_at: '2026-03-01T10:00:00Z', workshop_id: null },
                { id: 11, name: 'Second Seminar', slug: 'second', scheduled_at: '2026-04-01T10:00:00Z', workshop_id: null },
            ],
        });

        render(<SeminarMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup();
        const input = screen.getByPlaceholderText('Buscar seminários...');
        await user.click(input);

        // Wait for suggestions to appear
        await waitFor(() => {
            expect(screen.getByText('Keyboard Seminar')).toBeInTheDocument();
        });

        // Navigate down to highlight the first suggestion and press Enter
        await user.keyboard('{ArrowDown}');

        // Verify the highlighted item has the active class
        const firstSuggestion = screen.getByText('Keyboard Seminar').closest('[class*="px-3"]')!;
        expect(firstSuggestion.className).toContain('bg-accent');

        // Verify the Check icon is rendered for the highlighted item
        const checkIcon = firstSuggestion.querySelector('svg');
        expect(checkIcon).toBeInTheDocument();

        // Press Enter to select the highlighted suggestion
        await user.keyboard('{Enter}');
        expect(mockOnChange).toHaveBeenCalledWith([10]);
    });

});
