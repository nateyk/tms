import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
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
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link, router } from "@inertiajs/react";
import { Check, CircleCheck, Send, X } from "lucide-react";
import { FormEvent, useState } from "react";

type Disposal = {
    id: number;
    disposal_no: string;
    tyre_code: string | null;
    tyre_serial_number: string | null;
    tyre_brand: string | null;
    tyre_size: string | null;
    disposal_reason: string | null;
    final_condition: string | null;
    final_km_used: number | null;
    last_location: string;
    notes: string | null;
    status: string;
    status_label: string;
    prepared_by: string | null;
    voided_by: string | null;
    voided_at: string | null;
    void_reason: string | null;
    created_at: string | null;
};

type Actions = {
    can_submit: boolean;
    can_check: boolean;
    can_approve: boolean;
    can_complete: boolean;
    can_void: boolean;
};

const formatKm = (value: number | null) => value === null ? "Not recorded" : `${value.toLocaleString()} KM`;

export default function TyreDisposalShow({ disposal, actions }: { disposal: Disposal; actions: Actions }) {
    const [voidReason, setVoidReason] = useState("");
    const post = (name: "submit" | "check" | "approve" | "complete" | "void") => {
        router.post(route(`tyres.disposals.${name}`, disposal.id));
    };

    const voidVoucher = (event: FormEvent) => {
        event.preventDefault();
        router.post(route("tyres.disposals.void", disposal.id), { reason: voidReason });
    };

    return (
        <AuthenticatedLayout header={disposal.disposal_no}>
            <Head title={disposal.disposal_no} />

            <div className="mx-auto max-w-4xl space-y-6">
                <WorkflowHeader
                    title={disposal.disposal_no}
                    description="A tyre stays in service until this disposal voucher is approved and completed."
                    backHref={route("tyres.disposals.index")}
                    backLabel="Back to Disposals"
                    badge={disposal.status_label}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {actions.can_submit && <Button size="sm" onClick={() => post("submit")}><Send className="mr-2 h-4 w-4" />Submit</Button>}
                            {actions.can_check && <Button size="sm" variant="outline" onClick={() => post("check")}><Check className="mr-2 h-4 w-4" />Check</Button>}
                            {actions.can_approve && <Button size="sm" onClick={() => post("approve")}><CircleCheck className="mr-2 h-4 w-4" />Approve</Button>}
                            {actions.can_complete && <Button size="sm" onClick={() => post("complete")}><CircleCheck className="mr-2 h-4 w-4" />Complete disposal</Button>}
                            {actions.can_void && (
                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button size="sm" variant="outline"><X className="mr-2 h-4 w-4" />Void</Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <form onSubmit={voidVoucher}>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Void disposal voucher</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    The voucher is retained for audit and cannot be reopened.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <div className="space-y-2 py-4">
                                                <Label htmlFor="disposal_void_reason">Reason</Label>
                                                <Textarea
                                                    id="disposal_void_reason"
                                                    required
                                                    value={voidReason}
                                                    onChange={(event) => setVoidReason(event.target.value)}
                                                />
                                            </div>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel type="button">Keep voucher</AlertDialogCancel>
                                                <AlertDialogAction type="submit" disabled={!voidReason.trim()}>Void voucher</AlertDialogAction>
                                            </AlertDialogFooter>
                                        </form>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}
                        </div>
                    }
                />

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle>Disposal details</CardTitle>
                            <Badge variant="outline">{disposal.status_label}</Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="grid gap-4 p-5 sm:grid-cols-2">
                        <Detail label="Tyre" value={disposal.tyre_code ?? "-"} note={disposal.tyre_serial_number ?? "No serial recorded"} />
                        <Detail label="Last location" value={disposal.last_location} />
                        <Detail label="Disposal reason" value={disposal.disposal_reason ?? "-"} />
                        <Detail label="Final condition" value={disposal.final_condition ?? "Not recorded"} />
                        <Detail label="Tyre KM at voucher" value={formatKm(disposal.final_km_used)} />
                        <Detail label="Prepared by" value={disposal.prepared_by ?? "-"} />
                    </CardContent>
                </Card>

                {disposal.notes && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Notes</CardTitle></CardHeader>
                        <CardContent className="text-sm text-muted-foreground">{disposal.notes}</CardContent>
                    </Card>
                )}

                {disposal.void_reason && (
                    <Card className="border-destructive/30">
                        <CardHeader><CardTitle className="text-base text-destructive">Void record</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p>{disposal.void_reason}</p>
                            <p className="text-muted-foreground">
                                {disposal.voided_by ?? "Unknown user"}{disposal.voided_at ? ` · ${disposal.voided_at}` : ""}
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value, note }: { label: string; value: string; note?: string }) {
    return (
        <div className="rounded-md border bg-muted/20 p-3">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 font-semibold">{value}</p>
            {note && <p className="mt-1 text-xs text-muted-foreground">{note}</p>}
        </div>
    );
}
