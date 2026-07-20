import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useForm } from "@inertiajs/react";
import { Info } from "lucide-react";

type MovementCompletionDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    movement: {
        id: number;
        movement_no: string;
        tyre_code: string | null;
        from_location_display: string;
        to_location_display: string;
        requires_source_odometer: boolean;
        requires_destination_odometer: boolean;
        source_odometer_label: string;
        destination_odometer_label: string;
        from_odometer: number | null;
        to_odometer: number | null;
    } | null;
};

export function MovementCompletionDialog({
    open,
    onOpenChange,
    movement,
}: MovementCompletionDialogProps) {
    const { post, processing, reset } = useForm({});

    if (!movement) {
        return null;
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("tyres.movements.complete", movement!.id), {
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
        });
    };

    const handleClose = () => {
        onOpenChange(false);
        reset();
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent className="max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle>Complete Movement with Odometer</AlertDialogTitle>
                    <AlertDialogDescription>
                        Review the odometer readings saved on this voucher, then apply them to the vehicle and tyre record.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Movement:</span>
                            <span className="font-medium">{movement!.movement_no}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Tyre:</span>
                            <span className="font-medium">{movement!.tyre_code}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">From:</span>
                            <span className="font-medium">{movement!.from_location_display}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">To:</span>
                            <span className="font-medium">{movement!.to_location_display}</span>
                        </div>
                    </div>

                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            Voucher odometer readings are locked after approval. Completion applies these saved values to vehicle KM and tyre usage.
                        </AlertDescription>
                    </Alert>

                    {movement!.requires_source_odometer && (
                        <OdometerReview label="Odometer out" location={movement!.source_odometer_label} value={movement!.from_odometer} />
                    )}

                    {movement!.requires_destination_odometer && (
                        <OdometerReview label="Odometer in" location={movement!.destination_odometer_label} value={movement!.to_odometer} />
                    )}

                    <AlertDialogFooter>
                        <AlertDialogCancel type="button" onClick={handleClose}>
                            Cancel
                        </AlertDialogCancel>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Completing..." : "Complete Movement"}
                        </Button>
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>
    );
}

function OdometerReview({ label, location, value }: { label: string; location: string; value: number | null }) {
    return (
        <div className="rounded-md border bg-muted/20 px-3 py-2.5">
            <p className="text-xs text-muted-foreground">{label} - {location}</p>
            <p className="mt-1 text-lg font-semibold tabular-nums">
                {value === null ? "Not recorded" : `${value.toLocaleString()} KM`}
            </p>
        </div>
    );
}
