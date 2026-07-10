import { useMemo, useState } from "react";
import { Link } from "@inertiajs/react";
import { Info } from "lucide-react";
import { ModernTyreMap, KonvaSlot } from "@/components/fleet/modern-tyre-map";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { cn } from "@/lib/utils";
import { TyreMovementDialog } from "@/components/fleet/tyre-movement-dialog";
import { TyreDisposalDialog } from "@/components/fleet/tyre-disposal-dialog";

export type MapSlot = {
    code: string;
    display_code: string;
    label: string;
    axle?: number | null;
    tyre_code?: string | null;
    tyre_id?: number | null;
    brand?: string | null;
    serial_number?: string | null;
    status?: string;
    install_url?: string | null;
    color?: string;
};

export type TyreMapPayload = {
    mapData: MapSlot[];
    spareTyres: Array<{
        position: string;
        display_code: string;
        tyre_code?: string | null;
        tyre_id?: number | null;
        owner_label: string;
        serial_number?: string | null;
    }>;
    spareCapacity: number;
    konvaConfig: {
        slots: KonvaSlot[];
        assetType: string;
    };
    counts: {
        mounted: number;
        total: number;
        empty: number;
        spares_filled: number;
    };
    legend: Record<string, string>;
};

type GuideGroup = {
    title: string;
    subtitle?: string;
    slots: MapSlot[];
};

type MapUnit = "power" | "trailer";

type MapSelection = {
    unit: MapUnit;
    code: string;
} | null;

type VehicleSummary = {
    id: number;
    vehicle_code: string;
    display_code?: string;
    plate_number?: string | null;
    vehicle_type_name?: string | null;
    odometer?: number | null;
};

type VehicleTyreMapPanelProps = {
    vehicle: VehicleSummary;
    tyreMap: TyreMapPayload;
    trailer?: VehicleSummary | null;
    trailerTyreMap?: TyreMapPayload | null;
    movementFormProps?: {
        tyres: Array<{
            id: number;
            tyre_code: string;
            serial_number: string;
            status_label: string;
            current_location_type: string | null;
            current_location_id: number | null;
            current_position_code: string | null;
            source_label: string;
        }>;
        stores: Array<{ id: number; label: string }>;
        powerVehicles: Array<{ id: number; label: string }>;
        trailers: Array<{ id: number; label: string }>;
        destinationTypes: Array<{ value: string; label: string }>;
    };
    disposalFormProps?: {
        tyres: Array<{ id: number; tyre_code: string; status_label: string }>;
        disposalReasons: Array<{ value: string; label: string }>;
    };
};

const TRUCK_GUIDE_DEFINITIONS = [
    { title: "Front axle", codes: ["A", "B"] },
    { title: "1st drive axle", codes: ["C", "D", "E", "F"] },
    { title: "2nd drive axle", codes: ["G", "H", "I", "J"] },
    { title: "Spare wheel (front)", subtitle: "Between 1st and 2nd drive", codes: ["W"] },
    { title: "Tag axle", codes: ["K", "L", "M", "N"] },
    { title: "Spare wheel (rear)", subtitle: "Between tag and rear", codes: ["X"] },
    { title: "Rear axle", codes: ["O", "P", "Q", "R", "S", "T", "U", "V"] },
] as const;

function buildGuideGroups(mapData: MapSlot[], assetType: string): GuideGroup[] {
    if (assetType === "trailer") {
        const byAxle = new Map<number, MapSlot[]>();

        mapData.forEach((slot) => {
            const axle = Number(slot.axle ?? 0);
            if (!byAxle.has(axle)) {
                byAxle.set(axle, []);
            }
            byAxle.get(axle)!.push(slot);
        });

        return Array.from(byAxle.entries())
            .sort(([a], [b]) => a - b)
            .map(([axle, slots]) => ({
                title: `Trailer axle ${axle}`,
                slots,
            }));
    }

    return TRUCK_GUIDE_DEFINITIONS.map((group) => ({
        title: group.title,
        subtitle: "subtitle" in group ? group.subtitle : undefined,
        slots: group.codes
            .map((code) => mapData.find((slot) => slot.display_code === code))
            .filter((slot): slot is MapSlot => Boolean(slot)),
    })).filter((group) => group.slots.length > 0);
}

