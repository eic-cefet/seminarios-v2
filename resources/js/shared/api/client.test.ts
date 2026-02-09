import { ApiRequestError, seminarsApi, subjectsApi, workshopsApi, seminarTypesApi, coursesApi, statsApi, authApi, registrationApi, profileApi, bugReportApi } from './client';
import { createSeminar, createSubject, createWorkshop, createPaginatedResponse } from '@/test/factories';
import { getCookie } from './httpUtils';

// Mock httpUtils to avoid CSRF fetch in tests
vi.mock('./httpUtils', () => ({
    getCookie: vi.fn(() => null),
    getCsrfCookie: vi.fn(() => Promise.resolve()),
    buildQueryString: vi.fn(),
}));

const mockGetCookie = getCookie as ReturnType<typeof vi.fn>;

describe('ApiRequestError', () => {
    it('creates an error with code, message, status', () => {
        const error = new ApiRequestError('not_found', 'Resource not found', 404);
        expect(error.code).toBe('not_found');
        expect(error.message).toBe('Resource not found');
        expect(error.status).toBe(404);
        expect(error.name).toBe('ApiRequestError');
        expect(error).toBeInstanceOf(Error);
    });

    it('includes validation errors', () => {
        const errors = { email: ['Required'] };
        const error = new ApiRequestError('validation_error', 'Validation', 422, errors);
        expect(error.errors).toEqual(errors);
    });
});

