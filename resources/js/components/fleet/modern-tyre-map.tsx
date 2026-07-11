import { useMemo, useState, useRef, useEffect } from "react";
import { cn } from "@/lib/utils";
import { ArrowRight, Trash2, Eye } from "lucide-react";

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
};

export type { ModernTyreSlot as KonvaSlot };

type ModernTyreMapProps = {
    mapId: string;
    assetType: string;
    slots: ModernTyreSlot[];
    selectedPosition: string | null;
    onSelect: (code: string) => void;
    onContextMenuAction?: (action: string, slot: ModernTyreSlot, x?: number, y?: number) => void;
    className?: string;
};

const TRUCK_SLOTS = [
    { code: "A", display_code: "A", label: "Front Left", x: 20, y: 12, axle: 1, side: "left" },
    { code: "B", display_code: "B", label: "Front Right", x: 68, y: 12, axle: 1, side: "right" },
    { code: "C", display_code: "C", label: "1st Drive L1", x: 16, y: 26, axle: 2, side: "left" },
    { code: "D", display_code: "D", label: "1st Drive L2", x: 24, y: 26, axle: 2, side: "left" },
    { code: "E", display_code: "E", label: "1st Drive R1", x: 64, y: 26, axle: 2, side: "right" },
    { code: "F", display_code: "F", label: "1st Drive R2", x: 72, y: 26, axle: 2, side: "right" },
    { code: "G", display_code: "G", label: "2nd Drive L1", x: 16, y: 38, axle: 3, side: "left" },
    { code: "H", display_code: "H", label: "2nd Drive L2", x: 24, y: 38, axle: 3, side: "left" },
    { code: "I", display_code: "I", label: "2nd Drive R1", x: 64, y: 38, axle: 3, side: "right" },
    { code: "J", display_code: "J", label: "2nd Drive R2", x: 72, y: 38, axle: 3, side: "right" },
    { code: "W", display_code: "W", label: "Spare Wheel", x: 42, y: 49, axle: 2.5, side: "center" },
    { code: "K", display_code: "K", label: "Tag L1", x: 16, y: 60, axle: 4, side: "left" },
    { code: "L", display_code: "L", label: "Tag L2", x: 24, y: 60, axle: 4, side: "left" },
    { code: "M", display_code: "M", label: "Tag R1", x: 64, y: 60, axle: 4, side: "right" },
    { code: "N", display_code: "N", label: "Tag R2", x: 72, y: 60, axle: 4, side: "right" },
    { code: "X", display_code: "X", label: "Spare Wheel", x: 42, y: 72, axle: 4.5, side: "center" },
    { code: "O", display_code: "O", label: "Rear L1", x: 16, y: 82, axle: 5, side: "left" },
    { code: "P", display_code: "P", label: "Rear L2", x: 24, y: 82, axle: 5, side: "left" },
    { code: "Q", display_code: "Q", label: "Rear R1", x: 64, y: 82, axle: 5, side: "right" },
    { code: "R", display_code: "R", label: "Rear R2", x: 72, y: 82, axle: 5, side: "right" },
    { code: "S", display_code: "S", label: "Rear L3", x: 16, y: 92, axle: 6, side: "left" },
    { code: "T", display_code: "T", label: "Rear L4", x: 24, y: 92, axle: 6, side: "left" },
    { code: "U", display_code: "U", label: "Rear R3", x: 64, y: 92, axle: 6, side: "right" },
    { code: "V", display_code: "V", label: "Rear R4", x: 72, y: 92, axle: 6, side: "right" },
];

const TRAILER_SLOTS = [
    { code: "A", display_code: "A", label: "Axle 1 L1", x: 16, y: 10, axle: 1, side: "left" },
    { code: "B", display_code: "B", label: "Axle 1 L2", x: 24, y: 10, axle: 1, side: "left" },
    { code: "C", display_code: "C", label: "Axle 1 R1", x: 64, y: 10, axle: 1, side: "right" },
    { code: "D", display_code: "D", label: "Axle 1 R2", x: 72, y: 10, axle: 1, side: "right" },
    { code: "E", display_code: "E", label: "Axle 2 L1", x: 16, y: 22, axle: 2, side: "left" },
    { code: "F", display_code: "F", label: "Axle 2 L2", x: 24, y: 22, axle: 2, side: "left" },
    { code: "G", display_code: "G", label: "Axle 2 R1", x: 64, y: 22, axle: 2, side: "right" },
    { code: "H", display_code: "H", label: "Axle 2 R2", x: 72, y: 22, axle: 2, side: "right" },
    { code: "I", display_code: "I", label: "Axle 3 L1", x: 16, y: 34, axle: 3, side: "left" },
    { code: "J", display_code: "J", label: "Axle 3 L2", x: 24, y: 34, axle: 3, side: "left" },
    { code: "K", display_code: "K", label: "Axle 3 R1", x: 64, y: 34, axle: 3, side: "right" },
    { code: "L", display_code: "L", label: "Axle 3 R2", x: 72, y: 34, axle: 3, side: "right" },
    { code: "X", display_code: "X", label: "Spare Wheel", x: 42, y: 44, axle: 2.5, side: "center" },
];

