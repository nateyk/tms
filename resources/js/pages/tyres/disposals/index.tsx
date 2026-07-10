import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { usePermission } from "@/hooks/use-permission";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Pencil, Plus } from "lucide-react";

type Row = {
    id: number;
    display_number: string;
    tyre_code: string | null;
    disposal_reason: string;
    status: string;
    status_label: string;
};

export default function DisposalsIndex({
    disposals,
    filters,
    statusOptions,
}: {
    disposals: { data: Row[] };
    filters: { status: string | null };
    statusOptions: { value: string; label: string }[];
}) {
    const { can } = usePermission();

    return (
        <AuthenticatedLayout header="Tyre Disposals">
            <Head title="Tyre Disposals" />
            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                    <CardTitle>Disposal vouchers</CardTitle>
                    <div className="flex gap-2">
                        <Select
                            value={filters.status ?? "all"}
                            onValueChange={(status) =>
                                router.get(
                                    route("tyres.disposals.index"),
                                    status === "all" ? {} : { status },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {statusOptions.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {can("disposal.create") && (
                            <Button asChild>
                                <Link href={route("tyres.disposals.create")}>
                                    <Plus className="mr-2 h-4 w-4" />New disposal
                                </Link>
                            </Button>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No</TableHead>
                                <TableHead>Tyre</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {disposals.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                                        No disposal vouchers found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                disposals.data.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="font-medium">{row.display_number}</TableCell>
                                        <TableCell>{row.tyre_code ?? "—"}</TableCell>
                                        <TableCell>{row.disposal_reason}</TableCell>
                                        <TableCell>
                                            <VoucherStatusBadge label={row.status_label} status={row.status} />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="icon" asChild>
                                                <Link href={route("tyres.disposals.show", row.id)}>
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            {row.status === "draft" && can("disposal.create") && (
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={route("tyres.disposals.edit", row.id)}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
