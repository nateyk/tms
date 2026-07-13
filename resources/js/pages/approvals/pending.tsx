import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Check, X } from "lucide-react";

type MovementRow = {
    id: number;
    movement_no: string | null;
    tyre_code: string | null;
    tyre: {
        tyre_code: string | null;
    } | null;
    movement_type: string;
    movement_date: string | null;
    status: string;
    status_label: string;
    prepared_by: string | null;
    submitted_at: string | null;
};

export default function PendingApprovals({
    movements,
}: {
    movements: MovementRow[];
}) {
    const handleCheck = (id: number) => {
        router.post(route('tyres.movements.check', id));
    };

    const handleApprove = (id: number) => {
        router.post(route('tyres.movements.approve', id));
    };

    const handleReject = (id: number) => {
        router.post(route('tyres.movements.reject', id));
    };

    return (
        <AuthenticatedLayout header="Pending Approvals">
            <Head title="Pending Approvals" />

            <Card>
                <CardHeader>
                    <CardTitle>Tyre Movements Pending Approval</CardTitle>
                </CardHeader>
                <CardContent>
                    {movements.length === 0 ? (
                        <div className="text-center py-8 text-muted-foreground">
                            No pending approvals
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Tyre Code</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Submitted By</TableHead>
                                    <TableHead>Submitted Date</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.map((movement) => (
                                    <TableRow key={movement.id}>
                                        <TableCell className="font-medium">
                                            {movement.movement_no || `MOV-${movement.id}`}
                                        </TableCell>
                                        <TableCell>{movement.movement_type}</TableCell>
                                        <TableCell>
                                            {movement.tyre?.tyre_code || movement.tyre_code}
                                        </TableCell>
                                        <TableCell>
                                            {movement.movement_date
                                                ? new Date(movement.movement_date).toLocaleDateString()
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <VoucherStatusBadge label={movement.status_label} status={movement.status} />
                                        </TableCell>
                                        <TableCell>{movement.prepared_by || '—'}</TableCell>
                                        <TableCell>
                                            {movement.submitted_at
                                                ? new Date(movement.submitted_at).toLocaleDateString()
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link href={route('tyres.movements.show', movement.id)}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {movement.status === 'submitted' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleCheck(movement.id)}
                                                    >
                                                        <Check className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                {movement.status === 'checked' && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleApprove(movement.id)}
                                                        >
                                                            <Check className="h-4 w-4 text-green-600" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleReject(movement.id)}
                                                        >
                                                            <X className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
