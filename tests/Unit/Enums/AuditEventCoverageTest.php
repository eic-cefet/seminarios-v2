<?php

use App\Enums\AuditEvent;

it('exposes new coverage events', function (string $caseName, string $value) {
    expect(AuditEvent::from($value))->toBe(constant(AuditEvent::class.'::'.$caseName));
})->with([
    'login_failed' => ['UserLoginFailed', 'user.login_failed'],
    'password_changed' => ['UserPasswordChanged', 'user.password_changed'],
    'admin_password_reset' => ['UserPasswordResetByAdmin', 'user.password_reset_by_admin'],
    'role_assigned' => ['UserRoleAssigned', 'user.role_assigned'],
    'role_revoked' => ['UserRoleRevoked', 'user.role_revoked'],
    'admin_user_viewed' => ['UserViewedByAdmin', 'admin.user_viewed'],
    'admin_user_deleted' => ['UserDeletedByAdmin', 'admin.user_deleted'],
    'admin_user_restored' => ['UserRestoredByAdmin', 'admin.user_restored'],
    'admin_lgpd_viewed' => ['LgpdRecordViewedByAdmin', 'admin.lgpd_record_viewed'],
    'authorization_denied' => ['AuthorizationDenied', 'security.authorization_denied'],
]);
