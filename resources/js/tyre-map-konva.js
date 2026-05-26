import Konva from 'konva';

const DESIGN_WIDTH = 880;
const DESIGN_HEIGHT = 600;

const TYRE_RX = 20;
const TYRE_RY = 38;

const STATUS = {
    green: {
        fill: ['#4ade80', '#16a34a'],
        stroke: '#15803d',
        tread: '#14532d',
        text: '#052e16',
        glow: 'rgba(34, 197, 94, 0.35)',
    },
    blue: {
        fill: ['#60a5fa', '#2563eb'],
        stroke: '#1d4ed8',
        tread: '#1e3a8a',
        text: '#1e3a8a',
        glow: 'rgba(59, 130, 246, 0.35)',
    },
    orange: {
        fill: ['#fb923c', '#ea580c'],
        stroke: '#c2410c',
        tread: '#7c2d12',
        text: '#431407',
        glow: 'rgba(249, 115, 22, 0.35)',
    },
    red: {
        fill: ['#f87171', '#dc2626'],
        stroke: '#b91c1c',
        tread: '#7f1d1d',
        text: '#450a0a',
        glow: 'rgba(239, 68, 68, 0.35)',
    },
    yellow: {
        fill: ['#facc15', '#ca8a04'],
        stroke: '#a16207',
        tread: '#713f12',
        text: '#422006',
        glow: 'rgba(234, 179, 8, 0.35)',
    },
    black: {
        fill: ['#6b7280', '#374151'],
        stroke: '#1f2937',
        tread: '#030712',
        text: '#f9fafb',
        glow: 'rgba(55, 65, 81, 0.4)',
    },
    gray: {
        fill: ['#f8fafc', '#e2e8f0'],
        stroke: '#94a3b8',
        tread: '#64748b',
        text: '#475569',
        glow: 'rgba(148, 163, 184, 0.25)',
    },
};

const SELECT = { stroke: '#f59e0b', glow: 'rgba(245, 158, 11, 0.45)' };

/** @type {Map<string, { select: (c: string|null) => void, resize: () => void, destroy: () => void }>} */
const instances = new Map();

function dark() {
    return document.documentElement.classList.contains('dark');
}

function palette() {
    return dark()
        ? {
              canvas: ['#0c1222', '#111827'],
              deck: '#1e293b',
              deckStroke: '#334155',
              chassis: '#273549',
              chassisStroke: '#475569',
              axle: '#64748b',
              hub: '#334155',
              hubStroke: '#94a3b8',
              center: '#475569',
              label: '#94a3b8',
              labelStrong: '#e2e8f0',
              dualBadge: '#1e293b',
              dualBadgeText: '#94a3b8',
              side: '#64748b',
              ghost: '#334155',
          }
        : {
              canvas: ['#f8fafc', '#eef2f7'],
              deck: '#ffffff',
              deckStroke: '#e2e8f0',
              chassis: '#f1f5f9',
              chassisStroke: '#cbd5e1',
              axle: '#94a3b8',
              hub: '#e2e8f0',
              hubStroke: '#cbd5e1',
              center: '#cbd5e1',
              label: '#64748b',
              labelStrong: '#334155',
              dualBadge: '#f1f5f9',
              dualBadgeText: '#64748b',
              side: '#94a3b8',
              ghost: '#cbd5e1',
          };
}

/**
 * @param {Konva.Group} root
 */
