import { useEffect, useRef } from "react";
import { createTyreMap } from "@/lib/tyre-map-konva";
import { cn } from "@/lib/utils";

export type KonvaSlot = {
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
};

type TyreMapCanvasProps = {
    mapId: string;
    assetType: string;
    slots: KonvaSlot[];
    selectedPosition: string | null;
    onSelect: (code: string) => void;
    maxHeight?: number;
    fitMode?: "width" | "contain";
    className?: string;
};

export function TyreMapCanvas({
    mapId,
    assetType,
    slots,
    selectedPosition,
    onSelect,
    maxHeight = 560,
    fitMode = "width",
    className,
}: TyreMapCanvasProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<{ destroy: () => void; select: (code: string) => void; resize: () => void } | null>(null);

    useEffect(() => {
        if (!containerRef.current) {
            return;
        }

        mapRef.current = createTyreMap(containerRef.current, {
            slots,
            selectedPosition,
            assetType,
            onSelect,
            maxHeight,
            fitMode,
        });

        return () => {
            mapRef.current?.destroy();
            mapRef.current = null;
        };
    }, [mapId, assetType, slots, maxHeight, fitMode]);

    useEffect(() => {
        if (selectedPosition && mapRef.current) {
            mapRef.current.select(selectedPosition);
        }
    }, [selectedPosition]);

    useEffect(() => {
        mapRef.current?.resize();
    }, [maxHeight, fitMode]);

    return (
        <div
            ref={containerRef}
            data-tyre-map-konva
            className={cn("mx-auto block w-full max-w-none", className)}
            role="application"
            aria-label={`Tyre map ${mapId}`}
        />
    );
}
