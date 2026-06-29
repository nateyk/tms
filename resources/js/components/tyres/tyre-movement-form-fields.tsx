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
import { useEffect, useMemo, useState } from "react";

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
type PositionOption = { value: string; label: string };

type MovementFormData = {
    tyre_id: number | null;
    movement_date: string;
    to_location_type: string;
    to_location_id: number | null;
    to_position_code: string;
    from_odometer: number | null;
    to_odometer: number | null;
    reason: string;
    notes: string;
};

type TyreMovementFormFieldsProps = {
    data: MovementFormData;
    setData: <K extends keyof MovementFormData>(key: K, value: MovementFormData[K]) => void;
    errors: Partial<Record<keyof MovementFormData, string>>;
    tyres: TyreOption[];
    stores: LocationOption[];
    powerVehicles: LocationOption[];
    trailers: LocationOption[];
    destinationTypes: DestinationType[];
    readOnlyTyre?: boolean;
    sourceInfo?: {
        location_label: string;
        position_label: string;
        movement_type_label: string;
    };
};

const vehicleTypes = ["power_vehicle", "trailer"];
const fixedLocationTypes = ["maintenance_center", "disposal_yard"];

function isVehicleType(type: string): boolean {
    return vehicleTypes.includes(type);
}

export function TyreMovementFormFields({
    data,
    setData,
    errors,
    tyres,
    stores,
    powerVehicles,
    trailers,
    destinationTypes,
    readOnlyTyre = false,
    sourceInfo,
}: TyreMovementFormFieldsProps) {
    const [positionOptions, setPositionOptions] = useState<PositionOption[]>([]);
    const [loadingPositions, setLoadingPositions] = useState(false);

    const selectedTyre = useMemo(
        () => tyres.find((tyre) => tyre.id === data.tyre_id) ?? null,
        [tyres, data.tyre_id],
    );

    const destinationLocations = useMemo(() => {
        switch (data.to_location_type) {
            case "store":
                return stores;
            case "power_vehicle":
                return powerVehicles;
            case "trailer":
                return trailers;
            default:
                return [];
        }
    }, [data.to_location_type, stores, powerVehicles, trailers]);

    const derivedSource = sourceInfo ?? {
        location_label: selectedTyre?.source_label ?? "Select a tyre",
        position_label: selectedTyre?.current_position_code ?? "—",
        movement_type_label: "Auto after source and destination",
    };

    useEffect(() => {
        if (!isVehicleType(data.to_location_type) || !data.to_location_id) {
            setPositionOptions([]);
            return;
        }

        setLoadingPositions(true);
        fetch(route("tyres.movements.position-options", data.to_location_id))
            .then((response) => response.json())
            .then((options: PositionOption[]) => setPositionOptions(options))
            .finally(() => setLoadingPositions(false));
    }, [data.to_location_type, data.to_location_id]);

    const handleDestinationTypeChange = (value: string) => {
        setData("to_location_type", value);
        setData("to_position_code", "");
        if (fixedLocationTypes.includes(value)) {
            setData("to_location_id", 1);
        } else {
            setData("to_location_id", null);
        }
    };

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="tyre_id">Tyre</Label>
                    {readOnlyTyre ? (
                        <Input value={selectedTyre?.tyre_code ?? ""} disabled />
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
                    <Label htmlFor="movement_date">Movement date</Label>
                    <Input
                        id="movement_date"
                        type="date"
                        value={data.movement_date}
                        onChange={(e) => setData("movement_date", e.target.value)}
                    />
                    {errors.movement_date && (
                        <p className="text-sm text-destructive">{errors.movement_date}</p>
                    )}
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="rounded-lg border p-4 space-y-3">
                    <p className="text-sm font-medium">Source</p>
                    <dl className="grid gap-2 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Current location</dt>
                            <dd className="font-medium">{derivedSource.location_label}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Tyre position</dt>
                            <dd className="font-medium">{derivedSource.position_label || "—"}</dd>
                        </div>
                    </dl>
                    <div className="space-y-2">
                        <Label htmlFor="from_odometer">Odometer out</Label>
                        <Input
                            id="from_odometer"
                            type="number"
                            min={0}
                            value={data.from_odometer ?? ""}
                            onChange={(e) =>
                                setData(
                                    "from_odometer",
                                    e.target.value === "" ? null : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                </div>

                <div className="rounded-lg border p-4 space-y-3">
                    <p className="text-sm font-medium">Destination</p>
                    <div className="space-y-2">
                        <Label htmlFor="to_location_type">Destination type</Label>
                        <Select
                            value={data.to_location_type || undefined}
                            onValueChange={handleDestinationTypeChange}
                        >
                            <SelectTrigger id="to_location_type">
                                <SelectValue placeholder="Select destination type" />
                            </SelectTrigger>
                            <SelectContent>
                                {destinationTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.to_location_type && (
                            <p className="text-sm text-destructive">{errors.to_location_type}</p>
                        )}
                    </div>

                    {!fixedLocationTypes.includes(data.to_location_type) && (
                        <div className="space-y-2">
                            <Label htmlFor="to_location_id">Vehicle / store</Label>
                            <Select
                                value={data.to_location_id ? String(data.to_location_id) : undefined}
                                onValueChange={(value) => {
                                    setData("to_location_id", Number(value));
                                    setData("to_position_code", "");
                                }}
                            >
                                <SelectTrigger id="to_location_id">
                                    <SelectValue placeholder="Select destination" />
                                </SelectTrigger>
                                <SelectContent>
                                    {destinationLocations.map((location) => (
                                        <SelectItem key={location.id} value={String(location.id)}>
                                            {location.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.to_location_id && (
                                <p className="text-sm text-destructive">{errors.to_location_id}</p>
                            )}
                        </div>
                    )}

                    {isVehicleType(data.to_location_type) && (
                        <div className="space-y-2">
                            <Label htmlFor="to_position_code">Tyre position</Label>
                            <Select
                                value={data.to_position_code || undefined}
                                onValueChange={(value) => setData("to_position_code", value)}
                                disabled={loadingPositions || positionOptions.length === 0}
                            >
                                <SelectTrigger id="to_position_code">
                                    <SelectValue
                                        placeholder={
                                            loadingPositions
                                                ? "Loading positions..."
                                                : "Select position"
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {positionOptions.map((position) => (
                                        <SelectItem key={position.value} value={position.value}>
                                            {position.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.to_position_code && (
                                <p className="text-sm text-destructive">{errors.to_position_code}</p>
                            )}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="to_odometer">Odometer in</Label>
                        <Input
                            id="to_odometer"
                            type="number"
                            min={0}
                            value={data.to_odometer ?? ""}
                            onChange={(e) =>
                                setData(
                                    "to_odometer",
                                    e.target.value === "" ? null : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="reason">Reason</Label>
                    <Textarea
                        id="reason"
                        rows={3}
                        value={data.reason}
                        onChange={(e) => setData("reason", e.target.value)}
                    />
                    {errors.reason && (
                        <p className="text-sm text-destructive">{errors.reason}</p>
                    )}
                </div>
                <div className="space-y-2">
                    <Label htmlFor="notes">Internal notes</Label>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData("notes", e.target.value)}
                    />
                </div>
            </div>
        </div>
    );
}
