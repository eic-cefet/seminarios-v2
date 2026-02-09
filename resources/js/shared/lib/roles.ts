export const ROLES = {
    ADMIN: "admin",
    TEACHER: "teacher",
    USER: "user",
} as const;

export function hasRole(
    roles: string[] | undefined | null,
    role: string,
): boolean {
    return roles?.includes(role) ?? false;
}

export function hasAdminAccess(roles: string[] | undefined | null): boolean {
    return hasRole(roles, ROLES.ADMIN) || hasRole(roles, ROLES.TEACHER);
}
