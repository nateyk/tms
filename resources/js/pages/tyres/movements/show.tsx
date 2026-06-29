import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { VoucherWorkflowActions } from "@/components/tyres/voucher-workflow-actions";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Head, Link } from "@inertiajs/react";
import { Pencil, Trash2 } from "lucide-react";
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
import { router } from "@inertiajs/react";

type MovementDetail = {
    id: number;
    movement_no: string;
    display_number: string;
    status: string;
    status_label: string;
    movement_type_label: string;
    movement_date: string;
    tyre_code: string | null;
    tyre_id: number;
    from_location_display: string;
    from_position_display: string;
    to_location_display: string;
    to_position_display: string;
    from_odometer: number | null;
    to_odometer: number | null;
    reason: string;
    notes: string;
    prepared_by: string | null;
    checked_by: string | null;
    approved_by: string | null;
    submitted_at: string | null;
    checked_at: string | null;
    approved_at: string | null;
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

export default function MovementsShow({
    movement,
    can,
}: {
    movement: MovementDetail;
    can: Permissions;
}) {
    const deleteMovement = () => {
        router.delete(route("tyres.movements.destroy", movement.id));
    };

    return (
        <AuthenticatedLayout header={`Movement ${movement.display_number}`}>
            <Head title={`Movement ${movement.display_number}`} />

            <div className="space-y-6 max-w-4xl">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <CardTitle>{movement.display_number}</CardTitle>
                                <VoucherStatusBadge
                                    label={movement.status_label}
                                    status={movement.status}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {movement.movement_type_label} · {movement.movement_date}
                            </p>
                        </div>
                        <VoucherWorkflowActions
                            movementId={movement.id}
                            can={can}
                            pdfUrl={movement.pdf_url}
                        />
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm text-muted-foreground">Tyre</dt>
                                <dd className="font-medium">
                                    <Link
                                        href={route("tyres.show", movement.tyre_id)}
                                        className="text-primary hover:underline"
                                    >
                                        {movement.tyre_code}
                                    </Link>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-muted-foreground">Prepared by</dt>
                                <dd className="font-medium">{movement.prepared_by ?? "—"}</dd>
                            </div>
                        </dl>

                        <Separator />

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-lg border p-4 space-y-2">
                                <p className="text-sm font-medium">Source</p>
                                <p className="text-sm">{movement.from_location_display}</p>
                                <p className="text-sm text-muted-foreground">
                                    Position: {movement.from_position_display}
                                </p>
                                {movement.from_odometer != null && (
                                    <p className="text-sm text-muted-foreground">
                                        Odometer out: {movement.from_odometer.toLocaleString()} km
                                    </p>
                                )}
                            </div>
                            <div className="rounded-lg border p-4 space-y-2">
                                <p className="text-sm font-medium">Destination</p>
                                <p className="text-sm">{movement.to_location_display}</p>
                                <p className="text-sm text-muted-foreground">
                                    Position: {movement.to_position_display}
                                </p>
                                {movement.to_odometer != null && (
                                    <p className="text-sm text-muted-foreground">
                                        Odometer in: {movement.to_odometer.toLocaleString()} km
                                    </p>
                                )}
                            </div>
                        </div>

                        {(movement.reason || movement.notes) && (
                            <>
                                <Separator />
                                <div className="grid gap-4 md:grid-cols-2">
                                    {movement.reason && (
                                        <div>
                                            <p className="text-sm font-medium">Reason</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                                {movement.reason}
                                            </p>
                                        </div>
                                    )}
                                    {movement.notes && (
                                        <div>
                                            <p className="text-sm font-medium">Notes</p>
                                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                                {movement.notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        <Separator />

                        <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                            {movement.submitted_at && (
                                <div>
                                    <dt className="text-muted-foreground">Submitted</dt>
                                    <dd>{movement.submitted_at}</dd>
                                </div>
                            )}
                            {movement.checked_at && (
                                <div>
                                    <dt className="text-muted-foreground">Checked by {movement.checked_by}</dt>
                                    <dd>{movement.checked_at}</dd>
                                </div>
                            )}
                            {movement.approved_at && (
                                <div>
                                    <dt className="text-muted-foreground">Approved by {movement.approved_by}</dt>
                                    <dd>{movement.approved_at}</dd>
                                </div>
                            )}
                            {movement.completed_at && (
                                <div>
                                    <dt className="text-muted-foreground">Completed</dt>
                                    <dd>{movement.completed_at}</dd>
                                </div>
                            )}
                        </dl>

                        <div className="flex flex-wrap gap-2 pt-2">
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={route("tyres.movements.edit", movement.id)}>
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
                                                This permanently removes the draft movement voucher.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction onClick={deleteMovement}>
                                                Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}
                            <Button variant="ghost" asChild>
                                <Link href={route("tyres.movements.index")}>Back to list</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
