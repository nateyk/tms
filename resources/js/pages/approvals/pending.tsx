import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link } from "@inertiajs/react";
import {
    ArrowLeftRight,
    ExternalLink,
    Trash2,
    Truck,
} from "lucide-react";

type PendingItem = {
    id: number;
    number: string;
    subtitle: string;
    status: string;
    status_label: string;
    url: string;
};

type PendingSection = {
    key: string;
    label: string;
    count: number;
    items: PendingItem[];
};

const sectionIcons: Record<string, typeof ArrowLeftRight> = {
    movements: ArrowLeftRight,
    transfers: Truck,
    disposals: Trash2,
};

function StatusBadge({ status, label }: { status: string; label: string }) {
    return <VoucherStatusBadge status={status} label={label} />;
}

export default function PendingApprovals({ sections }: { sections: PendingSection[] }) {
    const totalPending = sections.reduce((sum, section) => sum + section.count, 0);

    return (
        <AuthenticatedLayout header="Pending Approvals">
            <Head title="Pending Approvals" />

            <p className="mb-6 text-sm text-muted-foreground">
                Vouchers waiting for check, approval, or completion. Open a row to continue the workflow.
            </p>

            {totalPending === 0 ? (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        No pending approvals — all queues are up to date.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-6 lg:grid-cols-2">
                    {sections.map((section) => {
                        const Icon = sectionIcons[section.key] ?? ArrowLeftRight;

                        return (
                            <Card key={section.key}>
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-400">
                                            <Icon className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <CardTitle>{section.label}</CardTitle>
                                            <CardDescription>
                                                {section.count} pending
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {section.items.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No pending items in this queue.
                                        </p>
                                    ) : (
                                        <ul className="divide-y">
                                            {section.items.map((item) => (
                                                <li
                                                    key={item.id}
                                                    className="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                                >
                                                    <div className="min-w-0 space-y-1">
                                                        <p className="truncate font-medium">{item.number}</p>
                                                        <p className="truncate text-sm text-muted-foreground">
                                                            {item.subtitle}
                                                        </p>
                                                        <StatusBadge
                                                            status={item.status}
                                                            label={item.status_label}
                                                        />
                                                    </div>
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={item.url}>
                                                            {section.key === "movements" ? "Review" : "Open"}
                                                            <ExternalLink className="ml-1 h-3 w-3" />
                                                        </Link>
                                                    </Button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