function drawTyre(root, colors, empty, selected) {
    const g = new Konva.Group({ listening: true });

    const glowRing = new Konva.Ellipse({
        radiusX: TYRE_RX + 10,
        radiusY: TYRE_RY + 10,
        fill: SELECT.glow,
        stroke: SELECT.stroke,
        strokeWidth: 2.5,
        visible: selected,
    });
    g.add(glowRing);

    const casing = new Konva.Ellipse({
        radiusX: TYRE_RX,
        radiusY: TYRE_RY,
        fillLinearGradientStartPoint: { x: -TYRE_RX, y: -TYRE_RY },
        fillLinearGradientEndPoint: { x: TYRE_RX, y: TYRE_RY },
        fillLinearGradientColorStops: empty
            ? [0, 'transparent', 1, 'transparent']
            : [0, colors.fill[0], 0.55, colors.fill[1], 1, colors.fill[1]],
        stroke: selected ? SELECT.stroke : empty ? palette().ghost : colors.stroke,
        strokeWidth: empty ? 2 : selected ? 3 : 2,
        dash: empty ? [6, 4] : undefined,
        shadowColor: empty ? 'transparent' : 'rgba(15,23,42,0.25)',
        shadowBlur: selected ? 14 : 8,
        shadowOffset: { x: 0, y: 3 },
        shadowOpacity: empty ? 0 : 0.5,
    });
    g.add(casing);

    if (!empty) {
        g.add(
            new Konva.Ellipse({
                radiusX: TYRE_RX - 3,
                radiusY: TYRE_RY - 3,
                stroke: colors.stroke,
                strokeWidth: 1,
                opacity: 0.35,
            }),
        );

        const treadTop = -TYRE_RY + 10;
        const treadH = TYRE_RY * 2 - 20;
        for (let i = 0; i < 7; i++) {
            const y = treadTop + i * (treadH / 7);
            g.add(
                new Konva.Line({
                    points: [-TYRE_RX + 6, y, TYRE_RX - 6, y + 2],
                    stroke: colors.tread,
                    strokeWidth: 1.4,
                    opacity: 0.35,
                    lineCap: 'round',
                }),
            );
        }

        g.add(
            new Konva.Rect({
                x: -TYRE_RX + 8,
                y: -4,
                width: (TYRE_RX - 8) * 2,
                height: 8,
                fill: colors.tread,
                opacity: 0.12,
                cornerRadius: 2,
            }),
        );
    }

    root.add(g);

    return { group: g, casing, glowRing };
}

/**
 * @param {Konva.Layer} layer
 * @param {Array<Record<string, unknown>>} slots
 * @param {ReturnType<typeof palette>} p
 */
function drawDualMounts(layer, slots, p) {
    const groups = new Map();

    slots.forEach((s) => {
        if (s.dual === 'single') {
            return;
        }
        const k = `${s.axle}-${s.side}`;
        if (!groups.has(k)) {
            groups.set(k, []);
        }
        groups.get(k).push(s);
    });

    groups.forEach((pair) => {
        if (pair.length < 2) {
            return;
        }
        const xs = pair.map((s) => Number(s.x)).sort((a, b) => a - b);
        const y = Number(pair[0].y);
        const x1 = xs[0];
        const x2 = xs[xs.length - 1];
        const padX = TYRE_RX + 6;
        const padY = TYRE_RY + 14;

        layer.add(
            new Konva.Rect({
                x: x1 - padX,
                y: y - padY,
                width: x2 - x1 + padX * 2,
                height: padY * 2,
                cornerRadius: 12,
                fill: p.dualBadge,
                stroke: p.deckStroke,
                strokeWidth: 1,
                opacity: 0.85,
            }),
        );
    });
}

function drawChassis(layer, p) {
    const cx = DESIGN_WIDTH / 2;
    layer.add(
        new Konva.Line({
            points: [
                cx, 78,
                cx - 52, 120,
                cx - 58, DESIGN_HEIGHT - 72,
                cx + 58, DESIGN_HEIGHT - 72,
                cx + 52, 120,
                cx, 78,
            ],
            closed: true,
            fill: p.chassis,
            stroke: p.chassisStroke,
            strokeWidth: 1.5,
            opacity: 0.9,
        }),
    );
}

/**
 * @param {HTMLElement} container
 */
