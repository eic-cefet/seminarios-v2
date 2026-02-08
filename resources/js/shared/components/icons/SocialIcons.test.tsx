import { render } from '@/test/test-utils';
import { GoogleIcon, GithubIcon } from './SocialIcons';

describe('SocialIcons', () => {
    describe('GoogleIcon', () => {
        it('renders an SVG element', () => {
            const { container } = render(<GoogleIcon />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('applies className', () => {
            const { container } = render(<GoogleIcon className="h-5 w-5" />);
            expect(container.querySelector('svg')?.getAttribute('class')).toBe('h-5 w-5');
        });

        it('renders Google brand colors', () => {
            const { container } = render(<GoogleIcon />);
            const paths = container.querySelectorAll('path');
            expect(paths.length).toBe(4);
        });
    });

    describe('GithubIcon', () => {
        it('renders an SVG element', () => {
            const { container } = render(<GithubIcon />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('applies className', () => {
            const { container } = render(<GithubIcon className="h-6 w-6" />);
            expect(container.querySelector('svg')?.getAttribute('class')).toBe('h-6 w-6');
        });

        it('renders a path element', () => {
            const { container } = render(<GithubIcon />);
            expect(container.querySelector('path')).toBeInTheDocument();
        });
    });
});
