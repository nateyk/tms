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

type MovementRow = {
    id: number;
    display_number: string;
    tyre_code: string | null;
    movement_type: string;
    movement_date: string | null;
    to_location_label: string;
    status: string;
    status_label: string;
    prepared_by: string | null;
};

type PaginatedMovements = {
    data: MovementRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type StatusOption = { value: string; label: string };

export default function MovementsIndex({
    movements,
    filters,
    statusOptions,
}: {
    movements: PaginatedMovements;
    filters: { status: string | null };
    statusOptions: StatusOption[];
}) {
    const { can } = usePermission();

    const filterByStatus = (status: string) => {
        router.get(
            route("tyres.movements.index"),
            status === "all" ? {} : { status },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout header="Tyre Movements">
            <Head title="Tyre Movements" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                    <CardTitle>Movement vouchers</CardTitle>
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
                        {can("movement.create") && (
                            <Button asChild>
                                <Link href={route("tyres.movements.create")}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    New movement
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
                                <TableHead>Type</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>To</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {movements.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground">
                                        No movement vouchers found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                movements.data.map((movement) => (
                                    <TableRow key={movement.id}>
                                        <TableCell className="font-medium">
                                            {movement.display_number}
                                        </TableCell>
                                        <TableCell>{movement.tyre_code ?? "—"}</TableCell>
                                        <TableCell>{movement.movement_type}</TableCell>
                                        <TableCell>{movement.movement_date ?? "—"}</TableCell>
                                        <TableCell>{movement.to_location_label}</TableCell>
                                        <TableCell>
                                            <VoucherStatusBadge
                                                label={movement.status_label}
                                                status={movement.status}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={route("tyres.movements.show", movement.id)}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {movement.status === "draft" && can("movement.create") && (
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={route("tyres.movements.edit", movement.id)}>
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