function getStatusColor(color: string): string {
    const colors: Record<string, string> = {
        green: "#16a34a",
        blue: "#2563eb",
        orange: "#ea580c",
        red: "#dc2626",
        yellow: "#ca8a04",
        black: "#020617",
        gray: "#94a3b8",
    };
    return colors[color] || colors.gray;
}

function TruckChassisSVG() {
    return (
        <svg
            viewBox="0 0 100 100"
            className="absolute inset-0 w-full h-full pointer-events-none"
            preserveAspectRatio="xMidYMid meet"
        >
            <defs>
                <linearGradient id="chassisGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#f8fafc" />
                    <stop offset="100%" stopColor="#f1f5f9" />
                </linearGradient>
                <linearGradient id="cabGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#e2e8f0" />
                    <stop offset="100%" stopColor="#cbd5e1" />
                </linearGradient>
                <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="1" stdDeviation="1" floodColor="#94a3b8" floodOpacity="0.3" />
                </filter>
            </defs>
            
            {/* Main chassis body */}
            <rect x="36" y="8" width="28" height="88" rx="3" fill="url(#chassisGradient)" stroke="#cbd5e1" strokeWidth="1" filter="url(#shadow)" />
            
            {/* Cab section */}
            <rect x="38" y="10" width="24" height="22" rx="2" fill="url(#cabGradient)" stroke="#94a3b8" strokeWidth="1" />
            
            {/* Windshield */}
            <rect x="40" y="12" width="20" height="12" rx="1" fill="#f8fafc" stroke="#cbd5e1" strokeWidth="0.5" />
            
            {/* Cab details */}
            <rect x="42" y="14" width="8" height="8" rx="0.5" fill="#e2e8f0" stroke="#cbd5e1" strokeWidth="0.3" />
            <rect x="50" y="14" width="8" height="8" rx="0.5" fill="#e2e8f0" stroke="#cbd5e1" strokeWidth="0.3" />
            
            {/* Side mirrors */}
            <rect x="34" y="14" width="3" height="4" rx="0.5" fill="#cbd5e1" stroke="#94a3b8" strokeWidth="0.5" />
            <rect x="63" y="14" width="3" height="4" rx="0.5" fill="#cbd5e1" stroke="#94a3b8" strokeWidth="0.5" />
            
            {/* Chassis frame lines */}
            <line x1="38" y1="34" x2="38" y2="92" stroke="#cbd5e1" strokeWidth="0.5" />
            <line x1="62" y1="34" x2="62" y2="92" stroke="#cbd5e1" strokeWidth="0.5" />
            
            {/* Axle indicators */}
            <g stroke="#94a3b8" strokeWidth="1.5" strokeLinecap="round">
                <line x1="28" y1="26" x2="72" y2="26" />
                <line x1="28" y1="38" x2="72" y2="38" />
                <line x1="28" y1="60" x2="72" y2="60" />
                <line x1="28" y1="82" x2="72" y2="82" />
                <line x1="28" y1="92" x2="72" y2="92" />
            </g>
            
            {/* Axle center points */}
            <g fill="#64748b">
                <circle cx="50" cy="26" r="1.5" />
                <circle cx="50" cy="38" r="1.5" />
                <circle cx="50" cy="60" r="1.5" />
                <circle cx="50" cy="82" r="1.5" />
                <circle cx="50" cy="92" r="1.5" />
            </g>
            
            {/* Center line */}
            <line x1="50" y1="8" x2="50" y2="96" stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4,4" />
            
            {/* Bottom bumper */}
            <rect x="34" y="94" width="32" height="2" rx="1" fill="#94a3b8" />
        </svg>
    );
}