function mergeCounts(primary: TyreMapPayload, secondary?: TyreMapPayload | null) {
    if (!secondary) {
        return primary.counts;
    }

    return {
        mounted: primary.counts.mounted + secondary.counts.mounted,
        total: primary.counts.total + secondary.counts.total,
        empty: primary.counts.empty + secondary.counts.empty,
        spares_filled: primary.counts.spares_filled + secondary.counts.spares_filled,
    };
}

function mergeSpareCapacity(primary: TyreMapPayload, secondary?: TyreMapPayload | null) {
    return primary.spareCapacity + (secondary?.spareCapacity ?? 0);
}

function findSelectedSlot(
    payload: TyreMapPayload,
    code: string,
): MapSlot | TyreMapPayload["spareTyres"][number] | null {
    return (
        payload.mapData.find((slot) => slot.code === code) ??
        payload.spareTyres.find((spare) => spare.position === code) ??
        null
    );
}

function CountStat({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-lg border bg-muted/40 px-3 py-2 text-center min-w-[4.5rem]">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-lg font-semibold tabular-nums">{value}</p>
        </div>
    );
}

function PositionBadge({
    code,
    selected,
    empty,
}: {
    code: string;
    selected?: boolean;
    empty?: boolean;
}) {
    return (
        <span
            className={cn(
                "flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-bold",
                empty
                    ? "border border-dashed border-muted-foreground/40 bg-muted text-muted-foreground"
                    : "bg-primary text-primary-foreground",
                selected && "ring-2 ring-ring ring-offset-2 ring-offset-background",
            )}
        >
            {code}
        </span>
    );
}

