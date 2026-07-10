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
    movementId: number;
    can: VoucherPermissions;
    pdfUrl?: string;
};

export function VoucherWorkflowActions({ movementId, can, pdfUrl }: VoucherWorkflowActionsProps) {
    const [rejectReason, setRejectReason] = useState("");
    const [processing, setProcessing] = useState(false);

    const postAction = (routeName: string, payload: Record<string, string> = {}) => {
        setProcessing(true);
        router.post(route(routeName, movementId), payload, {
            onFinish: () => setProcessing(false),
        });
    };

    const handleReject = (event: FormEvent) => {
        event.preventDefault();
        postAction("tyres.movements.reject", { reason: rejectReason });
    };

    return (
        <div className="flex flex-wrap gap-2">
            {pdfUrl && (
                <Button variant="outline" asChild>
                    <a href={pdfUrl} target="_blank" rel="noreferrer">
                        <FileDown className="mr-2 h-4 w-4" />
                        PDF
                    </a>
                </Button>
            )}

            {can.submit && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="secondary" disabled={processing}>
                            <Send className="mr-2 h-4 w-4" />
                            Submit
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Submit movement?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This sends the voucher to a checker for review.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("tyres.movements.submit")}>
                                Submit
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {can.check && (
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
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("tyres.movements.check")}>
                                Check
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {can.approve && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button disabled={processing}>Approve</Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Approve movement?</AlertDialogTitle>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("tyres.movements.approve")}>
                                Approve
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {can.complete && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button disabled={processing}>
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Complete
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Complete movement?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This applies changes to tyre inventory. This cannot be undone.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("tyres.movements.complete")}>
                                Complete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            {can.reject && (
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
                                <AlertDialogTitle>Reject movement</AlertDialogTitle>
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

            {can.cancel && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="outline" disabled={processing}>
                            Cancel draft
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Cancel draft?</AlertDialogTitle>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Keep draft</AlertDialogCancel>
                            <AlertDialogAction onClick={() => postAction("tyres.movements.cancel")}>
                                Cancel draft
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}
        </div>
    );
}
