import {
    Building2,
    Car,
    CircleDot,
    ClipboardCheck,
    FileBarChart,
    Gauge,
    History,
    LayoutDashboard,
    Recycle,
    Settings,
    Shield,
    Store,
    Truck,
    Users,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";

export type NavItem = {
    title: string;
    url: string;
    icon?: LucideIcon;
    permission?: string | string[];
};

export type NavGroup = {
    label: string;
    items: NavItem[];
};

export const tmsNavigation: NavGroup[] = [
    {
        label: "Fleet Tyre Operations",
        items: [
            {
                title: "Fleet",
                url: "/dashboard",
                icon: LayoutDashboard,
            },
            {
                title: "Vehicle Types",
                url: "/fleet/vehicle-types",
                icon: Truck,
            },
            {
                title: "Stores",
                url: "/fleet/stores",
                icon: Store,
            },
            {
                title: "Vehicles",
                url: "/fleet/vehicles",
                icon: Car,
                permission: "vehicle.view",
            },
            {
                title: "Trailer Transfers",
                url: "/fleet/trailer-transfers",
                icon: Building2,
                permission: "trailer.transfer",
            },
        ],
    },
    {
        label: "Tyre Operations",
        items: [
            {
                title: "Tyres",
                url: "/tyres",
                icon: CircleDot,
                permission: "tyre.view",
            },
            {
                title: "Reading Monitoring",
                url: "/tyres/reading-monitoring",
                icon: Gauge,
                permission: "tyre-reading.view",
            },
            {
                title: "Tyre Movements",
                url: "/tyres/movements",
                icon: History,
                permission: "movement.create",
            },
            {
                title: "Tyre Disposals",
                url: "/tyres/disposals",
                icon: Recycle,
                permission: "disposal.create",
            },
        ],
    },
    {
        label: "Approvals & Reports",
        items: [
            {
                title: "Pending Approvals",
                url: "/approvals/pending",
                icon: ClipboardCheck,
            },
            {
                title: "Reports",
                url: "/approvals/reports",
                icon: FileBarChart,
                permission: "report.view",
            },
            {
                title: "Audit Logs",
                url: "/approvals/audit-logs",
                icon: History,
                permission: "audit.view",
            },
        ],
    },
    {
        label: "Administration",
        items: [
            {
                title: "Users",
                url: "/admin/users",
                icon: Users,
            },
            {
                title: "Roles",
                url: "/admin/roles",
                icon: Shield,
            },
            {
                title: "Settings",
                url: "/admin/settings",
                icon: Settings,
                permission: "settings.manage",
            },
        ],
    },
];

export function filterNavigationByPermissions(
    groups: NavGroup[],
    permissions: string[],
): NavGroup[] {
    const canAccess = (permission?: string | string[]) => {
        if (!permission) {
            return true;
        }

        const required = Array.isArray(permission) ? permission : [permission];

        return required.some((item) => permissions.includes(item));
    };

    return groups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => canAccess(item.permission)),
        }))
        .filter((group) => group.items.length > 0);
}
