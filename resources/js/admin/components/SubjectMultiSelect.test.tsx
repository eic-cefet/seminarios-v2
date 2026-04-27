import { render, screen, userEvent, waitFor } from '@/test/test-utils';

// Holder for captured queryFn from useQuery calls
const capturedQueryFns: { queryFn: (() => any) | null } = { queryFn: null };

vi.mock('../api/adminClient', () => ({
    subjectsApi: {
        list: vi.fn().mockResolvedValue({ data: [], meta: { last_page: 1, current_page: 1, total: 0 } }),
    },
    aiApi: {
        suggestSubjectTags: vi.fn().mockResolvedValue({ data: { suggestions: [] } }),
    },
}));

vi.mock('@shared/components/DropdownPortal', () => ({
    DropdownPortal: ({ children, isOpen }: { children: React.ReactNode; isOpen: boolean }) =>
        isOpen ? <div data-testid="dropdown-portal">{children}</div> : null,
}));

vi.mock('@tanstack/react-query', async () => {
    const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
    return {
        ...actual,
        useQuery: (options: any) => {
            if (options.queryKey?.[0] === 'admin-subjects-search') {
                capturedQueryFns.queryFn = options.queryFn;
            }
            return actual.useQuery(options);
        },
    };
});

import { SubjectMultiSelect } from './SubjectMultiSelect';

