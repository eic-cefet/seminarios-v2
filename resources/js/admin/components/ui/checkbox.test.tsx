import { render, screen, userEvent } from '@/test/test-utils';
import { Checkbox } from './checkbox';

describe('Checkbox', () => {
    it('renders a checkbox', () => {
        render(<Checkbox />);
        expect(screen.getByRole('checkbox')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Checkbox className="custom-class" />);
        expect(screen.getByRole('checkbox')).toHaveClass('custom-class');
    });

    it('supports disabled state', () => {
        render(<Checkbox disabled />);
        expect(screen.getByRole('checkbox')).toBeDisabled();
    });

    it('toggles checked state on click', async () => {
        const user = userEvent.setup();
        const onCheckedChange = vi.fn();
        render(<Checkbox onCheckedChange={onCheckedChange} />);

        const checkbox = screen.getByRole('checkbox');
        expect(checkbox).not.toBeChecked();

        await user.click(checkbox);
        expect(onCheckedChange).toHaveBeenCalledWith(true);
    });

    it('renders as checked when checked prop is true', () => {
        render(<Checkbox checked />);
        expect(screen.getByRole('checkbox')).toBeChecked();
    });
});
