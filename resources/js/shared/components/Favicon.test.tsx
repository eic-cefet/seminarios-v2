import { render } from '@/test/test-utils';
import { Favicon } from './Favicon';

describe('Favicon', () => {
    it('adds favicon link elements to document head', () => {
        render(<Favicon />);

        const iconLink = document.querySelector('link[rel="icon"][type="image/x-icon"]');
        expect(iconLink).not.toBeNull();
        expect(iconLink?.getAttribute('href')).toBe('/favicon/favicon.ico');
    });

    it('adds apple-touch-icon links', () => {
        render(<Favicon />);

        const appleTouchIcon = document.querySelector('link[rel="apple-touch-icon"][sizes="180x180"]');
        expect(appleTouchIcon).not.toBeNull();
    });

    it('adds meta tags', () => {
        render(<Favicon />);

        const themeColor = document.querySelector('meta[name="theme-color"]');
        expect(themeColor).not.toBeNull();
        expect(themeColor?.getAttribute('content')).toBe('#ffffff');
    });

    it('does not duplicate elements on re-render', () => {
        const { rerender } = render(<Favicon />);
        rerender(<Favicon />);

        const icons = document.querySelectorAll('link[rel="icon"][type="image/x-icon"][href="/favicon/favicon.ico"]');
        expect(icons.length).toBe(1);
    });

    it('renders nothing visible', () => {
        const { container } = render(<Favicon />);
        expect(container.firstChild).toBeNull();
    });
});
