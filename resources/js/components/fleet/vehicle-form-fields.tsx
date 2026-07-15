import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";

export type VehicleFormData = {
    vehicle_code: string;
    plate_number: string;
    chassis_number: string;
    engine_number: string;
    asset_type: string;
    vehicle_type_id: number | null;
    status: string;
    current_location_id: number | null;
    manufacture_year: number | null;
    odometer: number | null;
    notes: string;
};

type Option = { value: string; label: string };
type VehicleTypeOption = { id: number; name: string; asset_type: string };
type LocationOption = { id: number; label: string };

type VehicleFormFieldsProps = {
    errors: Partial<Record<string, string>>;
    data: VehicleFormData;
    setData: <K extends keyof VehicleFormData>(key: K, value: VehicleFormData[K]) => void;
    assetTypes: Option[];
    vehicleStatuses: Option[];
    vehicleTypes: VehicleTypeOption[];
    locations: LocationOption[];
};

export function VehicleFormFields({
    errors,
    data,
    setData,
    assetTypes,
    vehicleStatuses,
    vehicleTypes,
    locations,
}: VehicleFormFieldsProps) {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="mb-4 text-sm font-semibold">Asset identity</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 rounded-md border bg-muted/20 p-3">
                        <div className="flex items-center justify-between gap-2">
                            <Label>Vehicle code</Label>
                            <Badge variant="secondary">Auto</Badge>
                        </div>
                        <p className="text-sm font-medium">
                            {data.vehicle_code || "Generated after save"}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            The system creates a unique code from the asset type.
                        </p>
                        <InputError message={errors.vehicle_code} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="plate_number">Plate number</Label>
                        <Input
                            id="plate_number"
                            value={data.plate_number}
                            onChange={(e) => setData("plate_number", e.target.value)}
                        />
                        <InputError message={errors.plate_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="chassis_number">Chassis number</Label>
                        <Input
                            id="chassis_number"
                            value={data.chassis_number}
                            onChange={(e) => setData("chassis_number", e.target.value)}
                        />
                        <InputError message={errors.chassis_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="engine_number">Engine number</Label>
                        <Input
                            id="engine_number"
                            value={data.engine_number}
                            onChange={(e) => setData("engine_number", e.target.value)}
                        />
                        <InputError message={errors.engine_number} />
                    </div>
                </div>
            </div>

            <div>
                <h3 className="mb-4 text-sm font-semibold">Asset setup</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label>Asset type</Label>
                        <Select
                            value={data.asset_type}
                            onValueChange={(value) => setData("asset_type", value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                {assetTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.asset_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Vehicle type</Label>
                        <Select
                            value={data.vehicle_type_id ? String(data.vehicle_type_id) : ""}
                            onValueChange={(value) =>
                                setData("vehicle_type_id", value ? Number(value) : null)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select vehicle type" />
                            </SelectTrigger>
                            <SelectContent>
                                {vehicleTypes.map((type) => (
                                    <SelectItem key={type.id} value={String(type.id)}>
                                        {type.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.vehicle_type_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData("status", value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                {vehicleStatuses.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Current location</Label>
                        <Select
                            value={
                                data.current_location_id
                                    ? String(data.current_location_id)
                                    : "none"
                            }
                            onValueChange={(value) =>
                                setData(
                                    "current_location_id",
                                    value === "none" ? null : Number(value),
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {locations.map((location) => (
                                    <SelectItem key={location.id} value={String(location.id)}>
                                        {location.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.current_location_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="manufacture_year">Manufacture year</Label>
                        <Input
                            id="manufacture_year"
                            type="number"
                            min={1980}
                            value={data.manufacture_year ?? ""}
                            onChange={(e) =>
                                setData(
                                    "manufacture_year",
                                    e.target.value ? Number(e.target.value) : null,
                                )
                            }
                        />
                        <InputError message={errors.manufacture_year} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="odometer">Odometer (km)</Label>
                        <Input
                            id="odometer"
                            type="number"
                            min={0}
                            value={data.odometer ?? ""}
                            onChange={(e) =>
                                setData(
                                    "odometer",
                                    e.target.value ? Number(e.target.value) : null,
                                )
                            }
                        />
                        <InputError message={errors.odometer} />
                    </div>
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData("notes", e.target.value)}
                    rows={3}
                />
                <InputError message={errors.notes} />
            </div>
        </div>
    );
}
