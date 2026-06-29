import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

const colorVariants: Record<string, string> = {
    draft: "border-border bg-muted text-muted-foreground",
    submitted: "border-blue-600/30 bg-blue-500/10 text-blue-700 dark:text-blue-400",
    checked: "border-yellow-600/30 bg-yellow-500/10 text-yellow-800 dark:text-yellow-400",
    approved: "border-green-600/30 bg-green-500/10 text-green-700 dark:text-green-400",
    rejected: "border-red-600/30 bg-red-500/10 text-red-700 dark:text-red-400",
    cancelled: "border-border bg-muted text-muted-foreground",
    completed: "border-green-600/30 bg-green-500/10 text-green-700 dark:text-green-400",
};

type VoucherStatusBadgeProps = {
    label: string;
    status: string;
    className?: string;
};

export function VoucherStatusBadge({ label, status, className }: VoucherStatusBadgeProps) {
    return (
        <Badge variant="outline" className={cn(colorVariants[status] ?? "", className)}>
            {label}
        </Badge>
    );
}
