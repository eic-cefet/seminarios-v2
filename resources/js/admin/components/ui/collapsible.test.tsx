import { render, screen, userEvent } from '@/test/test-utils';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from './collapsible';

describe('Collapsible', () => {
    it('renders collapsible component with trigger', () => {
        render(
            <Collapsible>
                <CollapsibleTrigger>Toggle</CollapsibleTrigger>
                <CollapsibleContent>Hidden content</CollapsibleContent>
            </Collapsible>,
        );

        expect(screen.getByText('Toggle')).toBeInTheDocument();
    });

    it('hides content by default', () => {
        render(
            <Collapsible>
                <CollapsibleTrigger>Toggle</CollapsibleTrigger>
                <CollapsibleContent>Hidden content</CollapsibleContent>
            </Collapsible>,
        );

        // Radix Collapsible removes content from DOM when closed
        expect(screen.queryByText('Hidden content')).not.toBeInTheDocument();
    });

    it('shows content when open prop is true', () => {
        render(
            <Collapsible open>
                <CollapsibleTrigger>Toggle</CollapsibleTrigger>
                <CollapsibleContent>Visible content</CollapsibleContent>
            </Collapsible>,
        );

        expect(screen.getByText('Visible content')).toBeVisible();
    });

    it('expands content when trigger is clicked', async () => {
        const user = userEvent.setup();
        render(
            <Collapsible>
                <CollapsibleTrigger>Toggle</CollapsibleTrigger>
                <CollapsibleContent>Expandable content</CollapsibleContent>
            </Collapsible>,
        );

        await user.click(screen.getByText('Toggle'));
        expect(screen.getByText('Expandable content')).toBeVisible();
    });

    it('collapses content when trigger is clicked again', async () => {
        const user = userEvent.setup();
        render(
            <Collapsible>
                <CollapsibleTrigger>Toggle</CollapsibleTrigger>
                <CollapsibleContent>Expandable content</CollapsibleContent>
            </Collapsible>,
        );

        await user.click(screen.getByText('Toggle'));
        expect(screen.getByText('Expandable content')).toBeVisible();

        await user.click(screen.getByText('Toggle'));
        expect(screen.queryByText('Expandable content')).not.toBeInTheDocument();
    });
});
