import { render, screen, fireEvent } from '@/test/test-utils';
import { SocialLoginButtons } from './SocialLoginButtons';

describe('SocialLoginButtons', () => {
    it('renders Google and GitHub buttons with default labels', () => {
        render(<SocialLoginButtons onSocialLogin={vi.fn()} />);

        expect(screen.getByText('Continuar com Google')).toBeInTheDocument();
        expect(screen.getByText('Continuar com GitHub')).toBeInTheDocument();
    });

    it('uses custom action label', () => {
        render(<SocialLoginButtons onSocialLogin={vi.fn()} actionLabel="Entrar" />);

        expect(screen.getByText('Entrar com Google')).toBeInTheDocument();
        expect(screen.getByText('Entrar com GitHub')).toBeInTheDocument();
    });

    it('renders divider text', () => {
        render(<SocialLoginButtons onSocialLogin={vi.fn()} dividerText="ou continue com" />);
        expect(screen.getByText('ou continue com')).toBeInTheDocument();
    });

    it('calls onSocialLogin with google when Google button clicked', () => {
        const onSocialLogin = vi.fn();
        render(<SocialLoginButtons onSocialLogin={onSocialLogin} />);

        fireEvent.click(screen.getByText('Continuar com Google'));
        expect(onSocialLogin).toHaveBeenCalledWith('google');
    });

    it('calls onSocialLogin with github when GitHub button clicked', () => {
        const onSocialLogin = vi.fn();
        render(<SocialLoginButtons onSocialLogin={onSocialLogin} />);

        fireEvent.click(screen.getByText('Continuar com GitHub'));
        expect(onSocialLogin).toHaveBeenCalledWith('github');
    });
});
