import { render, screen } from '@/test/test-utils';
import { Input } from './input';

describe('Input', () => {
    it('renders an input element', () => {
        render(<Input placeholder="Enter text" />);
        expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
    });

    it('applies type prop', () => {
        render(<Input type="email" placeholder="Email" />);
        expect(screen.getByPlaceholderText('Email')).toHaveAttribute('type', 'email');
    });

    it('applies custom className', () => {
        render(<Input className="custom" placeholder="test" />);
        expect(screen.getByPlaceholderText('test')).toHaveClass('custom');
    });

    it('supports disabled state', () => {
        render(<Input disabled placeholder="test" />);
        expect(screen.getByPlaceholderText('test')).toBeDisabled();
    });

    it('forwards value and onChange', () => {
        const onChange = vi.fn();
        render(<Input value="test" onChange={onChange} />);
        expect(screen.getByDisplayValue('test')).toBeInTheDocument();
    });
});
