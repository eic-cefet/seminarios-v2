import { render, screen } from '@/test/test-utils';
import { DropdownPortal } from './DropdownPortal';
import { createRef } from 'react';

describe('DropdownPortal', () => {
    function createAnchorRef() {
        const ref = createRef<HTMLDivElement>();
        const anchor = document.createElement('div');
        anchor.getBoundingClientRect = vi.fn(() => ({
            top: 100,
            left: 50,
            bottom: 130,
            right: 250,
            width: 200,
            height: 30,
            x: 50,
            y: 100,
            toJSON: () => {},
        }));
        document.body.appendChild(anchor);
        Object.defineProperty(ref, 'current', { value: anchor, writable: true });
        return ref;
    }

    it('renders nothing when isOpen is false', () => {
        const ref = createAnchorRef();
        render(
            <DropdownPortal anchorRef={ref} isOpen={false}>
                <div>Dropdown content</div>
            </DropdownPortal>,
        );
        expect(screen.queryByText('Dropdown content')).not.toBeInTheDocument();
    });

    it('renders children into a portal when isOpen is true', () => {
        const ref = createAnchorRef();
        render(
            <DropdownPortal anchorRef={ref} isOpen={true}>
                <div>Dropdown content</div>
            </DropdownPortal>,
        );
        expect(screen.getByText('Dropdown content')).toBeInTheDocument();
    });

    it('positions portal relative to anchor', () => {
        const ref = createAnchorRef();
        render(
            <DropdownPortal anchorRef={ref} isOpen={true}>
                <div>Content</div>
            </DropdownPortal>,
        );

        const portal = screen.getByText('Content').parentElement;
        expect(portal?.style.position).toBe('absolute');
        expect(portal?.style.width).toBe('200px');
    });
});
