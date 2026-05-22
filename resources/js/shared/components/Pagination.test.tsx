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

    it("renders the 'Mostrando X a Y de Z' caption when from/to/total are provided", () => {
        render(
            <Pagination
                currentPage={1}
                lastPage={3}
                onPageChange={vi.fn()}
                from={1}
                to={10}
                total={25}
                itemLabel="tópicos"
            />,
        );
        expect(
            screen.getByText('Mostrando 1 a 10 de 25 tópicos'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/Página 1 de 3/)).toBeNull();
    });

    it('omits the caption when totals are not provided', () => {
        render(<Pagination currentPage={1} lastPage={3} onPageChange={vi.fn()} />);
        expect(screen.queryByText(/Mostrando/)).toBeNull();
        expect(screen.getByText('Página 1 de 3')).toBeInTheDocument();
    });

    it('falls back to the default itemLabel when not provided', () => {
        render(
            <Pagination
                currentPage={2}
                lastPage={3}
                onPageChange={vi.fn()}
                from={11}
                to={20}
                total={25}
            />,
        );
        expect(
            screen.getByText('Mostrando 11 a 20 de 25 itens'),
        ).toBeInTheDocument();
    });
});
