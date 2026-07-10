import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertCircle } from "lucide-react";

type VehicleOdometerFormProps = {
    data: {
        odometer?: number | string;
        notes?: string;
    };
    errors: Record<string, string>;
    currentOdometer: number | null;
    onDataChange: (field: string, value: string | number) => void;
};

export function VehicleOdometerForm({
    data,
    errors,
    currentOdometer,
    onDataChange,
}: VehicleOdometerFormProps) {
    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="odometer">Current Odometer (KM) *</Label>
                <Input
                    id="odometer"
                    type="number"
                    min="0"
                    step="1"
                    value={data.odometer || ""}
                    onChange={(e) => onDataChange("odometer", Number(e.target.value))}
                    placeholder="Enter current odometer reading"
                    className={errors.odometer ? "border-destructive" : ""}
                />
                {errors.odometer && (
                    <p className="text-sm text-destructive">{errors.odometer}</p>
                )}
                {currentOdometer !== null && (
                    <p className="text-xs text-muted-foreground">
                        Latest known odometer: {currentOdometer.toLocaleString()} KM
                    </p>
                )}
            </div>

            {currentOdometer !== null && data.odometer && Number(data.odometer) < currentOdometer && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                        Odometer reading cannot be lower than the latest known odometer ({currentOdometer.toLocaleString()} KM).
                    </AlertDescription>
                </Alert>
            )}

            <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={data.notes || ""}
                    onChange={(e) => onDataChange("notes", e.target.value)}
                    placeholder="Add any notes about this odometer reading..."
                    rows={3}
                />
            </div>
        </div>
    );
}
