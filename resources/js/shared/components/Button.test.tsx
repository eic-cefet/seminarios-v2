import { render, screen } from '@/test/test-utils';
import { Button } from './Button';

describe('Button', () => {
    it('renders the primary variant with primary background class', () => {
        render(<Button variant="primary">Entrar</Button>);
        const btn = screen.getByRole('button', { name: 'Entrar' });
        expect(btn.className).toContain('bg-primary-600');
    });

    it('renders the default variant', () => {
        render(<Button>Click</Button>);
        expect(screen.getByRole('button').className).toContain('bg-white');
    });

    it('renders as a Slot when asChild', () => {
        render(
            <Button asChild>
                <a href="/x">Go</a>
            </Button>,
        );
        expect(screen.getByRole('link', { name: 'Go' })).toBeInTheDocument();
    });

    it('merges custom className', () => {
        render(<Button className="custom-x">x</Button>);
        expect(screen.getByRole('button').className).toContain('custom-x');
    });

    it('respects disabled prop', () => {
        render(<Button disabled>x</Button>);
        expect(screen.getByRole('button')).toBeDisabled();
    });

    it('handles onClick', () => {
        const onClick = vi.fn();
        render(<Button onClick={onClick}>Click</Button>);
        screen.getByRole('button').click();
        expect(onClick).toHaveBeenCalledTimes(1);
    });
});