function TrailerChassisSVG() {
    return (
        <svg
            viewBox="0 0 100 100"
            className="absolute inset-0 w-full h-full pointer-events-none"
            preserveAspectRatio="xMidYMid meet"
        >
            <defs>
                <linearGradient id="trailerGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#f8fafc" />
                    <stop offset="100%" stopColor="#f1f5f9" />
                </linearGradient>
                <filter id="trailerShadow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="1" stdDeviation="1" floodColor="#94a3b8" floodOpacity="0.3" />
                </filter>
            </defs>
            
            {/* Main trailer body */}
            <rect x="36" y="5" width="28" height="92" rx="3" fill="url(#trailerGradient)" stroke="#cbd5e1" strokeWidth="1" filter="url(#trailerShadow)" />
            
            {/* Top rail */}
            <rect x="38" y="7" width="24" height="3" rx="1" fill="#e2e8f0" stroke="#cbd5e1" strokeWidth="0.5" />
            
            {/* Frame lines */}
            <line x1="38" y1="12" x2="38" y2="93" stroke="#cbd5e1" strokeWidth="0.5" />
            <line x1="62" y1="12" x2="62" y2="93" stroke="#cbd5e1" strokeWidth="0.5" />
            
            {/* Cross braces */}
            <line x1="38" y1="25" x2="62" y2="25" stroke="#e2e8f0" strokeWidth="0.5" />
            <line x1="38" y1="50" x2="62" y2="50" stroke="#e2e8f0" strokeWidth="0.5" />
            <line x1="38" y1="75" x2="62" y2="75" stroke="#e2e8f0" strokeWidth="0.5" />
            
            {/* Axle indicators */}
            <g stroke="#94a3b8" strokeWidth="1.5" strokeLinecap="round">
                <line x1="28" y1="10" x2="72" y2="10" />
                <line x1="28" y1="22" x2="72" y2="22" />
                <line x1="28" y1="34" x2="72" y2="34" />
            </g>
            
            {/* Axle center points */}
            <g fill="#64748b">
                <circle cx="50" cy="10" r="1.5" />
                <circle cx="50" cy="22" r="1.5" />
                <circle cx="50" cy="34" r="1.5" />
            </g>
            
            {/* Center line */}
            <line x1="50" y1="5" x2="50" y2="97" stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4,4" />
            
            {/* Bottom bumper */}
            <rect x="34" y="95" width="32" height="2" rx="1" fill="#94a3b8" />
            
            {/* Trailer hitch */}
            <rect x="47" y="2" width="6" height="4" rx="1" fill="#94a3b8" stroke="#64748b" strokeWidth="0.5" />
        </svg>
    );
}

function TyreCard({
    slot,
    isSelected,
    onSelect,
    onContextMenuAction,
    isSpare = false,
}: {
    slot: ModernTyreSlot;
    isSelected: boolean;
    onSelect: () => void;
    onContextMenuAction?: (action: string, slot: ModernTyreSlot, x?: number, y?: number) => void;
    isSpare?: boolean;
}) {
    const hasTyre = Boolean(slot.tyre_code);
    const statusColor = getStatusColor(slot.color);

    const handleContextMenu = (e: React.MouseEvent) => {
        e.preventDefault();
        if (onContextMenuAction) {
            onContextMenuAction('context', slot, e.clientX, e.clientY);
        }
    };

    return (
        <button
            onClick={onSelect}
            onContextMenu={handleContextMenu}
            className={cn(
                "group relative flex items-center justify-center transition-all duration-300 ease-out",
                "focus:outline-none focus:ring-2 focus:ring-[#163B74] focus:ring-offset-2",
                isSpare ? "w-16 h-16" : "w-12 h-14"
            )}
            style={{
                left: `${slot.x}%`,
                top: `${slot.y}%`,
                position: "absolute",
                transform: "translate(-50%, -50%)",
            }}
        >
            {/* Card background with layered shadows */}
            <div
                className={cn(
                    "absolute inset-0 rounded-2xl transition-all duration-300",
                    hasTyre
                        ? "bg-white border-2 border-gray-200 shadow-[0_2px_8px_rgba(0,0,0,0.08),0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-[0_4px_16px_rgba(0,0,0,0.12),0_2px_6px_rgba(0,0,0,0.08)]"
                        : "bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 shadow-[0_1px_4px_rgba(0,0,0,0.04)]",
                    isSelected
                        ? "ring-3 ring-[#163B74] ring-offset-3 shadow-[0_6px_20px_rgba(22,59,116,0.25),0_3px_8px_rgba(22,59,116,0.15)] scale-105"
                        : "hover:scale-102 hover:ring-2 hover:ring-gray-300 hover:ring-offset-1"
                )}
            />

            {/* Inner highlight for filled tyres */}
            {hasTyre && (
                <div className="absolute inset-0 rounded-2xl bg-gradient-to-br from-white/50 to-transparent pointer-events-none" />
            )}

            {/* Status indicator with glow */}
            {hasTyre && (
                <div className="absolute top-1.5 right-1.5">
                    <div
                        className="w-2.5 h-2.5 rounded-full"
                        style={{
                            backgroundColor: statusColor,
                            boxShadow: `0 0 8px ${statusColor}80`
                        }}
                    />
                </div>
            )}

            {/* Label with better typography */}
            <span
                className={cn(
                    "relative z-10 font-bold tracking-tight transition-all duration-300",
                    hasTyre
                        ? "text-[#163B74] drop-shadow-sm"
                        : "text-gray-400",
                    isSelected && "text-[#163B74]",
                    isSpare ? "text-lg" : "text-sm"
                )}
            >
                {slot.display_code}
            </span>

            {/* Subtle hover overlay */}
            <div className={cn(
                "absolute inset-0 rounded-2xl transition-opacity duration-300 pointer-events-none",
                hasTyre
                    ? "bg-[#163B74] opacity-0 group-hover:opacity-8"
                    : "bg-gray-200 opacity-0 group-hover:opacity-20"
            )} />

            {/* Shine effect for filled tyres */}
            {hasTyre && (
                <div className="absolute top-0 left-0 right-0 h-1/2 rounded-t-2xl bg-gradient-to-b from-white/30 to-transparent pointer-events-none" />
            )}
        </button>
    );
}

