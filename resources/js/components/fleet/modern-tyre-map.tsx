import { useEffect, useMemo, useRef, useState } from "react";
import Konva from "konva";
import { Clipboard, Eye, Gauge, MoveRight, Ruler, X } from "lucide-react";
import { cn } from "@/lib/utils";

export type ModernTyreSlot = {
    code: string;
    display_code: string;
    label: string;
    axle?: number | null;
    side?: string | null;
    dual?: string | null;
    x?: number;
    y?: number;
    tyre_code?: string | null;
    color: string;
    estimated_remaining_percentage?: number | null;
    calculated_remaining_percentage?: number | null;
    latest_audited_remaining_percentage?: number | null;
    effective_remaining_percentage?: number | null;
    is_audited?: boolean | null;
    usage_status?: string | null;
    baseline_required?: boolean | null;
    tyre_id?: number | null;
    vehicle_id?: number | null;
    is_spare_position?: boolean;
    view_tyre_url?: string | null;
    create_movement_url?: string | null;
    create_baseline_url?: string | null;
    view_baseline_url?: string | null;
    record_audit_url?: string | null;
    record_km_url?: string | null;
};

export type { ModernTyreSlot as KonvaSlot };

type ModernTyreMapProps = {
    mapId: string;
    assetType: string;
    slots: ModernTyreSlot[];
    selectedPosition: string | null;
    onSelect: (code: string) => void;
    onContextMenuAction?: (action: string, slot: ModernTyreSlot, x?: number, y?: number) => void;
    showLegend?: boolean;
    className?: string;
};

type TyreSide = "left" | "right" | "center";
type TyreDual = "single" | "outer" | "inner";

type PositionedTyre = {
    slot: ModernTyreSlot;
    x: number;
    y: number;
    width: number;
    height: number;
    side: TyreSide;
    dual: TyreDual;
    isSpare: boolean;
};

type AxleGroup = {
    axle: number;
    y: number;
    left: PositionedTyre[];
    right: PositionedTyre[];
};

type VehicleMapLayout = {
    width: number;
    height: number;
    body: {
        x: number;
        y: number;
        width: number;
        height: number;
        cabHeight: number;
        isTrailer: boolean;
    };
    axleGroups: AxleGroup[];
    spares: PositionedTyre[];
};

type TyreContextMenuState = {
    slot: ModernTyreSlot;
    x: number;
    y: number;
} | null;

const TRUCK_SLOTS: ModernTyreSlot[] = [
    { code: "A", display_code: "A", label: "Front Left", axle: 1, side: "left", dual: "single", color: "gray" },
    { code: "B", display_code: "B", label: "Front Right", axle: 1, side: "right", dual: "single", color: "gray" },
    { code: "C", display_code: "C", label: "1st Drive L Outer", axle: 2, side: "left", dual: "outer", color: "gray" },
    { code: "D", display_code: "D", label: "1st Drive L Inner", axle: 2, side: "left", dual: "inner", color: "gray" },
    { code: "E", display_code: "E", label: "1st Drive R Inner", axle: 2, side: "right", dual: "inner", color: "gray" },
    { code: "F", display_code: "F", label: "1st Drive R Outer", axle: 2, side: "right", dual: "outer", color: "gray" },
    { code: "G", display_code: "G", label: "2nd Drive L Outer", axle: 3, side: "left", dual: "outer", color: "gray" },
    { code: "H", display_code: "H", label: "2nd Drive L Inner", axle: 3, side: "left", dual: "inner", color: "gray" },
    { code: "I", display_code: "I", label: "2nd Drive R Inner", axle: 3, side: "right", dual: "inner", color: "gray" },
    { code: "J", display_code: "J", label: "2nd Drive R Outer", axle: 3, side: "right", dual: "outer", color: "gray" },
    { code: "W", display_code: "W", label: "Spare Wheel", axle: 3.5, side: "center", dual: "single", color: "gray" },
    { code: "K", display_code: "K", label: "Tag Left Outer", axle: 4, side: "left", dual: "outer", color: "gray" },
    { code: "L", display_code: "L", label: "Tag Left Inner", axle: 4, side: "left", dual: "inner", color: "gray" },
    { code: "M", display_code: "M", label: "Tag Right Inner", axle: 4, side: "right", dual: "inner", color: "gray" },
    { code: "N", display_code: "N", label: "Tag Right Outer", axle: 4, side: "right", dual: "outer", color: "gray" },
    { code: "X", display_code: "X", label: "Spare Wheel", axle: 4.5, side: "center", dual: "single", color: "gray" },
    { code: "O", display_code: "O", label: "Rear Left Outer", axle: 5, side: "left", dual: "outer", color: "gray" },
    { code: "P", display_code: "P", label: "Rear Left Inner", axle: 5, side: "left", dual: "inner", color: "gray" },
    { code: "Q", display_code: "Q", label: "Rear Right Inner", axle: 5, side: "right", dual: "inner", color: "gray" },
    { code: "R", display_code: "R", label: "Rear Right Outer", axle: 5, side: "right", dual: "outer", color: "gray" },
    { code: "S", display_code: "S", label: "Rear Left Outer Rear", axle: 6, side: "left", dual: "outer", color: "gray" },
    { code: "T", display_code: "T", label: "Rear Left Inner Rear", axle: 6, side: "left", dual: "inner", color: "gray" },
    { code: "U", display_code: "U", label: "Rear Right Inner Rear", axle: 6, side: "right", dual: "inner", color: "gray" },
    { code: "V", display_code: "V", label: "Rear Right Outer Rear", axle: 6, side: "right", dual: "outer", color: "gray" },
];

