import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { getSemester } from '@shared/lib/utils';

vi.mock('../../api/adminClient', async (importOriginal) => {
    const actual = await importOriginal<typeof import('../../api/adminClient')>();
    return {
        ...actual,
        studentsApi: { list: vi.fn() },
    };
});

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useNavigate: vi.fn(() => vi.fn()) };
});

import StudentList from './StudentList';
import { studentsApi } from '../../api/adminClient';

// Polyfill pointer capture methods for Radix Select in jsdom
if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

describe('StudentList', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(studentsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Maria Estudante', email: 'maria@example.com', course: 'Engenharia', course_situation: 'studying' },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
            summary: { semester: '2026.1' },
        });
    });

    it('renders the student list for the current semester', async () => {
        render(<StudentList />);

        await waitFor(() => {
            expect(screen.getByText('Maria Estudante')).toBeInTheDocument();
        });

        expect(studentsApi.list).toHaveBeenCalledWith(
            expect.objectContaining({ semester: expect.stringMatching(/^\d{4}\.[12]$/) }),
        );
    });

    it('re-fetches when the semester dropdown changes', async () => {
        render(<StudentList />);
        const user = userEvent.setup();

        await waitFor(() => expect(studentsApi.list).toHaveBeenCalledTimes(1));

        const initialSemester = vi.mocked(studentsApi.list).mock.calls[0][0]?.semester;

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        const options = await screen.findAllByRole('option');
        const otherOption = options.find((option) => option.textContent !== initialSemester);
        expect(otherOption).toBeDefined();

        await user.click(otherOption!);

        await waitFor(() => {
            expect(studentsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ semester: otherOption!.textContent, page: 1 }),
            );
        });
    });

    it('shows the empty state message when no students are found', async () => {
        vi.mocked(studentsApi.list).mockResolvedValue({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
            summary: { semester: '2026.1' },
        });

        render(<StudentList />);

        await waitFor(() => {
            expect(
                screen.getByText('Nenhum aluno encontrado para o semestre selecionado.'),
            ).toBeInTheDocument();
        });
    });

    it('navigates to the student profile with the selected semester when a row is clicked', async () => {
        const { useNavigate } = await import('react-router-dom');
        const mockNavigate = vi.fn();
        vi.mocked(useNavigate).mockReturnValue(mockNavigate);

        render(<StudentList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Maria Estudante')).toBeInTheDocument();
        });

        const { year, semester } = getSemester();
        const currentSemester = `${year}.${semester}`;

        await user.click(screen.getByText('Maria Estudante').closest('tr')!);

        expect(mockNavigate).toHaveBeenCalledWith(`/students/1?semester=${currentSemester}`);
    });

    it('shows the "Concluído" badge for graduated students', async () => {
        vi.mocked(studentsApi.list).mockResolvedValue({
            data: [
                { id: 2, name: 'João Formado', email: 'joao@example.com', course: 'Matemática', course_situation: 'graduated' },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
            summary: { semester: '2026.1' },
        });

        render(<StudentList />);

        await waitFor(() => {
            expect(screen.getByText('Concluído')).toBeInTheDocument();
        });
        expect(screen.queryByText('Cursando')).not.toBeInTheDocument();
    });

    it('allows typing in the search input', async () => {
        render(<StudentList />);
        const user = userEvent.setup();

        const searchInput = screen.getByPlaceholderText('Buscar por nome ou e-mail...');
        await user.type(searchInput, 'maria');

        expect(searchInput).toHaveValue('maria');

        await waitFor(
            () => {
                expect(studentsApi.list).toHaveBeenCalledWith(
                    expect.objectContaining({ search: 'maria', page: 1 }),
                );
            },
            { timeout: 2000 },
        );
    });

    it('renders pagination controls and advances the page', async () => {
        vi.mocked(studentsApi.list).mockResolvedValue({
            data: [
                { id: 1, name: 'Maria Estudante', email: 'maria@example.com', course: 'Engenharia', course_situation: 'studying' },
            ],
            meta: { current_page: 1, last_page: 3, per_page: 10, total: 30, from: 1, to: 10 },
            summary: { semester: '2026.1' },
        });

        render(<StudentList />);
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByText('Próxima')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Próxima'));

        await waitFor(() => {
            expect(studentsApi.list).toHaveBeenCalledWith(
                expect.objectContaining({ page: 2 }),
            );
        });
    });
});
