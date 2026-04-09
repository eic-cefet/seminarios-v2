import { render, screen } from '@/test/test-utils';
import {
    Select,
    SelectGroup,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
    SelectLabel,
    SelectSeparator,
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

    it('renders SelectLabel inside a group', () => {
        render(
            <Select defaultValue="a" open>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectLabel>My Group Label</SelectLabel>
                        <SelectItem value="a">Option A</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByText('My Group Label')).toBeInTheDocument();
    });

    it('renders SelectLabel with custom className', () => {
        render(
            <Select defaultValue="a" open>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectLabel className="custom-label">Label</SelectLabel>
                        <SelectItem value="a">Option A</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByText('Label')).toHaveClass('custom-label');
    });

    it('renders SelectSeparator', () => {
        render(
            <Select defaultValue="a" open>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                    <SelectSeparator data-testid="select-separator" />
                    <SelectItem value="b">Option B</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByTestId('select-separator')).toBeInTheDocument();
    });

    it('renders SelectSeparator with custom className', () => {
        render(
            <Select defaultValue="a" open>
                <SelectTrigger>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="a">Option A</SelectItem>
                    <SelectSeparator data-testid="select-separator" className="custom-sep" />
                    <SelectItem value="b">Option B</SelectItem>
                </SelectContent>
            </Select>,
        );

        expect(screen.getByTestId('select-separator')).toHaveClass('custom-sep');
    });
});