function SelectedSlotDetails({
    slot,
    positionCode,
}: {
    slot: NonNullable<ReturnType<typeof findSelectedSlot>>;
    positionCode: string;
}) {
    const displayCode = "display_code" in slot ? slot.display_code : positionCode;
    const label = "label" in slot ? slot.label : "Spare tyre position";

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <PositionBadge code={displayCode} empty={!slot.tyre_id} selected />
                <div>
                    <p className="font-medium">{label}</p>
                    <p className="text-sm text-muted-foreground">{displayCode}</p>
                </div>
            </div>

            {slot.tyre_id ? (
                <dl className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Tyre</dt>
                        <dd className="font-medium">
                            <Link
                                href={route("tyres.show", slot.tyre_id)}
                                className="text-primary hover:underline"
                            >
                                {slot.tyre_code}
                            </Link>
                        </dd>
                    </div>
                    {"brand" in slot && (
                        <div>
                            <dt className="text-muted-foreground">Brand</dt>
                            <dd className="font-medium">{slot.brand || "—"}</dd>
                        </div>
                    )}
                    {"serial_number" in slot && (
                        <div>
                            <dt className="text-muted-foreground">Serial</dt>
                            <dd className="font-medium">{slot.serial_number || "—"}</dd>
                        </div>
                    )}
                    {"status" in slot && (
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">{slot.status ?? "—"}</dd>
                        </div>
                    )}
                </dl>
            ) : (
                <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-4">
                    <Badge variant="secondary">Open position</Badge>
                    {"install_url" in slot && slot.install_url && (
                        <Button size="sm" asChild>
                            <Link href={slot.install_url}>Fill {displayCode}</Link>
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}

function GuideSection({
    unit,
    unitLabel,
    groups,
    selection,
    onSelect,
}: {
    unit: MapUnit;
    unitLabel: string;
    groups: GuideGroup[];
    selection: MapSelection;
    onSelect: (unit: MapUnit, code: string) => void;
}) {
    if (groups.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {unitLabel}
            </p>
            {groups.map((group, index) => (
                <Card key={`${unit}-${group.title}-${group.subtitle || index}`} className="overflow-hidden shadow-none">
                    <CardHeader className="space-y-0 border-b bg-muted/50 px-3 py-2">
                        <CardTitle className="text-xs font-semibold uppercase tracking-wide">
                            {group.title}
                        </CardTitle>
                        {group.subtitle && (
                            <CardDescription className="text-xs">{group.subtitle}</CardDescription>
                        )}
                    </CardHeader>
                    <CardContent className="p-0">
                        {group.slots.map((slot) => {
                            const isSelected =
                                selection?.unit === unit && selection.code === slot.code;

                            return (
                                <Button
                                    key={`${unit}-${slot.code}`}
                                    type="button"
                                    variant="ghost"
                                    onClick={() => onSelect(unit, slot.code)}
                                    className={cn(
                                        "h-auto w-full justify-start gap-3 rounded-none border-b px-3 py-2.5 text-left last:border-b-0",
                                        isSelected && "bg-accent",
                                    )}
                                >
                                    <PositionBadge
                                        code={slot.display_code}
                                        selected={isSelected}
                                        empty={!slot.tyre_code}
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium">
                                            {slot.label}
                                        </span>
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {slot.tyre_code
                                                ? slot.tyre_code
                                                : slot.install_url
                                                  ? "No tyre mounted — fill position"
                                                  : "No tyre mounted"}
                                        </span>
                                    </span>
                                </Button>
                            );
                        })}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function TyreDiagramBlock({
    unit,
    sectionLabel,
    vehicleCode,
    mapId,
    payload,
    selection,
    onSelect,
    onContextMenuAction,
    diagramHeight,
}: {
    unit: MapUnit;
    sectionLabel: string;
    vehicleCode: string;
    mapId: string;
    payload: TyreMapPayload;
    selection: MapSelection;
    onSelect: (code: string) => void;
    onContextMenuAction: (action: string, slot: KonvaSlot) => void;
    diagramHeight: number;
}) {
    if (payload.mapData.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col">
            <div className="flex items-center justify-between border-b px-4 py-2.5">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {sectionLabel}
                    </p>
                    <p className="text-sm font-semibold">{vehicleCode}</p>
                </div>
                <Badge variant="outline" className="text-xs">
                    {payload.counts.mounted}/{payload.counts.total} mounted
                </Badge>
            </div>
            <div
                className="w-full overflow-y-auto overflow-x-hidden px-3 py-3"
                style={{ height: diagramHeight }}
            >
                <ModernTyreMap
                    mapId={mapId}
                    assetType={payload.konvaConfig.assetType}
                    slots={payload.konvaConfig.slots}
                    selectedPosition={selection?.unit === unit ? selection.code : null}
                    onSelect={onSelect}
                    onContextMenuAction={onContextMenuAction}
                />
            </div>
        </div>
    );
}

export function VehicleTyreMapPanel({
    vehicle,
    tyreMap,
    trailer,
    trailerTyreMap,
    movementFormProps,
    disposalFormProps,
}: VehicleTyreMapPanelProps) {
    const [selection, setSelection] = useState<MapSelection>(null);
    const [movementDialogOpen, setMovementDialogOpen] = useState(false);
    const [disposalDialogOpen, setDisposalDialogOpen] = useState(false);
    const [selectedTyreId, setSelectedTyreId] = useState<number | null>(null);

    const counts = useMemo(() => mergeCounts(tyreMap, trailerTyreMap), [tyreMap, trailerTyreMap]);
    const spareCapacity = useMemo(
        () => mergeSpareCapacity(tyreMap, trailerTyreMap),
        [tyreMap, trailerTyreMap],
    );

    const powerGuide = useMemo(
        () => buildGuideGroups(tyreMap.mapData, tyreMap.konvaConfig.assetType),
        [tyreMap],
    );

    const trailerGuide = useMemo(
        () =>
            trailerTyreMap
                ? buildGuideGroups(
                      trailerTyreMap.mapData,
                      trailerTyreMap.konvaConfig.assetType,
                  )
                : [],
        [trailerTyreMap],
    );

    const allSpares = useMemo(
        () => [...tyreMap.spareTyres, ...(trailerTyreMap?.spareTyres ?? [])],
        [tyreMap.spareTyres, trailerTyreMap?.spareTyres],
    );

    const selectedSlot = useMemo(() => {
        if (!selection) {
            return null;
        }

        const payload = selection.unit === "trailer" ? trailerTyreMap : tyreMap;
        if (!payload) {
            return null;
        }

        return findSelectedSlot(payload, selection.code);
    }, [selection, tyreMap, trailerTyreMap]);

    const headerMeta = [
        vehicle.vehicle_type_name,
        vehicle.plate_number,
        vehicle.odometer ? `${vehicle.odometer.toLocaleString()} km` : null,
        trailer ? `Trailer: ${trailer.vehicle_code}` : null,
    ]
        .filter(Boolean)
        .join(" · ");

    const handleSelect = (unit: MapUnit, code: string) => {
        setSelection({ unit, code });
    };

    const handleContextMenuAction = (action: string, slot: KonvaSlot, x?: number, y?: number) => {
        const payload = slot.code && tyreMap.konvaConfig.slots.find(s => s.code === slot.code) ? tyreMap : trailerTyreMap;
        if (!payload) return;

        const mapSlot = payload.mapData.find((s) => s.code === slot.code);
        if (!mapSlot) return;

        // Select the slot first
        const unit = payload === tyreMap ? "power" : "trailer";
        setSelection({ unit, code: slot.code });

        if (action === "view") {
            // View is handled by the selection
            return;
        }

        if (action === "movement" && mapSlot.tyre_id) {
            setSelectedTyreId(mapSlot.tyre_id);
            setMovementDialogOpen(true);
        }

        if (action === "disposal" && mapSlot.tyre_id) {
            setSelectedTyreId(mapSlot.tyre_id);
            setDisposalDialogOpen(true);
        }
    };

    if (tyreMap.mapData.length === 0 && !trailerTyreMap?.mapData.length) {
        return (
            <Card>
                <CardContent className="py-10 text-center text-sm text-muted-foreground">
                    No tyre positions configured for this vehicle.
                </CardContent>
            </Card>
        );
    }

    const hasTrailer = Boolean(trailer && trailerTyreMap);
    const powerDiagramHeight = hasTrailer ? 520 : 680;
    const trailerDiagramHeight = 320;
    const rightPanelHeight = hasTrailer
        ? powerDiagramHeight + trailerDiagramHeight + 80
        : powerDiagramHeight + 40;

    return (
        <>
            <Card>
                <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between pb-4">
                    <div>
                        <CardDescription>Vehicle tyre map</CardDescription>
                        <CardTitle>{vehicle.display_code ?? vehicle.vehicle_code}</CardTitle>
                        {headerMeta && <CardDescription className="mt-1">{headerMeta}</CardDescription>}
                    </div>
                    <div className="flex gap-2">
                        <CountStat label="Mounted" value={`${counts.mounted}/${counts.total}`} />
                        <CountStat label="Open" value={counts.empty} />
                        {spareCapacity > 0 && (
                            <CountStat
                                label="Spares"
                                value={`${counts.spares_filled}/${spareCapacity}`}
                            />
                        )}
                    </div>
                </CardHeader>

                <CardContent className="p-0">
                    <div className="grid lg:grid-cols-[13fr_7fr] lg:divide-x">
                        <div className="min-w-0 bg-muted/10 lg:min-h-0">
                            <TyreDiagramBlock
                                unit="power"
                                sectionLabel="Power unit"
                                vehicleCode={vehicle.vehicle_code}
                                mapId={`power-${vehicle.id}`}
                                payload={tyreMap}
                                selection={selection}
                                onSelect={(code) => handleSelect("power", code)}
                                onContextMenuAction={handleContextMenuAction}
                                diagramHeight={powerDiagramHeight}
                            />

                            {hasTrailer && (
                                <>
                                    <Separator />
                                    <TyreDiagramBlock
                                        unit="trailer"
                                        sectionLabel="Attached trailer"
                                        vehicleCode={trailer!.vehicle_code}
                                        mapId={`trailer-${trailer!.id}`}
                                        payload={trailerTyreMap!}
                                        selection={selection}
                                        onSelect={(code) => handleSelect("trailer", code)}
                                        onContextMenuAction={handleContextMenuAction}
                                        diagramHeight={trailerDiagramHeight}
                                    />
                                </>
                            )}
                        </div>

                        <div
                            className="flex flex-col gap-2.5 p-3 lg:overflow-y-auto"
                            style={{ minHeight: rightPanelHeight }}
                        >
                            <Card className="shadow-none shrink-0">
                                <CardHeader className="pb-2 pt-0">
                                    <CardTitle className="text-sm font-medium">Selected position</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {selectedSlot && selection ? (
                                        <SelectedSlotDetails
                                            slot={selectedSlot}
                                            positionCode={selection.code}
                                        />
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Click a labelled position on the diagram or in the guide
                                            below.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            {allSpares.length > 0 && (
                                <Card className="shadow-none shrink-0">
                                    <CardHeader className="flex flex-row items-center justify-between pb-2 pt-0">
                                        <CardTitle className="text-sm font-medium">Spare tyres</CardTitle>
                                        <Badge variant="secondary">
                                            {counts.spares_filled}/{spareCapacity}
                                        </Badge>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        {allSpares.map((spare) => (
                                            <div
                                                key={spare.position}
                                                className="flex items-center gap-3 rounded-lg border border-dashed p-3"
                                            >
                                                <PositionBadge
                                                    code={spare.display_code || "?"}
                                                    empty={!spare.tyre_code}
                                                />
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {spare.tyre_code || "No spare assigned"}
                                                    </p>
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        {spare.owner_label}
                                                        {spare.serial_number
                                                            ? ` · ${spare.serial_number}`
                                                            : " · Open pocket"}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            )}

                            <Card className="shadow-none min-h-0 flex-1 flex flex-col">
                                <CardHeader className="pb-2 pt-0 shrink-0">
                                    <CardTitle className="text-sm font-medium">Tyre position guide</CardTitle>
                                </CardHeader>
                                <CardContent className="min-h-0 flex-1 p-0 pb-3">
                                    <ScrollArea className="h-full max-h-[280px] px-4 lg:max-h-none">
                                        <div className="space-y-4 pb-2">
                                            <GuideSection
                                                unit="power"
                                                unitLabel={`Power — ${vehicle.vehicle_code}`}
                                                groups={powerGuide}
                                                selection={selection}
                                                onSelect={handleSelect}
                                            />

                                            {trailer && trailerTyreMap && (
                                                <GuideSection
                                                    unit="trailer"
                                                    unitLabel={`Trailer — ${trailer.vehicle_code}`}
                                                    groups={trailerGuide}
                                                    selection={selection}
                                                    onSelect={handleSelect}
                                                />
                                            )}

                                            <Alert>
                                                <Info className="h-4 w-4" />
                                                <AlertDescription>
                                                    Standard tyre position guide. Spare wheels W and X
                                                    appear on the diagram when configured for this
                                                    vehicle type.
                                                </AlertDescription>
                                            </Alert>
                                        </div>
                                    </ScrollArea>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {movementFormProps && (
                <TyreMovementDialog
                    open={movementDialogOpen}
                    onOpenChange={setMovementDialogOpen}
                    tyreId={selectedTyreId}
                    tyres={movementFormProps.tyres}
                    stores={movementFormProps.stores}
                    powerVehicles={movementFormProps.powerVehicles}
                    trailers={movementFormProps.trailers}
                    destinationTypes={movementFormProps.destinationTypes}
                />
            )}

            {disposalFormProps && (
                <TyreDisposalDialog
                    open={disposalDialogOpen}
                    onOpenChange={setDisposalDialogOpen}
                    tyreId={selectedTyreId}
                    tyres={disposalFormProps.tyres}
                    disposalReasons={disposalFormProps.disposalReasons}
                />
            )}
        </>
    );
}

export { VehicleTyreMapPanel as TyreMapPanel };
