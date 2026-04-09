import { render, screen } from '@/test/test-utils';
import { Button } from './button';

describe('Button', () => {
    it('renders children', () => {
        render(<Button>Click me</Button>);
        expect(screen.getByText('Click me')).toBeInTheDocument();
    });

    it('renders as button element by default', () => {
        render(<Button>Test</Button>);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Button className="custom-class">Test</Button>);
        expect(screen.getByRole('button')).toHaveClass('custom-class');
    });

    it('supports disabled state', () => {
        render(<Button disabled>Test</Button>);
        expect(screen.getByRole('button')).toBeDisabled();
    });

    it('renders as child component when asChild', () => {
        render(
            <Button asChild>
                <a href="/test">Link Button</a>
            </Button>,
        );
        const link = screen.getByText('Link Button');
        expect(link.tagName).toBe('A');
    });

    it('handles onClick', () => {
        const onClick = vi.fn();
        render(<Button onClick={onClick}>Click</Button>);
        screen.getByRole('button').click();
        expect(onClick).toHaveBeenCalledTimes(1);
    });
});
