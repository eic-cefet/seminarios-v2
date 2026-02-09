import { AdminApiError, dashboardApi, usersApi, locationsApi, subjectsApi, workshopsApi, registrationsApi, seminarsApi, presenceLinkApi } from './adminClient';
import { getCookie } from '@shared/api/httpUtils';

vi.mock('@shared/api/httpUtils', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@shared/api/httpUtils')>();
    return {
        ...actual,
        getCookie: vi.fn(() => null),
        getCsrfCookie: vi.fn(() => Promise.resolve()),
    };
});

const mockGetCookie = getCookie as ReturnType<typeof vi.fn>;

describe('AdminApiError', () => {
    it('creates an error with correct properties', () => {
        const error = new AdminApiError('not_found', 'Not found', 404);
        expect(error.code).toBe('not_found');
        expect(error.message).toBe('Not found');
        expect(error.status).toBe(404);
        expect(error.name).toBe('AdminApiError');
        expect(error).toBeInstanceOf(Error);
    });

    it('includes validation errors', () => {
        const errors = { name: ['Required'] };
        const error = new AdminApiError('validation_error', 'Validation', 422, errors);
        expect(error.errors).toEqual(errors);
    });
});

describe('Admin API endpoints', () => {
    let fetchSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        fetchSpy = vi.spyOn(globalThis, 'fetch');
    });

    afterEach(() => {
        fetchSpy.mockRestore();
    });

    function mockSuccess(data: unknown) {
        fetchSpy.mockResolvedValue(new Response(JSON.stringify(data), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
        }));
    }

    function mockError(status: number) {
        fetchSpy.mockResolvedValue(new Response(
            JSON.stringify({ error: 'server_error', message: 'Error' }),
            { status, headers: { 'Content-Type': 'application/json' } },
        ));
    }

    describe('dashboardApi', () => {
        it('stats fetches dashboard stats', async () => {
            mockSuccess({ data: { counts: { users: 10 } } });
            const result = await dashboardApi.stats();
            expect(result.data.counts.users).toBe(10);
        });
    });

    describe('usersApi', () => {
        it('list fetches paginated users', async () => {
            mockSuccess({ data: [], meta: { current_page: 1 } });
            const result = await usersApi.list({ page: 1, search: 'test' });
            expect(result).toBeDefined();
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/users'),
                expect.any(Object),
            );
        });

        it('get fetches a single user', async () => {
            mockSuccess({ data: { id: 1, name: 'Test' } });
            const result = await usersApi.get(1);
            expect(result.data.id).toBe(1);
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await usersApi.create({ name: 'Test', email: 'test@test.com', password: 'pass' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/users'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('update sends PUT', async () => {
            mockSuccess({ message: 'Updated', data: { id: 1 } });
            await usersApi.update(1, { name: 'Updated' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/users/1'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });

        it('delete sends DELETE', async () => {
            mockSuccess({ message: 'Deleted' });
            await usersApi.delete(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/users/1'),
                expect.objectContaining({ method: 'DELETE' }),
            );
        });

        it('restore sends POST', async () => {
            mockSuccess({ message: 'Restored', data: { id: 1 } });
            await usersApi.restore(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/users/1/restore'),
                expect.objectContaining({ method: 'POST' }),
            );
        });
    });

    describe('locationsApi', () => {
        it('list fetches locations', async () => {
            mockSuccess({ data: [], meta: {} });
            await locationsApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/locations'), expect.any(Object));
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await locationsApi.create({ name: 'Room', max_vacancies: 30 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/locations'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('delete sends DELETE', async () => {
            mockSuccess({ message: 'Deleted' });
            await locationsApi.delete(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/locations/1'),
                expect.objectContaining({ method: 'DELETE' }),
            );
        });
    });

    describe('subjectsApi', () => {
        it('list fetches subjects', async () => {
            mockSuccess({ data: [], meta: {} });
            await subjectsApi.list({ search: 'test' });
            expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/subjects'), expect.any(Object));
        });

        it('merge sends POST', async () => {
            mockSuccess({ message: 'Merged', data: { id: 1 } });
            await subjectsApi.merge({ target_id: 1, source_ids: [2, 3] });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/merge'),
                expect.objectContaining({ method: 'POST' }),
            );
        });
    });

    describe('workshopsApi', () => {
        it('list fetches workshops', async () => {
            mockSuccess({ data: [], meta: {} });
            await workshopsApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/workshops'), expect.any(Object));
        });

        it('searchSeminars fetches search results', async () => {
            mockSuccess({ data: [] });
            await workshopsApi.searchSeminars({ search: 'test' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/search-seminars'),
                expect.any(Object),
            );
        });
    });

    describe('registrationsApi', () => {
        it('list fetches registrations', async () => {
            mockSuccess({ data: [], meta: {} });
            await registrationsApi.list({ seminar_id: 1 });
            expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/registrations'), expect.any(Object));
        });

        it('togglePresence sends PATCH', async () => {
            mockSuccess({ message: 'Toggled', data: { id: 1 } });
            await registrationsApi.togglePresence(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/registrations/1/presence'),
                expect.objectContaining({ method: 'PATCH' }),
            );
        });
    });

    describe('seminarsApi', () => {
        it('list fetches seminars', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list({ upcoming: true });
            expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/seminars'), expect.any(Object));
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await seminarsApi.create({
                name: 'Test', scheduled_at: '2026-01-01', active: true,
                subject_names: ['Topic'], speaker_ids: [1],
            });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars'),
                expect.objectContaining({ method: 'POST' }),
            );
        });
    });

    describe('presenceLinkApi', () => {
        it('get fetches presence link', async () => {
            mockSuccess({ data: null });
            await presenceLinkApi.get(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1/presence-link'),
                expect.any(Object),
            );
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await presenceLinkApi.create(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1/presence-link'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('toggle sends PATCH', async () => {
            mockSuccess({ message: 'Toggled', data: { id: 1 } });
            await presenceLinkApi.toggle(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1/presence-link/toggle'),
                expect.objectContaining({ method: 'PATCH' }),
            );
        });
    });

    describe('seminarsApi extended', () => {
        it('get fetches a single seminar', async () => {
            mockSuccess({ data: { id: 1, name: 'Test Seminar' } });
            const result = await seminarsApi.get(1);
            expect(result.data.id).toBe(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1'),
                expect.any(Object),
            );
        });

        it('update sends PUT', async () => {
            mockSuccess({ message: 'Updated', data: { id: 1 } });
            await seminarsApi.update(1, { name: 'Updated' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });

        it('delete sends DELETE', async () => {
            mockSuccess({ message: 'Deleted' });
            await seminarsApi.delete(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars/1'),
                expect.objectContaining({ method: 'DELETE' }),
            );
        });

        it('list passes search param', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list({ search: 'ai' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('search=ai'),
                expect.any(Object),
            );
        });

        it('list passes active param', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list({ active: true });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('active=1'),
                expect.any(Object),
            );
        });
    });

    describe('locationsApi extended', () => {
        it('get fetches a single location', async () => {
            mockSuccess({ data: { id: 1, name: 'Room A' } });
            const result = await locationsApi.get(1);
            expect(result.data.id).toBe(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/locations/1'),
                expect.any(Object),
            );
        });

        it('update sends PUT', async () => {
            mockSuccess({ message: 'Updated', data: { id: 1 } });
            await locationsApi.update(1, { name: 'Updated Room' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/locations/1'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });
    });

    describe('subjectsApi extended', () => {
        it('get fetches a single subject', async () => {
            mockSuccess({ data: { id: 1, name: 'AI' } });
            const result = await subjectsApi.get(1);
            expect(result.data.id).toBe(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/1'),
                expect.any(Object),
            );
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await subjectsApi.create({ name: 'New Subject' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('update sends PUT', async () => {
            mockSuccess({ message: 'Updated', data: { id: 1 } });
            await subjectsApi.update(1, { name: 'Updated Subject' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/1'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });

        it('delete sends DELETE', async () => {
            mockSuccess({ message: 'Deleted' });
            await subjectsApi.delete(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/subjects/1'),
                expect.objectContaining({ method: 'DELETE' }),
            );
        });
    });

    describe('workshopsApi extended', () => {
        it('get fetches a single workshop', async () => {
            mockSuccess({ data: { id: 1, name: 'ML Workshop' } });
            const result = await workshopsApi.get(1);
            expect(result.data.id).toBe(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/1'),
                expect.any(Object),
            );
        });

        it('create sends POST', async () => {
            mockSuccess({ message: 'Created', data: { id: 1 } });
            await workshopsApi.create({ name: 'New Workshop' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops'),
                expect.objectContaining({ method: 'POST' }),
            );
        });

        it('update sends PUT', async () => {
            mockSuccess({ message: 'Updated', data: { id: 1 } });
            await workshopsApi.update(1, { name: 'Updated Workshop' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/1'),
                expect.objectContaining({ method: 'PUT' }),
            );
        });

        it('delete sends DELETE', async () => {
            mockSuccess({ message: 'Deleted' });
            await workshopsApi.delete(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/1'),
                expect.objectContaining({ method: 'DELETE' }),
            );
        });
    });

    describe('dashboardApi extended', () => {
        it('seminars fetches all seminars', async () => {
            mockSuccess({ data: [{ id: 1, name: 'S1' }] });
            const result = await dashboardApi.seminars();
            expect(result.data).toHaveLength(1);
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/seminars'),
                expect.any(Object),
            );
        });
    });

    describe('error handling', () => {
        it('throws AdminApiError on error', async () => {
            mockError(500);
            try {
                await dashboardApi.stats();
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(AdminApiError);
                expect((err as AdminApiError).status).toBe(500);
            }
        });

        it('throws AdminApiError on 404', async () => {
            mockError(404);
            try {
                await usersApi.get(999);
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(AdminApiError);
                expect((err as AdminApiError).status).toBe(404);
            }
        });

        it('handles non-JSON error response', async () => {
            fetchSpy.mockResolvedValue(new Response('Not JSON', {
                status: 500,
                statusText: 'Internal Server Error',
            }));

            try {
                await dashboardApi.stats();
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(AdminApiError);
                expect((err as AdminApiError).code).toBe('unknown_error');
            }
        });

        it('includes validation errors on 422', async () => {
            fetchSpy.mockResolvedValue(new Response(
                JSON.stringify({ error: 'validation_error', message: 'Invalid', errors: { name: ['Required'] } }),
                { status: 422, headers: { 'Content-Type': 'application/json' } },
            ));

            try {
                await usersApi.create({ name: '', email: '', password: '' });
                expect.unreachable('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(AdminApiError);
                expect((err as AdminApiError).errors).toEqual({ name: ['Required'] });
            }
        });
    });

    describe('XSRF token handling', () => {
        it('includes X-XSRF-TOKEN header when cookie is present', async () => {
            mockGetCookie.mockReturnValue('test-xsrf-token');
            mockSuccess({ data: { counts: { users: 10 } } });

            await dashboardApi.stats();

            expect(fetchSpy).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'X-XSRF-TOKEN': 'test-xsrf-token',
                    }),
                }),
            );

            mockGetCookie.mockReturnValue(null);
        });
    });

    describe('workshopsApi.searchSeminars with workshop_id', () => {
        it('includes workshop_id param when provided', async () => {
            mockSuccess({ data: [] });
            await workshopsApi.searchSeminars({ search: 'test', workshop_id: 5 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('workshop_id=5'),
                expect.any(Object),
            );
        });
    });

    describe('usersApi.list query params coverage', () => {
        it('passes trashed param', async () => {
            mockSuccess({ data: [], meta: {} });
            await usersApi.list({ trashed: true });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('trashed=true'),
                expect.any(Object),
            );
        });

        it('passes role param', async () => {
            mockSuccess({ data: [], meta: {} });
            await usersApi.list({ role: 'admin' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('role=admin'),
                expect.any(Object),
            );
        });

        it('builds no query string when no params', async () => {
            mockSuccess({ data: [], meta: {} });
            await usersApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.not.stringContaining('?'),
                expect.any(Object),
            );
        });
    });

    describe('locationsApi.list query params coverage', () => {
        it('passes page param', async () => {
            mockSuccess({ data: [], meta: {} });
            await locationsApi.list({ page: 2 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=2'),
                expect.any(Object),
            );
        });
    });

    describe('subjectsApi.list query params coverage', () => {
        it('passes page param', async () => {
            mockSuccess({ data: [], meta: {} });
            await subjectsApi.list({ page: 3 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=3'),
                expect.any(Object),
            );
        });

        it('builds no query string when no params', async () => {
            mockSuccess({ data: [], meta: {} });
            await subjectsApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.not.stringContaining('?'),
                expect.any(Object),
            );
        });
    });

    describe('workshopsApi.list query params coverage', () => {
        it('passes page param', async () => {
            mockSuccess({ data: [], meta: {} });
            await workshopsApi.list({ page: 2 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=2'),
                expect.any(Object),
            );
        });

        it('passes search param', async () => {
            mockSuccess({ data: [], meta: {} });
            await workshopsApi.list({ search: 'ml' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('search=ml'),
                expect.any(Object),
            );
        });
    });

    describe('workshopsApi.searchSeminars query params coverage', () => {
        it('builds no query string when no params match', async () => {
            mockSuccess({ data: [] });
            await workshopsApi.searchSeminars({});
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/workshops/search-seminars'),
                expect.any(Object),
            );
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.not.stringContaining('?'),
                expect.any(Object),
            );
        });
    });

    describe('registrationsApi.list query params coverage', () => {
        it('passes page param', async () => {
            mockSuccess({ data: [], meta: {} });
            await registrationsApi.list({ page: 4 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=4'),
                expect.any(Object),
            );
        });

        it('passes search param', async () => {
            mockSuccess({ data: [], meta: {} });
            await registrationsApi.list({ search: 'john' });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('search=john'),
                expect.any(Object),
            );
        });

        it('builds no query string when no params', async () => {
            mockSuccess({ data: [], meta: {} });
            await registrationsApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.not.stringContaining('?'),
                expect.any(Object),
            );
        });
    });

    describe('seminarsApi.list query params coverage', () => {
        it('passes page param', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list({ page: 5 });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('page=5'),
                expect.any(Object),
            );
        });

        it('passes active=0 when active is false', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list({ active: false });
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('active=0'),
                expect.any(Object),
            );
        });

        it('builds no query string when no params', async () => {
            mockSuccess({ data: [], meta: {} });
            await seminarsApi.list();
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.not.stringContaining('?'),
                expect.any(Object),
            );
        });
    });
});
