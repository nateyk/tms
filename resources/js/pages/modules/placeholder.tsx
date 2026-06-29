import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head } from "@inertiajs/react";

type PlaceholderProps = {
    title: string;
    description: string;
};

export default function ModulePlaceholder({ title, description }: PlaceholderProps) {
    return (
        <AuthenticatedLayout header={title}>
            <Head title={title} />

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-2">
                        <CardTitle>{title}</CardTitle>
                        <Badge variant="secondary">Coming in next phase</Badge>
                    </div>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Backend domain logic is ported. This module UI will be built with shadcn/ui
                        components in the next implementation phase.
                    </p>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
