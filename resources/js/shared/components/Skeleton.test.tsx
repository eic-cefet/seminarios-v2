import { render } from '@testing-library/react';
import { Skeleton } from './Skeleton';

describe('Skeleton', () => {
    it('renders a div with the skeleton class', () => {
        const { container } = render(<Skeleton data-testid="sk" />);
        const el = container.firstChild as HTMLElement;
        expect(el.tagName).toBe('DIV');
        expect(el).toHaveClass('skeleton');
    });

    it('forwards className and merges with skeleton', () => {
        const { container } = render(<Skeleton className="h-10 w-20" />);
        const el = container.firstChild as HTMLElement;
        expect(el).toHaveClass('skeleton');
        expect(el).toHaveClass('h-10');
        expect(el).toHaveClass('w-20');
    });

    it('uses skeleton-on-primary when tone="onPrimary"', () => {
        const { container } = render(<Skeleton tone="onPrimary" />);
        const el = container.firstChild as HTMLElement;
        expect(el).toHaveClass('skeleton-on-primary');
        expect(el).not.toHaveClass('skeleton');
    });

    it('is aria-hidden by default', () => {
        const { container } = render(<Skeleton />);
        const el = container.firstChild as HTMLElement;
        expect(el).toHaveAttribute('aria-hidden', 'true');
    });

    it('has rounded-md by default', () => {
        const { container } = render(<Skeleton />);
        const el = container.firstChild as HTMLElement;
        expect(el).toHaveClass('rounded-md');
    });
});
