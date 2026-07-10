import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { VoucherWorkflowActions } from "@/components/tyres/voucher-workflow-actions";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
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
import { Head, Link, router } from "@inertiajs/react";
import { Pencil, Trash2 } from "lucide-react";

type TransferDetail = {
    id: number;
    transfer_no: string;
    display_number: string;
    status: string;
    status_label: string;
    transfer_date: string;
    trailer_label: string | null;
    trailer_vehicle_id: number;
    from_power_label: string;
    to_power_label: string | null;
    to_power_vehicle_id: number;
    location_label: string | null;
    from_odometer: number | null;
    to_odometer: number | null;
    reason: string;
    notes: string;
    prepared_by: string | null;
    checked_by: string | null;
    approved_by: string | null;
    completed_at: string | null;
    pdf_url: string;
};

type Permissions = {
    update: boolean;
    delete: boolean;
    submit: boolean;
    check: boolean;
    approve: boolean;
    reject: boolean;
    complete: boolean;
    cancel: boolean;
};

export default function TrailerTransfersShow({
    transfer,
    can,
}: {
    transfer: TransferDetail;
    can: Permissions;
}) {
    const deleteTransfer = () => {
        router.delete(route("fleet.trailer-transfers.destroy", transfer.id));
    };

    return (
        <AuthenticatedLayout header={`Transfer ${transfer.display_number}`}>
            <Head title={`Transfer ${transfer.display_number}`} />

            <div className="space-y-6 max-w-3xl">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <CardTitle>{transfer.display_number}</CardTitle>
                                <VoucherStatusBadge
                                    label={transfer.status_label}
                                    status={transfer.status}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {transfer.transfer_date}
                                {transfer.location_label ? ` · ${transfer.location_label}` : ""}
                            </p>
                        </div>
                        <VoucherWorkflowActions
                            recordId={transfer.id}
                            routePrefix="fleet.trailer-transfers"
                            can={can}
                            pdfUrl={transfer.pdf_url}
                            entityLabel="transfer"
                            completeDescription="This reassigns the trailer to the new power unit. Tyres stay on the trailer."
                        />
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm text-muted-foreground">Trailer</dt>
                                <dd className="font-medium">
                                    <Link
                                        href={route("fleet.vehicles.show", transfer.trailer_vehicle_id)}
                                        className="text-primary hover:underline"
                                    >
                                        {transfer.trailer_label}
                                    </Link>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-muted-foreground">Prepared by</dt>
                                <dd className="font-medium">{transfer.prepared_by ?? "—"}</dd>
                            </div>
                        </dl>

                        <Separator />

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-lg border p-4 space-y-2">
                                <p className="text-sm font-medium">From power unit</p>
                                <p className="text-sm">{transfer.from_power_label}</p>
                                {transfer.from_odometer != null && (
                                    <p className="text-sm text-muted-foreground">
                                        Odometer out: {transfer.from_odometer.toLocaleString()} km
                                    </p>
                                )}
                            </div>
                            <div className="rounded-lg border p-4 space-y-2">
                                <p className="text-sm font-medium">To power unit</p>
                                <p className="text-sm">
                                    <Link
                                        href={route(
                                            "fleet.vehicles.show",
                                            transfer.to_power_vehicle_id,
                                        )}
                                        className="text-primary hover:underline"
                                    >
                                        {transfer.to_power_label}
                                    </Link>
                                </p>
                                {transfer.to_odometer != null && (
                                    <p className="text-sm text-muted-foreground">
                                        Odometer in: {transfer.to_odometer.toLocaleString()} km
                                    </p>
                                )}
                            </div>
                        </div>

                        {(transfer.reason || transfer.notes) && (
                            <>
                                <Separator />
                                <div className="grid gap-4 md:grid-cols-2">
                                    {transfer.reason && (
                                        <div>
                                            <p className="text-sm font-medium">Reason</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                                {transfer.reason}
                                            </p>
                                        </div>
                                    )}
                                    {transfer.notes && (
                                        <div>
                                            <p className="text-sm font-medium">Notes</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                                {transfer.notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        {(transfer.checked_by || transfer.approved_by || transfer.completed_at) && (
                            <>
                                <Separator />
                                <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                                    {transfer.checked_by && (
                                        <div>
                                            <dt className="text-muted-foreground">Checked by</dt>
                                            <dd>{transfer.checked_by}</dd>
                                        </div>
                                    )}
                                    {transfer.approved_by && (
                                        <div>
                                            <dt className="text-muted-foreground">Approved by</dt>
                                            <dd>{transfer.approved_by}</dd>
                                        </div>
                                    )}
                                    {transfer.completed_at && (
                                        <div>
                                            <dt className="text-muted-foreground">Completed</dt>
                                            <dd>{transfer.completed_at}</dd>
                                        </div>
                                    )}
                                </dl>
                            </>
                        )}

                        <div className="flex flex-wrap gap-2 pt-2">
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={route("fleet.trailer-transfers.edit", transfer.id)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit draft
                                    </Link>
                                </Button>
                            )}
                            {can.delete && (
                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button variant="destructive">
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Delete draft
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Delete draft?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This permanently removes the draft transfer voucher.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction onClick={deleteTransfer}>
                                                Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}
                            <Button variant="ghost" asChild>
                                <Link href={route("fleet.trailer-transfers.index")}>
                                    Back to list
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
