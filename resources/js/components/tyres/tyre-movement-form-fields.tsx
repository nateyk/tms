import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";
import { AlertTriangle, CheckCircle2, ChevronDown, Circle, MapPin, Search } from "lucide-react";
import { useEffect, useMemo, useState, type ReactNode } from "react";

type TyreOption = {
    id: number;
    tyre_code: string;
    serial_number: string | null;
    brand?: string | null;
    size?: string | null;
    status?: string;
    status_label: string;
    current_location_type: string | null;
    current_location_id: number | null;
    current_position_code: string | null;
    source_label: string;
    source_position_label?: string;
    position_type?: "running" | "spare" | null;
    current_vehicle_odometer?: number | null;
    installed_odometer?: number | null;
    has_pending_movement?: boolean;
};

type LocationOption = { id: number; label: string };
type DestinationVehicleOption = LocationOption & {
    vehicle_code?: string;
    plate_number?: string | null;
    vehicle_type_name?: string | null;
    asset_type?: string;
    current_odometer?: number | null;
    mounted_count?: number;
    available_position_count?: number;
    status?: string;
    power_available_count?: number;
    trailer_available_count?: number;
    total_available_count?: number;
    attached_trailer?: {
        id: number;
        vehicle_code?: string;
        plate_number?: string | null;
        label: string;
        vehicle_type_name?: string | null;
        current_odometer?: number | null;
        available_position_count?: number;
    } | null;
};
type DestinationType = { value: string; label: string };
type PositionOption = {
    value: string;
    owner_type: "power_vehicle" | "trailer";
    owner_vehicle_id: number;
    owner_vehicle_code: string;
    owner_label: string;
    owner_current_odometer?: number | null;
    code: string;
    display_code: string;
    label: string;
    type: "running" | "spare";
    is_empty: boolean;
    is_occupied: boolean;
    mounted_tyre_id: number | null;
    mounted_tyre_code: string | null;
    disabled_reason: string | null;
    disabled?: boolean;
};

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

type MovementFormSetter = {
    <K extends keyof MovementFormData>(key: K, value: MovementFormData[K]): void;
    (data: MovementFormData): void;
};

type TyreMovementFormFieldsProps = {
    data: MovementFormData;
    setData: MovementFormSetter;
    errors: Partial<Record<keyof MovementFormData, string>>;
    tyres: TyreOption[];
    stores: LocationOption[];
    powerVehicles: DestinationVehicleOption[];
    trailers: DestinationVehicleOption[];
    destinationTypes: DestinationType[];
    destinationTargets?: DestinationType[];
    readOnlyTyre?: boolean;
    compact?: boolean;
    onTyreSelected?: (tyreId: number | null) => void;
    sourceInfo?: {
        location_label: string;
        position_label: string;
        movement_type_label: string;
    };
};

const vehicleTypes = ["power_vehicle", "trailer"];

function isVehicleType(type: string): boolean {
    return vehicleTypes.includes(type);
}

function formatKm(value: number | null | undefined): string {
    return value === null || value === undefined ? "Not recorded" : `${value.toLocaleString()} KM`;
}

function positionTypeLabel(type: "running" | "spare" | null | undefined): string {
    if (type === "spare") {
        return "Spare position";
    }

    if (type === "running") {
        return "Running position";
    }

    return "Not mounted";
}

