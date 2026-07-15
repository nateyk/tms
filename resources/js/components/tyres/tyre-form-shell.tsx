import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import type { ReactNode } from "react";

type TyreFormShellProps = {
    title: string;
    description?: string;
    backHref: string;
    backLabel: string;
    children: ReactNode;
    footer: ReactNode;
    maxWidth?: string;
};

export function TyreFormShell({
    title,
    description,
    backHref,
    backLabel,
    children,
    footer,
    maxWidth = "max-w-4xl",
}: TyreFormShellProps) {
    return (
        <div className={cn("mx-auto w-full space-y-4", maxWidth)}>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={backHref}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            {backLabel}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                    {description && (
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{description}</p>
                    )}
                </div>
            </div>

            <Card className="overflow-hidden">
                <CardContent className="p-5 sm:p-6">
                    {children}
                </CardContent>
                <div className="flex flex-col-reverse gap-2 border-t bg-muted/20 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    {footer}
                </div>
            </Card>
        </div>
    );
}
