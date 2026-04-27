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

    it('portals into the nearest [role="dialog"] ancestor when present', () => {
        const dialog = document.createElement('div');
        dialog.setAttribute('role', 'dialog');
        document.body.appendChild(dialog);

        const anchor = document.createElement('div');
        anchor.getBoundingClientRect = vi.fn(() => ({
            top: 0, left: 0, bottom: 0, right: 0, width: 100, height: 30, x: 0, y: 0, toJSON: () => {},
        }));
        dialog.appendChild(anchor);

        const ref = createRef<HTMLDivElement>();
        Object.defineProperty(ref, 'current', { value: anchor, writable: true });

        render(
            <DropdownPortal anchorRef={ref} isOpen={true}>
                <div data-testid="dropdown-content">Inside Dialog</div>
            </DropdownPortal>,
        );

        const content = screen.getByTestId('dropdown-content');
        expect(dialog.contains(content)).toBe(true);

        document.body.removeChild(dialog);
    });

    it('handles anchor becoming null during scroll/resize', () => {
        const ref = createAnchorRef();
        render(
            <DropdownPortal anchorRef={ref} isOpen={true}>
                <div>Content</div>
            </DropdownPortal>,
        );

        // Verify content renders initially
        expect(screen.getByText('Content')).toBeInTheDocument();

        // Set ref.current to null to simulate anchor removal
        Object.defineProperty(ref, 'current', { value: null, writable: true });

        // Trigger scroll event - updatePosition should bail out early
        window.dispatchEvent(new Event('scroll'));

        // Content should still be in the DOM (portal doesn't unmount)
        expect(screen.getByText('Content')).toBeInTheDocument();
    });

    it('renders nothing when anchorRef.current is null', () => {
        const ref = createRef<HTMLDivElement>();
        // ref.current is null by default — without an anchor we cannot resolve a
        // container, so the portal stays unmounted.
        render(
            <DropdownPortal anchorRef={ref} isOpen={true}>
                <div>Should not position</div>
            </DropdownPortal>,
        );

        expect(screen.queryByText('Should not position')).not.toBeInTheDocument();
    });
});