function createTyreMap(container, config) {
    const slots = config.slots ?? [];
    const meta = new Map();
    const p = palette();

    const stage = new Konva.Stage({
        container,
        width: DESIGN_WIDTH,
        height: DESIGN_HEIGHT,
    });

    const bgLayer = new Konva.Layer({ listening: false });
    const layer = new Konva.Layer();
    const uiLayer = new Konva.Layer({ listening: false });
    stage.add(bgLayer);
    stage.add(layer);
    stage.add(uiLayer);

    bgLayer.add(
        new Konva.Rect({
            x: 0,
            y: 0,
            width: DESIGN_WIDTH,
            height: DESIGN_HEIGHT,
            fillLinearGradientColorStops: [0, p.canvas[0], 1, p.canvas[1]],
            fillLinearGradientStartPoint: { x: 0, y: 0 },
            fillLinearGradientEndPoint: { x: DESIGN_WIDTH, y: DESIGN_HEIGHT },
        }),
    );

    layer.add(
        new Konva.Rect({
            x: 24,
            y: 20,
            width: DESIGN_WIDTH - 48,
            height: DESIGN_HEIGHT - 40,
            cornerRadius: 20,
            fill: p.deck,
            stroke: p.deckStroke,
            strokeWidth: 1,
            shadowColor: 'rgba(0,0,0,0.08)',
            shadowBlur: 24,
            shadowOffsetY: 8,
        }),
    );

    drawChassis(layer, p);

    const frontLbl = new Konva.Label({ x: DESIGN_WIDTH / 2 - 42, y: 28, listening: false });
    frontLbl.add(new Konva.Tag({ fill: p.deck, stroke: p.deckStroke, cornerRadius: 6 }));
    frontLbl.add(
        new Konva.Text({
            text: '▲  FRONT',
            fontSize: 11,
            fontStyle: 'bold',
            fill: p.labelStrong,
            padding: 6,
            fontFamily: 'Inter, system-ui, sans-serif',
        }),
    );
    uiLayer.add(frontLbl);

    layer.add(
        new Konva.Line({
            points: [DESIGN_WIDTH / 2, 56, DESIGN_WIDTH / 2, DESIGN_HEIGHT - 48],
            stroke: p.center,
            dash: [8, 6],
            strokeWidth: 1,
        }),
    );

    uiLayer.add(
        new Konva.Text({
            x: 36,
            y: DESIGN_HEIGHT / 2 - 20,
            text: 'LEFT',
            fontSize: 10,
            fontStyle: 'bold',
            fill: p.side,
            rotation: -90,
            fontFamily: 'Inter, system-ui, sans-serif',
        }),
    );
    uiLayer.add(
        new Konva.Text({
            x: DESIGN_WIDTH - 48,
            y: DESIGN_HEIGHT / 2 + 20,
            text: 'RIGHT',
            fontSize: 10,
            fontStyle: 'bold',
            fill: p.side,
            rotation: 90,
            fontFamily: 'Inter, system-ui, sans-serif',
        }),
    );

    const axles = [...new Set(slots.map((s) => s.axle).filter(Boolean))].sort(
        (a, b) => Number(a) - Number(b),
    );

    axles.forEach((axle) => {
        const sample = slots.find((s) => s.axle === axle);
        if (!sample) {
            return;
        }
        const y = Number(sample.y);
        const isSteer = slots.some((s) => s.axle === axle && s.dual === 'single');

        layer.add(
            new Konva.Line({
                points: [56, y, DESIGN_WIDTH - 56, y],
                stroke: p.axle,
                strokeWidth: 2.5,
                lineCap: 'round',
                opacity: 0.85,
            }),
        );

        layer.add(
            new Konva.Circle({
                x: DESIGN_WIDTH / 2,
                y,
                radius: 11,
                fill: p.hub,
                stroke: p.hubStroke,
                strokeWidth: 2,
                shadowColor: 'rgba(0,0,0,0.15)',
                shadowBlur: 4,
                shadowOffsetY: 1,
            }),
        );

        const badge = new Konva.Label({ x: 44, y: y - 10, listening: false });
        badge.add(new Konva.Tag({ fill: p.dualBadge, stroke: p.deckStroke, cornerRadius: 4 }));
        badge.add(
            new Konva.Text({
                text: isSteer ? `Axle ${axle} · Steer` : `Axle ${axle} · Drive`,
                fontSize: 10,
                fontStyle: '600',
                fill: p.label,
                padding: 4,
                fontFamily: 'Inter, system-ui, sans-serif',
            }),
        );
        uiLayer.add(badge);
    });

    drawDualMounts(layer, slots, p);

    slots.forEach((slot) => {
        const code = String(slot.code);
        const colorKey = String(slot.color ?? 'gray');
        const colors = STATUS[colorKey] ?? STATUS.gray;
        const empty = !slot.tyre_code;
        const x = Number(slot.x);
        const y = Number(slot.y);
        const selected = config.selectedPosition === code;

        const hit = new Konva.Group({ x, y });
        const { casing, glowRing } = drawTyre(hit, colors, empty, selected);

        const pill = new Konva.Label({ x: -26, y: TYRE_RY + 8, listening: false });
        pill.add(
            new Konva.Tag({
                fill: dark() ? '#0f172a' : '#ffffff',
                stroke: empty ? p.ghost : colors.stroke,
                cornerRadius: 5,
                shadowColor: 'rgba(0,0,0,0.1)',
                shadowBlur: 4,
                shadowOffsetY: 1,
            }),
        );
        pill.add(
            new Konva.Text({
                text: code,
                fontSize: 11,
                fontStyle: 'bold',
                fill: empty ? p.label : colors.text,
                padding: 5,
                fontFamily: 'Inter, system-ui, sans-serif',
            }),
        );
        hit.add(pill);

        if (slot.tyre_code) {
            const tc = String(slot.tyre_code);
            hit.add(
                new Konva.Text({
                    x: -TYRE_RX,
                    y: -5,
                    width: TYRE_RX * 2,
                    align: 'center',
                    text: tc.replace('TYR-', ''),
                    fontSize: 9,
                    fontStyle: 'bold',
                    fill: colors.text,
                    fontFamily: 'Inter, system-ui, sans-serif',
                    listening: false,
                }),
            );
        } else {
            hit.add(
                new Konva.Text({
                    x: -TYRE_RX,
                    y: -4,
                    width: TYRE_RX * 2,
                    align: 'center',
                    text: 'EMPTY',
                    fontSize: 7,
                    fontStyle: 'bold',
                    letterSpacing: 0.5,
                    fill: p.label,
                    fontFamily: 'Inter, system-ui, sans-serif',
                    listening: false,
                }),
            );
        }

        layer.add(hit);

        hit.on('mouseenter', () => {
            stage.container().style.cursor = 'pointer';
            new Konva.Tween({
                node: casing,
                scaleX: 1.06,
                scaleY: 1.06,
                duration: 0.12,
                easing: Konva.Easings.EaseOut,
            }).play();
        });

        hit.on('mouseleave', () => {
            stage.container().style.cursor = 'default';
            new Konva.Tween({
                node: casing,
                scaleX: 1,
                scaleY: 1,
                duration: 0.12,
                easing: Konva.Easings.EaseOut,
            }).play();
        });

        hit.on('click tap', () => config.onSelect?.(code));

        meta.set(code, { hit, casing, glowRing, colors, empty, slot });
    });

    function select(code) {
        config.selectedPosition = code;
        meta.forEach((m, slotCode) => {
            const isSel = slotCode === code;
            m.glowRing.visible(isSel);
            m.casing.stroke(isSel ? SELECT.stroke : m.empty ? palette().ghost : m.colors.stroke);
            m.casing.strokeWidth(isSel ? 3 : 2);
            m.casing.shadowBlur(isSel ? 16 : 8);
        });
        layer.batchDraw();
    }

    select(config.selectedPosition ?? null);

    function resize() {
        const wrap = container.parentElement;
        if (!wrap) {
            return;
        }
        const w = wrap.clientWidth;
        const scale = Math.min(w / DESIGN_WIDTH, 1);
        stage.width(DESIGN_WIDTH * scale);
        stage.height(DESIGN_HEIGHT * scale);
        stage.scale({ x: scale, y: scale });
        stage.batchDraw();
    }

    resize();
    const ro = new ResizeObserver(resize);
    ro.observe(container.parentElement ?? container);

    return {
        stage,
        select,
        resize,
        destroy() {
            ro.disconnect();
            stage.destroy();
        },
    };
}

