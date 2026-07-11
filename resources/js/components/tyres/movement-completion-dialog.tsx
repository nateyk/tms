import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useForm } from "@inertiajs/react";
import { Info } from "lucide-react";

type MovementCompletionDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    movement: {
        id: number;
        movement_no: string;
        tyre_code: string;
        from_location_display: string;
        to_location_display: string;
        requires_source_odometer: boolean;
        requires_destination_odometer: boolean;
        source_odometer_label: string;
        destination_odometer_label: string;
        source_vehicle_latest_odometer: number | null;
        destination_vehicle_latest_odometer: number | null;
    } | null;
};

export function MovementCompletionDialog({
    open,
    onOpenChange,
    movement,
}: MovementCompletionDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        from_odometer: "",
        to_odometer: "",
    });

    if (!movement) {
        return null;
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("tyres.movements.complete-with-odometer", movement!.id), {
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
                        Capture odometer readings at physical completion time for tyre KM usage calculation.
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
                            Odometer is captured at physical completion time and will be used for tyre KM usage calculation.
                        </AlertDescription>
                    </Alert>

                    {movement!.requires_source_odometer && (
                        <div className="space-y-2">
                            <Label htmlFor="from_odometer">
                                Source Odometer (KM) - {movement!.source_odometer_label}
                            </Label>
                            <Input
                                id="from_odometer"
                                type="number"
                                min="0"
                                step="1"
                                value={data.from_odometer}
                                onChange={(e) => setData("from_odometer", e.target.value)}
                                placeholder="Enter source odometer"
                                className={errors.from_odometer ? "border-destructive" : ""}
                            />
                            {errors.from_odometer && (
                                <p className="text-sm text-destructive">{errors.from_odometer}</p>
                            )}
                            {movement!.source_vehicle_latest_odometer !== null && (
                                <p className="text-xs text-muted-foreground">
                                    Latest known odometer: {movement!.source_vehicle_latest_odometer.toLocaleString()} KM
                                </p>
                            )}
                        </div>
                    )}

                    {movement!.requires_destination_odometer && (
                        <div className="space-y-2">
                            <Label htmlFor="to_odometer">
                                Destination Odometer (KM) - {movement!.destination_odometer_label}
                            </Label>
                            <Input
                                id="to_odometer"
                                type="number"
                                min="0"
                                step="1"
                                value={data.to_odometer}
                                onChange={(e) => setData("to_odometer", e.target.value)}
                                placeholder="Enter destination odometer"
                                className={errors.to_odometer ? "border-destructive" : ""}
                            />
                            {errors.to_odometer && (
                                <p className="text-sm text-destructive">{errors.to_odometer}</p>
                            )}
                            {movement!.destination_vehicle_latest_odometer !== null && (
                                <p className="text-xs text-muted-foreground">
                                    Latest known odometer: {movement!.destination_vehicle_latest_odometer.toLocaleString()} KM
                                </p>
                            )}
                        </div>
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
