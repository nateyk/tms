import { MovementCompletionDialog } from "@/components/tyres/movement-completion-dialog";
import { VoucherStatusBadge } from "@/components/tyres/voucher-status-badge";
import { VoucherWorkflowActions } from "@/components/tyres/voucher-workflow-actions";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, CalendarDays, MapPin, Pencil, Route, UserRound } from "lucide-react";
import { useState } from "react";

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
    requires_source_odometer: boolean;
    requires_destination_odometer: boolean;
    source_odometer_label: string;
    destination_odometer_label: string;
    source_vehicle_latest_odometer: number | null;
    destination_vehicle_latest_odometer: number | null;
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
    const [completionOpen, setCompletionOpen] = useState(false);
    const workflowCan = { ...can, complete: false };
    const hasWorkflowActions =
        Boolean(movement.pdf_url) || can.submit || can.check || can.approve || can.reject || can.cancel;

    return (
        <AuthenticatedLayout header={`Movement ${movement.display_number}`}>
            <Head title={`Movement ${movement.display_number}`} />

            <div className="max-w-5xl space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={route("tyres.movements.index")}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Movements
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {hasWorkflowActions && (
                            <VoucherWorkflowActions
                                recordId={movement.id}
                                routePrefix="tyres.movements"
                                can={workflowCan}
                                pdfUrl={movement.pdf_url}
                            />
                        )}
                        {can.complete && (
                            <>
                                <Button onClick={() => setCompletionOpen(true)}>Complete Movement</Button>
                                <MovementCompletionDialog
                                    open={completionOpen}
                                    onOpenChange={setCompletionOpen}
                                    movement={movement}
                                />
                            </>
                        )}
                    </div>
                </div>

                <Card className="overflow-hidden">
                    <CardHeader className="border-b bg-muted/30">
                        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div className="min-w-0 space-y-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle className="text-2xl">{movement.display_number}</CardTitle>
                                    <VoucherStatusBadge label={movement.status_label} status={movement.status} />
                                </div>
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                    <span className="inline-flex items-center gap-1.5">
                                        <Route className="h-4 w-4" />
                                        {movement.movement_type_label}
                                    </span>
                                    <span className="inline-flex items-center gap-1.5">
                                        <CalendarDays className="h-4 w-4" />
                                        {movement.movement_date}
                                    </span>
                                    <span className="inline-flex items-center gap-1.5">
                                        <UserRound className="h-4 w-4" />
                                        {movement.prepared_by ?? "Not assigned"}
                                    </span>
                                </div>
                            </div>

                            <div className="rounded-lg border bg-background px-4 py-3 text-sm md:min-w-52">
                                <p className="text-muted-foreground">Tyre</p>
                                <Link
                                    href={route("tyres.show", movement.tyre_id)}
                                    className="font-semibold text-primary hover:underline"
                                >
                                    {movement.tyre_code}
                                </Link>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <MovementLocationCard
                                title="Source"
                                location={movement.from_location_display}
                                position={movement.from_position_display}
                                odometerLabel="Odometer out"
                                odometer={movement.from_odometer}
                            />
                            <MovementLocationCard
                                title="Destination"
                                location={movement.to_location_display}
                                position={movement.to_position_display}
                                odometerLabel="Odometer in"
                                odometer={movement.to_odometer}
                            />
                        </div>

                        {(movement.reason || movement.notes) && (
                            <>
                                <Separator />
                                <div className="grid gap-4 md:grid-cols-2">
                                    {movement.reason && (
                                        <div>
                                            <p className="text-sm font-medium">Reason</p>
                                            <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                                {movement.reason}
                                            </p>
                                        </div>
                                    )}
                                    {movement.notes && (
                                        <div>
                                            <p className="text-sm font-medium">Notes</p>
                                            <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                                {movement.notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        <Separator />

                        <dl className="grid gap-3 rounded-lg border bg-muted/20 p-4 text-sm sm:grid-cols-2">
                            {movement.submitted_at && (
                                <TimelineItem label="Submitted" value={movement.submitted_at} />
                            )}
                            {movement.checked_at && (
                                <TimelineItem
                                    label={`Checked by ${movement.checked_by ?? "unknown"}`}
                                    value={movement.checked_at}
                                />
                            )}
                            {movement.approved_at && (
                                <TimelineItem
                                    label={`Approved by ${movement.approved_by ?? "unknown"}`}
                                    value={movement.approved_at}
                                />
                            )}
                            {movement.completed_at && (
                                <TimelineItem label="Completed" value={movement.completed_at} />
                            )}
                            {!movement.submitted_at &&
                                !movement.checked_at &&
                                !movement.approved_at &&
                                !movement.completed_at && (
                                    <TimelineItem label="Workflow" value="Draft not submitted" />
                                )}
                        </dl>

                        <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
                            <Button variant="ghost" asChild>
                                <Link href={route("tyres.movements.index")}>Back to list</Link>
                            </Button>
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={route("tyres.movements.edit", movement.id)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit draft
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function MovementLocationCard({
    title,
    location,
    position,
    odometerLabel,
    odometer,
}: {
    title: string;
    location: string;
    position: string;
    odometerLabel: string;
    odometer: number | null;
}) {
    return (
        <div className="rounded-lg border bg-background p-4">
            <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                <MapPin className="h-4 w-4 text-muted-foreground" />
                {title}
            </div>
            <p className="font-medium">{location}</p>
            <p className="mt-1 text-sm text-muted-foreground">Position: {position}</p>
            {odometer != null && (
                <p className="mt-2 text-sm text-muted-foreground">
                    {odometerLabel}: {odometer.toLocaleString()} km
                </p>
            )}
        </div>
    );
}

function TimelineItem({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
