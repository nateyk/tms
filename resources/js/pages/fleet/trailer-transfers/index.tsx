import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { usePermission } from "@/hooks/use-permission";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Pencil, Plus } from "lucide-react";

type TransferRow = {
    id: number;
    display_number: string;
    trailer_code: string | null;
    from_power_code: string | null;
    to_power_code: string | null;
    transfer_date: string | null;
    status: string;
    status_label: string;
    prepared_by: string | null;
};

type PaginatedTransfers = {
    data: TransferRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type StatusOption = { value: string; label: string };

export default function TrailerTransfersIndex({
    transfers,
    filters,
    statusOptions,
}: {
    transfers: PaginatedTransfers;
    filters: { status: string | null };
    statusOptions: StatusOption[];
}) {
    const { can } = usePermission();

    const filterByStatus = (status: string) => {
        router.get(
            route("fleet.trailer-transfers.index"),
            status === "all" ? {} : { status },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout header="Trailer Transfers">
            <Head title="Trailer Transfers" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                    <CardTitle>Trailer transfer vouchers</CardTitle>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={filters.status ?? "all"}
                            onValueChange={filterByStatus}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Filter status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {statusOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {can("trailer.transfer") && (
                            <Button asChild>
                                <Link href={route("fleet.trailer-transfers.create")}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    New transfer
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
                                <TableHead>Trailer</TableHead>
                                <TableHead>From</TableHead>
                                <TableHead>To</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transfers.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground">
                                        No trailer transfers found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                transfers.data.map((transfer) => (
                                    <TableRow key={transfer.id}>
                                        <TableCell className="font-medium">
                                            {transfer.display_number}
                                        </TableCell>
                                        <TableCell>{transfer.trailer_code ?? "—"}</TableCell>
                                        <TableCell>{transfer.from_power_code ?? "—"}</TableCell>
                                        <TableCell>{transfer.to_power_code ?? "—"}</TableCell>
                                        <TableCell>{transfer.transfer_date ?? "—"}</TableCell>
                                        <TableCell>
                                            <VoucherStatusBadge
                                                label={transfer.status_label}
                                                status={transfer.status}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link
                                                        href={route(
                                                            "fleet.trailer-transfers.show",
                                                            transfer.id,
                                                        )}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {transfer.status === "draft" &&
                                                    can("trailer.transfer") && (
                                                        <Button variant="ghost" size="icon" asChild>
                                                            <Link
                                                                href={route(
                                                                    "fleet.trailer-transfers.edit",
                                                                    transfer.id,
                                                                )}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                            </div>
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