describe('fetchApi (via API namespaces)', () => {
    let fetchSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        fetchSpy = vi.spyOn(globalThis, 'fetch');
    });

    afterEach(() => {
        fetchSpy.mockRestore();
    });

    function mockFetchSuccess(data: unknown) {
        fetchSpy.mockResolvedValue(new Response(JSON.stringify(data), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
        }));
    }

    function mockFetchError(status: number, data?: unknown) {
        fetchSpy.mockResolvedValue(new Response(
            JSON.stringify(data ?? { error: 'server_error', message: 'Error' }),
            { status, headers: { 'Content-Type': 'application/json' } },
        ));
    }

    describe('seminarsApi', () => {
        it('list fetches paginated seminars', async () => {
            const response = createPaginatedResponse([createSeminar()]);
            mockFetchSuccess(response);

            const result = await seminarsApi.list({ page: 1 });
            expect(result.data).toHaveLength(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars'),
                expect.any(Object),
            );
        });

        it('upcoming fetches upcoming seminars', async () => {
            mockFetchSuccess({ data: [createSeminar()] });

            const result = await seminarsApi.upcoming();
            expect(result.data).toHaveLength(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/upcoming'),
                expect.any(Object),
            );
        });

        it('get fetches a single seminar by slug', async () => {
            const seminar = createSeminar({ slug: 'test-slug' });
            mockFetchSuccess({ data: seminar });

            const result = await seminarsApi.get('test-slug');
            expect(result.data.slug).toBe('test-slug');
        });

        it('bySubject fetches seminars for a subject', async () => {
            mockFetchSuccess(createPaginatedResponse([createSeminar()]));

            const result = await seminarsApi.bySubject(1, { upcoming: true });
            expect(result.data).toHaveLength(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/1/seminars'),
                expect.any(Object),
            );
        });
    });

    describe('subjectsApi', () => {
        it('list fetches subjects', async () => {
            mockFetchSuccess({ data: [createSubject()] });
            const result = await subjectsApi.list();
            expect(result.data).toHaveLength(1);
        });

        it('get fetches a single subject', async () => {
            const subject = createSubject({ id: 5 });
            mockFetchSuccess({ data: subject });
            const result = await subjectsApi.get(5);
            expect(result.data.id).toBe(5);
        });
    });

    describe('workshopsApi', () => {
        it('list fetches workshops', async () => {
            mockFetchSuccess({ data: [createWorkshop()] });
            const result = await workshopsApi.list();
            expect(result.data).toHaveLength(1);
        });

        it('get fetches a single workshop', async () => {
            mockFetchSuccess({ data: createWorkshop({ id: 3 }) });
            const result = await workshopsApi.get(3);
            expect(result.data.id).toBe(3);
        });

        it('seminars fetches workshop seminars', async () => {
            mockFetchSuccess(createPaginatedResponse([createSeminar()]));
            const result = await workshopsApi.seminars(1);
            expect(result.data).toHaveLength(1);
        });
    });

    describe('seminarTypesApi', () => {
        it('list fetches seminar types', async () => {
            mockFetchSuccess({ data: [{ id: 1, name: 'Palestra' }] });
            const result = await seminarTypesApi.list();
            expect(result.data).toHaveLength(1);
        });
    });

    describe('coursesApi', () => {
        it('list fetches courses', async () => {
            mockFetchSuccess({ data: [{ id: 1, name: 'CS' }] });
            const result = await coursesApi.list();
            expect(result.data).toHaveLength(1);
        });
    });

    describe('statsApi', () => {
        it('get fetches stats', async () => {
            mockFetchSuccess({ data: { subjects: 5, seminars: 10, workshops: 3 } });
            const result = await statsApi.get();
            expect(result.data.seminars).toBe(10);
        });
    });

    describe('authApi', () => {
        it('login sends credentials', async () => {
            mockFetchSuccess({ user: { id: 1, name: 'Test', email: 'test@test.com' } });
            const result = await authApi.login({ email: 'test@test.com', password: 'pass' });
            expect(result.user.email).toBe('test@test.com');
        });

        it('register sends registration data', async () => {
            mockFetchSuccess({ user: { id: 1, name: 'Test', email: 'test@test.com' } });
            const result = await authApi.register({
                name: 'Test',
                email: 'test@test.com',
                password: 'password',
                password_confirmation: 'password',
                course_situation: 'studying',
                course_role: 'Aluno',
            });
            expect(result.user).toBeDefined();
        });

        it('logout sends POST', async () => {
            mockFetchSuccess({ message: 'Logged out' });
            const result = await authApi.logout();
            expect(result.message).toBe('Logged out');
        });

        it('me fetches current user', async () => {
            mockFetchSuccess({ user: { id: 1, name: 'Test', email: 'test@test.com' } });
            const result = await authApi.me();
            expect(result.user.id).toBe(1);
        });

        it('forgotPassword sends email', async () => {
            mockFetchSuccess({ message: 'Sent' });
            const result = await authApi.forgotPassword('test@test.com');
            expect(result.message).toBe('Sent');
        });

        it('resetPassword sends reset data', async () => {
            mockFetchSuccess({ message: 'Reset' });
            const result = await authApi.resetPassword({
                token: 'token',
                email: 'test@test.com',
                password: 'new',
                password_confirmation: 'new',
            });
            expect(result.message).toBe('Reset');
        });

        it('exchangeCode sends code', async () => {
            mockFetchSuccess({ user: { id: 1, name: 'Test', email: 'test@test.com' } });
            const result = await authApi.exchangeCode('code123');
            expect(result.user).toBeDefined();
        });
    });

    describe('registrationApi', () => {
        it('status checks registration', async () => {
            mockFetchSuccess({ registered: true, registration: { id: 1, created_at: '2026-01-01' } });
            const result = await registrationApi.status('test-slug');
            expect(result.registered).toBe(true);
        });

        it('register sends POST', async () => {
            mockFetchSuccess({ message: 'Registered', registration: { id: 1, seminar_id: 1, created_at: '2026-01-01' } });
            const result = await registrationApi.register('test-slug');
            expect(result.message).toBe('Registered');
        });

        it('unregister sends DELETE', async () => {
            mockFetchSuccess({ message: 'Unregistered' });
            const result = await registrationApi.unregister('test-slug');
            expect(result.message).toBe('Unregistered');
        });
    });

    describe('profileApi', () => {
        it('get fetches profile', async () => {
            mockFetchSuccess({ user: { id: 1, name: 'Test', email: 'test@test.com' } });
            const result = await profileApi.get();
            expect(result.user.id).toBe(1);
        });

        it('update sends PUT', async () => {
            mockFetchSuccess({ message: 'Updated', user: { id: 1, name: 'New', email: 'new@test.com' } });
            const result = await profileApi.update({ name: 'New', email: 'new@test.com' });
            expect(result.message).toBe('Updated');
        });

        it('updatePassword sends PUT', async () => {
            mockFetchSuccess({ message: 'Updated' });
            const result = await profileApi.updatePassword({
                current_password: 'old',
                password: 'new',
                password_confirmation: 'new',
            });
            expect(result.message).toBe('Updated');
        });

        it('registrations fetches paginated registrations', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } });
            const result = await profileApi.registrations({ page: 1 });
            expect(result.data).toEqual([]);
        });

        it('certificates fetches paginated certificates', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } });
            const result = await profileApi.certificates();
            expect(result.data).toEqual([]);
        });
    });

    describe('profileApi extended', () => {
        it('updateStudentData sends PUT', async () => {
            mockFetchSuccess({ message: 'Updated', user: { id: 1 } });
            const result = await profileApi.updateStudentData({
                course_situation: 'studying',
                course_role: 'Aluno',
            });
            expect(result.message).toBe('Updated');
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/profile/student-data'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });

        it('pendingEvaluations fetches evaluations', async () => {
            mockFetchSuccess({ data: [{ id: 1, seminar: { id: 1, name: 'Seminar' } }] });
            const result = await profileApi.pendingEvaluations();
            expect(result.data).toHaveLength(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/profile/pending-evaluations'),
                expect.any(Object),
            );
        });

        it('submitRating sends POST', async () => {
            mockFetchSuccess({ message: 'Rated', rating: { id: 1, score: 5, comment: null } });
            const result = await profileApi.submitRating(1, { score: 5, comment: 'Great' });
            expect(result.message).toBe('Rated');
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/profile/ratings/1'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('registrations passes pagination params', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 2, last_page: 5, per_page: 10, total: 50 } });
            const result = await profileApi.registrations({ page: 2, per_page: 10 });
            expect(result.meta.current_page).toBe(2);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=2'),
                expect.any(Object),
            );
        });

        it('certificates passes pagination params', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } });
            const result = await profileApi.certificates({ page: 1, per_page: 15 });
            expect(result.data).toEqual([]);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=1'),
                expect.any(Object),
            );
        });

        it('registrations with no params produces no query string', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } });
            const result = await profileApi.registrations();
            expect(result.data).toEqual([]);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringMatching(/\/profile\/registrations$/),
                expect.any(Object),
            );
        });

        it('registrations with only per_page param', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 5, total: 0 } });
            const result = await profileApi.registrations({ per_page: 5 });
            expect(result.data).toEqual([]);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('per_page=5'),
                expect.any(Object),
            );
        });

        it('certificates with no params produces no query string', async () => {
            mockFetchSuccess({ data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } });
            const result = await profileApi.certificates();
            expect(result.data).toEqual([]);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringMatching(/\/profile\/certificates$/),
                expect.any(Object),
            );
        });
    });

    describe('seminarsApi extended', () => {
        it('list passes query params', async () => {
            mockFetchSuccess(createPaginatedResponse([createSeminar()]));
            await seminarsApi.list({ type: 'palestra', upcoming: true, sort: 'date', direction: 'desc', per_page: 5 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('type=palestra'),
                expect.any(Object),
            );
        });

        it('list passes subject param', async () => {
            mockFetchSuccess(createPaginatedResponse([createSeminar()]));
            await seminarsApi.list({ subject: 42 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('subject=42'),
                expect.any(Object),
            );
        });

        it('list passes expired param', async () => {
            mockFetchSuccess(createPaginatedResponse([createSeminar()]));
            await seminarsApi.list({ expired: true });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('expired=1'),
                expect.any(Object),
            );
        });

        it('bySubject passes pagination params', async () => {
            mockFetchSuccess(createPaginatedResponse([]));
            await seminarsApi.bySubject(2, { page: 3, direction: 'asc' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/2/seminars'),
                expect.any(Object),
            );
        });

        it('bySubject passes per_page param', async () => {
            mockFetchSuccess(createPaginatedResponse([]));
            await seminarsApi.bySubject(2, { per_page: 25 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('per_page=25'),
                expect.any(Object),
            );
        });

        it('bySubject with no params produces no query string', async () => {
            mockFetchSuccess(createPaginatedResponse([]));
            await seminarsApi.bySubject(5);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringMatching(/\/subjects\/5\/seminars$/),
                expect.any(Object),
            );
        });
    });

    describe('subjectsApi extended', () => {
        it('list passes sort and limit params', async () => {
            mockFetchSuccess({ data: [createSubject()] });
            await subjectsApi.list({ sort: 'seminars', limit: 10 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('sort=seminars'),
                expect.any(Object),
            );
        });
    });

    describe('workshopsApi extended', () => {
        it('seminars passes pagination params', async () => {
            mockFetchSuccess(createPaginatedResponse([]));
            await workshopsApi.seminars(1, { upcoming: true, page: 2, per_page: 5 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/1/seminars'),
                expect.any(Object),
            );
        });

        it('seminars passes direction param', async () => {
            mockFetchSuccess(createPaginatedResponse([]));
            await workshopsApi.seminars(3, { direction: 'desc' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('direction=desc'),
                expect.any(Object),
            );
        });
    });

    describe('bugReportApi', () => {
        it('submit sends FormData POST', async () => {
            mockFetchSuccess({ message: 'Sent' });
            const result = await bugReportApi.submit({
                subject: 'Bug',
                message: 'Something broke',
                name: 'User',
                email: 'user@test.com',
            });
            expect(result.message).toBe('Sent');
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/bug-report'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('submit throws ApiRequestError on failure', async () => {
            mockFetchError(500);

            try {
                await bugReportApi.submit({ subject: 'Bug', message: 'Error' });
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(ApiRequestError);
                expect((err as ApiRequestError).status).toBe(500);
            }
        });
    });

    describe('error handling', () => {
        it('throws ApiRequestError on non-ok response', async () => {
            mockFetchError(422, { error: 'validation_error', message: 'Invalid data' });

            try {
                await seminarsApi.list();
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(ApiRequestError);
                expect((err as ApiRequestError).code).toBe('validation_error');
                expect((err as ApiRequestError).status).toBe(422);
            }
        });

        it('handles non-JSON error response', async () => {
            fetchSpy.mockResolvedValue(new Response('Not JSON', {
                status: 500,
                statusText: 'Internal Server Error',
            }));

            try {
                await seminarsApi.list();
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(ApiRequestError);
                expect((err as ApiRequestError).code).toBe('unknown_error');
            }
        });
    });

    describe('XSRF token handling', () => {
        it('includes X-XSRF-TOKEN header when cookie is present', async () => {
            mockGetCookie.mockReturnValue('my-xsrf-token');
            mockFetchSuccess({ data: [] });

            await seminarsApi.upcoming();

            expect(fetchSpy).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'X-XSRF-TOKEN': 'my-xsrf-token',
                    }),
                }),
            );

            mockGetCookie.mockReturnValue(null);
        });
    });

    describe('bugReportApi extended', () => {
        it('submit includes files in FormData when provided', async () => {
            mockFetchSuccess({ message: 'Sent' });
            const file = new File(['content'], 'screenshot.png', { type: 'image/png' });

            const result = await bugReportApi.submit({
                subject: 'Bug',
                message: 'Something broke',
                files: [file],
            });
            expect(result.message).toBe('Sent');

            const callArgs = fetchSpy.mock.calls[0];
            const body = callArgs[1]?.body as FormData;
            expect(body).toBeInstanceOf(FormData);
            expect(body.getAll('files[]')).toHaveLength(1);
        });

        it('submit includes X-XSRF-TOKEN header when cookie is present', async () => {
            mockGetCookie.mockReturnValue('bug-xsrf-token');
            mockFetchSuccess({ message: 'Sent' });

            await bugReportApi.submit({ subject: 'Bug', message: 'Error' });

            expect(fetchSpy).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'X-XSRF-TOKEN': 'bug-xsrf-token',
                    }),
                }),
            );

            mockGetCookie.mockReturnValue(null);
        });

        it('submit handles non-JSON error response', async () => {
            fetchSpy.mockResolvedValue(new Response('Not JSON', {
                status: 500,
                statusText: 'Internal Server Error',
            }));

            try {
                await bugReportApi.submit({ subject: 'Bug', message: 'Error' });
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(ApiRequestError);
                expect((err as ApiRequestError).code).toBe('unknown_error');
                expect((err as ApiRequestError).message).toBe('Internal Server Error');
            }
        });
    });
});
