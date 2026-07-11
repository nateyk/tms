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

type DisposalDetail = {
    id: number;
    display_number: string;
    status: string;
    status_label: string;
    tyre_code: string | null;
    disposal_reason_label: string;
    last_position_display: string;
    final_km_used: number | null;
    final_condition: string;
    estimated_scrap_value: number | null;
    sold_amount: number | null;
    notes: string;
    prepared_by: string | null;
    completed_at: string | null;
    pdf_url: string;
};

export default function DisposalsShow({
    disposal,
    can,
}: {
    disposal: DisposalDetail;
    can: Record<string, boolean>;
}) {
    return (
        <AuthenticatedLayout header={`Disposal ${disposal.display_number}`}>
            <Head title={`Disposal ${disposal.display_number}`} />
            <Card className="max-w-3xl">
                <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle>{disposal.display_number}</CardTitle>
                            <VoucherStatusBadge label={disposal.status_label} status={disposal.status} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {disposal.disposal_reason_label} · {disposal.tyre_code}
                        </p>
                    </div>
                    <VoucherWorkflowActions
                        recordId={disposal.id}
                        routePrefix="tyres.disposals"
                        can={can}
                        pdfUrl={disposal.pdf_url}
                    />
                </CardHeader>
                <CardContent className="space-y-6">
                    <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                        <div><dt className="text-muted-foreground">Last position</dt><dd className="font-medium">{disposal.last_position_display}</dd></div>
                        <div><dt className="text-muted-foreground">Prepared by</dt><dd className="font-medium">{disposal.prepared_by ?? "—"}</dd></div>
                        {disposal.final_km_used != null && (
                            <div><dt className="text-muted-foreground">Final KM</dt><dd className="font-medium">{disposal.final_km_used.toLocaleString()}</dd></div>
                        )}
                        {disposal.final_condition && (
                            <div><dt className="text-muted-foreground">Condition</dt><dd className="font-medium">{disposal.final_condition}</dd></div>
                        )}
                        {disposal.estimated_scrap_value != null && (
                            <div><dt className="text-muted-foreground">Scrap value</dt><dd className="font-medium">ETB {disposal.estimated_scrap_value.toLocaleString()}</dd></div>
                        )}
                        {disposal.sold_amount != null && (
                            <div><dt className="text-muted-foreground">Sold amount</dt><dd className="font-medium">ETB {disposal.sold_amount.toLocaleString()}</dd></div>
                        )}
                    </dl>
                    {disposal.notes && (
                        <>
                            <Separator />
                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">{disposal.notes}</p>
                        </>
                    )}
                    {disposal.completed_at && (
                        <p className="text-sm text-muted-foreground">Completed: {disposal.completed_at}</p>
                    )}
                    <div className="flex flex-wrap gap-2 pt-2">
                        {can.update && (
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.disposals.edit", disposal.id)}>
                                    <Pencil className="mr-2 h-4 w-4" />Edit draft
                                </Link>
                            </Button>
                        )}
                        {can.delete && (
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="destructive"><Trash2 className="mr-2 h-4 w-4" />Delete draft</Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Delete draft?</AlertDialogTitle>
                                        <AlertDialogDescription>This permanently removes the draft voucher.</AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction onClick={() => router.delete(route("tyres.disposals.destroy", disposal.id))}>
                                            Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        )}
                        <Button variant="ghost" asChild>
                            <Link href={route("tyres.disposals.index")}>Back to list</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