function getWire(host) {
    const root = host.closest('[wire\\:id]');
    const id = root?.getAttribute('wire:id');
    return id && window.Livewire ? window.Livewire.find(id) : null;
}

function initHost(host) {
    const id = host.dataset.mapId;
    if (!id || instances.has(id)) {
        return;
    }

    let config;
    try {
        config = JSON.parse(host.dataset.config || '{}');
    } catch {
        return;
    }

    const wire = getWire(host);
    const map = createTyreMap(host, {
        slots: config.slots ?? [],
        selectedPosition: config.selectedPosition ?? null,
        onSelect(code) {
            map.select(code);
            wire?.call('selectPosition', code);
        },
    });

    instances.set(id, map);
}

function destroyHost(host) {
    const id = host.dataset.mapId;
    instances.get(id)?.destroy();
    instances.delete(id);
}

function initAll() {
    document.querySelectorAll('[data-tyre-map-konva]').forEach((host) => {
        if (host.dataset.mounted !== '1') {
            host.dataset.mounted = '1';
            initHost(host);
        }
    });
}

function teardownAll() {
    document.querySelectorAll('[data-tyre-map-konva]').forEach(destroyHost);
    document.querySelectorAll('[data-tyre-map-konva]').forEach((h) => delete h.dataset.mounted);
}

document.addEventListener('DOMContentLoaded', initAll);
document.addEventListener('livewire:navigated', () => {
    teardownAll();
    initAll();
});

window.TmsTyreMapKonva = { initAll, teardownAll };
