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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useEffect } from "react";

type TyreDisposalDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    tyreId?: number | null;
    initialValues?: {
        tyre_id?: number | null;
        disposal_reason?: string;
        final_condition?: string;
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
        disposal_reason: initialValues?.disposal_reason ?? "",
        final_condition: initialValues?.final_condition ?? "",
        disposal_notes: initialValues?.disposal_notes ?? "",
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        setData({
            tyre_id: initialValues?.tyre_id ?? tyreId ?? null,
            disposal_reason: initialValues?.disposal_reason ?? "",
            final_condition: initialValues?.final_condition ?? "",
            disposal_notes: initialValues?.disposal_notes ?? "",
        });
    }, [initialValues, open, setData, tyreId]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("tyres.disposals.store"), {
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
                            <Label htmlFor="disposal_reason">Disposal reason</Label>
                            <Select
                                value={data.disposal_reason}
                                onValueChange={(value) => setData("disposal_reason", value)}
                            >
                                <SelectTrigger id="disposal_reason">
                                    <SelectValue placeholder="Choose reason" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="worn_out">Worn out</SelectItem>
                                    <SelectItem value="full_damage">Full damage</SelectItem>
                                    <SelectItem value="scrap">Scrap</SelectItem>
                                    <SelectItem value="sold">Sold</SelectItem>
                                    <SelectItem value="lost">Lost</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.disposal_reason && (
                                <p className="text-sm text-destructive">{errors.disposal_reason}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="final_condition">Final condition</Label>
                            <Input
                                id="final_condition"
                                placeholder="Example: sidewall damage"
                                value={data.final_condition}
                                onChange={(e) => setData("final_condition", e.target.value)}
                            />
                            {errors.final_condition && (
                                <p className="text-sm text-destructive">{errors.final_condition}</p>
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
