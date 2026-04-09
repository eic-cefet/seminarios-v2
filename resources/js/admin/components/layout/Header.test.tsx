import { render, screen, fireEvent } from '@/test/test-utils';
import { Header } from './Header';

describe('Header', () => {
    it('renders the header element', () => {
        render(<Header />);
        expect(screen.getByRole('banner')).toBeInTheDocument();
    });

    it('renders the menu toggle button with sr-only text', () => {
        render(<Header />);
        expect(screen.getByText('Toggle menu')).toBeInTheDocument();
    });

    it('renders the title when provided', () => {
        render(<Header title="Test Title" />);
        expect(screen.getByText('Test Title')).toBeInTheDocument();
    });

    it('does not render a title when not provided', () => {
        render(<Header />);
        expect(screen.queryByRole('heading')).not.toBeInTheDocument();
    });

    it('calls onMenuClick when menu button is clicked', () => {
        const onMenuClick = vi.fn();
        render(<Header onMenuClick={onMenuClick} />);
        fireEvent.click(screen.getByRole('button'));
        expect(onMenuClick).toHaveBeenCalledTimes(1);
    });
});
