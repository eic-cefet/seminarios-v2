import { render, screen, userEvent } from '@/test/test-utils';
import { MultiSelect, type MultiSelectOption } from './multi-select';

const options: MultiSelectOption[] = [
    { value: '1', label: 'Option 1' },
    { value: '2', label: 'Option 2' },
    { value: '3', label: 'Option 3' },
];

describe('MultiSelect', () => {
    it('renders multi-select component', () => {
        render(
            <MultiSelect options={options} selected={[]} onChange={vi.fn()} />,
        );

        expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('renders default placeholder', () => {
        render(
            <MultiSelect options={options} selected={[]} onChange={vi.fn()} />,
        );

        expect(screen.getByText('Selecione...')).toBeInTheDocument();
    });

    it('renders custom placeholder', () => {
        render(
            <MultiSelect
                options={options}
                selected={[]}
                onChange={vi.fn()}
                placeholder="Pick items"
            />,
        );

        expect(screen.getByText('Pick items')).toBeInTheDocument();
    });

    it('renders selected items as badges', () => {
        render(
            <MultiSelect
                options={options}
                selected={['1', '3']}
                onChange={vi.fn()}
            />,
        );

        expect(screen.getByText('Option 1')).toBeInTheDocument();
        expect(screen.getByText('Option 3')).toBeInTheDocument();
        expect(screen.queryByText('Selecione...')).not.toBeInTheDocument();
    });

    it('shows options when trigger is clicked', async () => {
        const user = userEvent.setup();
        render(
            <MultiSelect options={options} selected={[]} onChange={vi.fn()} />,
        );

        await user.click(screen.getByRole('combobox'));

        expect(screen.getByText('Option 1')).toBeInTheDocument();
        expect(screen.getByText('Option 2')).toBeInTheDocument();
        expect(screen.getByText('Option 3')).toBeInTheDocument();
    });

    it('calls onChange when an option is selected', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <MultiSelect options={options} selected={[]} onChange={onChange} />,
        );

        await user.click(screen.getByRole('combobox'));
        await user.click(screen.getByText('Option 2'));

        expect(onChange).toHaveBeenCalledWith(['2']);
    });

    it('calls onChange to remove when a selected option is clicked', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <MultiSelect
                options={options}
                selected={['1', '2']}
                onChange={onChange}
            />,
        );

        await user.click(screen.getByRole('combobox'));
        // "Option 1" appears both as badge and as dropdown option, get the dropdown one (last)
        const option1Elements = screen.getAllByText('Option 1');
        await user.click(option1Elements[option1Elements.length - 1]);

        expect(onChange).toHaveBeenCalledWith(['2']);
    });

    it('removes selected item when clicking the X badge button (handleRemove)', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <MultiSelect
                options={options}
                selected={['1', '2']}
                onChange={onChange}
            />,
        );

        // Find the X icon inside the badge for "Option 1"
        const option1Badge = screen.getByText('Option 1');
        const xIcon = option1Badge.parentElement!.querySelector('svg.cursor-pointer')!;
        await user.click(xIcon);

        // handleRemove should call onChange with the item filtered out
        expect(onChange).toHaveBeenCalledWith(['2']);
    });

    it('stopPropagation prevents popover from opening when removing badge', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        render(
            <MultiSelect
                options={options}
                selected={['1', '2']}
                onChange={onChange}
            />,
        );

        // Click the X on the "Option 1" badge
        const option1Badge = screen.getByText('Option 1');
        const xIcon = option1Badge.parentElement!.querySelector('svg')!;
        await user.click(xIcon);

        // handleRemove was called (onChange fired)
        expect(onChange).toHaveBeenCalled();
        // The popover should NOT have opened (stopPropagation prevents the trigger click)
        expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });
});
