import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
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
    attached_power_vehicle_id: number | null;
    attached_trailer_vehicle_id: number | null;
    notes: string;
};

type Option = { value: string; label: string };
type VehicleTypeOption = { id: number; name: string; asset_type: string; tyre_count: number | null; axle_count: number | null };
type LocationOption = { id: number; label: string };
type VehicleAttachOption = { id: number; label: string };

type VehicleFormFieldsProps = {
    errors: Partial<Record<string, string>>;
    data: VehicleFormData;
    setData: {
        <K extends keyof VehicleFormData>(key: K, value: VehicleFormData[K]): void;
        (data: VehicleFormData): void;
    };
    assetTypes: Option[];
    vehicleStatuses: Option[];
    vehicleTypes: VehicleTypeOption[];
    locations: LocationOption[];
    attachablePowerVehicles: VehicleAttachOption[];
    attachableTrailers: VehicleAttachOption[];
};

export function VehicleFormFields({
    errors,
    data,
    setData,
    assetTypes,
    vehicleStatuses,
    vehicleTypes,
    locations,
    attachablePowerVehicles,
    attachableTrailers,
}: VehicleFormFieldsProps) {
    const matchingVehicleTypes = vehicleTypes.filter((type) => type.asset_type === data.asset_type);
    const selectedVehicleType = vehicleTypes.find((type) => type.id === data.vehicle_type_id);
    const attachLabel = data.asset_type === "power_vehicle" ? "Attach trailer" : "Attach to power vehicle";
    const attachOptions = data.asset_type === "power_vehicle" ? attachableTrailers : attachablePowerVehicles;
    const attachField: "attached_trailer_vehicle_id" | "attached_power_vehicle_id" =
        data.asset_type === "power_vehicle" ? "attached_trailer_vehicle_id" : "attached_power_vehicle_id";
    const canAttach = data.asset_type === "power_vehicle" || data.asset_type === "trailer";
    const emptyAttachText =
        data.asset_type === "power_vehicle"
            ? "No free trailers found. Create a trailer first, or detach one from another power vehicle."
            : "No free power vehicles found. Create a power vehicle first, or detach this trailer's power unit.";

    const changeAssetType = (value: string) => {
        const firstMatchingType = vehicleTypes.find((type) => type.asset_type === value);

        setData({
            ...data,
            asset_type: value,
            vehicle_type_id: firstMatchingType?.id ?? null,
            attached_power_vehicle_id: null,
            attached_trailer_vehicle_id: null,
        });
    };

    const selectClassName =
        "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50";

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
                        <select
                            className={selectClassName}
                            value={data.asset_type}
                            onChange={(event) => changeAssetType(event.target.value)}
                        >
                            {assetTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.asset_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Vehicle type</Label>
                        <select
                            className={selectClassName}
                            value={data.vehicle_type_id ? String(data.vehicle_type_id) : ""}
                            onChange={(event) =>
                                setData("vehicle_type_id", event.target.value ? Number(event.target.value) : null)
                            }
                        >
                            <option value="">Select vehicle type</option>
                            {matchingVehicleTypes.map((type) => (
                                <option key={type.id} value={String(type.id)}>
                                    {type.name}
                                </option>
                            ))}
                        </select>
                        {selectedVehicleType ? (
                            <p className="text-xs text-muted-foreground">
                                Tyre positions: {selectedVehicleType.tyre_count ?? 0}
                                {selectedVehicleType.axle_count ? ` across ${selectedVehicleType.axle_count} axles` : ""}.
                                This controls where tyres can be mounted on the map.
                            </p>
                        ) : (
                            <p className="text-xs text-muted-foreground">
                                Choose the matching vehicle type so tyre positions are available.
                            </p>
                        )}
                        <InputError message={errors.vehicle_type_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Status</Label>
                        <select
                            className={selectClassName}
                            value={data.status}
                            onChange={(event) => setData("status", event.target.value)}
                        >
                            {vehicleStatuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Current location</Label>
                        <select
                            className={selectClassName}
                            value={
                                data.current_location_id
                                    ? String(data.current_location_id)
                                    : "none"
                            }
                            onChange={(event) =>
                                setData(
                                    "current_location_id",
                                    event.target.value === "none" ? null : Number(event.target.value),
                                )
                            }
                        >
                            <option value="none">None</option>
                            {locations.map((location) => (
                                <option key={location.id} value={String(location.id)}>
                                    {location.label}
                                </option>
                            ))}
                        </select>
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

            {canAttach && (
                <div className="rounded-md border bg-muted/20 p-4">
                    <div className="mb-4">
                        <h3 className="text-sm font-semibold">Vehicle attachment</h3>
                        <p className="text-xs text-muted-foreground">
                            Link a power vehicle and trailer into one active working unit.
                        </p>
                    </div>
                    <div className="grid gap-2 sm:max-w-md">
                        <Label>{attachLabel}</Label>
                        <select
                            className={selectClassName}
                            value={data[attachField] ? String(data[attachField]) : "none"}
                            onChange={(event) =>
                                setData(attachField, event.target.value === "none" ? null : Number(event.target.value))
                            }
                        >
                            <option value="none">Not attached</option>
                            {attachOptions.map((vehicle) => (
                                <option key={vehicle.id} value={String(vehicle.id)}>
                                    {vehicle.label}
                                </option>
                            ))}
                        </select>
                        {attachOptions.length === 0 ? (
                            <p className="text-xs text-muted-foreground">{emptyAttachText}</p>
                        ) : (
                            <p className="text-xs text-muted-foreground">
                                This only lists vehicles that are not already attached.
                            </p>
                        )}
                        <InputError message={errors[attachField]} />
                    </div>
                </div>
            )}

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
