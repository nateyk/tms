import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link, router } from "@inertiajs/react";
import { Check, CircleCheck, Send, X } from "lucide-react";

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
    const post = (name: "submit" | "check" | "approve" | "complete" | "void") => {
        router.post(route(`tyres.disposals.${name}`, disposal.id));
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
                            {actions.can_void && <Button size="sm" variant="outline" onClick={() => post("void")}><X className="mr-2 h-4 w-4" />Void</Button>}
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