function groupPositionOptions(options: PositionOption[]): Array<{ title: string; subtitle?: string; positions: PositionOption[] }> {
    const grouped = new Map<string, PositionOption[]>();

    options.forEach((position) => {
        const key = `${position.owner_type}:${position.owner_vehicle_id}`;
        grouped.set(key, [...(grouped.get(key) ?? []), position]);
    });

    return Array.from(grouped.values()).map((positions) => {
        const owner = positions[0];
        const isTrailer = owner.owner_type === "trailer";

        return {
            title: isTrailer ? `Attached Trailer - ${owner.owner_label}` : `Power Unit - ${owner.owner_label}`,
            subtitle: isTrailer ? "Positions on the attached trailer" : "Positions on the selected power unit",
            positions,
        };
    });
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
    destinationTargets,
    readOnlyTyre = false,
    compact = false,
    onTyreSelected,
    sourceInfo,
}: TyreMovementFormFieldsProps) {
    const [positionOptions, setPositionOptions] = useState<PositionOption[]>([]);
    const [tyreSearch, setTyreSearch] = useState("");
    const [tyrePickerOpen, setTyrePickerOpen] = useState(false);
    const [loadingPositions, setLoadingPositions] = useState(false);
    const [positionLoadError, setPositionLoadError] = useState(false);

    const updateData = (updates: Partial<MovementFormData>) => {
        setData({ ...data, ...updates });
    };

    const selectedTyre = useMemo(
        () => tyres.find((tyre) => Number(tyre.id) === Number(data.tyre_id)) ?? null,
        [data.tyre_id, tyres],
    );
    const filteredTyres = useMemo(() => {
        const query = tyreSearch.trim().toLowerCase();

        if (!query) {
            return tyres;
        }

        return tyres.filter((tyre) => [
            tyre.tyre_code,
            tyre.serial_number,
            tyre.source_label,
            tyre.source_position_label,
            tyre.current_position_code,
            tyre.status_label,
        ].filter(Boolean).some((value) => String(value).toLowerCase().includes(query)));
    }, [tyreSearch, tyres]);

    const availableTyres = useMemo(
        () => filteredTyres.filter((tyre) => tyre.current_location_type !== "power_vehicle" && tyre.current_location_type !== "trailer"),
        [filteredTyres],
    );
    const mountedTyres = useMemo(
        () => filteredTyres.filter((tyre) => tyre.current_location_type === "power_vehicle" || tyre.current_location_type === "trailer"),
        [filteredTyres],
    );

    const attachedTrailerIds = useMemo(
        () => new Set(powerVehicles.map((vehicle) => vehicle.attached_trailer?.id).filter((id): id is number => Boolean(id))),
        [powerVehicles],
    );
    const destinationUnits = useMemo(
        () => [
            ...powerVehicles,
            ...trailers.filter((trailer) => !attachedTrailerIds.has(trailer.id)),
        ],
        [attachedTrailerIds, powerVehicles, trailers],
    );

    const initialUnitId = useMemo(() => {
        if (data.to_location_type === "power_vehicle") {
            return data.to_location_id;
        }

        return powerVehicles.find((vehicle) => vehicle.attached_trailer?.id === data.to_location_id)?.id
            ?? (data.to_location_type === "trailer" && destinationUnits.some((unit) => unit.id === data.to_location_id)
                ? data.to_location_id
                : null);
    }, [data.to_location_id, data.to_location_type, destinationUnits, powerVehicles]);
    const [destinationTarget, setDestinationTarget] = useState(
        data.to_location_type === "store" ? "store" : (data.to_location_id || data.tyre_id ? "vehicle_unit" : ""),
    );
    const [selectedUnitId, setSelectedUnitId] = useState<number | null>(initialUnitId);
    const [selectedPositionValue, setSelectedPositionValue] = useState(data.to_position_code || "");

    const selectedUnit = useMemo(
        () => destinationUnits.find((vehicle) => vehicle.id === selectedUnitId) ?? null,
        [destinationUnits, selectedUnitId],
    );
    const availableDestinationUnits = useMemo(
        () => destinationUnits.filter((vehicle) => (vehicle.total_available_count ?? vehicle.available_position_count ?? 0) > 0),
        [destinationUnits],
    );

    const selectedDestinationVehicle = useMemo(
        () => {
            const direct = destinationUnits.find(
                (vehicle) => vehicle.id === data.to_location_id && (!vehicle.asset_type || vehicle.asset_type === data.to_location_type),
            );

            if (direct) {
                return direct;
            }

            const attachedTrailer = data.to_location_type === "trailer"
                ? powerVehicles.find((vehicle) => vehicle.attached_trailer?.id === data.to_location_id)?.attached_trailer
                : null;

            return attachedTrailer ? {
                id: attachedTrailer.id,
                label: attachedTrailer.label,
                vehicle_code: attachedTrailer.vehicle_code,
                plate_number: attachedTrailer.plate_number,
                vehicle_type_name: attachedTrailer.vehicle_type_name,
                asset_type: "trailer",
                current_odometer: attachedTrailer.current_odometer,
                available_position_count: attachedTrailer.available_position_count,
            } : null;
        },
        [data.to_location_id, data.to_location_type, destinationUnits],
    );

    const selectedPosition = useMemo(
        () => positionOptions.find((position) => position.value === selectedPositionValue)
            ?? positionOptions.find((position) => position.code === data.to_position_code)
            ?? null,
        [data.to_position_code, positionOptions, selectedPositionValue],
    );
    const positionGroups = useMemo(() => groupPositionOptions(positionOptions), [positionOptions]);

    const sourceNeedsOdometer = selectedTyre?.position_type === "running";
    const destinationNeedsOdometer = destinationTarget === "vehicle_unit" && selectedPosition?.type === "running";

    // Map actions populate Inertia form data after this component has mounted.
    // Keep the local destination controls aligned with that prefilled voucher.
    useEffect(() => {
        if (!destinationTarget && data.tyre_id) {
            setDestinationTarget("vehicle_unit");
        }
    }, [data.tyre_id, destinationTarget]);

    useEffect(() => {
        if (destinationTarget === "vehicle_unit" && !selectedUnitId && initialUnitId) {
            setSelectedUnitId(initialUnitId);
        }
    }, [destinationTarget, initialUnitId, selectedUnitId]);

    useEffect(() => {
        if (
            selectedTyre?.position_type === "running"
            && selectedTyre.current_vehicle_odometer !== null
            && selectedTyre.current_vehicle_odometer !== undefined
            && data.from_odometer === null
        ) {
            setData("from_odometer", selectedTyre.current_vehicle_odometer);
        }
    }, [data.from_odometer, selectedTyre?.current_vehicle_odometer, selectedTyre?.id, selectedTyre?.position_type]);

    useEffect(() => {
        if (destinationTarget !== "vehicle_unit" || !selectedUnitId) {
            setPositionOptions([]);
            setPositionLoadError(false);
            return;
        }

        let cancelled = false;
        setLoadingPositions(true);
        setPositionLoadError(false);

        fetch(route("tyres.movements.position-options", selectedUnitId), { headers: { Accept: "application/json" } })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Unable to load destination positions");
                }

                return response.json() as Promise<PositionOption[]>;
            })
            .then((options: PositionOption[]) => {
                if (cancelled) {
                    return;
                }

                setPositionOptions(options);
                const preselected = options.find((position) =>
                    position.code === data.to_position_code
                    && position.owner_type === data.to_location_type
                    && position.owner_vehicle_id === data.to_location_id,
                );
                if (preselected) {
                    setSelectedPositionValue(preselected.value);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setPositionOptions([]);
                    setPositionLoadError(true);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoadingPositions(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [destinationTarget, selectedUnitId]);

    const handleTyreChange = (value: string) => {
        const tyreId = Number.parseInt(value, 10);
        const nextTyreId = Number.isFinite(tyreId) && tyreId > 0 ? tyreId : null;
        updateData({ tyre_id: nextTyreId, from_odometer: null });
        onTyreSelected?.(nextTyreId);
        setTyreSearch("");
        setTyrePickerOpen(false);
    };

    const handleDestinationTypeChange = (value: string) => {
        setDestinationTarget(value);
        updateData({
            to_location_type: value === "store" ? "store" : "power_vehicle",
            to_location_id: null,
            to_position_code: "",
            to_odometer: null,
        });
        setSelectedUnitId(null);
        setSelectedPositionValue("");
        setPositionOptions([]);
    };

    const handleDestinationChange = (value: string) => {
        const destinationId = Number.parseInt(value, 10);
        const destination = destinationUnits.find((location) => Number(location.id) === destinationId);
        const destinationType = destination?.asset_type === "trailer" ? "trailer" : "power_vehicle";

        setSelectedUnitId(Number.isFinite(destinationId) && destinationId > 0 ? destinationId : null);
        const currentOdometer = destination && "current_odometer" in destination
            ? (typeof destination.current_odometer === "number" ? destination.current_odometer : null)
            : null;
        updateData({
            to_location_type: destinationType,
            to_location_id: Number.isFinite(destinationId) && destinationId > 0 ? destinationId : null,
            to_position_code: "",
            to_odometer: currentOdometer,
        });
        setSelectedPositionValue("");
        setPositionOptions([]);
    };

    const handlePositionChange = (position: PositionOption) => {
        setSelectedPositionValue(position.value);
        updateData({
            to_location_type: position.owner_type,
            to_location_id: position.owner_vehicle_id,
            to_position_code: position.code,
            to_odometer: position.owner_current_odometer ?? null,
        });
    };

    const preview = buildPreview(selectedTyre, selectedDestinationVehicle, selectedPosition, data, stores);

    return (
        <div className="space-y-5">
            <Card className={compact ? "shadow-none" : undefined}>
                <CardHeader className={compact ? "px-4 py-3" : undefined}>
                    <CardTitle className="text-base">1. Tyre Selection</CardTitle>
                    <CardDescription>Select the tyre first. The system then locks the current source.</CardDescription>
                </CardHeader>
                <CardContent className={cn("space-y-4", compact && "px-4 py-3") }>
                    <Field label="Tyre" error={errors.tyre_id}>
                        {readOnlyTyre ? (
                            <Input value={selectedTyre ? tyreOptionLabel(selectedTyre) : ""} disabled />
                        ) : (
                            <div className="relative">
                                    <button
                                        type="button"
                                        onClick={() => setTyrePickerOpen((open) => !open)}
                                        className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 text-left text-sm shadow-sm outline-none transition focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        aria-label="Choose tyre"
                                        aria-expanded={tyrePickerOpen}
                                        aria-haspopup="listbox"
                                    >
                                        <span className={cn("flex min-w-0 items-center gap-2 truncate", !selectedTyre && "text-muted-foreground")}>
                                            <Search className="h-4 w-4 shrink-0" />
                                            {selectedTyre ? tyreOptionLabel(selectedTyre) : "Search and select tyre"}
                                        </span>
                                        <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    </button>
                                {tyrePickerOpen && (
                                    <div
                                        role="listbox"
                                        className="absolute z-50 mt-2 w-full rounded-md border bg-popover p-2 shadow-md"
                                    >
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            autoFocus
                                            value={tyreSearch}
                                            onChange={(event) => setTyreSearch(event.target.value)}
                                            placeholder="Search code, serial number, or location"
                                            className="pl-9"
                                            aria-label="Search tyres"
                                        />
                                    </div>
                                    <div className="mt-2 max-h-64 space-y-2 overflow-y-auto">
                                        {availableTyres.length > 0 && (
                                            <TyreOptionGroup
                                                title="Available"
                                                tyres={availableTyres}
                                                selectedId={data.tyre_id}
                                                onSelect={handleTyreChange}
                                            />
                                        )}
                                        {mountedTyres.length > 0 && (
                                            <TyreOptionGroup
                                                title="Mounted"
                                                description="Muted but selectable for relocation"
                                                tyres={mountedTyres}
                                                selectedId={data.tyre_id}
                                                onSelect={handleTyreChange}
                                                muted
                                            />
                                        )}
                                        {filteredTyres.length === 0 && (
                                            <p className="px-3 py-5 text-center text-sm text-muted-foreground">
                                                No tyres match this search.
                                            </p>
                                        )}
                                    </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </Field>

                    {selectedTyre && (
                        <div className="grid gap-3 rounded-md border bg-muted/20 p-3 text-sm md:grid-cols-4">
                            <SummaryItem label="Tyre code" value={selectedTyre.tyre_code} />
                            <SummaryItem label="Serial" value={selectedTyre.serial_number ?? "-"} />
                            <SummaryItem label="Brand" value={selectedTyre.brand ?? "-"} />
                            <SummaryItem label="Size" value={selectedTyre.size ?? "-"} />
                            {selectedTyre.has_pending_movement && (
                                <div className="md:col-span-4">
                                    <WarningText>This tyre already has a pending movement voucher.</WarningText>
                                </div>
                            )}
                            {selectedTyre.status === "disposed" && (
                                <div className="md:col-span-4">
                                    <WarningText>Disposed tyres cannot be moved.</WarningText>
                                </div>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="grid gap-5 lg:grid-cols-2">
                <Card className={compact ? "shadow-none" : undefined}>
                    <CardHeader className={compact ? "px-4 py-3" : undefined}>
                        <CardTitle className="text-base">2. From</CardTitle>
                        <CardDescription>Current tyre location, detected from the tyre record.</CardDescription>
                    </CardHeader>
                    <CardContent className={cn("space-y-4", compact && "px-4 py-3") }>
                        <div className="grid gap-3 rounded-md border bg-muted/20 p-3 text-sm sm:grid-cols-2">
                            <SummaryItem label="Location" value={sourceInfo?.location_label ?? selectedTyre?.source_label ?? "Select a tyre"} />
                            <SummaryItem label="Position" value={sourceInfo?.position_label ?? selectedTyre?.source_position_label ?? selectedTyre?.current_position_code ?? "-"} />
                            <SummaryItem label="Position type" value={positionTypeLabel(selectedTyre?.position_type)} />
                            <SummaryItem label="Vehicle KM" value={formatKm(selectedTyre?.current_vehicle_odometer)} />
                            {selectedTyre?.installed_odometer !== null && selectedTyre?.installed_odometer !== undefined && (
                                <SummaryItem label="Installed KM" value={formatKm(selectedTyre.installed_odometer)} />
                            )}
                        </div>

                        {sourceNeedsOdometer ? (
                            <Field label="Odometer out from source vehicle" error={errors.from_odometer}>
                                <Input
                                    type="number"
                                    min={0}
                                    value={data.from_odometer ?? ""}
                                    onChange={(e) =>
                                        setData("from_odometer", e.target.value === "" ? null : Number(e.target.value))
                                    }
                                />
                                <HelperText>Required because this tyre is coming from a running position.</HelperText>
                            </Field>
                        ) : (
                            <InfoText>Odometer out is not required for store or spare source positions.</InfoText>
                        )}
                    </CardContent>
                </Card>

                <Card className={compact ? "shadow-none" : undefined}>
                    <CardHeader className={compact ? "px-4 py-3" : undefined}>
                        <CardTitle className="text-base">3. To</CardTitle>
                        <CardDescription>Choose a valid destination and then an empty position.</CardDescription>
                    </CardHeader>
                    <CardContent className={cn("space-y-4", compact && "px-4 py-3") }>
                        <Field label="Destination target" error={errors.to_location_type}>
                            <select
                                value={destinationTarget}
                                onChange={(event) => handleDestinationTypeChange(event.target.value)}
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            >
                                <option value="" disabled>Choose store or vehicle unit</option>
                                {(destinationTargets ?? [
                                    { value: "store", label: "Store" },
                                    { value: "vehicle_unit", label: "Vehicle / Attached Unit" },
                                ]).map((type) => (
                                    <option key={type.value} value={type.value}>{type.label}</option>
                                ))}
                            </select>
                        </Field>

                        {destinationTarget === "store" && (
                            <Field label="Destination store" error={errors.to_location_id}>
                            <select
                                value={data.to_location_id ? String(data.to_location_id) : ""}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    updateData({
                                        to_location_id: Number(value),
                                        to_position_code: "",
                                        to_odometer: null,
                                    });
                                }}
                                disabled={destinationTarget !== "store"}
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="" disabled>Select destination store</option>
                                {stores.map((location) => (
                                    <option key={location.id} value={String(location.id)}>{location.label}</option>
                                ))}
                            </select>
                            </Field>
                        )}

                        {destinationTarget === "vehicle_unit" && (
                            <Field label="Vehicle / attached unit" error={errors.to_location_id}>
                                <select
                                    value={selectedUnitId ? String(selectedUnitId) : ""}
                                    onChange={(event) => handleDestinationChange(event.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                >
                                    <option value="" disabled>Select vehicle or attached unit</option>
                                    {availableDestinationUnits.map((vehicle) => (
                                        <option key={vehicle.id} value={String(vehicle.id)}>
                                            {vehicle.label}{vehicle.attached_trailer ? ` | Trailer: ${vehicle.attached_trailer.label}` : ""}
                                        </option>
                                    ))}
                                </select>
                                {availableDestinationUnits.length === 0 && (
                                    <HelperText>No active vehicle unit currently has an open tyre position.</HelperText>
                                )}
                            </Field>
                        )}

                        {selectedUnit && destinationTarget === "vehicle_unit" && (
                            <div className="grid gap-2 rounded-md border bg-muted/20 p-3 text-xs text-muted-foreground sm:grid-cols-3">
                                <SummaryItem label={selectedUnit.asset_type === "trailer" ? "Open positions" : "Power open"} value={`${selectedUnit.power_available_count ?? selectedUnit.available_position_count ?? "-"}`} />
                                <SummaryItem label="Attached trailer" value={selectedUnit.attached_trailer ? `${selectedUnit.trailer_available_count ?? 0} open` : "None"} />
                                <SummaryItem label="Current KM" value={formatKm(selectedUnit.current_odometer)} />
                            </div>
                        )}

                        {destinationTarget === "vehicle_unit" && (
                            <Field label="Destination position" error={errors.to_position_code}>
                                <div className="space-y-4">
                                    {!selectedUnitId && (
                                        <InfoText>Select a vehicle or attached trailer first.</InfoText>
                                    )}
                                    {positionGroups.map((group) => (
                                        <section key={group.title} className="space-y-2">
                                            <div className="flex items-center justify-between gap-2">
                                                <div>
                                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{group.title}</p>
                                                    {group.subtitle && <p className="text-[11px] text-muted-foreground">{group.subtitle}</p>}
                                                </div>
                                                <span className="text-[11px] text-muted-foreground">{group.positions.filter((position) => position.is_empty).length} open</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                {group.positions.map((position) => {
                                                    const selected = selectedPositionValue === position.value
                                                        || (data.to_position_code === position.code
                                                            && data.to_location_type === position.owner_type
                                                            && data.to_location_id === position.owner_vehicle_id);
                                                    const disabled = position.is_occupied || position.disabled;

                                                    return (
                                                        <button
                                                            key={position.value}
                                                            type="button"
                                                            disabled={disabled}
                                                            title={`${position.display_code} - ${position.label}${position.mounted_tyre_code ? ` - ${position.mounted_tyre_code}` : ""}`}
                                                            onClick={() => handlePositionChange(position)}
                                                            className={cn(
                                                                "min-h-16 rounded-md border p-2 text-left text-sm transition",
                                                                selected && "border-primary bg-primary text-primary-foreground shadow-sm",
                                                                !selected && !disabled && "bg-background hover:border-primary/60 hover:bg-primary/5",
                                                                disabled && "cursor-not-allowed border-dashed bg-muted/40 text-muted-foreground",
                                                            )}
                                                        >
                                                            <div className="flex items-center justify-between gap-1">
                                                                <span className="font-semibold">{position.display_code}</span>
                                                                <span className="text-[10px]">{position.type === "spare" ? "Spare" : "Run"}</span>
                                                            </div>
                                                            <p className="mt-1 line-clamp-1 text-[11px]">{position.label}</p>
                                                            <p className="mt-1 flex items-center gap-1 text-[11px]">
                                                                {position.is_empty ? <CheckCircle2 className="h-3 w-3 text-emerald-500" /> : <Circle className="h-3 w-3" />}
                                                                <span className="truncate">{position.is_empty ? "Open" : position.mounted_tyre_code}</span>
                                                            </p>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </section>
                                    ))}
                                </div>
                                {loadingPositions && <HelperText>Loading destination positions...</HelperText>}
                                {positionLoadError && <WarningText>Destination positions could not be loaded. Choose the unit again or refresh the page.</WarningText>}
                                {!loadingPositions && selectedUnitId && positionOptions.length === 0 && (
                                    <WarningText>No tyre positions are configured for this destination.</WarningText>
                                )}
                                {selectedPosition?.type === "spare" && (
                                    <InfoText>Spare positions do not gain running KM. Odometer in is optional for audit.</InfoText>
                                )}
                            </Field>
                        )}

                        {destinationNeedsOdometer ? (
                            <Field label="Odometer in at destination vehicle" error={errors.to_odometer}>
                                <Input
                                    type="number"
                                    min={0}
                                    value={data.to_odometer ?? ""}
                                    onChange={(e) =>
                                        setData("to_odometer", e.target.value === "" ? null : Number(e.target.value))
                                    }
                                />
                                <HelperText>Required because the destination is a running position.</HelperText>
                            </Field>
                        ) : destinationTarget === "store" ? (
                            <InfoText>Odometer in is not required for store or spare destinations.</InfoText>
                        ) : destinationTarget === "vehicle_unit" && selectedPosition?.type === "spare" ? (
                            <InfoText>Odometer in is not required for a spare position.</InfoText>
                        ) : (
                            <InfoText>Select an open destination position to determine whether vehicle KM is required.</InfoText>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className={compact ? "shadow-none" : undefined}>
                <CardHeader className={compact ? "px-4 py-3" : undefined}>
                    <CardTitle className="text-base">4. Movement Details</CardTitle>
                    <CardDescription>These details are saved on the voucher. The tyre moves only after completion.</CardDescription>
                </CardHeader>
                <CardContent className={cn("grid gap-4 md:grid-cols-2", compact && "px-4 py-3") }>
                    <Field label="Movement date" error={errors.movement_date}>
                        <Input
                            type="date"
                            value={data.movement_date}
                            onChange={(e) => setData("movement_date", e.target.value)}
                        />
                    </Field>
                    <div />
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
                </CardContent>
            </Card>

            <div className="rounded-md border bg-muted/20 p-4">
                <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                    <Search className="h-4 w-4" />
                    Voucher preview
                </div>
                <p className="text-sm text-muted-foreground">{preview}</p>
            </div>
        </div>
    );
}

function buildPreview(
    tyre: TyreOption | null,
    destinationVehicle: DestinationVehicleOption | null,
    position: PositionOption | null,
    data: MovementFormData,
    stores: LocationOption[],
): string {
    if (!tyre) {
        return "Select a tyre to preview this movement voucher.";
    }

    const source = `${tyre.source_label}${tyre.current_position_code ? ` position ${tyre.current_position_code}` : ""}`;

    if (data.to_location_type === "store") {
        const store = stores.find((option) => option.id === data.to_location_id);
        return `Move ${tyre.tyre_code} from ${source} to ${store?.label ?? "selected store"}.`;
    }

    if (destinationVehicle && position) {
        return `Move ${tyre.tyre_code} from ${source} to ${destinationVehicle.vehicle_code ?? destinationVehicle.label} position ${position.display_code}.`;
    }

    return `Move ${tyre.tyre_code} from ${source}. Choose a destination and empty position to complete the draft.`;
}

function tyreOptionLabel(tyre: TyreOption): string {
    return [tyre.tyre_code, tyre.serial_number ? `Serial ${tyre.serial_number}` : null]
        .filter(Boolean)
        .join(" - ");
}

function TyreOptionGroup({
    title,
    description,
    tyres,
    selectedId,
    onSelect,
    muted = false,
}: {
    title: string;
    description?: string;
    tyres: TyreOption[];
    selectedId: number | null;
    onSelect: (value: string) => void;
    muted?: boolean;
}) {
    return (
        <div className="space-y-1">
            <div className="px-2 py-1">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{title}</p>
                {description && <p className="text-[11px] text-muted-foreground">{description}</p>}
            </div>
            {tyres.map((tyre) => {
                const selected = Number(selectedId) === Number(tyre.id);
                const mounted = tyre.current_location_type === "power_vehicle" || tyre.current_location_type === "trailer";

                return (
                    <button
                        key={tyre.id}
                        type="button"
                        onPointerDown={(event) => {
                            event.preventDefault();
                            onSelect(String(tyre.id));
                        }}
                        className={cn(
                            "flex w-full items-start justify-between gap-3 rounded-md border px-3 py-2 text-left transition",
                            selected && "border-primary bg-primary/10 ring-1 ring-primary/30",
                            !selected && "bg-background hover:border-primary/50 hover:bg-primary/5",
                            muted && !selected && "opacity-65",
                        )}
                    >
                        <span className="min-w-0">
                            <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span className="font-semibold text-foreground">{tyre.tyre_code}</span>
                                {tyre.serial_number && (
                                    <span className="text-xs text-muted-foreground">Serial: {tyre.serial_number}</span>
                                )}
                            </span>
                            <span className="mt-1 block truncate text-xs text-muted-foreground">
                                {mounted && tyre.current_position_code ? `${tyre.source_label} - Position ${tyre.current_position_code}` : tyre.source_label}
                            </span>
                        </span>
                        <span className="shrink-0 text-right">
                            <span className={cn(
                                "block text-[11px] font-medium",
                                mounted ? "text-muted-foreground" : "text-emerald-700 dark:text-emerald-400",
                            )}>
                                {mounted ? "Mounted" : tyre.status_label}
                            </span>
                            {mounted && <span className="block text-[10px] text-muted-foreground">Select to relocate</span>}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}

function SummaryItem({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="font-medium text-foreground">{value}</p>
        </div>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

function HelperText({ children }: { children: ReactNode }) {
    return <p className="text-xs text-muted-foreground">{children}</p>;
}

function InfoText({ children }: { children: ReactNode }) {
    return (
        <p className="flex items-start gap-2 rounded-md border bg-muted/20 p-3 text-sm text-muted-foreground">
            <MapPin className="mt-0.5 h-4 w-4 shrink-0" />
            {children}
        </p>
    );
}

function WarningText({ children }: { children: ReactNode }) {
    return (
        <p className="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
            {children}
        </p>
    );
}
