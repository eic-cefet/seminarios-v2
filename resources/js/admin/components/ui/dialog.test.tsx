import { render, screen, userEvent } from '@/test/test-utils';
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from './dialog';

describe('Dialog', () => {
    it('renders dialog trigger', () => {
        render(
            <Dialog>
                <DialogTrigger>Open Dialog</DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Title</DialogTitle>
                    </DialogHeader>
                </DialogContent>
            </Dialog>,
        );

        expect(screen.getByText('Open Dialog')).toBeInTheDocument();
    });

    it('does not show content initially', () => {
        render(
            <Dialog>
                <DialogTrigger>Open Dialog</DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Dialog Title</DialogTitle>
                        <DialogDescription>Dialog body</DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>,
        );

        expect(screen.queryByText('Dialog Title')).not.toBeInTheDocument();
    });

    it('opens dialog and shows content when trigger is clicked', async () => {
        const user = userEvent.setup();
        render(
            <Dialog>
                <DialogTrigger>Open Dialog</DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Dialog Title</DialogTitle>
                        <DialogDescription>Dialog description</DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>,
        );

        await user.click(screen.getByText('Open Dialog'));

        expect(screen.getByText('Dialog Title')).toBeInTheDocument();
        expect(screen.getByText('Dialog description')).toBeInTheDocument();
    });

    it('shows close button inside dialog content', async () => {
        const user = userEvent.setup();
        render(
            <Dialog>
                <DialogTrigger>Open</DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Title</DialogTitle>
                    </DialogHeader>
                </DialogContent>
            </Dialog>,
        );

        await user.click(screen.getByText('Open'));
        expect(screen.getByText('Close')).toBeInTheDocument();
    });
});
