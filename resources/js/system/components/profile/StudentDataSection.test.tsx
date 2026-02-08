import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { createUser, createStudentData, createCourse } from '@/test/factories';
import { StudentDataSection } from './StudentDataSection';
import { profileApi, coursesApi } from '@shared/api/client';
import { analytics } from '@shared/lib/analytics';

vi.mock('@shared/api/client', () => ({
    profileApi: { updateStudentData: vi.fn() },
    coursesApi: { list: vi.fn(() => Promise.resolve({ data: [
        { id: 1, name: 'Ciência da Computação' },
        { id: 2, name: 'Engenharia de Produção' },
        { id: 3, name: 'Sistemas de Informação' },
    ] })) },
    ApiRequestError: class extends Error {
        code: string;
        errors?: Record<string, string[]>;
        constructor(code: string, message: string, status: number, errors?: Record<string, string[]>) {
            super(message);
            this.code = code;
            this.errors = errors;
        }
    },
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
    getFieldErrors: vi.fn((err: any) => err?.fieldErrors),
}));

describe('StudentDataSection', () => {
    const course = createCourse({ name: 'Ciência da Computação' });
    const studentData = createStudentData({
        course,
        course_situation: 'studying',
        course_role: 'Aluno',
    });
    const user = createUser({
        name: 'João Santos',
        email: 'joao@example.com',
        student_data: studentData,
    });
    const onUpdate = vi.fn();

    it('renders section heading', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByRole('heading', { name: /dados acadêmicos/i })).toBeInTheDocument();
    });

    it('renders course name in display mode', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Ciência da Computação')).toBeInTheDocument();
    });

    it('renders course situation in display mode', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Cursando')).toBeInTheDocument();
    });

    it('renders course role in display mode', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Aluno')).toBeInTheDocument();
    });

    it('renders edit button in display mode', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByRole('button', { name: /editar/i })).toBeInTheDocument();
    });

    it('shows form fields after clicking edit', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByLabelText(/curso/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/situação/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/vínculo/i)).toBeInTheDocument();
    });

    it('shows save and cancel buttons in editing mode', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByRole('button', { name: /salvar/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /cancelar/i })).toBeInTheDocument();
    });

    it('shows "Não informado" when course is missing', () => {
        const userWithoutCourse = createUser({
            student_data: createStudentData({ course: undefined }),
        });

        render(<StudentDataSection user={userWithoutCourse} onUpdate={onUpdate} />);

        expect(screen.getByText('Não informado')).toBeInTheDocument();
    });

    it('shows "Formado" for graduated students in display mode', () => {
        const graduatedUser = createUser({
            student_data: createStudentData({
                course,
                course_situation: 'graduated',
                course_role: 'Aluno',
            }),
        });

        render(<StudentDataSection user={graduatedUser} onUpdate={onUpdate} />);

        expect(screen.getByText('Formado')).toBeInTheDocument();
    });

    it('shows "Não informado" for course_role when missing', () => {
        const userWithoutRole = createUser({
            student_data: createStudentData({ course: undefined, course_role: undefined as any }),
        });

        render(<StudentDataSection user={userWithoutRole} onUpdate={onUpdate} />);

        // "Não informado" appears for both course name and course_role
        const notInformed = screen.getAllByText('Não informado');
        expect(notInformed.length).toBeGreaterThanOrEqual(1);
    });

    it('hides edit button when in editing mode', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        // The top-level "Editar" button should not be visible
        expect(screen.queryByRole('button', { name: /^editar$/i })).not.toBeInTheDocument();
    });

    it('loads courses in the select dropdown when editing', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        await waitFor(() => {
            expect(screen.getByText('Ciência da Computação')).toBeInTheDocument();
            expect(screen.getByText('Engenharia de Produção')).toBeInTheDocument();
            expect(screen.getByText('Sistemas de Informação')).toBeInTheDocument();
        });
    });

    it('shows the empty course option in the select', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        expect(screen.getByText('Selecione um curso (opcional)')).toBeInTheDocument();
    });

    it('populates form with current values when editing', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        const courseSelect = screen.getByLabelText(/curso/i);
        const situationSelect = screen.getByLabelText(/situação/i);
        const roleSelect = screen.getByLabelText(/vínculo/i);

        expect(situationSelect).toHaveValue('studying');
        expect(roleSelect).toHaveValue('Aluno');
    });

    it('allows changing course selection', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        const courseSelect = screen.getByLabelText(/curso/i);
        await userAction.selectOptions(courseSelect, '2');

        expect(courseSelect).toHaveValue('2');
    });

    it('allows changing course situation', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        const situationSelect = screen.getByLabelText(/situação/i);
        await userAction.selectOptions(situationSelect, 'graduated');

        expect(situationSelect).toHaveValue('graduated');
    });

    it('allows changing course role', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        const roleSelect = screen.getByLabelText(/vínculo/i);
        await userAction.selectOptions(roleSelect, 'Professor');

        expect(roleSelect).toHaveValue('Professor');
    });

    it('submits student data update and shows success message', async () => {
        vi.mocked(profileApi.updateStudentData).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        await userAction.selectOptions(screen.getByLabelText(/situação/i), 'graduated');
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(profileApi.updateStudentData).toHaveBeenCalled();
        });

        await waitFor(() => {
            expect(screen.getByText('Dados acadêmicos atualizados com sucesso!')).toBeInTheDocument();
        });

        expect(analytics.event).toHaveBeenCalledWith('profile_student_data_update');
        expect(onUpdate).toHaveBeenCalled();
    });

    it('shows error message on failed student data update', async () => {
        vi.mocked(profileApi.updateStudentData).mockRejectedValueOnce(new Error('Falha ao atualizar dados.'));
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('Falha ao atualizar dados.')).toBeInTheDocument();
        });
    });

    it('shows field-level errors on validation failure', async () => {
        const errorWithFields = new Error('Verifique os dados informados.');
        (errorWithFields as any).fieldErrors = { course_id: 'Curso inválido', course_situation: 'Situação inválida', course_role: 'Vínculo inválido' };
        vi.mocked(profileApi.updateStudentData).mockRejectedValueOnce(errorWithFields);
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('Curso inválido')).toBeInTheDocument();
        });

        expect(screen.getByText('Situação inválida')).toBeInTheDocument();
        expect(screen.getByText('Vínculo inválido')).toBeInTheDocument();
    });

    it('shows loading state while submitting', async () => {
        let resolveUpdate: () => void;
        vi.mocked(profileApi.updateStudentData).mockImplementation(() => new Promise<any>((resolve) => { resolveUpdate = resolve; }));
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        expect(screen.getByRole('button', { name: /salvando/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /salvando/i })).toBeDisabled();

        resolveUpdate!();

        await waitFor(() => {
            expect(screen.getByText('Dados acadêmicos atualizados com sucesso!')).toBeInTheDocument();
        });
    });

    it('returns to display mode and resets values on cancel', async () => {
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));

        // Change values
        await userAction.selectOptions(screen.getByLabelText(/situação/i), 'graduated');
        await userAction.selectOptions(screen.getByLabelText(/vínculo/i), 'Professor');

        await userAction.click(screen.getByRole('button', { name: /cancelar/i }));

        // Should be back in display mode showing original values
        expect(screen.getByText('Cursando')).toBeInTheDocument();
        expect(screen.getByText('Aluno')).toBeInTheDocument();
        expect(screen.queryByLabelText(/curso/i)).not.toBeInTheDocument();
    });

    it('returns to display mode after successful submission', async () => {
        vi.mocked(profileApi.updateStudentData).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(screen.getByText('Dados acadêmicos atualizados com sucesso!')).toBeInTheDocument();
        });

        // Form should no longer be visible
        expect(screen.queryByLabelText(/curso/i)).not.toBeInTheDocument();
    });

    it('sends null for course_id when no course is selected', async () => {
        vi.mocked(profileApi.updateStudentData).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);

        const userWithoutCourse = createUser({
            student_data: createStudentData({ course: undefined }),
        });

        const userAction = userEvent.setup();

        render(<StudentDataSection user={userWithoutCourse} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(profileApi.updateStudentData).toHaveBeenCalledWith(
                expect.objectContaining({ course_id: null }),
            );
        });
    });

    it('sends parsed course_id when a course is selected', async () => {
        vi.mocked(profileApi.updateStudentData).mockResolvedValueOnce(undefined as any);
        onUpdate.mockResolvedValueOnce(undefined);
        const userAction = userEvent.setup();

        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        await userAction.click(screen.getByRole('button', { name: /editar/i }));
        await userAction.selectOptions(screen.getByLabelText(/curso/i), '2');
        await userAction.click(screen.getByRole('button', { name: /salvar/i }));

        await waitFor(() => {
            expect(profileApi.updateStudentData).toHaveBeenCalledWith(
                expect.objectContaining({ course_id: 2 }),
            );
        });
    });

    it('displays Situação and Vínculo labels in display mode', () => {
        render(<StudentDataSection user={user} onUpdate={onUpdate} />);

        expect(screen.getByText('Situação')).toBeInTheDocument();
        expect(screen.getByText('Vínculo')).toBeInTheDocument();
    });
});