const TRAILER_SLOTS: ModernTyreSlot[] = [
    { code: "A", display_code: "A", label: "Axle 1 L Outer", axle: 1, side: "left", dual: "outer", color: "gray" },
    { code: "B", display_code: "B", label: "Axle 1 L Inner", axle: 1, side: "left", dual: "inner", color: "gray" },
    { code: "C", display_code: "C", label: "Axle 1 R Inner", axle: 1, side: "right", dual: "inner", color: "gray" },
    { code: "D", display_code: "D", label: "Axle 1 R Outer", axle: 1, side: "right", dual: "outer", color: "gray" },
    { code: "E", display_code: "E", label: "Axle 2 L Outer", axle: 2, side: "left", dual: "outer", color: "gray" },
    { code: "F", display_code: "F", label: "Axle 2 L Inner", axle: 2, side: "left", dual: "inner", color: "gray" },
    { code: "G", display_code: "G", label: "Axle 2 R Inner", axle: 2, side: "right", dual: "inner", color: "gray" },
    { code: "H", display_code: "H", label: "Axle 2 R Outer", axle: 2, side: "right", dual: "outer", color: "gray" },
    { code: "I", display_code: "I", label: "Axle 3 L Outer", axle: 3, side: "left", dual: "outer", color: "gray" },
    { code: "J", display_code: "J", label: "Axle 3 L Inner", axle: 3, side: "left", dual: "inner", color: "gray" },
    { code: "K", display_code: "K", label: "Axle 3 R Inner", axle: 3, side: "right", dual: "inner", color: "gray" },
    { code: "L", display_code: "L", label: "Axle 3 R Outer", axle: 3, side: "right", dual: "outer", color: "gray" },
    { code: "X", display_code: "X", label: "Spare Wheel", axle: 2.5, side: "center", dual: "single", color: "gray" },
];

