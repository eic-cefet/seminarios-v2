import { adminNavigation, filterNavigation } from './navigation';

describe('adminNavigation', () => {
    it('contains Dashboard as first item', () => {
        expect(adminNavigation[0].label).toBe('Dashboard');
        expect(adminNavigation[0].href).toBe('/');
    });

    it('contains expected navigation items', () => {
        const labels = adminNavigation.map((item) => item.label);
        expect(labels).toContain('Dashboard');
        expect(labels).toContain('Usuários');
        expect(labels).toContain('Seminários');
        expect(labels).toContain('Inscrições');
        expect(labels).toContain('Relatórios');
    });

    it('has children for Seminários', () => {
        const seminarios = adminNavigation.find((item) => item.label === 'Seminários');
        expect(seminarios?.children).toBeDefined();
        expect(seminarios?.children?.length).toBeGreaterThan(0);
    });
});

describe('filterNavigation', () => {
    it('returns all items for admin users', () => {
        const filtered = filterNavigation(adminNavigation, true);
        const labels = filtered.map((item) => item.label);
        expect(labels).toContain('Dashboard');
        expect(labels).toContain('Usuários');
        expect(labels).toContain('Inscrições');
        expect(labels).toContain('Relatórios');
    });

    it('filters out adminOnly items for non-admin users', () => {
        const filtered = filterNavigation(adminNavigation, false);
        const labels = filtered.map((item) => item.label);
        expect(labels).toContain('Dashboard');
        expect(labels).not.toContain('Usuários');
        expect(labels).not.toContain('Inscrições');
    });

    it('filters adminOnly children', () => {
        const filtered = filterNavigation(adminNavigation, false);
        const seminarios = filtered.find((item) => item.label === 'Seminários');
        expect(seminarios?.children).toBeDefined();
        // Only non-adminOnly children should remain
        const childLabels = seminarios?.children?.map((c) => c.label) ?? [];
        expect(childLabels).toContain('Seminários');
        expect(childLabels).not.toContain('Locais');
    });

    it('removes parent items when all children are filtered out', () => {
        const filtered = filterNavigation(adminNavigation, false);
        const labels = filtered.map((item) => item.label);
        // Relatórios has only adminOnly children, so it should be removed
        expect(labels).not.toContain('Relatórios');
    });

    it('returns null and filters out items with all adminOnly children for non-admin', () => {
        const DummyIcon = (() => null) as any;
        const customNav = [
            {
                label: 'AllAdminChildren',
                icon: DummyIcon,
                children: [
                    { label: 'Child1', href: '/c1', adminOnly: true },
                    { label: 'Child2', href: '/c2', adminOnly: true },
                ],
            },
            {
                label: 'MixedChildren',
                icon: DummyIcon,
                children: [
                    { label: 'PublicChild', href: '/public' },
                    { label: 'AdminChild', href: '/admin', adminOnly: true },
                ],
            },
        ];

        const filtered = filterNavigation(customNav, false);
        const labels = filtered.map((item) => item.label);

        // AllAdminChildren should be removed (all children are adminOnly)
        expect(labels).not.toContain('AllAdminChildren');
        // MixedChildren should remain with only PublicChild
        expect(labels).toContain('MixedChildren');
        const mixed = filtered.find((item) => item.label === 'MixedChildren');
        expect(mixed?.children?.map((c) => c.label)).toEqual(['PublicChild']);
    });
});
