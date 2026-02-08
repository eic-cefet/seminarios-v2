import { render, screen } from '@/test/test-utils';
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from './select';

describe('Select', () => {
    it('renders select trigger', () => {
        render(
            <Select>
                <SelectTrigger>
                    <SelectValue placeholder="Choose an option" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                    <SelectItem value="b">Option B</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('renders placeholder text', () => {
        render(
            <Select>
                <SelectTrigger>
                    <SelectValue placeholder="Choose an option" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByText('Choose an option')).toBeInTheDocument();
    });

    it('applies custom className to trigger', () => {
        render(
            <Select>
                <SelectTrigger className="custom-trigger">
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByRole('combobox')).toHaveClass('custom-trigger');
    });

    it('supports disabled state', () => {
        render(
            <Select disabled>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByRole('combobox')).toBeDisabled();
    });

    it('displays selected value', () => {
        render(
            <Select defaultValue="a">
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                    <SelectItem value="b">Option B</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByText('Option A')).toBeInTheDocument();
    });
});
