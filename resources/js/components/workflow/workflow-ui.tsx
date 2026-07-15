import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { Link } from "@inertiajs/react";
import { ArrowLeft, ArrowRight, CheckCircle2, Circle, type LucideIcon } from "lucide-react";
import type { ReactNode } from "react";

type WorkflowHeaderProps = {
    title: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    badge?: string;
    actions?: ReactNode;
};

export function WorkflowHeader({
    title,
    description,
    backHref,
    backLabel = "Back",
    badge,
    actions,
}: WorkflowHeaderProps) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                {backHref && (
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={backHref}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            {backLabel}
                        </Link>
                    </Button>
                )}
                <div className="flex flex-wrap items-center gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                    {badge && <Badge variant="outline">{badge}</Badge>}
                </div>
                {description && <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
        </div>
    );
}

type WorkflowActionCardProps = {
    title: string;
    description: string;
    value?: string | number;
    href?: string;
    icon?: LucideIcon;
    tone?: "default" | "success" | "warning" | "danger" | "info";
    actionLabel?: string;
};

export function WorkflowActionCard({
    title,
    description,
    value,
    href,
    icon: Icon,
    tone = "default",
    actionLabel = "Open",
}: WorkflowActionCardProps) {
    const toneClass = {
        default: "border-border bg-card",
        success: "border-green-200 bg-green-50 dark:border-green-400/25 dark:bg-green-500/10",
        warning: "border-amber-200 bg-amber-50 dark:border-amber-400/30 dark:bg-amber-500/10",
        danger: "border-red-200 bg-red-50 dark:border-red-400/30 dark:bg-red-500/10",
        info: "border-blue-200 bg-blue-50 dark:border-blue-400/30 dark:bg-blue-500/10",
    }[tone];

    return (
        <Card className={cn("border", toneClass)}>
            <CardContent className="flex h-full flex-col gap-3 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-foreground">{title}</p>
                        <p className="mt-1 text-xs text-muted-foreground">{description}</p>
                    </div>
                    {Icon && <Icon className="h-5 w-5 shrink-0 text-muted-foreground" />}
                </div>
                <div className="mt-auto flex items-end justify-between gap-3">
                    {value !== undefined && <p className="text-2xl font-semibold">{value}</p>}
                    {href && (
                        <Button size="sm" variant="outline" asChild className="ml-auto">
                            <Link href={href}>
                                {actionLabel}
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

type WorkflowStepsProps = {
    steps: Array<{ label: string; done?: boolean }>;
};

export function WorkflowSteps({ steps }: WorkflowStepsProps) {
    return (
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            {steps.map((step, index) => {
                const Icon = step.done ? CheckCircle2 : Circle;
                return (
                    <div key={`${step.label}-${index}`} className="flex items-center gap-2 rounded-md border bg-muted/20 px-3 py-2 text-sm">
                        <Icon className={cn("h-4 w-4", step.done ? "text-green-600" : "text-muted-foreground")} />
                        <span className="font-medium">{step.label}</span>
                    </div>
                );
            })}
        </div>
    );
}

type PlannedWorkflowProps = {
    title: string;
    description: string;
    steps: string[];
    primaryAction?: ReactNode;
};

export function PlannedWorkflow({ title, description, steps, primaryAction }: PlannedWorkflowProps) {
    return (
        <Card>
            <CardContent className="space-y-5 p-5 sm:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{title}</h2>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{description}</p>
                    </div>
                    {primaryAction}
                </div>
                <WorkflowSteps steps={steps.map((label, index) => ({ label, done: index === 0 }))} />
            </CardContent>
        </Card>
    );
}