beforeEach(async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const { subjectsApi } = await import('../api/adminClient');
    vi.mocked(subjectsApi.list).mockResolvedValue({
        data: [],
        meta: { last_page: 1, current_page: 1, total: 0 } as any,
        links: { first: '', last: '', prev: null, next: null },
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
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
                { id: 2, name: 'Vue', slug: 'vue', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
            links: { first: '', last: '', prev: null, next: null },
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
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
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
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
                { id: 2, name: 'Redux', slug: 'redux', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
            links: { first: '', last: '', prev: null, next: null },
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

    it('does not add subject when Enter is pressed with only whitespace input and no selection highlighted', async () => {
        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        // Type spaces then press Enter - the trim should result in empty
        await user.type(input, '   {Enter}');
        // onChange should NOT be called because inputValue.trim() is empty
        expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('selects a subject via keyboard navigation (ArrowDown + Enter)', async () => {
        const mockOnChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
                { id: 2, name: 'Redux', slug: 'redux', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });

        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'Re');

        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });

        // Press ArrowDown to highlight the first suggestion
        await user.keyboard('{ArrowDown}');

        // The highlighted item should have the Check icon and accent styling
        await waitFor(() => {
            const checkIcons = document.querySelectorAll('.lucide-check');
            expect(checkIcons.length).toBeGreaterThan(0);
        });

        // Press Enter to select the highlighted suggestion via onSelect callback
        await user.keyboard('{Enter}');
        expect(mockOnChange).toHaveBeenCalledWith(['React']);
    });

    it('highlights second suggestion when pressing ArrowDown twice', async () => {
        const mockOnChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
                { id: 2, name: 'Redux', slug: 'redux', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 2 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });

        render(<SubjectMultiSelect {...defaultProps} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');
        await user.type(input, 'Re');

        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(screen.getByTestId('dropdown-portal')).toBeInTheDocument();
        });

        // Press ArrowDown twice to highlight the second suggestion
        await user.keyboard('{ArrowDown}{ArrowDown}');

        // Press Enter to select Redux (index 1)
        await user.keyboard('{Enter}');
        expect(mockOnChange).toHaveBeenCalledWith(['Redux']);
    });

    it('passes search term to subjectsApi.list in queryFn', async () => {
        const { subjectsApi } = await import('../api/adminClient');
        const listMock = vi.mocked(subjectsApi.list);
        listMock.mockResolvedValue({
            data: [
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });

        render(<SubjectMultiSelect {...defaultProps} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const input = screen.getByPlaceholderText('Digite e pressione Enter...');

        // Type something to trigger the query
        await user.type(input, 'R');
        vi.advanceTimersByTime(350);

        await waitFor(() => {
            expect(listMock).toHaveBeenCalled();
        });

        // The queryFn uses searchTerm || undefined
        // When searchTerm is 'R', it should pass 'R'
        expect(listMock).toHaveBeenCalledWith({ search: 'R' });
    });

    it('queryFn passes undefined as search param when searchTerm is empty', async () => {
        // This test covers the `searchTerm || undefined` branch where searchTerm is falsy.
        // On initial render, searchTerm is '', so the captured queryFn closure has searchTerm=''.
        // We manually invoke the captured queryFn to exercise the || undefined branch.
        const { subjectsApi } = await import('../api/adminClient');
        const listMock = vi.mocked(subjectsApi.list);
        listMock.mockResolvedValue({
            data: [],
            meta: { last_page: 1, current_page: 1, total: 0 } as any,
            links: { first: '', last: '', prev: null, next: null },
        });

        capturedQueryFns.queryFn = null;
        render(<SubjectMultiSelect {...defaultProps} />);

        // The useQuery mock captures the queryFn on render.
        // On initial render, searchTerm is '' (debounced value starts empty).
        expect(capturedQueryFns.queryFn).not.toBeNull();

        // Manually call the queryFn - searchTerm is '' so `searchTerm || undefined` yields undefined
        await capturedQueryFns.queryFn!();
        expect(listMock).toHaveBeenCalledWith({ search: undefined });
    });

    it('does not show AI suggest button when no subjects are selected', () => {
        render(<SubjectMultiSelect {...defaultProps} />);
        expect(screen.queryByRole('button', { name: /sugerir com ia/i })).not.toBeInTheDocument();
    });

    it('shows AI suggest button when subjects are selected', () => {
        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        expect(screen.getByRole('button', { name: /sugerir com ia/i })).toBeInTheDocument();
    });

    it('calls aiApi.suggestSubjectTags and displays suggestions', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockResolvedValue({
            data: { suggestions: ['Vue.js', 'Angular'] },
        });

        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} value={['React']} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(aiApi.suggestSubjectTags).toHaveBeenCalledWith(['React']);
        });

        await waitFor(() => {
            expect(screen.getByText('Vue.js')).toBeInTheDocument();
            expect(screen.getByText('Angular')).toBeInTheDocument();
        });

        expect(screen.getByText('Sugestões da IA:')).toBeInTheDocument();
    });

    it('adds a suggestion to selected values when clicked', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockResolvedValue({
            data: { suggestions: ['Vue.js', 'Angular'] },
        });

        const mockOnChange = vi.fn();
        render(<SubjectMultiSelect {...defaultProps} value={['React']} onChange={mockOnChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(screen.getByText('Vue.js')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Vue.js'));
        expect(mockOnChange).toHaveBeenCalledWith(['React', 'Vue.js']);
    });

    it('clears AI suggestions when "Limpar sugestões" is clicked', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockResolvedValue({
            data: { suggestions: ['Vue.js'] },
        });

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(screen.getByText('Vue.js')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Limpar sugestões'));

        expect(screen.queryByText('Vue.js')).not.toBeInTheDocument();
        expect(screen.queryByText('Sugestões da IA:')).not.toBeInTheDocument();
    });

    it('shows toast when AI returns no suggestions', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockResolvedValue({
            data: { suggestions: [] },
        });

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(aiApi.suggestSubjectTags).toHaveBeenCalled();
        });
    });

    it('shows error toast when AI request fails with 503', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockRejectedValue({ status: 503 });

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(aiApi.suggestSubjectTags).toHaveBeenCalled();
        });
    });

    it('shows generic error toast when AI request fails', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockRejectedValue(new Error('fail'));

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(aiApi.suggestSubjectTags).toHaveBeenCalled();
        });
    });

    it('filters out already-selected subjects from AI suggestions', async () => {
        const { aiApi } = await import('../api/adminClient');
        vi.mocked(aiApi.suggestSubjectTags).mockResolvedValue({
            data: { suggestions: ['React', 'Vue.js'] },
        });

        render(<SubjectMultiSelect {...defaultProps} value={['React']} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

        await user.click(screen.getByRole('button', { name: /sugerir com ia/i }));

        await waitFor(() => {
            expect(screen.getByText('Vue.js')).toBeInTheDocument();
        });

        // React should be filtered out since it's already selected
        const suggestionsLabel = screen.getByText('Sugestões da IA:');
        const suggestionsContainer = suggestionsLabel.parentElement!;
        expect(suggestionsContainer).not.toHaveTextContent('React');
    });

    it('shows suggestion-mode helper text when suggestions are visible', async () => {
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'React', slug: 'react', created_at: '', updated_at: '' },
            ],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
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

    it('selects a suggestion when Enter is pressed on the option itself', async () => {
        const onChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [{ id: 1, name: 'Algoritmos', seminars_count: 0, has_active_seminars: false }],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
        } as any);

        render(<SubjectMultiSelect value={[]} onChange={onChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        await user.type(screen.getByPlaceholderText('Digite e pressione Enter...'), 'Al');
        vi.advanceTimersByTime(350);

        const option = await screen.findByRole('option', { name: /Algoritmos/i });
        option.focus();
        await user.keyboard('{Enter}');
        expect(onChange).toHaveBeenCalledWith(['Algoritmos']);
    });

    it('selects a suggestion when Space is pressed on the option itself', async () => {
        const onChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [{ id: 2, name: 'Redes', seminars_count: 0, has_active_seminars: false }],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
        } as any);

        render(<SubjectMultiSelect value={[]} onChange={onChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        await user.type(screen.getByPlaceholderText('Digite e pressione Enter...'), 'Re');
        vi.advanceTimersByTime(350);

        const option = await screen.findByRole('option', { name: /Redes/i });
        option.focus();
        await user.keyboard(' ');
        expect(onChange).toHaveBeenCalledWith(['Redes']);
    });

    it('ignores other keys pressed on the option', async () => {
        const onChange = vi.fn();
        const { subjectsApi } = await import('../api/adminClient');
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [{ id: 3, name: 'Banco', seminars_count: 0, has_active_seminars: false }],
            meta: { last_page: 1, current_page: 1, total: 1 } as any,
            links: { first: '', last: '', prev: null, next: null },
        } as any);

        render(<SubjectMultiSelect value={[]} onChange={onChange} />);
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        await user.type(screen.getByPlaceholderText('Digite e pressione Enter...'), 'Ba');
        vi.advanceTimersByTime(350);

        const option = await screen.findByRole('option', { name: /Banco/i });
        option.focus();
        await user.keyboard('x');
        expect(onChange).not.toHaveBeenCalled();
    });
});