function ContextMenu({ x, y, onClose, onAction, hasTyre }: { x: number; y: number; onClose: () => void; onAction: (action: string) => void; hasTyre: boolean }) {
    const menuRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                onClose();
            }
        };

        const handleEsc = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEsc);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEsc);
        };
    }, [onClose]);

    // Adjust position if menu would go off screen
    const adjustedX = Math.min(x, window.innerWidth - 200);
    const adjustedY = Math.min(y, window.innerHeight - 150);

    return (
        <div
            ref={menuRef}
            className="fixed z-50 min-w-[180px] rounded-lg border bg-white shadow-lg py-1"
            style={{ left: adjustedX, top: adjustedY }}
        >
            <button
                onClick={() => { onAction('view'); onClose(); }}
                className="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 flex items-center gap-2"
            >
                <Eye className="h-4 w-4" />
                Tyre Operations
            </button>
            {hasTyre && (
                <>
                    <div className="h-px bg-gray-200 my-1" />
                    <button
                        onClick={() => { onAction('movement'); onClose(); }}
                        className="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 flex items-center gap-2"
                    >
                        <ArrowRight className="h-4 w-4" />
                        Tyre Movements
                    </button>
                    <button
                        onClick={() => { onAction('disposal'); onClose(); }}
                        className="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 flex items-center gap-2"
                    >
                        <Trash2 className="h-4 w-4" />
                        Tyre Disposals
                    </button>
                </>
            )}
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
    className,
}: ModernTyreMapProps) {
    const isTrailer = assetType === "trailer";
    const baseSlots = isTrailer ? TRAILER_SLOTS : TRUCK_SLOTS;
    const [contextMenu, setContextMenu] = useState<{ slot: ModernTyreSlot; x: number; y: number } | null>(null);

    const mergedSlots = useMemo(() => {
        return baseSlots.map((base) => {
            const slotData = slots.find((s) => s.code === base.code);
            return {
                ...base,
                tyre_code: slotData?.tyre_code || null,
                color: slotData?.color || "gray",
            };
        });
    }, [baseSlots, slots]);

    const containerHeight = isTrailer ? "min-h-[400px] md:min-h-[500px]" : "min-h-[600px] md:min-h-[700px]";

    const handleContextMenuAction = (action: string, slot: ModernTyreSlot, x?: number, y?: number) => {
        if (action === 'context' && x !== undefined && y !== undefined) {
            setContextMenu({ slot, x, y });
        } else if (onContextMenuAction) {
            onContextMenuAction(action, slot);
        }
    };

    const handleMenuAction = (action: string) => {
        if (contextMenu && onContextMenuAction) {
            onContextMenuAction(action, contextMenu.slot);
        }
    };

    return (
        <div
            className={cn(
                "relative w-full bg-white rounded-xl border border-gray-200 overflow-hidden",
                containerHeight,
                className
            )}
            role="application"
            aria-label={`Tyre map ${mapId}`}
        >
            {/* Chassis background */}
            {isTrailer ? <TrailerChassisSVG /> : <TruckChassisSVG />}

            {/* Tyre cards */}
            {mergedSlots.map((slot) => (
                <TyreCard
                    key={`${mapId}-${slot.code}`}
                    slot={slot}
                    isSelected={selectedPosition === slot.code}
                    onSelect={() => onSelect(slot.code)}
                    onContextMenuAction={handleContextMenuAction}
                    isSpare={slot.side === "center"}
                />
            ))}

            {/* Context menu */}
            {contextMenu && (
                <ContextMenu
                    x={contextMenu.x}
                    y={contextMenu.y}
                    onClose={() => setContextMenu(null)}
                    onAction={handleMenuAction}
                    hasTyre={Boolean(contextMenu.slot.tyre_code)}
                />
            )}
        </div>
    );
}
