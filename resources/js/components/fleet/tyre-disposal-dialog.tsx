import { useForm } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

type TyreDisposalDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    tyreId?: number | null;
    initialValues?: {
        tyre_id?: number | null;
        disposal_date?: string;
        disposal_reason?: string;
        disposal_notes?: string;
    };
};

export function TyreDisposalDialog({
    open,
    onOpenChange,
    tyreId,
    initialValues,
}: TyreDisposalDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tyre_id: initialValues?.tyre_id ?? tyreId ?? null,
        disposal_date: initialValues?.disposal_date ?? new Date().toISOString().split('T')[0],
        disposal_reason: initialValues?.disposal_reason ?? "",
        disposal_notes: initialValues?.disposal_notes ?? "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post("/tyres/disposals", {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const handleClose = () => {
        reset();
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Tyre Disposal</DialogTitle>
                    <DialogDescription>
                        Record the disposal of a tyre from the fleet.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="disposal_date">Disposal Date</Label>
                            <Input
                                id="disposal_date"
                                type="date"
                                value={data.disposal_date}
                                onChange={(e) => setData('disposal_date', e.target.value)}
                            />
                            {errors.disposal_date && (
                                <p className="text-sm text-destructive">{errors.disposal_date}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="disposal_reason">Disposal Reason</Label>
                            <Input
                                id="disposal_reason"
                                placeholder="e.g., End of life, Damage, etc."
                                value={data.disposal_reason}
                                onChange={(e) => setData('disposal_reason', e.target.value)}
                            />
                            {errors.disposal_reason && (
                                <p className="text-sm text-destructive">{errors.disposal_reason}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="disposal_notes">Notes</Label>
                            <Textarea
                                id="disposal_notes"
                                placeholder="Additional details about the disposal..."
                                value={data.disposal_notes}
                                onChange={(e) => setData('disposal_notes', e.target.value)}
                                rows={3}
                            />
                            {errors.disposal_notes && (
                                <p className="text-sm text-destructive">{errors.disposal_notes}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Saving..." : "Record Disposal"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
