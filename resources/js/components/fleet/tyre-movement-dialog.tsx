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
import { TyreMovementFormFields } from "@/components/tyres/tyre-movement-form-fields";

type TyreOption = {
    id: number;
    tyre_code: string;
    serial_number: string;
    status_label: string;
    current_location_type: string | null;
    current_location_id: number | null;
    current_position_code: string | null;
    source_label: string;
};

type LocationOption = { id: number; label: string };
type DestinationType = { value: string; label: string };

type TyreMovementDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    tyreId?: number | null;
    tyres: TyreOption[];
    stores: LocationOption[];
    powerVehicles: LocationOption[];
    trailers: LocationOption[];
    destinationTypes: DestinationType[];
    initialValues?: {
        tyre_id?: number | null;
        movement_date?: string;
        to_location_type?: string;
        to_location_id?: number | null;
        to_position_code?: string;
        from_odometer?: number | null;
        to_odometer?: number | null;
        reason?: string;
        notes?: string;
    };
};

export function TyreMovementDialog({
    open,
    onOpenChange,
    tyreId,
    tyres,
    stores,
    powerVehicles,
    trailers,
    destinationTypes,
    initialValues,
}: TyreMovementDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tyre_id: initialValues?.tyre_id ?? tyreId ?? null,
        movement_date: initialValues?.movement_date ?? new Date().toISOString().split('T')[0],
        to_location_type: initialValues?.to_location_type ?? "",
        to_location_id: initialValues?.to_location_id ?? null,
        to_position_code: initialValues?.to_position_code ?? "",
        from_odometer: initialValues?.from_odometer ?? null,
        to_odometer: initialValues?.to_odometer ?? null,
        reason: initialValues?.reason ?? "",
        notes: initialValues?.notes ?? "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post("/tyres/movements", {
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
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Tyre Movement</DialogTitle>
                    <DialogDescription>
                        Record the movement of a tyre to a new location or position.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <TyreMovementFormFields
                        data={data}
                        setData={setData}
                        errors={errors}
                        tyres={tyres}
                        stores={stores}
                        powerVehicles={powerVehicles}
                        trailers={trailers}
                        destinationTypes={destinationTypes}
                        readOnlyTyre={Boolean(tyreId)}
                    />
                    <DialogFooter className="mt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Saving..." : "Save Movement"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