const LONG_LAYOUT_CODES = new Set(["W", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V"]);
const COMPACT_TRAILER_SLOT_COUNT = TRAILER_SLOTS.length;

const GEOMETRY = {
    width: 700,
    tyreWidth: 56,
    tyreHeight: 58,
    tyreGap: 7,
    spareWidth: 104,
    spareHeight: 50,
    bodyWidth: 224,
    sideGap: 10,
    topPadding: 22,
    bottomPadding: 28,
    cabHeight: 112,
    axleSpacing: 96,
    firstTruckAxleY: 132,
    firstTrailerAxleY: 96,
};

const LEGEND = [
    { label: "Good", color: "green" },
    { label: "Watch", color: "yellow" },
    { label: "Low", color: "orange" },
    { label: "End of Life", color: "red" },
    { label: "No Baseline", color: "gray", baselineRequired: true },
    { label: "Empty", color: "empty" },
];

function visibleCode(slot: Pick<ModernTyreSlot, "code" | "display_code">): string {
    return String(slot.display_code || slot.code);
}

function normalizeSide(side?: string | null): TyreSide {
    if (side === "center") {
        return "center";
    }

    return side === "right" ? "right" : "left";
}

function normalizeDual(dual?: string | null): TyreDual {
    if (dual === "inner" || dual === "outer") {
        return dual;
    }

    return "single";
}

function isSpareSlot(slot: ModernTyreSlot): boolean {
    const code = visibleCode(slot);

    return Boolean(slot.is_spare_position) || normalizeSide(slot.side) === "center" || code === "W" || code === "X" || /spare/i.test(slot.label);
}

function mergeTemplateSlots(slots: ModernTyreSlot[], assetType: string): ModernTyreSlot[] {
    const displayCodes = new Set(slots.map((slot) => visibleCode(slot)));
    const usesLongLayout =
        slots.length > COMPACT_TRAILER_SLOT_COUNT ||
        Array.from(displayCodes).some((code) => LONG_LAYOUT_CODES.has(code));
    const template = assetType === "trailer" && !usesLongLayout ? TRAILER_SLOTS : TRUCK_SLOTS;
    const visibleTemplate = template.filter((slot) => displayCodes.has(slot.display_code));
    const baseSlots = visibleTemplate.length > 0 ? visibleTemplate : template;

    return baseSlots.map((base) => {
        const slotData = slots.find(
            (slot) => slot.code === base.code || visibleCode(slot) === base.display_code,
        );

        return {
            ...base,
            ...slotData,
            code: slotData?.code || base.code,
            display_code: slotData?.display_code || base.display_code,
            label: slotData?.label || base.label,
            axle: slotData?.axle ?? base.axle,
            side: slotData?.side ?? base.side,
            dual: slotData?.dual ?? base.dual,
            tyre_code: slotData?.tyre_code || null,
            color: slotData?.color || "gray",
        };
    });
}

function dualSortValue(slot: ModernTyreSlot): number {
    const side = normalizeSide(slot.side);
    const dual = normalizeDual(slot.dual);

    if (dual === "single") {
        return 0;
    }

    if (side === "left") {
        return dual === "outer" ? 0 : 1;
    }

    return dual === "inner" ? 0 : 1;
}

function buildAxleGroups(slots: ModernTyreSlot[]): Array<Omit<AxleGroup, "y">> {
    const grouped = new Map<number, { axle: number; left: PositionedTyre[]; right: PositionedTyre[] }>();

    slots.filter((slot) => !isSpareSlot(slot)).forEach((slot, index) => {
        const axle = Number(slot.axle ?? Math.floor(index / 4) + 1);
        const side = normalizeSide(slot.side);
        const tyre: PositionedTyre = {
            slot,
            x: 0,
            y: 0,
            width: GEOMETRY.tyreWidth,
            height: GEOMETRY.tyreHeight,
            side,
            dual: normalizeDual(slot.dual),
            isSpare: false,
        };

        if (!grouped.has(axle)) {
            grouped.set(axle, { axle, left: [], right: [] });
        }

        grouped.get(axle)![side === "right" ? "right" : "left"].push(tyre);
    });

    return Array.from(grouped.values())
        .sort((a, b) => a.axle - b.axle)
        .map((group) => ({
            ...group,
            left: group.left.sort((a, b) => dualSortValue(a.slot) - dualSortValue(b.slot)),
            right: group.right.sort((a, b) => dualSortValue(a.slot) - dualSortValue(b.slot)),
        }));
}

function calculateAxleYPositions(
    axleGroups: Array<Omit<AxleGroup, "y">>,
    isTrailer: boolean,
): Map<number, number> {
    const yPositions = new Map<number, number>();
    const firstY = isTrailer ? GEOMETRY.firstTrailerAxleY : GEOMETRY.firstTruckAxleY;

    axleGroups.forEach((group, index) => {
        yPositions.set(group.axle, firstY + index * GEOMETRY.axleSpacing);
    });

    return yPositions;
}

function calculateTyreBoxPositions(group: Omit<AxleGroup, "y">, y: number): AxleGroup {
    const centerX = GEOMETRY.width / 2;
    const bodyHalf = GEOMETRY.bodyWidth / 2;
    const innerLeftX = centerX - bodyHalf - GEOMETRY.sideGap - GEOMETRY.tyreWidth / 2;
    const innerRightX = centerX + bodyHalf + GEOMETRY.sideGap + GEOMETRY.tyreWidth / 2;

    const positionLeft = group.left.map((tyre, index, tyres) => ({
        ...tyre,
        x: innerLeftX - (tyres.length - 1 - index) * (GEOMETRY.tyreWidth + GEOMETRY.tyreGap),
        y,
    }));
    const positionRight = group.right.map((tyre, index) => ({
        ...tyre,
        x: innerRightX + index * (GEOMETRY.tyreWidth + GEOMETRY.tyreGap),
        y,
    }));

    return {
        axle: group.axle,
        y,
        left: positionLeft,
        right: positionRight,
    };
}

function calculateSpareY(slot: ModernTyreSlot, axleGroups: AxleGroup[]): number {
    const axle = Number(slot.axle ?? 0);
    const before = axleGroups.filter((group) => group.axle <= Math.floor(axle)).at(-1);
    const after = axleGroups.find((group) => group.axle >= Math.ceil(axle));

    if (before && after && before.axle !== after.axle) {
        return (before.y + after.y) / 2;
    }

    if (before) {
        return before.y + GEOMETRY.axleSpacing / 2;
    }

    if (after) {
        return after.y - GEOMETRY.axleSpacing / 2;
    }

    return GEOMETRY.firstTruckAxleY + GEOMETRY.axleSpacing;
}

function calculateVehicleMapLayout(slots: ModernTyreSlot[], assetType: string): VehicleMapLayout {
    const isTrailer = assetType === "trailer" && slots.length <= COMPACT_TRAILER_SLOT_COUNT;
    const axleGroupsWithoutY = buildAxleGroups(slots);
    const axleYPositions = calculateAxleYPositions(axleGroupsWithoutY, isTrailer);
    const axleGroups = axleGroupsWithoutY.map((group) =>
        calculateTyreBoxPositions(group, axleYPositions.get(group.axle) ?? GEOMETRY.firstTruckAxleY),
    );
    const spares = slots.filter(isSpareSlot).map((slot) => ({
        slot,
        x: GEOMETRY.width / 2,
        y: calculateSpareY(slot, axleGroups),
        width: GEOMETRY.spareWidth,
        height: GEOMETRY.spareHeight,
        side: "center" as TyreSide,
        dual: "single" as TyreDual,
        isSpare: true,
    }));
    const firstAxleY = axleGroups[0]?.y ?? (isTrailer ? GEOMETRY.firstTrailerAxleY : GEOMETRY.firstTruckAxleY);
    const lastAxleY = axleGroups.at(-1)?.y ?? firstAxleY;
    const bodyTop = isTrailer ? GEOMETRY.topPadding : GEOMETRY.topPadding + 10;
    const bodyBottom = lastAxleY + GEOMETRY.tyreHeight / 2 + 24;
    const bodyHeight = Math.max(bodyBottom - bodyTop, isTrailer ? 260 : 360);
    const height = Math.round(bodyTop + bodyHeight + GEOMETRY.bottomPadding);

    return {
        width: GEOMETRY.width,
        height,
        body: {
            x: (GEOMETRY.width - GEOMETRY.bodyWidth) / 2,
            y: bodyTop,
            width: GEOMETRY.bodyWidth,
            height: bodyHeight,
            cabHeight: isTrailer ? 28 : GEOMETRY.cabHeight,
            isTrailer,
        },
        axleGroups,
        spares,
    };
}

function getStatusStyle(slot: ModernTyreSlot, isSpare = false) {
    const hasTyre = Boolean(slot.tyre_code);

    if (!hasTyre) {
        return {
            fill: "#ffffff",
            stroke: "#cbd5e1",
            text: "#94a3b8",
            subText: "#94a3b8",
            shadowOpacity: 0,
            dash: [7, 5],
        };
    }

    if (isSpare) {
        return {
            fill: "#f8fafc",
            stroke: "#cbd5e1",
            text: "#17365f",
            subText: "#64748b",
            shadowOpacity: 0.08,
            dash: undefined,
        };
    }

    if (slot.baseline_required) {
        return {
            fill: "#f8fafc",
            stroke: "#94a3b8",
            text: "#334155",
            subText: "#64748b",
            shadowOpacity: 0.08,
            dash: undefined,
        };
    }

    const styles: Record<string, { fill: string; stroke: string; text: string; subText: string }> = {
        green: { fill: "#ecfdf3", stroke: "#86efac", text: "#14532d", subText: "#166534" },
        blue: { fill: "#eff6ff", stroke: "#93c5fd", text: "#1e3a8a", subText: "#1d4ed8" },
        yellow: { fill: "#fffbeb", stroke: "#fcd34d", text: "#78350f", subText: "#92400e" },
        orange: { fill: "#fff7ed", stroke: "#fdba74", text: "#7c2d12", subText: "#c2410c" },
        red: { fill: "#fef2f2", stroke: "#fca5a5", text: "#7f1d1d", subText: "#b91c1c" },
        black: { fill: "#fff1f2", stroke: "#991b1b", text: "#7f1d1d", subText: "#7f1d1d" },
        gray: { fill: "#f8fafc", stroke: "#cbd5e1", text: "#334155", subText: "#64748b" },
    };

    const style = styles[slot.color] || styles.gray;

    return {
        ...style,
        shadowOpacity: 0.12,
        dash: undefined,
    };
}

function renderVehicleBody(layer: Konva.Layer, layout: VehicleMapLayout) {
    const { body } = layout;

    layer.add(
        new Konva.Rect({
            x: body.x,
            y: body.y,
            width: body.width,
            height: body.height,
            cornerRadius: 20,
            fillLinearGradientStartPoint: { x: body.x, y: body.y },
            fillLinearGradientEndPoint: { x: body.x, y: body.y + body.height },
            fillLinearGradientColorStops: [0, "#f8fafc", 1, "#edf3f8"],
            stroke: "#b9c7d6",
            strokeWidth: 4,
            shadowColor: "#64748b",
            shadowBlur: 18,
            shadowOpacity: 0.16,
            shadowOffsetY: 8,
        }),
    );

    layer.add(
        new Konva.Line({
            points: [GEOMETRY.width / 2, body.y + 8, GEOMETRY.width / 2, body.y + body.height - 8],
            stroke: "#cbd5e1",
            strokeWidth: 3,
            dash: [14, 14],
            opacity: 0.9,
        }),
    );

    if (body.isTrailer) {
        layer.add(
            new Konva.Rect({
                x: body.x + 18,
                y: body.y + 16,
                width: body.width - 36,
                height: 18,
                cornerRadius: 8,
                fill: "#e2e8f0",
                stroke: "#b9c7d6",
                strokeWidth: 2,
            }),
        );
        return;
    }

    layer.add(
        new Konva.Rect({
            x: body.x + 16,
            y: body.y + 12,
            width: body.width - 32,
            height: body.cabHeight,
            cornerRadius: 16,
            fill: "#e5edf5",
            stroke: "#9fb0c3",
            strokeWidth: 4,
        }),
    );

    layer.add(
        new Konva.Rect({
            x: body.x + 34,
            y: body.y + 30,
            width: body.width - 68,
            height: 46,
            cornerRadius: 6,
            fill: "#f8fafc",
            stroke: "#cbd5e1",
            strokeWidth: 2,
        }),
    );

    layer.add(
        new Konva.Line({
            points: [body.x + body.width / 2, body.y + 30, body.x + body.width / 2, body.y + 76],
            stroke: "#dbe4ee",
            strokeWidth: 2,
        }),
    );

    [
        { x: body.x - 16, y: body.y + 40 },
        { x: body.x + body.width + 4, y: body.y + 40 },
    ].forEach((mirror) => {
        layer.add(
            new Konva.Rect({
                ...mirror,
                width: 12,
                height: 28,
                cornerRadius: 4,
                fill: "#d8e2ec",
                stroke: "#9fb0c3",
                strokeWidth: 2,
            }),
        );
    });
}

function renderAxleLine(layer: Konva.Layer, axle: AxleGroup) {
    const leftInner = axle.left.at(-1);
    const rightInner = axle.right[0];
    const startX = leftInner ? leftInner.x + leftInner.width / 2 : GEOMETRY.width / 2 - GEOMETRY.bodyWidth / 2;
    const endX = rightInner ? rightInner.x - rightInner.width / 2 : GEOMETRY.width / 2 + GEOMETRY.bodyWidth / 2;

    layer.add(
        new Konva.Line({
            points: [startX, axle.y, endX, axle.y],
            stroke: "#8ea0b4",
            strokeWidth: 7,
            lineCap: "round",
        }),
    );

    layer.add(
        new Konva.Circle({
            x: GEOMETRY.width / 2,
            y: axle.y,
            radius: 9,
            fill: "#64748b",
            stroke: "#f8fafc",
            strokeWidth: 2,
        }),
    );
}

function tyreSubtitle(slot: ModernTyreSlot, isSpare: boolean): string {
    if (!slot.tyre_code) {
        return "";
    }

    if (isSpare) {
        return "Spare";
    }

    if (slot.baseline_required) {
        return "No Base";
    }

    if (typeof slot.estimated_remaining_percentage === "number") {
        return `${Math.round(slot.estimated_remaining_percentage)}%`;
    }

    return "Mounted";
}

function renderTyreBox(
    layer: Konva.Layer,
    tyre: PositionedTyre,
    selectedPosition: string | null,
    onSelect: (code: string) => void,
    onContextMenuAction?: (action: string, slot: ModernTyreSlot, x?: number, y?: number) => void,
) {
    const { slot } = tyre;
    const style = getStatusStyle(slot, false);
    const isSelected = selectedPosition === slot.code;
    const group = new Konva.Group({
        x: tyre.x - tyre.width / 2,
        y: tyre.y - tyre.height / 2,
        width: tyre.width,
        height: tyre.height,
        listening: true,
    });

    group.add(
        new Konva.Rect({
            width: tyre.width,
            height: tyre.height,
            cornerRadius: 12,
            fill: style.fill,
            stroke: isSelected ? "#17365f" : style.stroke,
            strokeWidth: isSelected ? 3 : 2,
            dash: style.dash,
            shadowColor: "#0f172a",
            shadowBlur: style.shadowOpacity ? 10 : 0,
            shadowOpacity: style.shadowOpacity,
            shadowOffsetY: style.shadowOpacity ? 4 : 0,
        }),
    );

    group.add(
        new Konva.Text({
            x: 0,
            y: slot.tyre_code ? 10 : 16,
            width: tyre.width,
            text: slot.display_code,
            align: "center",
            fontSize: 15,
            fontStyle: "700",
            fill: style.text,
        }),
    );

    const subtitle = tyreSubtitle(slot, false);
    if (subtitle) {
        group.add(
            new Konva.Text({
                x: 0,
                y: 29,
                width: tyre.width,
                text: subtitle,
                align: "center",
                fontSize: subtitle.length > 5 ? 9 : 11,
                fontStyle: "600",
                fill: style.subText,
            }),
        );
    }

    if (slot.is_audited) {
        group.add(
            new Konva.Circle({
                x: tyre.width - 10,
                y: 10,
                radius: 6,
                fill: "#17365f",
            }),
        );
        group.add(
            new Konva.Text({
                x: tyre.width - 15,
                y: 4,
                width: 10,
                text: "A",
                align: "center",
                fontSize: 9,
                fontStyle: "700",
                fill: "#ffffff",
            }),
        );
    }

    group.on("click tap", () => onSelect(slot.code));
    group.on("dblclick dbltap", () => {
        openUrl(slot.tyre_code ? slot.view_tyre_url : slot.create_movement_url);
    });
    group.on("mouseenter", () => {
        const stage = group.getStage();
        if (stage) {
            stage.container().style.cursor = "pointer";
        }
    });
    group.on("mouseleave", () => {
        const stage = group.getStage();
        if (stage) {
            stage.container().style.cursor = "default";
        }
    });
    group.on("contextmenu", (event) => {
        event.evt.preventDefault();
        onSelect(slot.code);
        onContextMenuAction?.("context", slot, event.evt.clientX, event.evt.clientY);
    });

    layer.add(group);
}

function renderSpareTyre(
    layer: Konva.Layer,
    tyre: PositionedTyre,
    selectedPosition: string | null,
    onSelect: (code: string) => void,
    onContextMenuAction?: (action: string, slot: ModernTyreSlot, x?: number, y?: number) => void,
) {
    const { slot } = tyre;
    const style = getStatusStyle(slot, true);
    const isSelected = selectedPosition === slot.code;
    const group = new Konva.Group({
        x: tyre.x - tyre.width / 2,
        y: tyre.y - tyre.height / 2,
        width: tyre.width,
        height: tyre.height,
        listening: true,
    });

    group.add(
        new Konva.Rect({
            width: tyre.width,
            height: tyre.height,
            cornerRadius: 14,
            fill: style.fill,
            stroke: isSelected ? "#17365f" : style.stroke,
            strokeWidth: isSelected ? 3 : 2,
            dash: slot.tyre_code ? undefined : [7, 5],
            shadowColor: "#0f172a",
            shadowBlur: slot.tyre_code ? 10 : 0,
            shadowOpacity: slot.tyre_code ? 0.08 : 0,
            shadowOffsetY: slot.tyre_code ? 4 : 0,
        }),
    );

    group.add(
        new Konva.Text({
            x: 0,
            y: 8,
            width: tyre.width,
            text: slot.display_code,
            align: "center",
            fontSize: 16,
            fontStyle: "700",
            fill: style.text,
        }),
    );

    group.add(
        new Konva.Text({
            x: 0,
            y: 28,
            width: tyre.width,
            text: "Spare",
            align: "center",
            fontSize: 10,
            fontStyle: "600",
            fill: style.subText,
        }),
    );

    group.on("click tap", () => onSelect(slot.code));
    group.on("dblclick dbltap", () => {
        openUrl(slot.tyre_code ? slot.view_tyre_url : slot.create_movement_url);
    });
    group.on("mouseenter", () => {
        const stage = group.getStage();
        if (stage) {
            stage.container().style.cursor = "pointer";
        }
    });
    group.on("mouseleave", () => {
        const stage = group.getStage();
        if (stage) {
            stage.container().style.cursor = "default";
        }
    });
    group.on("contextmenu", (event) => {
        event.evt.preventDefault();
        onSelect(slot.code);
        onContextMenuAction?.("context", slot, event.evt.clientX, event.evt.clientY);
    });

    layer.add(group);
}

function renderLegend() {
    return (
        <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 border-t border-slate-100 px-3 py-2 text-[11px] text-slate-600">
            {LEGEND.map((item) => {
                const style =
                    item.color === "empty"
                        ? getStatusStyle({ code: "", display_code: "", label: "", color: "gray", tyre_code: null })
                        : getStatusStyle({
                              code: "",
                              display_code: "",
                              label: "",
                              color: item.color,
                              tyre_code: "mounted",
                              baseline_required: item.baselineRequired,
                          });

                return (
                    <span key={item.label} className="inline-flex items-center gap-1.5">
                        <span
                            className="h-2.5 w-2.5 rounded-sm border"
                            style={{
                                backgroundColor: style.fill,
                                borderColor: style.stroke,
                                borderStyle: style.dash ? "dashed" : "solid",
                            }}
                        />
                        {item.label}
                    </span>
                );
            })}
        </div>
    );
}

function clampMenuPosition(x: number, y: number) {
    const width = 220;
    const height = 240;

    return {
        x: Math.min(Math.max(8, x), window.innerWidth - width - 8),
        y: Math.min(Math.max(8, y), window.innerHeight - height - 8),
    };
}

function openUrl(url?: string | null) {
    if (url) {
        window.location.href = url;
    }
}

function TyreActionMenu({
    menu,
    onClose,
}: {
    menu: NonNullable<TyreContextMenuState>;
    onClose: () => void;
}) {
    const menuRef = useRef<HTMLDivElement>(null);
    const hasTyre = Boolean(menu.slot.tyre_code);
    const isSpare = isSpareSlot(menu.slot);
    const position = clampMenuPosition(menu.x, menu.y);

    useEffect(() => {
        const handlePointerDown = (event: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                onClose();
            }
        };
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                onClose();
            }
        };

        document.addEventListener("mousedown", handlePointerDown);
        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.removeEventListener("mousedown", handlePointerDown);
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [onClose]);

    const run = (callback: () => void) => {
        callback();
        onClose();
    };

    const copyTyreCode = () => {
        if (menu.slot.tyre_code) {
            void navigator.clipboard?.writeText(menu.slot.tyre_code);
        }
    };

    return (
        <div
            ref={menuRef}
            className="fixed z-50 w-56 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-lg"
            style={{ left: position.x, top: position.y }}
        >
            <div className="border-b px-3 py-2">
                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    Position {menu.slot.display_code}
                </p>
                <p className="truncate text-sm font-semibold">
                    {hasTyre ? menu.slot.tyre_code : isSpare ? "Empty spare pocket" : "Empty position"}
                </p>
            </div>
            <div className="p-1">
                {hasTyre && (
                    <button
                        type="button"
                        className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent"
                        onClick={() => run(() => openUrl(menu.slot.view_tyre_url))}
                    >
                        <Eye className="h-4 w-4" />
                        View Tyre
                    </button>
                )}
                {hasTyre && menu.slot.record_audit_url && (
                    <button
                        type="button"
                        className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent"
                        onClick={() => run(() => openUrl(menu.slot.record_audit_url))}
                    >
                        <Gauge className="h-4 w-4" />
                        Record Condition Audit
                    </button>
                )}
                <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={!menu.slot.create_movement_url}
                    onClick={() => run(() => openUrl(menu.slot.create_movement_url))}
                >
                    <MoveRight className="h-4 w-4" />
                    {hasTyre ? (isSpare ? "Create Movement from Spare" : "Create Movement") : "Mount Tyre Here"}
                </button>
                {hasTyre && (
                    <button
                        type="button"
                        className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                        disabled={!menu.slot.create_baseline_url && !menu.slot.view_baseline_url}
                        onClick={() =>
                            run(() => openUrl(menu.slot.view_baseline_url || menu.slot.create_baseline_url))
                        }
                    >
                        <Ruler className="h-4 w-4" />
                        {menu.slot.baseline_required ? "Set Baseline" : "View/Edit Baseline"}
                    </button>
                )}
                <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={!menu.slot.record_km_url}
                    onClick={() => run(() => openUrl(menu.slot.record_km_url))}
                >
                    <Gauge className="h-4 w-4" />
                    Record Vehicle KM
                </button>
                {hasTyre && (
                    <>
                        <div className="my-1 h-px bg-border" />
                        <button
                            type="button"
                            className="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent"
                            onClick={() => run(copyTyreCode)}
                        >
                            <Clipboard className="h-4 w-4" />
                            Copy Tyre Code
                        </button>
                    </>
                )}
                <button
                    type="button"
                    className="mt-1 flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-xs text-muted-foreground hover:bg-accent"
                    onClick={onClose}
                >
                    <X className="h-3.5 w-3.5" />
                    Close
                </button>
            </div>
        </div>
    );
}

