import { render } from '@/test/test-utils';
import { Skeleton } from './skeleton';

describe('Skeleton', () => {
    it('renders a div element', () => {
        const { container } = render(<Skeleton />);
        expect(container.firstChild).toBeInstanceOf(HTMLDivElement);
    });

    it('applies animate-pulse class', () => {
        const { container } = render(<Skeleton />);
        expect(container.firstChild).toHaveClass('animate-pulse');
    });

    it('applies custom className', () => {
        const { container } = render(<Skeleton className="h-4 w-20" />);
        expect(container.firstChild).toHaveClass('h-4');
        expect(container.firstChild).toHaveClass('w-20');
    });
});
