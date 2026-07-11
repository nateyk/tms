import { useForm, router } from "@inertiajs/react";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { TyreDisposalFormFields } from "@/components/tyres/tyre-disposal-form-fields";

type TyreOption = { id: number; tyre_code: string; status_label: string };
type Option = { value: string; label: string };

type TyreDisposalDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    tyreId?: number | null;
    tyres: TyreOption[];
    disposalReasons: Option[];
    initialValues?: {
        tyre_id?: number | null;
        disposal_reason?: string;
        final_km_used?: number | null;
        final_condition?: string;
        estimated_scrap_value?: number | null;
        sold_amount?: number | null;
        notes?: string;
    };
};

export function TyreDisposalDialog({
    open,
    onOpenChange,
    tyreId,
    tyres,
    disposalReasons,
    initialValues,
}: TyreDisposalDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tyre_id: initialValues?.tyre_id ?? tyreId ?? null,
        disposal_reason: initialValues?.disposal_reason ?? "",
        final_km_used: initialValues?.final_km_used ?? null,
        final_condition: initialValues?.final_condition ?? "",
        estimated_scrap_value: initialValues?.estimated_scrap_value ?? null,
        sold_amount: initialValues?.sold_amount ?? null,
        notes: initialValues?.notes ?? "",
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
        <AlertDialog open={open} onOpenChange={handleClose}>
            <AlertDialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <AlertDialogHeader>
                    <AlertDialogTitle>Tyre Disposal</AlertDialogTitle>
                    <AlertDialogDescription>
                        Record the disposal of a tyre from the fleet.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <form onSubmit={handleSubmit}>
                    <TyreDisposalFormFields
                        data={data}
                        setData={setData}
                        errors={errors}
                        tyres={tyres}
                        disposalReasons={disposalReasons}
                        readOnlyTyre={Boolean(tyreId)}
                    />
                    <AlertDialogFooter className="mt-6">
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
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>
    );
}
