import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@/test/test-utils';
import { SeminarCard } from './SeminarCard';
import { createSeminar } from '@/test/factories';

vi.mock('@shared/lib/utils', async () => {
    const actual = await vi.importActual('@shared/lib/utils');
    return {
        ...actual,
        formatDateTime: vi.fn((date: string) => 'Formatted Date'),
        isExpired: vi.fn((date: string) => date === '2020-01-01T00:00:00Z'),
        containsHTML: vi.fn((text: string) => text.includes('<')),
    };
});

describe('SeminarCard', () => {
    it('renders a Link to /seminario/${seminar.slug}', () => {
        const seminar = createSeminar({ slug: 'test-seminar' });
        render(<SeminarCard seminar={seminar} />);

        const link = screen.getByRole('link');
        expect(link).toHaveAttribute('href', '/seminario/test-seminar');
    });

    it('shows seminar name', () => {
        const seminar = createSeminar({ name: 'Test Seminar' });
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('Test Seminar')).toBeInTheDocument();
    });

    it('shows formatted date', () => {
        const seminar = createSeminar();
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('Formatted Date')).toBeInTheDocument();
    });

    it('shows registration count with "inscritos"', () => {
        const seminar = createSeminar({ registrationsCount: 42 });
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('42 inscritos')).toBeInTheDocument();
    });

    it('shows "Encerrado" badge when expired', () => {
        const seminar = createSeminar({
            scheduledAt: '2020-01-01T00:00:00Z',
            isExpired: true
        });
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('Encerrado')).toBeInTheDocument();
    });

    it('shows subjects when showSubject=true (default)', () => {
        const seminar = createSeminar({
            subjects: [
                { id: 1, name: 'Subject 1', seminarsCount: 5 },
                { id: 2, name: 'Subject 2', seminarsCount: 3 }
            ]
        });
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('Subject 1, Subject 2')).toBeInTheDocument();
    });

    it('hides subjects when showSubject=false', () => {
        const seminar = createSeminar({
            subjects: [
                { id: 1, name: 'Subject 1', seminarsCount: 5 }
            ]
        });
        render(<SeminarCard seminar={seminar} showSubject={false} />);

        expect(screen.queryByText('Subject 1')).not.toBeInTheDocument();
    });

    it('shows "Clique para ver os detalhes" when description contains HTML', () => {
        const seminar = createSeminar({
            description: '<p>HTML content</p>'
        });
        render(<SeminarCard seminar={seminar} />);

        expect(screen.getByText('Clique para ver os detalhes')).toBeInTheDocument();
        expect(screen.queryByText('<p>HTML content</p>')).not.toBeInTheDocument();
    });
});
