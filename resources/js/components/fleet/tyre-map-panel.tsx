import { useMemo, useState } from "react";
import { Link } from "@inertiajs/react";
import { ChevronDown, Info } from "lucide-react";
import { ModernTyreMap, type KonvaSlot } from "@/components/fleet/modern-tyre-map";
import { TyreDisposalDialog } from "@/components/fleet/tyre-disposal-dialog";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { cn } from "@/lib/utils";

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
};

const TRUCK_GUIDE_DEFINITIONS = [
    { title: "Front axle", codes: ["A", "B"] },
    { title: "1st drive axle", codes: ["C", "D", "E", "F"] },
    { title: "2nd drive axle", codes: ["G", "H", "I", "J"] },
    { title: "Spare wheel", subtitle: "Between 1st and 2nd drive", codes: ["W"] },
    { title: "Tag axle", codes: ["K", "L", "M", "N"] },
    { title: "Spare wheel", subtitle: "Between tag and rear", codes: ["X"] },
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
            {groups.map((group) => (
                <Card key={`${unit}-${group.title}`} className="overflow-hidden shadow-none">
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
                                        <span className="hidden">
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
    mapId,
    payload,
    selection,
    onSelect,
    onMovementAction,
    onDisposalAction,
}: {
    unit: MapUnit;
    mapId: string;
    payload: TyreMapPayload;
    selection: MapSelection;
    onSelect: (code: string) => void;
    onMovementAction?: (slot: KonvaSlot) => void;
    onDisposalAction?: (slot: KonvaSlot) => void;
}) {
    if (payload.mapData.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col">
            <div className="w-full px-3 py-4">
                <ModernTyreMap
                    mapId={mapId}
                    assetType={payload.konvaConfig.assetType}
                    slots={payload.konvaConfig.slots}
                    selectedPosition={selection?.unit === unit ? selection.code : null}
                    onSelect={onSelect}
                    onMovementAction={onMovementAction}
                    onDisposalAction={onDisposalAction}
                    className="mx-auto max-w-[560px] border-0 shadow-none"
                />
            </div>
        </div>
    );
}

export function VehicleTyreMapPanel({
    vehicle,
    tyreMap,
}: VehicleTyreMapPanelProps) {
    const [selection, setSelection] = useState<MapSelection>(null);
    const [guideOpen, setGuideOpen] = useState(false);
    const [disposalDialogOpen, setDisposalDialogOpen] = useState(false);
    const [selectedTyreId, setSelectedTyreId] = useState<number | null>(null);

    const powerGuide = useMemo(
        () => buildGuideGroups(tyreMap.mapData, tyreMap.konvaConfig.assetType),
        [tyreMap],
    );

    const selectedSlot = useMemo(() => {
        if (!selection) {
            return null;
        }

        return findSelectedSlot(tyreMap, selection.code);
    }, [selection, tyreMap]);

    const handleSelect = (unit: MapUnit, code: string) => {
        setSelection({ unit, code });
    };

    const handleMovementAction = (slot: KonvaSlot) => {
        if (slot.create_movement_url) {
            window.location.assign(slot.create_movement_url);
        }
    };

    const handleDisposalAction = (slot: KonvaSlot) => {
        if (slot.tyre_id) {
            setSelectedTyreId(slot.tyre_id);
            setDisposalDialogOpen(true);
        }
    };

    if (tyreMap.mapData.length === 0) {
        return (
            <Card>
                <CardContent className="py-10 text-center text-sm text-muted-foreground">
                    No tyre positions configured for this vehicle.
                </CardContent>
            </Card>
        );
    }

    return (
        <>
        <Card>
            <CardContent className="p-0">
                <div className="grid lg:grid-cols-[minmax(0,1fr)_360px] lg:divide-x">
                    <div className="min-w-0 bg-white lg:min-h-0 dark:bg-background">
                        <TyreDiagramBlock
                            unit="power"
                            mapId={`power-${vehicle.id}`}
                            payload={tyreMap}
                            selection={selection}
                            onSelect={(code) => handleSelect("power", code)}
                            onMovementAction={handleMovementAction}
                            onDisposalAction={handleDisposalAction}
                        />

                    </div>

                    <div className="flex flex-col gap-2.5 p-3 lg:overflow-y-auto">
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

                        <Collapsible open={guideOpen} onOpenChange={setGuideOpen}>
                            <Card className="shadow-none">
                            <CardHeader className="pb-2 pt-0">
                                <CollapsibleTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="h-auto w-full justify-between p-0 text-left hover:bg-transparent"
                                    >
                                        <CardTitle className="text-sm font-medium">Tyre position guide</CardTitle>
                                        <ChevronDown
                                            className={cn(
                                                "h-4 w-4 text-muted-foreground transition-transform",
                                                guideOpen && "rotate-180",
                                            )}
                                        />
                                    </Button>
                                </CollapsibleTrigger>
                            </CardHeader>
                            <CollapsibleContent>
                                <CardContent className="p-0 pb-3">
                                    <div className="px-4">
                                    <div className="space-y-4 pb-2">
                                        <GuideSection
                                            unit="power"
                                            unitLabel={`Vehicle — ${vehicle.vehicle_code}`}
                                            groups={powerGuide}
                                            selection={selection}
                                            onSelect={handleSelect}
                                        />

                                        <Alert>
                                            <Info className="h-4 w-4" />
                                            <AlertDescription>
                                                Standard tyre position guide. Spare wheels W and X
                                                appear on the diagram when configured for this
                                                vehicle type.
                                            </AlertDescription>
                                        </Alert>
                                    </div>
                                    </div>
                                </CardContent>
                            </CollapsibleContent>
                            </Card>
                        </Collapsible>
                    </div>
                </div>
            </CardContent>
        </Card>

        <TyreDisposalDialog
            open={disposalDialogOpen}
            onOpenChange={setDisposalDialogOpen}
            tyreId={selectedTyreId}
        />
        </>
    );
}

export { VehicleTyreMapPanel as TyreMapPanel };
