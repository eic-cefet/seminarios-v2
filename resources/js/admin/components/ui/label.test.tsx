import { render, screen } from '@/test/test-utils';
import { Label } from './label';

describe('Label', () => {
    it('renders label text', () => {
        render(<Label>Email</Label>);
        expect(screen.getByText('Email')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Label className="custom">Name</Label>);
        expect(screen.getByText('Name')).toHaveClass('custom');
    });

    it('supports htmlFor prop', () => {
        render(<Label htmlFor="email-input">Email</Label>);
        expect(screen.getByText('Email')).toHaveAttribute('for', 'email-input');
    });
});
