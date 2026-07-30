import { Button } from "@/components/ui/button";
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
import { router } from "@inertiajs/react";
import { CheckCircle, ClipboardCheck, FileDown, Send, XCircle } from "lucide-react";
import { FormEvent, useState } from "react";

type VoucherPermissions = {
    submit?: boolean;
    check?: boolean;
    approve?: boolean;
    reject?: boolean;
    complete?: boolean;
    cancel?: boolean;
};

type VoucherWorkflowActionsProps = {
    recordId: number;
    routePrefix: string;
    can: VoucherPermissions;
    status: string;
    pdfUrl?: string;
};

export function VoucherWorkflowActions({ recordId, routePrefix, can, status, pdfUrl }: VoucherWorkflowActionsProps) {
    const [rejectReason, setRejectReason] = useState("");
    const [voidReason, setVoidReason] = useState("");
    const [processing, setProcessing] = useState(false);

    const actionPermissions: VoucherPermissions = {
        submit: status === "draft" && can.submit,
        check: status === "submitted" && can.check,
        approve: status === "checked" && can.approve,
        reject: ["submitted", "checked"].includes(status) && can.reject,
        complete: status === "approved" && can.complete,
        cancel: ["draft", "submitted", "checked", "approved"].includes(status) && can.cancel,
    };

    const postAction = (action: string, payload: Record<string, string> = {}) => {
        setProcessing(true);
        router.post(route(`${routePrefix}.${action}`, recordId), payload, {
            onFinish: () => setProcessing(false),
        });
    };

    const handleReject = (event: FormEvent) => {
        event.preventDefault();
        postAction("reject", { reason: rejectReason });
    };

    return (
        <div className="flex flex-wrap items-center justify-end gap-2">
            {pdfUrl && (
                <Button variant="outline" asChild>
                    <a href={pdfUrl} target="_blank" rel="noreferrer">
                        <FileDown className="mr-2 h-4 w-4" />
                        PDF
                    </a>
                </Button>
            )}

            {actionPermissions.submit && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="secondary" disabled={processing}>
                            <Send className="mr-2 h-4 w-4" />
                            Submit
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Submit for review?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This sends the voucher to a checker for review.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("submit")}>
                                Submit
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {actionPermissions.check && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="secondary" disabled={processing}>
                            <ClipboardCheck className="mr-2 h-4 w-4" />
                            Check
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Mark as checked?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This confirms the voucher details are correct.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("check")}>
                                Check
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {actionPermissions.approve && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button disabled={processing}>Approve</Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Approve voucher?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This approves the voucher for completion.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("approve")}>
                                Approve
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {actionPermissions.complete && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button disabled={processing}>
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Complete
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Complete voucher?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This applies changes to tyre inventory. This cannot be undone.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("complete")}>
                                Complete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {actionPermissions.reject && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="destructive" disabled={processing}>
                            <XCircle className="mr-2 h-4 w-4" />
                            Reject
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <form onSubmit={handleReject}>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Reject voucher</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Provide a reason for rejection.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <div className="py-4 space-y-2">
                                <Label htmlFor="reject_reason">Reason</Label>
                                <Textarea
                                    id="reject_reason"
                                    required
                                    value={rejectReason}
                                    onChange={(e) => setRejectReason(e.target.value)}
                                />
                            </div>
                            <AlertDialogFooter>
                                <AlertDialogCancel type="button">Cancel</AlertDialogCancel>
                                <AlertDialogAction type="submit" disabled={!rejectReason.trim()}>
                                    Reject
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </form>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {actionPermissions.cancel && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="outline" disabled={processing}>
                            <XCircle className="mr-2 h-4 w-4" />
                            Void
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                postAction("cancel", { reason: voidReason });
                            }}
                        >
                            <AlertDialogHeader>
                                <AlertDialogTitle>Void voucher</AlertDialogTitle>
                                <AlertDialogDescription>
                                    This ends the voucher workflow. A voided voucher is retained for audit and cannot be reopened.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <div className="space-y-2 py-4">
                                <Label htmlFor="void_reason">Reason</Label>
                                <Textarea
                                    id="void_reason"
                                    required
                                    value={voidReason}
                                    onChange={(event) => setVoidReason(event.target.value)}
                                />
                            </div>
                            <AlertDialogFooter>
                                <AlertDialogCancel type="button">Keep voucher</AlertDialogCancel>
                                <AlertDialogAction type="submit" disabled={!voidReason.trim() || processing}>
                                    Void voucher
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </form>
                    </AlertDialogContent>
                </AlertDialog>
            )}
        </div>
    );
}
