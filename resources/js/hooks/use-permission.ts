import { usePage } from "@inertiajs/react";
import { PageProps } from "@/types";

export function usePermission() {
    const { auth } = usePage<PageProps>().props;

    const can = (permission: string | string[]): boolean => {
        const permissions = auth.user?.permissions ?? [];
        const required = Array.isArray(permission) ? permission : [permission];

        return required.some((item) => permissions.includes(item));
    };

    const hasRole = (role: string | string[]): boolean => {
        const roles = auth.user?.roles ?? [];
        const required = Array.isArray(role) ? role : [role];

        return required.some((item) => roles.includes(item));
    };

    return { can, hasRole, permissions: auth.user?.permissions ?? [], roles: auth.user?.roles ?? [] };
}
