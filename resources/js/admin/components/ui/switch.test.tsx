import { render, screen, userEvent } from '@/test/test-utils';
import { Switch } from './switch';

describe('Switch', () => {
    it('renders a switch', () => {
        render(<Switch />);
        expect(screen.getByRole('switch')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Switch className="custom-class" />);
        expect(screen.getByRole('switch')).toHaveClass('custom-class');
    });

    it('supports disabled state', () => {
        render(<Switch disabled />);
        expect(screen.getByRole('switch')).toBeDisabled();
    });

    it('toggles state on click', async () => {
        const user = userEvent.setup();
        const onCheckedChange = vi.fn();
        render(<Switch onCheckedChange={onCheckedChange} />);

        const switchEl = screen.getByRole('switch');
        expect(switchEl).toHaveAttribute('data-state', 'unchecked');

        await user.click(switchEl);
        expect(onCheckedChange).toHaveBeenCalledWith(true);
    });

    it('renders as checked when checked prop is true', () => {
        render(<Switch checked />);
        expect(screen.getByRole('switch')).toHaveAttribute('data-state', 'checked');
    });
});
