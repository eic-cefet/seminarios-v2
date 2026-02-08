import { render, screen, fireEvent } from '@/test/test-utils';
import { MarkdownEditor } from './MarkdownEditor';

describe('MarkdownEditor', () => {
    const defaultProps = {
        value: '',
        onChange: vi.fn(),
    };

    it('renders a textarea', () => {
        render(<MarkdownEditor {...defaultProps} />);
        expect(screen.getByPlaceholderText('Escreva a descrição em Markdown...')).toBeInTheDocument();
    });

    it('renders the label when provided', () => {
        render(<MarkdownEditor {...defaultProps} label="Descrição" />);
        expect(screen.getByText('Descrição')).toBeInTheDocument();
    });

    it('does not render a label when not provided', () => {
        render(<MarkdownEditor {...defaultProps} />);
        expect(screen.queryByText('Descrição')).not.toBeInTheDocument();
    });

    it('renders the write and preview tabs', () => {
        render(<MarkdownEditor {...defaultProps} />);
        expect(screen.getByText('Escrever')).toBeInTheDocument();
        expect(screen.getByText('Visualizar')).toBeInTheDocument();
    });

    it('calls onChange when typing in the textarea', () => {
        const onChange = vi.fn();
        render(<MarkdownEditor {...defaultProps} onChange={onChange} />);

        fireEvent.change(screen.getByPlaceholderText('Escreva a descrição em Markdown...'), {
            target: { value: 'Hello world' },
        });
        expect(onChange).toHaveBeenCalledWith('Hello world');
    });

    it('displays the current value in the textarea', () => {
        render(<MarkdownEditor {...defaultProps} value="My content" />);
        expect(screen.getByDisplayValue('My content')).toBeInTheDocument();
    });

    it('renders an error message when provided', () => {
        render(<MarkdownEditor {...defaultProps} error="Campo obrigatório" />);
        expect(screen.getByText('Campo obrigatório')).toBeInTheDocument();
    });

    it('does not render an error message when not provided', () => {
        render(<MarkdownEditor {...defaultProps} />);
        expect(screen.queryByText('Campo obrigatório')).not.toBeInTheDocument();
    });

    it('renders the write tab as default active tab', () => {
        render(<MarkdownEditor {...defaultProps} />);
        // The textarea should be visible (write tab is default)
        expect(screen.getByPlaceholderText('Escreva a descrição em Markdown...')).toBeVisible();
    });

    it('renders both tab triggers', () => {
        render(<MarkdownEditor {...defaultProps} value="**Bold text**" />);
        // Both tab triggers should be present and clickable
        const writeTab = screen.getByText('Escrever');
        const previewTab = screen.getByText('Visualizar');
        expect(writeTab).toHaveAttribute('data-state', 'active');
        expect(previewTab).toHaveAttribute('data-state', 'inactive');
    });
});
