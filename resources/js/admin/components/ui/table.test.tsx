import { render, screen } from '@/test/test-utils';
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
    TableFooter,
    TableCaption,
} from './table';

describe('Table', () => {
    it('renders table elements', () => {
        render(
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow>
                        <TableCell>John</TableCell>
                        <TableCell>john@example.com</TableCell>
                    </TableRow>
                </TableBody>
            </Table>,
        );

        expect(screen.getByRole('table')).toBeInTheDocument();
        expect(screen.getAllByRole('rowgroup')).toHaveLength(2);
        expect(screen.getAllByRole('row')).toHaveLength(2);
        expect(screen.getAllByRole('columnheader')).toHaveLength(2);
        expect(screen.getAllByRole('cell')).toHaveLength(2);
    });

    it('renders content in cells', () => {
        render(
            <Table>
                <TableBody>
                    <TableRow>
                        <TableCell>Cell content</TableCell>
                    </TableRow>
                </TableBody>
            </Table>,
        );

        expect(screen.getByText('Cell content')).toBeInTheDocument();
    });

    it('renders table footer', () => {
        render(
            <Table>
                <TableBody>
                    <TableRow>
                        <TableCell>Data</TableCell>
                    </TableRow>
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell>Footer</TableCell>
                    </TableRow>
                </TableFooter>
            </Table>,
        );

        expect(screen.getByText('Footer')).toBeInTheDocument();
    });

    it('renders table caption', () => {
        render(
            <Table>
                <TableCaption>A list of items</TableCaption>
                <TableBody>
                    <TableRow>
                        <TableCell>Data</TableCell>
                    </TableRow>
                </TableBody>
            </Table>,
        );

        expect(screen.getByText('A list of items')).toBeInTheDocument();
    });

    it('applies custom className to Table', () => {
        render(
            <Table className="custom-table">
                <TableBody>
                    <TableRow>
                        <TableCell>Data</TableCell>
                    </TableRow>
                </TableBody>
            </Table>,
        );

        expect(screen.getByRole('table')).toHaveClass('custom-table');
    });
});
