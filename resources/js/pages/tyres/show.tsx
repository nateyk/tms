import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreStatusBadge } from "@/components/tyres/tyre-status-badge";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Head, Link, router } from "@inertiajs/react";
import {
    CheckCircle2,
    ExternalLink,
    FileText,
    Pencil,
    QrCode,
    RefreshCw,
} from "lucide-react";
import { useState, type ReactNode } from "react";

type TyreDetail = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    pattern: string;
    supplier: string;
    source_label: string;
    purchase_date: string;
    purchase_price: number;
    invoice_number: string;
    initial_tread_depth: number | null;
    current_tread_depth: number | null;
    notes: string;
    status: string;
    status_label: string;
    status_color: string;
    current_location_type: string;
    current_location_id: number | null;
    current_position_code: string;
    vehicle_plate: string;
    qr_public_url: string | null;
    qr_scan_url: string;
    total_km: number;
    cost_per_km: number | null;
    created_at: string | null;
    updated_at: string | null;
    recent_movements: {
        movement_no: string;
        movement_type: string;
        status: string;
    }[];
    recent_maintenance: {
        maintenance_no: string;
        problem_type: string;
        status: string;
    }[];
};

type Permissions = {
    update: boolean;
    delete: boolean;
    approve: boolean;
};

function DetailItem({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{value || "—"}</dd>
        </div>
    );
}

export default function TyresShow({ tyre, can }: { tyre: TyreDetail; can: Permissions }) {
    const [approving, setApproving] = useState(false);
    const [regenerating, setRegenerating] = useState(false);

    const approve = () => {
        setApproving(true);
        router.post(route("tyres.approve", tyre.id), {}, { onFinish: () => setApproving(false) });
    };

    const regenerateQr = () => {
        setRegenerating(true);
        router.post(
            route("tyres.regenerate-qr", tyre.id),
            {},
            { onFinish: () => setRegenerating(false) },
        );
    };

    const isPending = tyre.status === "pending_approval";

    return (
        <AuthenticatedLayout header={tyre.tyre_code}>
            <Head title={tyre.tyre_code} />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle className="flex flex-wrap items-center gap-2">
                                {tyre.tyre_code}
                                <TyreStatusBadge
                                    label={tyre.status_label}
                                    color={tyre.status_color}
                                />
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">{tyre.serial_number}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {can.approve && isPending && (
                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button size="sm" disabled={approving}>
                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                            Approve registration
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>
                                                Approve tyre registration?
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will mark the tyre as available and generate its
                                                QR code.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction onClick={approve}>
                                                Approve
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}

                            {!isPending && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={regenerateQr}
                                    disabled={regenerating}
                                >
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Regenerate QR
                                </Button>
                            )}

                            <Button variant="outline" size="sm" asChild>
                                <a href={tyre.qr_scan_url} target="_blank" rel="noreferrer">
                                    <QrCode className="mr-2 h-4 w-4" />
                                    QR profile
                                </a>
                            </Button>

                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.registration.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    Registration PDF
                                </a>
                            </Button>

                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.history.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    History PDF
                                </a>
                            </Button>

                            {can.update && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route("tyres.edit", tyre.id)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <DetailItem label="Brand" value={tyre.brand_name} />
                            <DetailItem label="Size" value={tyre.size_label} />
                            <DetailItem label="Pattern" value={tyre.pattern} />
                            <DetailItem label="Supplier" value={tyre.supplier} />
                            <DetailItem label="Source" value={tyre.source_label} />
                            <DetailItem label="Purchase date" value={tyre.purchase_date} />
                            <DetailItem
                                label="Purchase price"
                                value={`ETB ${tyre.purchase_price.toLocaleString()}`}
                            />
                            <DetailItem label="Invoice" value={tyre.invoice_number} />
                            <DetailItem
                                label="Initial tread"
                                value={
                                    tyre.initial_tread_depth
                                        ? `${tyre.initial_tread_depth} mm`
                                        : null
                                }
                            />
                            <DetailItem
                                label="Current tread"
                                value={
                                    tyre.current_tread_depth
                                        ? `${tyre.current_tread_depth} mm`
                                        : null
                                }
                            />
                            <DetailItem label="Location type" value={tyre.current_location_type} />
                            <DetailItem label="Vehicle / plate" value={tyre.vehicle_plate} />
                            <DetailItem label="Position" value={tyre.current_position_code} />
                            <DetailItem
                                label="Total km"
                                value={tyre.total_km.toLocaleString()}
                            />
                            <DetailItem
                                label="Cost per km"
                                value={tyre.cost_per_km ? tyre.cost_per_km.toFixed(4) : null}
                            />
                        </dl>

                        {tyre.notes && (
                            <>
                                <Separator className="my-4" />
                                <DetailItem label="Notes" value={tyre.notes} />
                            </>
                        )}

                        {tyre.qr_public_url && (
                            <>
                                <Separator className="my-4" />
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <img
                                        src={tyre.qr_public_url}
                                        alt={`QR code for ${tyre.tyre_code}`}
                                        className="h-44 w-44 rounded border bg-white p-2"
                                    />
                                    <div className="space-y-2 text-sm">
                                        <p className="font-medium">Scan profile</p>
                                        <a
                                            href={tyre.qr_scan_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-primary hover:underline"
                                        >
                                            {tyre.qr_scan_url}
                                            <ExternalLink className="h-3 w-3" />
                                        </a>
                                    </div>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                {tyre.recent_movements.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recent movements</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Movement no.</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tyre.recent_movements.map((movement) => (
                                        <TableRow key={movement.movement_no}>
                                            <TableCell>{movement.movement_no}</TableCell>
                                            <TableCell>{movement.movement_type}</TableCell>
                                            <TableCell>{movement.status}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {tyre.recent_maintenance.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recent maintenance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Maintenance no.</TableHead>
                                        <TableHead>Problem</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tyre.recent_maintenance.map((record) => (
                                        <TableRow key={record.maintenance_no}>
                                            <TableCell>{record.maintenance_no}</TableCell>
                                            <TableCell>{record.problem_type}</TableCell>
                                            <TableCell>{record.status}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
