import { render, screen, userEvent, fireEvent, waitFor } from '@/test/test-utils';

vi.mock('../api/adminClient', () => ({
    subjectsApi: {
        list: vi.fn().mockResolvedValue({ data: [], meta: { last_page: 1, current_page: 1, total: 0 } }),
    },
}));

vi.mock('@shared/components/DropdownPortal', () => ({
    DropdownPortal: ({ children, isOpen }: { children: React.ReactNode; isOpen: boolean }) =>
        isOpen ? <div data-testid="dropdown-portal">{children}</div> : null,
}));

import { SubjectMultiSelect } from './SubjectMultiSelect';

beforeEach(async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const { subjectsApi } = await import('../api/adminClient');
    vi.mocked(subjectsApi.list).mockResolvedValue({
        data: [],
        meta: { last_page: 1, current_page: 1, total: 0 } as any,
    });
});

afterEach(() => {
    vi.useRealTimers();
});

describe('SubjectMultiSelect', () => {
    const defaultProps = {
        value: [] as string[],
        onChange: vi.fn(),
    };

    it('renders the component', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        expect(screen.getByPlaceholderText('Digite e pressione Enter...')).toBeInTheDocument();
    });

    it('renders the label when provided', () => {
        render(<SubjectMultiSelect {...defaultProps} label="Tópicos" />);
        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });

    it('renders the placeholder when no values are selected', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        expect(screen.getByPlaceholderText('Digite e pressione Enter...')).toBeInTheDocument();
    });

    it('renders selected values as badges', () => {
        render(<SubjectMultiSelect {...defaultProps} value={['React', 'TypeScript']} />);
        expect(screen.getByText('React')).toBeInTheDocument();
        expect(screen.getByText('TypeScript')).toBeInTheDocument();
    });

    it('hides placeholder when values are selected', () => {
        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        expect(screen.getByPlaceholderText('')).toBeInTheDocument();
    });

    it('renders an error message when provided', () => {
        render(<SubjectMultiSelect {...defaultProps} error="Selecione pelo menos um tópico" />);
        expect(screen.getByText('Selecione pelo menos um tópico')).toBeInTheDocument();
    });

    it('renders the helper text', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        expect(screen.getByText('Digite para buscar ou pressione Enter para adicionar novo tópico.')).toBeInTheDocument();
    });

    it('allows typing in the input', async () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'React');
        expect(input).toHaveValue('React');
    });

    it('calls onChange when pressing Enter to add a new subject', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'New Topic{Enter}');
        expect(mockOnChange).toHaveBeenCalledWith(['New Topic']);
    });

    it('calls onChange when removing a badge', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} value={['React', 'Vue']} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        // Find the X button next to "React" text - it's the SVG in the parent element
        const reactText = screen.getByText('React');
        const removeButton = reactText.parentElement!.querySelector('svg')!;
        await user.click(removeButton);
        expect(mockOnChange).toHaveBeenCalledWith(['Vue']);
    });

    it('does not add duplicate subjects', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} value={['React']} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('');
        await user.type(input, 'React{Enter}');
        // Should not call onChange because React already exists
        expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('removes last subject when pressing Backspace on empty input', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} value={['React', 'Vue']} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('');
        await user.click(input);
        await user.keyboard('{Backspace}');
        expect(mockOnChange).toHaveBeenCalledWith(['React']);
    });

    it('does not render label when not provided', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        expect(screen.queryByText('Tópicos')).not.toBeInTheDocument();
    });

    it('does not render error when not provided', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        const errorElements = document.querySelectorAll('.text-red-500');
        expect(errorElements.length).toBe(0);
    });

    it('shows suggestions dropdown when subjects are available from API', async () => {
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', created_at: '', updated_at: '' },
                { id: 2, name: 'Vue', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
        });

        render(<SubjectMultiSelect {...defaultProps} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');

        // Need to type to trigger search (enabled: searchTerm.length > 0)
        await user.type(input, 'Re');

        // Advance past the 300ms debounce
        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });
        expect(screen.getByText('React')).toBeInTheDocument();
    });

    it('selects a subject from dropdown suggestions', async () => {
        const mockOnChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
        });

        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'Re');

        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });

        await user.click(screen.getByText('React'));
        expect(mockOnChange).toHaveBeenCalledWith(['React']);
    });

    it('filters out already selected subjects from suggestions', async () => {
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', created_at: '', updated_at: '' },
                { id: 2, name: 'Redux', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
        });

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('');
        await user.type(input, 'Re');

        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });

        // Only Redux should appear in the dropdown, not React (already selected)
        const dropdownPortal = screen.getByTestId('dropdown-portal');
        expect(dropdownPortal).toHaveTextContent('Redux');
        expect(dropdownPortal).not.toHaveTextContent('React');
    });

    it('trims whitespace when adding subject via Enter', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, '  Whitespace  {Enter}');
        expect(mockOnChange).toHaveBeenCalledWith(['Whitespace']);
    });

    it('does not add empty string via Enter', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.click(input);
        await user.keyboard('{Enter}');
        expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('does not call onChange when Backspace is pressed but no items selected', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.click(input);
        await user.keyboard('{Backspace}');
        expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('shows suggestion-mode helper text when suggestions are visible', async () => {
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
        });

        render(<SubjectMultiSelect {...defaultProps} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'Re');

        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });

        // Should show the navigation helper text
        expect(screen.getByText(/Use ↑↓ para navegar/)).toBeInTheDocument();
    });
});
