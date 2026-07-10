import { LucideProps } from "lucide-react";
import { ForwardRefExoticComponent, ReactNode, RefAttributes } from "react";

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
    permissions: string[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = T & {
    auth: {
        user: User | null;
    };
    url: string;
    flash?: {
        success?: string;
        error?: string;
    };
};

export type MenuItemProp = {
    title: string;
    href: string;
    icon?:
        | ForwardRefExoticComponent<
              Omit<LucideProps, "ref"> & RefAttributes<SVGSVGElement>
          >
        | ReactNode;
    variant:
        | "link"
        | "default"
        | "ghost"
        | "destructive"
        | "outline"
        | "secondary"
        | null
        | undefined;
};

export interface UsageSummary {
    has_baseline: boolean;
    status: string;
    total_used_km: number | null;
    usage_percentage: number | null;
    estimated_remaining_percentage: number | null;
    baseline_percentage: number | null;
    expected_life_km: number | null;
}

export interface UsageHistoryItem {
    id: number;
    vehicle_code: string | null;
    vehicle_plate: string | null;
    position_code: string;
    installed_odometer: number | null;
    removed_odometer: number | null;
    km_used: number | null;
    installed_date: string | null;
    removed_date: string | null;
    status: string;
    movement_id: number | null;
    is_active: boolean;
}

export interface OdometerReading {
    id: number;
    odometer: number;
    reading_date: string;
    source: string;
    source_id: number | null;
    recorded_by: number | null;
    recorded_by_name: string | null;
    notes: string | null;
    created_at: string;
}
