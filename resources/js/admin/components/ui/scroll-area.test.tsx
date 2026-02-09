import { render, screen } from '@/test/test-utils';
import { ScrollArea, ScrollBar } from './scroll-area';

describe('ScrollArea', () => {
    it('renders scroll area with children', () => {
        render(
            <ScrollArea>
                <p>Scrollable content</p>
            </ScrollArea>,
        );

        expect(screen.getByText('Scrollable content')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        const { container } = render(
            <ScrollArea className="custom-scroll">
                <p>Content</p>
            </ScrollArea>,
        );

        const scrollRoot = container.firstChild as HTMLElement;
        expect(scrollRoot).toHaveClass('custom-scroll');
    });

    it('renders multiple children', () => {
        render(
            <ScrollArea>
                <p>Item 1</p>
                <p>Item 2</p>
                <p>Item 3</p>
            </ScrollArea>,
        );

        expect(screen.getByText('Item 1')).toBeInTheDocument();
        expect(screen.getByText('Item 2')).toBeInTheDocument();
        expect(screen.getByText('Item 3')).toBeInTheDocument();
    });

    it('renders ScrollBar with horizontal orientation', () => {
        render(
            <ScrollArea>
                <p>Horizontal content</p>
                <ScrollBar orientation="horizontal" />
            </ScrollArea>,
        );

        // The component renders without error and content is visible
        expect(screen.getByText('Horizontal content')).toBeInTheDocument();
    });

    it('renders ScrollBar with explicit vertical orientation', () => {
        render(
            <ScrollArea>
                <p>Vertical content</p>
                <ScrollBar orientation="vertical" />
            </ScrollArea>,
        );

        expect(screen.getByText('Vertical content')).toBeInTheDocument();
    });
});
