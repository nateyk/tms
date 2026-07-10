import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";

type TyreOption = { id: number; tyre_code: string; status_label: string };
type Option = { value: string; label: string };

type DisposalFormData = {
    tyre_id: number | null;
    disposal_reason: string;
    final_km_used: number | null;
    final_condition: string;
    estimated_scrap_value: number | null;
    sold_amount: number | null;
    notes: string;
};

type TyreDisposalFormFieldsProps = {
    data: DisposalFormData;
    setData: <K extends keyof DisposalFormData>(key: K, value: DisposalFormData[K]) => void;
    errors: Partial<Record<keyof DisposalFormData, string>>;
    tyres: TyreOption[];
    disposalReasons: Option[];
    readOnlyTyre?: boolean;
};

export function TyreDisposalFormFields({
    data,
    setData,
    errors,
    tyres,
    disposalReasons,
    readOnlyTyre = false,
}: TyreDisposalFormFieldsProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="tyre_id">Tyre</Label>
                    {readOnlyTyre ? (
                        <Input
                            value={tyres.find((t) => t.id === data.tyre_id)?.tyre_code ?? ""}
                            disabled
                        />
                    ) : (
                        <Select
                            value={data.tyre_id ? String(data.tyre_id) : undefined}
                            onValueChange={(value) => setData("tyre_id", Number(value))}
                        >
                            <SelectTrigger id="tyre_id">
                                <SelectValue placeholder="Select tyre" />
                            </SelectTrigger>
                            <SelectContent>
                                {tyres.map((tyre) => (
                                    <SelectItem key={tyre.id} value={String(tyre.id)}>
                                        {tyre.tyre_code} · {tyre.status_label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {errors.tyre_id && (
                        <p className="text-sm text-destructive">{errors.tyre_id}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="disposal_reason">Disposal reason</Label>
                    <Select
                        value={data.disposal_reason || undefined}
                        onValueChange={(value) => setData("disposal_reason", value)}
                    >
                        <SelectTrigger id="disposal_reason">
                            <SelectValue placeholder="Select reason" />
                        </SelectTrigger>
                        <SelectContent>
                            {disposalReasons.map((reason) => (
                                <SelectItem key={reason.value} value={reason.value}>
                                    {reason.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.disposal_reason && (
                        <p className="text-sm text-destructive">{errors.disposal_reason}</p>
                    )}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="final_km_used">Final KM used</Label>
                    <Input
                        id="final_km_used"
                        type="number"
                        min={0}
                        value={data.final_km_used ?? ""}
                        onChange={(e) =>
                            setData(
                                "final_km_used",
                                e.target.value === "" ? null : Number(e.target.value),
                            )
                        }
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="final_condition">Final condition</Label>
                    <Input
                        id="final_condition"
                        value={data.final_condition}
                        onChange={(e) => setData("final_condition", e.target.value)}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="estimated_scrap_value">Estimated scrap value (ETB)</Label>
                    <Input
                        id="estimated_scrap_value"
                        type="number"
                        min={0}
                        step="0.01"
                        value={data.estimated_scrap_value ?? ""}
                        onChange={(e) =>
                            setData(
                                "estimated_scrap_value",
                                e.target.value === "" ? null : Number(e.target.value),
                            )
                        }
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="sold_amount">Sold amount (ETB)</Label>
                    <Input
                        id="sold_amount"
                        type="number"
                        min={0}
                        step="0.01"
                        value={data.sold_amount ?? ""}
                        onChange={(e) =>
                            setData(
                                "sold_amount",
                                e.target.value === "" ? null : Number(e.target.value),
                            )
                        }
                    />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    rows={3}
                    value={data.notes}
                    onChange={(e) => setData("notes", e.target.value)}
                />
            </div>
        </div>
    );
}
