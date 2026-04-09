import { render, screen, userEvent } from '@/test/test-utils';
import { Tabs, TabsList, TabsTrigger, TabsContent } from './tabs';

describe('Tabs', () => {
    it('renders tabs with tab list and content', () => {
        render(
            <Tabs defaultValue="tab1">
                <TabsList>
                    <TabsTrigger value="tab1">Tab 1</TabsTrigger>
                    <TabsTrigger value="tab2">Tab 2</TabsTrigger>
                </TabsList>
                <TabsContent value="tab1">Content 1</TabsContent>
                <TabsContent value="tab2">Content 2</TabsContent>
            </Tabs>,
        );

        expect(screen.getByRole('tablist')).toBeInTheDocument();
        expect(screen.getByText('Tab 1')).toBeInTheDocument();
        expect(screen.getByText('Tab 2')).toBeInTheDocument();
        expect(screen.getByText('Content 1')).toBeInTheDocument();
    });

    it('clicking tab switches content', async () => {
        const user = userEvent.setup();
        render(
            <Tabs defaultValue="tab1">
                <TabsList>
                    <TabsTrigger value="tab1">Tab 1</TabsTrigger>
                    <TabsTrigger value="tab2">Tab 2</TabsTrigger>
                </TabsList>
                <TabsContent value="tab1">Content 1</TabsContent>
                <TabsContent value="tab2">Content 2</TabsContent>
            </Tabs>,
        );

        expect(screen.getByText('Content 1')).toBeVisible();

        await user.click(screen.getByText('Tab 2'));

        expect(screen.getByText('Content 2')).toBeVisible();
    });

    it('applies custom className to TabsList', () => {
        render(
            <Tabs defaultValue="tab1">
                <TabsList className="custom-list">
                    <TabsTrigger value="tab1">Tab 1</TabsTrigger>
                </TabsList>
                <TabsContent value="tab1">Content</TabsContent>
            </Tabs>,
        );

        expect(screen.getByRole('tablist')).toHaveClass('custom-list');
    });

    it('marks the active tab trigger', () => {
        render(
            <Tabs defaultValue="tab1">
                <TabsList>
                    <TabsTrigger value="tab1">Tab 1</TabsTrigger>
                    <TabsTrigger value="tab2">Tab 2</TabsTrigger>
                </TabsList>
                <TabsContent value="tab1">Content 1</TabsContent>
                <TabsContent value="tab2">Content 2</TabsContent>
            </Tabs>,
        );

        expect(screen.getByText('Tab 1')).toHaveAttribute('data-state', 'active');
        expect(screen.getByText('Tab 2')).toHaveAttribute('data-state', 'inactive');
    });
});
