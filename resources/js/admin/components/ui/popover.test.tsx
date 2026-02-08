import { render, screen, userEvent } from '@/test/test-utils';
import { Popover, PopoverTrigger, PopoverContent } from './popover';

describe('Popover', () => {
    it('renders popover trigger', () => {
        render(
            <Popover>
                <PopoverTrigger>Open Popover</PopoverTrigger>
                <PopoverContent>Popover body</PopoverContent>
            </Popover>,
        );

        expect(screen.getByText('Open Popover')).toBeInTheDocument();
    });

    it('does not show content initially', () => {
        render(
            <Popover>
                <PopoverTrigger>Open Popover</PopoverTrigger>
                <PopoverContent>Popover body</PopoverContent>
            </Popover>,
        );

        expect(screen.queryByText('Popover body')).not.toBeInTheDocument();
    });

    it('shows popover content when trigger is clicked', async () => {
        const user = userEvent.setup();
        render(
            <Popover>
                <PopoverTrigger>Open Popover</PopoverTrigger>
                <PopoverContent>Popover body</PopoverContent>
            </Popover>,
        );

        await user.click(screen.getByText('Open Popover'));
        expect(screen.getByText('Popover body')).toBeInTheDocument();
    });

    it('applies custom className to content', async () => {
        const user = userEvent.setup();
        render(
            <Popover>
                <PopoverTrigger>Open</PopoverTrigger>
                <PopoverContent className="custom-popover">Content</PopoverContent>
            </Popover>,
        );

        await user.click(screen.getByText('Open'));
        expect(screen.getByText('Content')).toHaveClass('custom-popover');
    });
});
