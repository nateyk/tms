import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

const colorVariants: Record<string, string> = {
    green: "border-green-600/30 bg-green-500/10 text-green-700 dark:text-green-400",
    blue: "border-blue-600/30 bg-blue-500/10 text-blue-700 dark:text-blue-400",
    orange: "border-orange-600/30 bg-orange-500/10 text-orange-700 dark:text-orange-400",
    red: "border-red-600/30 bg-red-500/10 text-red-700 dark:text-red-400",
    black: "border-border bg-muted text-muted-foreground",
    yellow: "border-yellow-600/30 bg-yellow-500/10 text-yellow-800 dark:text-yellow-400",
};

type TyreStatusBadgeProps = {
    label: string;
    color: string;
    className?: string;
};

export function TyreStatusBadge({ label, color, className }: TyreStatusBadgeProps) {
    return (
        <Badge variant="outline" className={cn(colorVariants[color] ?? "", className)}>
            {label}
        </Badge>
    );
}
