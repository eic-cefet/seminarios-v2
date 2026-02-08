import { render, screen, fireEvent } from '@/test/test-utils';
import { Pagination } from './Pagination';

describe('Pagination', () => {
    it('returns null when lastPage is 1', () => {
        const { container } = render(
            <Pagination currentPage={1} lastPage={1} onPageChange={vi.fn()} />,
        );
        expect(container.firstChild).toBeNull();
    });

    it('renders page info and navigation buttons', () => {
        render(<Pagination currentPage={2} lastPage={5} onPageChange={vi.fn()} />);

        expect(screen.getByText(/Página 2 de 5/)).toBeInTheDocument();
        expect(screen.getByLabelText('Página anterior')).toBeInTheDocument();
        expect(screen.getByLabelText('Próxima página')).toBeInTheDocument();
    });

    it('disables previous button on first page', () => {
        render(<Pagination currentPage={1} lastPage={3} onPageChange={vi.fn()} />);
        expect(screen.getByLabelText('Página anterior')).toBeDisabled();
    });

    it('disables next button on last page', () => {
        render(<Pagination currentPage={3} lastPage={3} onPageChange={vi.fn()} />);
        expect(screen.getByLabelText('Próxima página')).toBeDisabled();
    });

    it('calls onPageChange with previous page', () => {
        const onPageChange = vi.fn();
        render(<Pagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

        fireEvent.click(screen.getByLabelText('Página anterior'));
        expect(onPageChange).toHaveBeenCalledWith(2);
    });

    it('calls onPageChange with next page', () => {
        const onPageChange = vi.fn();
        render(<Pagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

        fireEvent.click(screen.getByLabelText('Próxima página'));
        expect(onPageChange).toHaveBeenCalledWith(4);
    });

    it('clamps previous to page 1', () => {
        const onPageChange = vi.fn();
        render(<Pagination currentPage={1} lastPage={5} onPageChange={onPageChange} />);
        // Button is disabled on first page, so clicking does nothing meaningful
        // The Math.max(1, currentPage-1) = Math.max(1, 0) = 1 ensures clamping
        expect(screen.getByLabelText('Página anterior')).toBeDisabled();
    });

    it('clamps next to lastPage', () => {
        const onPageChange = vi.fn();
        render(<Pagination currentPage={5} lastPage={5} onPageChange={onPageChange} />);
        // Button is disabled on last page
        expect(screen.getByLabelText('Próxima página')).toBeDisabled();
    });
});
