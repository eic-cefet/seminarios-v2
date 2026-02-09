import { render } from '@testing-library/react';
import { Separator } from './separator';

describe('Separator', () => {
    it('renders a separator element', () => {
        const { container } = render(<Separator />);
        expect(container.querySelector('[role="separator"], [data-orientation]')).toBeTruthy();
    });

    it('applies custom className', () => {
        const { container } = render(<Separator className="my-4" />);
        const sep = container.firstChild as HTMLElement;
        expect(sep).toHaveClass('my-4');
    });

    it('renders with vertical orientation', () => {
        const { container } = render(<Separator orientation="vertical" />);
        const sep = container.firstChild as HTMLElement;
        expect(sep).toHaveAttribute('data-orientation', 'vertical');
    });

    it('renders with horizontal orientation by default', () => {
        const { container } = render(<Separator />);
        const sep = container.firstChild as HTMLElement;
        expect(sep).toHaveAttribute('data-orientation', 'horizontal');
    });
});
