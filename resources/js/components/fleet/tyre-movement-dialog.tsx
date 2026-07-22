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
import { AlertTriangle, Loader2 } from "lucide-react";
import { useEffect, useState, type ComponentProps } from "react";

type MovementFieldsProps = ComponentProps<typeof TyreMovementFormFields>;
type MovementOptions = Pick<MovementFieldsProps, "tyres" | "stores" | "powerVehicles" | "trailers" | "destinationTypes" | "destinationTargets">;

type TyreMovementDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    tyreId?: number | null;
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
    const [options, setOptions] = useState<MovementOptions | null>(null);
    const [loadingOptions, setLoadingOptions] = useState(false);
    const [optionsError, setOptionsError] = useState(false);
    const initialSignature = JSON.stringify({ tyreId: tyreId ?? null, initialValues: initialValues ?? null });

    useEffect(() => {
        if (!open) {
            return;
        }

        // Inertia's key/value setter is asynchronous. A single update keeps the
        // prefilled tyre and destination together when the map opens this dialog.
        setData({
            tyre_id: initialValues?.tyre_id ?? tyreId ?? null,
            movement_date: initialValues?.movement_date ?? new Date().toISOString().split("T")[0],
            to_location_type: initialValues?.to_location_type ?? "",
            to_location_id: initialValues?.to_location_id ?? null,
            to_position_code: initialValues?.to_position_code ?? "",
            from_odometer: initialValues?.from_odometer ?? null,
            to_odometer: initialValues?.to_odometer ?? null,
            reason: initialValues?.reason ?? "",
            notes: initialValues?.notes ?? "",
        });
    }, [initialSignature, open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;
        setLoadingOptions(true);
        setOptionsError(false);

        fetch(route("tyres.movements.form-options"), { headers: { Accept: "application/json" } })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Unable to load movement options");
                }

                return response.json() as Promise<MovementOptions>;
            })
            .then((payload) => {
                if (!cancelled) {
                    setOptions(payload);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setOptionsError(true);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoadingOptions(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("tyres.movements.store"), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const handleClose = () => {
        reset();
        setOptions(null);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={(nextOpen) => nextOpen ? onOpenChange(true) : handleClose()}>
            <DialogContent className="max-h-[94vh] max-w-5xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{tyreId ? "Move tyre" : "Mount tyre"}</DialogTitle>
                    <DialogDescription>
                        Create a draft voucher from this position. The tyre changes location only after completion.
                    </DialogDescription>
                </DialogHeader>
                {loadingOptions && (
                    <div className="flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Loading movement options...
                    </div>
                )}
                {optionsError && (
                    <div className="flex items-center gap-2 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                        <AlertTriangle className="h-4 w-4" />
                        Movement options could not be loaded. Close and try again.
                    </div>
                )}
                {options && (
                    <form onSubmit={handleSubmit}>
                        <TyreMovementFormFields
                            data={data}
                            setData={setData}
                            errors={errors}
                            tyres={options.tyres}
                            stores={options.stores}
                            powerVehicles={options.powerVehicles}
                            trailers={options.trailers}
                            destinationTypes={options.destinationTypes}
                            destinationTargets={options.destinationTargets}
                            readOnlyTyre={Boolean(tyreId)}
                            compact
                        />
                        <DialogFooter className="mt-5 border-t pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleClose}
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Saving..." : "Save draft voucher"}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