export function ModernTyreMap({
    mapId,
    assetType,
    slots,
    selectedPosition,
    onSelect,
    onContextMenuAction,
    showLegend = true,
    className,
}: ModernTyreMapProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const stageRef = useRef<HTMLDivElement>(null);
    const [containerWidth, setContainerWidth] = useState(0);
    const normalizedSlots = useMemo(() => mergeTemplateSlots(slots, assetType), [slots, assetType]);
    const [contextMenu, setContextMenu] = useState<TyreContextMenuState>(null);
    const layout = useMemo(
        () => calculateVehicleMapLayout(normalizedSlots, assetType),
        [normalizedSlots, assetType],
    );

    useEffect(() => {
        const node = containerRef.current;
        if (!node) {
            return undefined;
        }

        const updateWidth = () => setContainerWidth(node.clientWidth || layout.width);
        updateWidth();

        const resizeObserver = new ResizeObserver(updateWidth);
        resizeObserver.observe(node);

        return () => resizeObserver.disconnect();
    }, [layout.width]);

    useEffect(() => {
        const node = stageRef.current;
        if (!node) {
            return undefined;
        }

        node.innerHTML = "";

        const availableWidth = Math.max(320, containerWidth || layout.width);
        const renderWidth = Math.min(layout.width, availableWidth);
        const scale = renderWidth / layout.width;
        const stage = new Konva.Stage({
            container: node,
            width: renderWidth,
            height: layout.height * scale,
        });
        const layer = new Konva.Layer({ scaleX: scale, scaleY: scale });
        const handleContextMenu = (action: string, slot: ModernTyreSlot, x?: number, y?: number) => {
            if (action === "context" && x != null && y != null) {
                setContextMenu({ slot, x, y });
            }

            onContextMenuAction?.(action, slot, x, y);
        };

        renderVehicleBody(layer, layout);
        layout.axleGroups.forEach((axle) => renderAxleLine(layer, axle));
        layout.spares.forEach((spare) =>
            renderSpareTyre(layer, spare, selectedPosition, onSelect, handleContextMenu),
        );
        layout.axleGroups.forEach((axle) => {
            [...axle.left, ...axle.right].forEach((tyre) =>
                renderTyreBox(layer, tyre, selectedPosition, onSelect, handleContextMenu),
            );
        });

        stage.add(layer);
        layer.draw();

        return () => {
            stage.destroy();
        };
    }, [containerWidth, layout, onContextMenuAction, onSelect, selectedPosition]);

    return (
        <div
            ref={containerRef}
            className={cn(
                "w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm",
                className,
            )}
            role="application"
            aria-label={`Tyre map ${mapId}`}
        >
            <div ref={stageRef} className="flex justify-center px-2 py-2" />
            {showLegend && renderLegend()}
            {contextMenu && <TyreActionMenu menu={contextMenu} onClose={() => setContextMenu(null)} />}
        </div>
    );
}
