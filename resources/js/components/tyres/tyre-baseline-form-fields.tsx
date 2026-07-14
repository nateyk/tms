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

type TyreOption = {
    id: number;
    tyre_code: string;
    serial_number: string;
    current_location_type: string | null;
    current_location_id: number | null;
    current_position_code: string | null;
    location_display: string;
    current_vehicle_odometer?: number | null;
};

type BaselineFormFieldsProps = {
    data: {
        tyre_id?: number | string;
        baseline_location_type?: string | null;
        baseline_location_id?: number | string | null;
        baseline_position_code?: string | null;
        baseline_percentage?: number | string;
        expected_life_km?: number | string;
        baseline_odometer?: number | string;
        baseline_date?: string;
        notes?: string;
    };
    errors: Record<string, string>;
    tyres: TyreOption[];
    prefilled?: {
        id: number;
        tyre_code: string;
        current_location_type: string | null;
        current_location_id: number | null;
        current_position_code: string | null;
        location_display: string;
        current_vehicle_odometer?: number | null;
    } | null;
    onDataChange: (field: string, value: string | number | null) => void;
};

export function TyreBaselineFormFields({
    data,
    errors,
    tyres,
    prefilled,
    onDataChange,
}: BaselineFormFieldsProps) {
    const selectedTyre = prefilled || tyres.find((t) => t.id === Number(data.tyre_id));
    const isMounted = selectedTyre?.current_location_type && selectedTyre.current_location_type !== "store";

    const setSelectedTyre = (tyreId: number) => {
        const tyre = tyres.find((item) => item.id === tyreId);

        onDataChange("tyre_id", tyreId);
        onDataChange("baseline_location_type", tyre?.current_location_type ?? null);
        onDataChange("baseline_location_id", tyre?.current_location_id ?? null);
        onDataChange("baseline_position_code", tyre?.current_position_code ?? null);

        if (tyre?.current_vehicle_odometer !== null && tyre?.current_vehicle_odometer !== undefined) {
            onDataChange("baseline_odometer", tyre.current_vehicle_odometer);
        }
    };

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="tyre_id">Tyre *</Label>
                <Select
                    value={data.tyre_id ? String(data.tyre_id) : ""}
                    onValueChange={(value) => setSelectedTyre(Number(value))}
                    disabled={!!prefilled}
                >
                    <SelectTrigger id="tyre_id" className={errors.tyre_id ? "border-destructive" : ""}>
                        <SelectValue placeholder="Select a tyre" />
                    </SelectTrigger>
                    <SelectContent>
                        {tyres.map((tyre) => (
                            <SelectItem key={tyre.id} value={String(tyre.id)}>
                                {tyre.tyre_code} - {tyre.serial_number} ({tyre.location_display})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.tyre_id && (
                    <p className="text-sm text-destructive">{errors.tyre_id}</p>
                )}
            </div>

            {selectedTyre && (
                <div className="space-y-2 p-4 bg-muted rounded-md">
                    <p className="text-sm font-medium">Current Location</p>
                    <p className="text-sm text-muted-foreground">{selectedTyre.location_display}</p>
                    {selectedTyre.current_position_code && (
                        <p className="text-sm text-muted-foreground">
                            Position: {selectedTyre.current_position_code}
                        </p>
                    )}
                    {selectedTyre.current_vehicle_odometer !== null && selectedTyre.current_vehicle_odometer !== undefined && (
                        <p className="text-sm text-muted-foreground">
                            Latest vehicle KM: {selectedTyre.current_vehicle_odometer.toLocaleString()} KM
                        </p>
                    )}
                </div>
            )}

            {isMounted && (
                <div className="space-y-2">
                    <Label htmlFor="baseline_odometer">Baseline Odometer (KM)</Label>
                    <Input
                        id="baseline_odometer"
                        type="number"
                        min="0"
                        step="1"
                        value={data.baseline_odometer || ""}
                        onChange={(e) => onDataChange("baseline_odometer", e.target.value === "" ? null : Number(e.target.value))}
                        placeholder="Enter current odometer reading"
                        className={errors.baseline_odometer ? "border-destructive" : ""}
                    />
                    {errors.baseline_odometer && (
                        <p className="text-sm text-destructive">{errors.baseline_odometer}</p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Required for tyres mounted on running vehicle positions. Spare positions can be baselined without accumulating running KM.
                    </p>
                </div>
            )}

            <div className="space-y-2">
                <Label htmlFor="baseline_percentage">Baseline Percentage *</Label>
                <Input
                    id="baseline_percentage"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value={data.baseline_percentage || "100"}
                    onChange={(e) => onDataChange("baseline_percentage", Number(e.target.value))}
                    placeholder="Enter baseline percentage (0-100)"
                    className={errors.baseline_percentage ? "border-destructive" : ""}
                />
                {errors.baseline_percentage && (
                    <p className="text-sm text-destructive">{errors.baseline_percentage}</p>
                )}
                <p className="text-xs text-muted-foreground">
                    Percentage of tyre life remaining at baseline (default: 100%)
                </p>
            </div>

            <div className="space-y-2">
                <Label htmlFor="expected_life_km">Expected Life KM *</Label>
                <Input
                    id="expected_life_km"
                    type="number"
                    min="1"
                    step="1"
                    value={data.expected_life_km || "100000"}
                    onChange={(e) => onDataChange("expected_life_km", Number(e.target.value))}
                    placeholder="Enter expected life in KM"
                    className={errors.expected_life_km ? "border-destructive" : ""}
                />
                {errors.expected_life_km && (
                    <p className="text-sm text-destructive">{errors.expected_life_km}</p>
                )}
                <p className="text-xs text-muted-foreground">
                    Total expected KM usage for this tyre (default: 100,000 KM)
                </p>
            </div>

            <div className="space-y-2">
                <Label htmlFor="baseline_date">Baseline Date *</Label>
                <Input
                    id="baseline_date"
                    type="date"
                    value={data.baseline_date || new Date().toISOString().split('T')[0]}
                    onChange={(e) => onDataChange("baseline_date", e.target.value)}
                    className={errors.baseline_date ? "border-destructive" : ""}
                />
                {errors.baseline_date && (
                    <p className="text-sm text-destructive">{errors.baseline_date}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={data.notes || ""}
                    onChange={(e) => onDataChange("notes", e.target.value)}
                    placeholder="Add any additional notes about this baseline..."
                    rows={3}
                />
            </div>
        </div>
    );
}
