import { render, screen, waitFor, userEvent, fireEvent } from '@/test/test-utils';
import { AiTextToolbar } from './AiTextToolbar';

const mockTransformText = vi.fn();

vi.mock('@admin/api/adminClient', () => ({
    aiApi: {
        transformText: (...args: unknown[]) => mockTransformText(...args),
    },
}));

vi.mock('sonner', () => ({
    toast: {
        warning: vi.fn(),
        error: vi.fn(),
    },
}));

import { toast } from 'sonner';

describe('AiTextToolbar', () => {
    const defaultProps = {
        value: 'Some text',
        onChange: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the IA button', () => {
        render(<AiTextToolbar {...defaultProps} />);
        expect(screen.getByRole('button', { name: /IA/i })).toBeInTheDocument();
    });

    it('shows all 5 action options when popover is opened', async () => {
        render(<AiTextToolbar {...defaultProps} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));

        expect(screen.getByText('Formatar em Markdown')).toBeInTheDocument();
        expect(screen.getByText('Resumir')).toBeInTheDocument();
        expect(screen.getByText('Explicar melhor')).toBeInTheDocument();
        expect(screen.getByText('Tom formal')).toBeInTheDocument();
        expect(screen.getByText('Tom casual')).toBeInTheDocument();
    });

    it('shows warning toast when value is empty', async () => {
        render(<AiTextToolbar {...defaultProps} value="" />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        expect(toast.warning).toHaveBeenCalledWith('Digite algum texto antes de usar a IA.');
        expect(mockTransformText).not.toHaveBeenCalled();
    });

    it('shows warning toast when value is whitespace only', async () => {
        render(<AiTextToolbar {...defaultProps} value="   " />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        expect(toast.warning).toHaveBeenCalledWith('Digite algum texto antes de usar a IA.');
    });

    it('calls transformText and onChange on successful action', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} onChange={onChange} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        await waitFor(() => {
            expect(mockTransformText).toHaveBeenCalledWith('Some text', 'shorten');
            expect(onChange).toHaveBeenCalledWith('AI result');
        });
    });

    it('calls onLoadingChange during action', async () => {
        const onLoadingChange = vi.fn();
        let resolvePromise: (value: unknown) => void;
        mockTransformText.mockReturnValue(new Promise((resolve) => { resolvePromise = resolve; }));

        render(<AiTextToolbar {...defaultProps} onLoadingChange={onLoadingChange} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        expect(onLoadingChange).toHaveBeenCalledWith(true);

        resolvePromise!({ data: { text: 'result' } });

        await waitFor(() => {
            expect(onLoadingChange).toHaveBeenCalledWith(false);
        });
    });

    it('shows error toast when API fails', async () => {
        mockTransformText.mockRejectedValue(new Error('fail'));

        render(<AiTextToolbar {...defaultProps} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        await waitFor(() => {
            expect(toast.error).toHaveBeenCalledWith('Erro ao processar texto com IA. Tente novamente.');
        });
    });

    it('shows undo/redo buttons after first AI action', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} onChange={onChange} />);
        const user = userEvent.setup();

        // No undo/redo initially
        expect(screen.queryByTitle('Desfazer alteração da IA')).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        await waitFor(() => {
            expect(screen.getByTitle('Desfazer alteração da IA')).toBeInTheDocument();
            expect(screen.getByTitle('Refazer alteração da IA')).toBeInTheDocument();
        });

        // Shows version counter
        expect(screen.getByText('2/2')).toBeInTheDocument();
    });

    it('undo reverts to original text', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} value="original" onChange={onChange} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        await waitFor(() => {
            expect(onChange).toHaveBeenCalledWith('AI result');
        });

        // Click undo
        await user.click(screen.getByTitle('Desfazer alteração da IA'));

        expect(onChange).toHaveBeenCalledWith('original');
        expect(screen.getByText('1/2')).toBeInTheDocument();
    });

    it('redo restores AI text after undo', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} value="original" onChange={onChange} />);
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));

        await waitFor(() => {
            expect(screen.getByTitle('Desfazer alteração da IA')).toBeInTheDocument();
        });

        // Undo then redo
        await user.click(screen.getByTitle('Desfazer alteração da IA'));
        expect(onChange).toHaveBeenCalledWith('original');

        await user.click(screen.getByTitle('Refazer alteração da IA'));
        expect(onChange).toHaveBeenCalledWith('AI result');
        expect(screen.getByText('2/2')).toBeInTheDocument();
    });

    it('second AI action appends to version history', async () => {
        const onChange = vi.fn();
        mockTransformText
            .mockResolvedValueOnce({ data: { text: 'v1' } })
            .mockResolvedValueOnce({ data: { text: 'v2' } });

        const { rerender } = render(<AiTextToolbar value="original" onChange={onChange} />);
        const user = userEvent.setup();

        const getIaButton = () => screen.getAllByRole('button').find(b => b.textContent?.trim() === 'IA')!;

        // First action
        await user.click(getIaButton());
        await user.click(screen.getByText('Resumir'));
        await waitFor(() => expect(onChange).toHaveBeenCalledWith('v1'));

        // Rerender with new value
        rerender(<AiTextToolbar value="v1" onChange={onChange} />);

        // Second action
        await user.click(getIaButton());
        await user.click(screen.getByText('Tom formal'));
        await waitFor(() => expect(onChange).toHaveBeenCalledWith('v2'));

        expect(screen.getByText('3/3')).toBeInTheDocument();
    });

    it('undo after second action truncates future versions on new action', async () => {
        const onChange = vi.fn();
        mockTransformText
            .mockResolvedValueOnce({ data: { text: 'v1' } })
            .mockResolvedValueOnce({ data: { text: 'v2' } })
            .mockResolvedValueOnce({ data: { text: 'v3' } });

        const { rerender } = render(<AiTextToolbar value="original" onChange={onChange} />);
        const user = userEvent.setup();

        const getIaButton = () => screen.getAllByRole('button').find(b => b.textContent?.trim() === 'IA')!;

        // First action
        await user.click(getIaButton());
        await user.click(screen.getByText('Resumir'));
        await waitFor(() => expect(onChange).toHaveBeenCalledWith('v1'));
        rerender(<AiTextToolbar value="v1" onChange={onChange} />);

        // Second action
        await user.click(getIaButton());
        await user.click(screen.getByText('Resumir'));
        await waitFor(() => expect(onChange).toHaveBeenCalledWith('v2'));
        rerender(<AiTextToolbar value="v2" onChange={onChange} />);

        // Undo back to v1
        await user.click(screen.getByTitle('Desfazer alteração da IA'));
        rerender(<AiTextToolbar value="v1" onChange={onChange} />);

        // New action from v1 — should truncate v2 and append v3
        await user.click(getIaButton());
        await user.click(screen.getByText('Tom formal'));
        await waitFor(() => expect(onChange).toHaveBeenCalledWith('v3'));

        // Now history is [original, v1, v3] — not [original, v1, v2, v3]
        expect(screen.getByText('3/3')).toBeInTheDocument();
    });

    it('undo is a no-op when already at original version', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} value="original" onChange={onChange} />);
        const user = userEvent.setup();

        // First action to create history
        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));
        await waitFor(() => expect(screen.getByTitle('Desfazer alteração da IA')).toBeInTheDocument());

        // Undo to original
        await user.click(screen.getByTitle('Desfazer alteração da IA'));
        expect(onChange).toHaveBeenCalledWith('original');

        onChange.mockClear();

        // Force click undo button even though disabled (fireEvent bypasses disabled)
        const undoBtn = screen.getByTitle('Desfazer alteração da IA');
        fireEvent.click(undoBtn);

        // onChange should not have been called
        expect(onChange).not.toHaveBeenCalled();
    });

    it('redo is a no-op when already at latest version', async () => {
        const onChange = vi.fn();
        mockTransformText.mockResolvedValue({ data: { text: 'AI result' } });

        render(<AiTextToolbar {...defaultProps} value="original" onChange={onChange} />);
        const user = userEvent.setup();

        // First action
        await user.click(screen.getByRole('button', { name: /IA/i }));
        await user.click(screen.getByText('Resumir'));
        await waitFor(() => expect(screen.getByTitle('Refazer alteração da IA')).toBeInTheDocument());

        onChange.mockClear();

        // Force click redo even though at latest (disabled)
        const redoBtn = screen.getByTitle('Refazer alteração da IA');
        fireEvent.click(redoBtn);

        expect(onChange).not.toHaveBeenCalled();
    });

    it('works with all action types', async () => {
        const actions = [
            { label: 'Formatar em Markdown', action: 'format_markdown' },
            { label: 'Resumir', action: 'shorten' },
            { label: 'Explicar melhor', action: 'explain' },
            { label: 'Tom formal', action: 'formal' },
            { label: 'Tom casual', action: 'casual' },
        ];

        for (const { label, action } of actions) {
            vi.clearAllMocks();
            mockTransformText.mockResolvedValue({ data: { text: 'result' } });

            const onChange = vi.fn();
            const { unmount } = render(<AiTextToolbar value="test" onChange={onChange} />);
            const user = userEvent.setup();

            await user.click(screen.getByRole('button', { name: /IA/i }));
            await user.click(screen.getByText(label));

            await waitFor(() => {
                expect(mockTransformText).toHaveBeenCalledWith('test', action);
            });

            unmount();
        }
    });
});
