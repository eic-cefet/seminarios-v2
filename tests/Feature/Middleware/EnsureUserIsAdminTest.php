<?php

describe('EnsureUserIsAdmin Middleware', function () {
    it('allows admin user to access admin routes', function () {
        $admin = actingAsAdmin();

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertSuccessful();
    });

    it('allows teacher user to access admin routes', function () {
        $teacher = actingAsTeacher();

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertSuccessful();
    });

    it('returns forbidden for regular user on api routes', function () {
        $user = actingAsUser();

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertForbidden();
    });

    it('returns unauthorized for unauthenticated user on api routes', function () {
        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertUnauthorized();
    });

});
