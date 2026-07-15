import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { useEffect, useMemo, useState, type ReactNode } from "react";

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
        position_label: selectedTyre?.current_position_code ?? "-",
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
                <Field label="Tyre" error={errors.tyre_id} className="sm:col-span-2">
                    {readOnlyTyre ? (
                        <Input value={selectedTyre?.tyre_code ?? ""} disabled />
                    ) : (
                        <Select
                            value={data.tyre_id ? String(data.tyre_id) : undefined}
                            onValueChange={(value) => setData("tyre_id", Number(value))}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select tyre" />
                            </SelectTrigger>
                            <SelectContent>
                                {tyres.map((tyre) => (
                                    <SelectItem key={tyre.id} value={String(tyre.id)}>
                                        {tyre.tyre_code} - {tyre.status_label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </Field>

                <Field label="Movement date" error={errors.movement_date}>
                    <Input
                        type="date"
                        value={data.movement_date}
                        onChange={(e) => setData("movement_date", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <section className="space-y-4 rounded-md border bg-muted/20 p-4">
                    <div>
                        <h3 className="text-sm font-semibold">From</h3>
                        <p className="text-xs text-muted-foreground">Current tyre location</p>
                    </div>
                    <dl className="grid gap-2 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Location</dt>
                            <dd className="font-medium">{derivedSource.location_label}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Position</dt>
                            <dd className="font-medium">{derivedSource.position_label || "-"}</dd>
                        </div>
                    </dl>
                    <Field label="Odometer out" error={errors.from_odometer}>
                        <Input
                            type="number"
                            min={0}
                            value={data.from_odometer ?? ""}
                            onChange={(e) =>
                                setData("from_odometer", e.target.value === "" ? null : Number(e.target.value))
                            }
                        />
                    </Field>
                </section>

                <section className="space-y-4 rounded-md border bg-muted/20 p-4">
                    <div>
                        <h3 className="text-sm font-semibold">To</h3>
                        <p className="text-xs text-muted-foreground">New tyre location</p>
                    </div>
                    <Field label="Destination type" error={errors.to_location_type}>
                        <Select
                            value={data.to_location_type || undefined}
                            onValueChange={handleDestinationTypeChange}
                        >
                            <SelectTrigger>
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
                    </Field>

                    {!fixedLocationTypes.includes(data.to_location_type) && (
                        <Field label="Destination" error={errors.to_location_id}>
                            <Select
                                value={data.to_location_id ? String(data.to_location_id) : undefined}
                                onValueChange={(value) => {
                                    setData("to_location_id", Number(value));
                                    setData("to_position_code", "");
                                }}
                            >
                                <SelectTrigger>
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
                        </Field>
                    )}

                    {isVehicleType(data.to_location_type) && (
                        <Field label="Position" error={errors.to_position_code}>
                            <Select
                                value={data.to_position_code || undefined}
                                onValueChange={(value) => setData("to_position_code", value)}
                                disabled={loadingPositions || positionOptions.length === 0}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={loadingPositions ? "Loading positions..." : "Select position"} />
                                </SelectTrigger>
                                <SelectContent>
                                    {positionOptions.map((position) => (
                                        <SelectItem key={position.value} value={position.value}>
                                            {position.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}

                    <Field label="Odometer in" error={errors.to_odometer}>
                        <Input
                            type="number"
                            min={0}
                            value={data.to_odometer ?? ""}
                            onChange={(e) =>
                                setData("to_odometer", e.target.value === "" ? null : Number(e.target.value))
                            }
                        />
                    </Field>
                </section>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Reason" error={errors.reason}>
                    <Textarea
                        rows={3}
                        value={data.reason}
                        onChange={(e) => setData("reason", e.target.value)}
                    />
                </Field>
                <Field label="Internal notes" error={errors.notes}>
                    <Textarea
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData("notes", e.target.value)}
                    />
                </Field>
            </div>
        </div>
    );
}

function Field({
    label,
    error,
    children,
    className = "",
}: {
    label: string;
    error?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={`space-y-2 ${className}`}>
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
