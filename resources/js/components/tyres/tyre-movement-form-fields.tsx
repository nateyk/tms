import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
};
type DestinationType = { value: string; label: string };
type PositionOption = {
    value: string;
    code: string;
    display_code: string;
    label: string;
    type: "running" | "spare";
    is_empty: boolean;
    is_occupied: boolean;
    mounted_tyre_id: number | null;
    mounted_tyre_code: string | null;
    disabled_reason: string | null;
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

type TyreMovementFormFieldsProps = {
    data: MovementFormData;
    setData: <K extends keyof MovementFormData>(key: K, value: MovementFormData[K]) => void;
    errors: Partial<Record<keyof MovementFormData, string>>;
    tyres: TyreOption[];
    stores: LocationOption[];
    powerVehicles: DestinationVehicleOption[];
    trailers: DestinationVehicleOption[];
    destinationTypes: DestinationType[];
    readOnlyTyre?: boolean;
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
    const groups = [
        { title: "Front axle", codes: ["A", "B"] },
        { title: "1st drive axle", codes: ["C", "D", "E", "F"] },
        { title: "2nd drive axle", codes: ["G", "H", "I", "J"] },
        { title: "Spare wheels", subtitle: "Non-running positions", codes: ["W", "X"] },
        { title: "Tag axle", codes: ["K", "L", "M", "N"] },
        { title: "Rear axles", codes: ["O", "P", "Q", "R", "S", "T", "U", "V"] },
    ];
    const used = new Set<string>();
    const result = groups.map((group) => {
        const positions = group.codes
            .map((code) => options.find((position) => position.display_code === code || position.code === code))
            .filter((position): position is PositionOption => Boolean(position));
        positions.forEach((position) => used.add(position.code));
        return { title: group.title, subtitle: group.subtitle, positions };
    }).filter((group) => group.positions.length > 0);
    const remaining = options.filter((position) => !used.has(position.code));

    if (remaining.length > 0) {
        result.push({ title: "Other positions", subtitle: undefined, positions: remaining });
    }

    return result;
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
    const [tyreSearch, setTyreSearch] = useState("");
    const [tyrePickerOpen, setTyrePickerOpen] = useState(false);
    const [selectedTyreId, setSelectedTyreId] = useState<number | null>(data.tyre_id);
    const [loadingPositions, setLoadingPositions] = useState(false);

    const selectedTyre = useMemo(
        () => tyres.find((tyre) => Number(tyre.id) === Number(selectedTyreId)) ?? null,
        [selectedTyreId, tyres],
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

    const destinationLocations = useMemo(() => {
        const sourceVehicleId = selectedTyre?.current_location_id;
        const sourceType = selectedTyre?.current_location_type;

        if (data.to_location_type === "store") {
            return stores;
        }

        const vehicles = data.to_location_type === "trailer" ? trailers : powerVehicles;

        return vehicles
            .filter((vehicle) => (vehicle.available_position_count ?? 1) > 0 || vehicle.id === sourceVehicleId)
            .map((vehicle) => ({
                ...vehicle,
                label:
                    vehicle.id === sourceVehicleId && sourceType === data.to_location_type
                        ? `${vehicle.label} - Rotation on same vehicle`
                        : vehicle.label,
            }));
    }, [data.to_location_type, powerVehicles, selectedTyre, stores, trailers]);

    const selectedDestinationVehicle = useMemo(
        () =>
            [...powerVehicles, ...trailers].find(
                (vehicle) => vehicle.id === data.to_location_id && (!vehicle.asset_type || vehicle.asset_type === data.to_location_type),
            ) ?? null,
        [data.to_location_id, data.to_location_type, powerVehicles, trailers],
    );

    const selectedPosition = useMemo(
        () => positionOptions.find((position) => position.code === data.to_position_code) ?? null,
        [data.to_position_code, positionOptions],
    );
    const positionGroups = useMemo(() => groupPositionOptions(positionOptions), [positionOptions]);

    const sourceNeedsOdometer = selectedTyre?.position_type === "running";
    const destinationNeedsOdometer = isVehicleType(data.to_location_type) && selectedPosition?.type === "running";

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

    const handleTyreChange = (value: string) => {
        const tyreId = Number.parseInt(value, 10);
        const nextTyreId = Number.isFinite(tyreId) && tyreId > 0 ? tyreId : null;
        setSelectedTyreId(nextTyreId);
        setData("tyre_id", nextTyreId);
        setData("from_odometer", null);
        setTyreSearch("");
        setTyrePickerOpen(false);
    };

    const handleDestinationTypeChange = (value: string) => {
        setData("to_location_type", value);
        setData("to_location_id", null);
        setData("to_position_code", "");
        setData("to_odometer", null);
    };

    const handleDestinationChange = (value: string) => {
        const destinationId = Number.parseInt(value, 10);
        const destination = destinationLocations.find((location) => Number(location.id) === destinationId);

        setData("to_location_id", Number.isFinite(destinationId) && destinationId > 0 ? destinationId : null);
        setData("to_position_code", "");
        const currentOdometer = destination && "current_odometer" in destination
            ? (typeof destination.current_odometer === "number" ? destination.current_odometer : null)
            : null;
        setData("to_odometer", currentOdometer);
    };

    const preview = buildPreview(selectedTyre, selectedDestinationVehicle, selectedPosition, data, stores);

    return (
        <div className="space-y-5">
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">1. Tyre Selection</CardTitle>
                    <CardDescription>Select the tyre first. The system then locks the current source.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <Field label="Tyre" error={errors.tyre_id}>
                        {readOnlyTyre ? (
                            <Input value={selectedTyre ? tyreOptionLabel(selectedTyre) : ""} disabled />
                        ) : (
                            <Popover open={tyrePickerOpen} onOpenChange={setTyrePickerOpen}>
                                <PopoverTrigger asChild>
                                    <button
                                        type="button"
                                        className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 text-left text-sm shadow-sm outline-none transition focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        aria-label="Choose tyre"
                                    >
                                        <span className={cn("flex min-w-0 items-center gap-2 truncate", !selectedTyre && "text-muted-foreground")}>
                                            <Search className="h-4 w-4 shrink-0" />
                                            {selectedTyre ? tyreOptionLabel(selectedTyre) : "Search and select tyre"}
                                        </span>
                                        <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent align="start" className="w-[min(520px,calc(100vw-2rem))] p-2">
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
                                                selectedId={selectedTyreId}
                                                onSelect={handleTyreChange}
                                            />
                                        )}
                                        {mountedTyres.length > 0 && (
                                            <TyreOptionGroup
                                                title="Mounted"
                                                description="Muted but selectable for relocation"
                                                tyres={mountedTyres}
                                                selectedId={selectedTyreId}
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
                                </PopoverContent>
                            </Popover>
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
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">2. From</CardTitle>
                        <CardDescription>Current tyre location, detected from the tyre record.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
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

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">3. To</CardTitle>
                        <CardDescription>Choose a valid destination and then an empty position.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
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

                        <Field label={data.to_location_type === "store" ? "Destination store" : "Destination vehicle"} error={errors.to_location_id}>
                            <Select
                                value={data.to_location_id ? String(data.to_location_id) : undefined}
                                onValueChange={handleDestinationChange}
                                disabled={!data.to_location_type}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={data.to_location_type ? "Select destination" : "Choose destination type first"} />
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

                        {selectedDestinationVehicle && (
                            <div className="grid gap-2 rounded-md border bg-muted/20 p-3 text-xs text-muted-foreground sm:grid-cols-3">
                                <SummaryItem label="Available" value={`${selectedDestinationVehicle.available_position_count ?? "-"}`} />
                                <SummaryItem label="Mounted" value={`${selectedDestinationVehicle.mounted_count ?? "-"}`} />
                                <SummaryItem label="Latest KM" value={formatKm(selectedDestinationVehicle.current_odometer)} />
                            </div>
                        )}

                        {isVehicleType(data.to_location_type) && (
                            <Field label="Destination position" error={errors.to_position_code}>
                                <div className="space-y-4">
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
                                                    const selected = data.to_position_code === position.code;
                                                    const disabled = position.is_occupied;

                                                    return (
                                                        <button
                                                            key={position.code}
                                                            type="button"
                                                            disabled={disabled}
                                                            title={`${position.display_code} - ${position.label}${position.mounted_tyre_code ? ` - ${position.mounted_tyre_code}` : ""}`}
                                                            onClick={() => setData("to_position_code", position.code)}
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
                                {!loadingPositions && data.to_location_id && positionOptions.length === 0 && (
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
                        ) : (
                            <InfoText>Odometer in is not required for store or spare destinations.</InfoText>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">4. Movement Details</CardTitle>
                    <CardDescription>These details are saved on the voucher. The tyre moves only after completion.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
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
                        onClick={() => onSelect(String(tyre.id))}
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
