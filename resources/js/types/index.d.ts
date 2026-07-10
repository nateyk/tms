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
