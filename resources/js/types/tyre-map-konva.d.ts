declare module "@/lib/tyre-map-konva" {
    export function createTyreMap(
        container: HTMLElement,
        config: {
            slots: unknown[];
            selectedPosition?: string | null;
            assetType?: string | null;
            onSelect?: (code: string) => void;
            maxHeight?: number;
            fitMode?: "width" | "contain";
            maxScale?: number;
        },
    ): {
        destroy: () => void;
        select: (code: string) => void;
        resize: () => void;
    };
}
