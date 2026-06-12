import Konva from 'konva';

const DESIGN_WIDTH = 520;
const DESIGN_HEIGHT = 1510;
const TRUCK_BODY_BOTTOM_PADDING = 132;
const BODY_X = 192;
const BODY_W = 136;
const BODY_TOP = 34;
const BODY_BOTTOM = 1478;
const BADGE = '#0a3972';
const TRAILER_DESIGN = { width: 520, height: 760 };

const SLOTS = {
    A: { kind: 'wheel', tire: [106, 190, 50, 112], badge: [48, 218], side: 'left' },
    B: { kind: 'wheel', tire: [364, 190, 50, 112], badge: [462, 218], side: 'right' },
    C: { kind: 'wheel', tire: [90, 398, 42, 120], badge: [48, 412], side: 'left' },
    D: { kind: 'wheel', tire: [132, 398, 42, 120], badge: [48, 466], side: 'left' },
    E: { kind: 'wheel', tire: [344, 398, 42, 120], badge: [462, 412], side: 'right' },
    F: { kind: 'wheel', tire: [386, 398, 42, 120], badge: [462, 466], side: 'right' },
    G: { kind: 'wheel', tire: [90, 578, 42, 120], badge: [48, 592], side: 'left' },
    H: { kind: 'wheel', tire: [132, 578, 42, 120], badge: [48, 646], side: 'left' },
    I: { kind: 'wheel', tire: [344, 578, 42, 120], badge: [462, 592], side: 'right' },
    J: { kind: 'wheel', tire: [386, 578, 42, 120], badge: [462, 646], side: 'right' },
    W: { kind: 'spare', wheel: [223, 742, 74], box: [198, 715, 124, 118], badge: [235, 808] },
    K: { kind: 'wheel', tire: [90, 904, 42, 120], badge: [48, 918], side: 'left' },
    L: { kind: 'wheel', tire: [132, 904, 42, 120], badge: [48, 972], side: 'left' },
    M: { kind: 'wheel', tire: [344, 904, 42, 120], badge: [462, 918], side: 'right' },
    N: { kind: 'wheel', tire: [386, 904, 42, 120], badge: [462, 972], side: 'right' },
    X: { kind: 'spare', wheel: [223, 1096, 74], box: [198, 1069, 124, 118], badge: [235, 1162] },
    O: { kind: 'wheel', tire: [90, 1244, 42, 120], badge: [48, 1258], side: 'left' },
    P: { kind: 'wheel', tire: [132, 1244, 42, 120], badge: [48, 1312], side: 'left' },
    Q: { kind: 'wheel', tire: [344, 1244, 42, 120], badge: [462, 1258], side: 'right' },
    R: { kind: 'wheel', tire: [386, 1244, 42, 120], badge: [462, 1312], side: 'right' },
    S: { kind: 'wheel', tire: [90, 1384, 42, 120], badge: [48, 1398], side: 'left' },
    T: { kind: 'wheel', tire: [132, 1384, 42, 120], badge: [48, 1452], side: 'left' },
    U: { kind: 'wheel', tire: [344, 1384, 42, 120], badge: [462, 1398], side: 'right' },
    V: { kind: 'wheel', tire: [386, 1384, 42, 120], badge: [462, 1452], side: 'right' },
};

const TRAILER_SLOTS = {
    A: { kind: 'wheel', tire: [90, 150, 42, 120], badge: [48, 164], side: 'left' },
    B: { kind: 'wheel', tire: [132, 150, 42, 120], badge: [48, 218], side: 'left' },
    C: { kind: 'wheel', tire: [344, 150, 42, 120], badge: [462, 164], side: 'right' },
    D: { kind: 'wheel', tire: [386, 150, 42, 120], badge: [462, 218], side: 'right' },
    E: { kind: 'wheel', tire: [90, 340, 42, 120], badge: [48, 354], side: 'left' },
    F: { kind: 'wheel', tire: [132, 340, 42, 120], badge: [48, 408], side: 'left' },
    G: { kind: 'wheel', tire: [344, 340, 42, 120], badge: [462, 354], side: 'right' },
    H: { kind: 'wheel', tire: [386, 340, 42, 120], badge: [462, 408], side: 'right' },
    I: { kind: 'wheel', tire: [90, 530, 42, 120], badge: [48, 544], side: 'left' },
    J: { kind: 'wheel', tire: [132, 530, 42, 120], badge: [48, 598], side: 'left' },
    K: { kind: 'wheel', tire: [344, 530, 42, 120], badge: [462, 544], side: 'right' },
    L: { kind: 'wheel', tire: [386, 530, 42, 120], badge: [462, 598], side: 'right' },
};

const STATUS = {
    green: '#16a34a',
    blue: '#2563eb',
    orange: '#ea580c',
    red: '#dc2626',
    yellow: '#ca8a04',
    black: '#020617',
    gray: '#94a3b8',
};

const SELECT = { stroke: '#f59e0b', fill: 'rgba(245, 158, 11, 0.16)' };
const instances = new Map();

function dark() {
    return document.documentElement.classList.contains('dark');
}

function palette() {
    return dark()
        ? {
              paper: '#0b1220',
              bodyFill: 'rgba(226, 232, 240, 0.12)',
              bodyLine: 'rgba(203, 213, 225, 0.28)',
              bodyShade: 'rgba(226, 232, 240, 0.2)',
              glass: 'rgba(2, 6, 23, 0.72)',
              axle: '#334155',
              axleHi: '#94a3b8',
              tyre: '#020617',
              tyreSide: '#111827',
              tread: '#475569',
          }
        : {
              paper: '#ffffff',
              bodyFill: 'rgba(241, 245, 249, 0.72)',
              bodyLine: 'rgba(148, 163, 184, 0.55)',
              bodyShade: 'rgba(203, 213, 225, 0.72)',
              glass: '#111827',
              axle: '#45484d',
              axleHi: '#aeb4bb',
              tyre: '#111111',
              tyreSide: '#2b2d31',
              tread: '#6b7280',
          };
}

function displayCodeFor(slot) {
    return String(slot.display_code || slot.label || slot.code);
}

function statusFor(slot) {
    return STATUS[String(slot.color ?? 'gray')] ?? STATUS.gray;
}

function layoutFor(slot, mode) {
    const code = displayCodeFor(slot);

    if (mode === 'trailer' && TRAILER_SLOTS[code]) {
        return TRAILER_SLOTS[code];
    }

    if (SLOTS[code]) {
        return SLOTS[code];
    }

    const side = String(slot.side ?? 'left');
    const axle = Number(slot.axle || 1);
    const y = 190 + (axle - 1) * 170;
    return {
        kind: 'wheel',
        tire: side === 'right' ? [364, y, 50, 112] : [106, y, 50, 112],
        badge: side === 'right' ? [462, y + 28] : [48, y + 28],
        side,
    };
}

function axleCentersForSlots(slots, mode) {
    const centers = new Set();

    (slots ?? []).forEach((slot) => {
        const spec = layoutFor(slot, mode);
        if (!spec || spec.kind === 'spare' || !spec.tire) {
            return;
        }

        const [, y, , h] = spec.tire;
        centers.add(Math.round(y + h / 2));
    });

    return Array.from(centers).sort((a, b) => a - b);
}

function visualBottomForSlots(slots, mode) {
    const bottoms = [0];

    (slots ?? []).forEach((slot) => {
        const spec = layoutFor(slot, mode);
        if (!spec) {
            return;
        }

        if (spec.kind === 'spare' && spec.box) {
            const [, y, , h] = spec.box;
            bottoms.push(y + h);
            return;
        }

        if (spec.tire) {
            const [, y, , h] = spec.tire;
            bottoms.push(y + h);
        }
    });

    return Math.max(...bottoms);
}

function designFor(mode, slots) {
    if (mode === 'trailer') {
        return TRAILER_DESIGN;
    }

    const axleCenters = axleCentersForSlots(slots, mode);
    const lastAxle = axleCenters.length > 0 ? Math.max(...axleCenters) : 246;
    const lastVisual = visualBottomForSlots(slots, mode);

    return {
        width: DESIGN_WIDTH,
        height: Math.max(760, Math.min(DESIGN_HEIGHT, Math.max(lastAxle + TRUCK_BODY_BOTTOM_PADDING, lastVisual + 64))),
    };
}

function drawFrame(layer, p, design) {
    layer.add(
        new Konva.Rect({
            x: 0,
            y: 0,
            width: design.width,
            height: design.height,
            fill: p.paper,
            listening: false,
        }),
    );
}

function drawBadge(layer, x, y, text, selected, empty, onClick) {
    const group = new Konva.Group({ x, y, listening: true });

    group.add(
        new Konva.Rect({
            x: -5,
            y: -5,
            width: 50,
            height: 50,
            fill: selected ? SELECT.fill : 'transparent',
            stroke: selected ? SELECT.stroke : 'transparent',
            strokeWidth: 3,
            cornerRadius: 7,
        }),
    );

    group.add(
        new Konva.Rect({
            x: 0,
            y: 0,
            width: 40,
            height: 40,
            fill: BADGE,
            stroke: empty ? '#60a5fa' : '#08315f',
            strokeWidth: empty ? 2 : 1,
            dash: empty ? [5, 3] : undefined,
            cornerRadius: 5,
            shadowColor: 'rgba(15, 23, 42, 0.22)',
            shadowBlur: 6,
            shadowOffsetY: 2,
        }),
    );

    group.add(
        new Konva.Text({
            x: 0,
            y: 5,
            width: 40,
            text,
            align: 'center',
            fontSize: 27,
            fontStyle: 'bold',
            fill: '#ffffff',
            fontFamily: 'Inter, Arial, sans-serif',
            listening: false,
        }),
    );

    group.on('mouseenter', () => {
        layer.getStage().container().style.cursor = 'pointer';
        group.to({ scaleX: 1.05, scaleY: 1.05, duration: 0.08 });
    });

    group.on('mouseleave', () => {
        layer.getStage().container().style.cursor = 'default';
        group.to({ scaleX: 1, scaleY: 1, duration: 0.08 });
    });

    group.on('click tap', onClick);
    layer.add(group);
}

function drawTreadedTyre(layer, x, y, w, h, p, empty) {
    layer.add(
        new Konva.Rect({
            x,
            y,
            width: w,
            height: h,
            fillLinearGradientStartPoint: { x, y },
            fillLinearGradientEndPoint: { x: x + w, y },
            fillLinearGradientColorStops: [0, '#050505', 0.18, p.tyreSide, 0.5, empty ? '#4b5563' : p.tyre, 0.82, p.tyreSide, 1, '#050505'],
            stroke: empty ? '#94a3b8' : '#050505',
            strokeWidth: 1,
            dash: empty ? [6, 5] : undefined,
            cornerRadius: 13,
            shadowColor: 'rgba(15, 23, 42, 0.24)',
            shadowBlur: 7,
            shadowOffsetY: 3,
            listening: false,
        }),
    );

    for (let i = 0; i < 6; i += 1) {
        const tx = x + 7 + i * ((w - 14) / 5);
        layer.add(
            new Konva.Line({
                points: [tx, y + 6, tx - 4, y + h / 2, tx, y + h - 6],
                stroke: p.tread,
                strokeWidth: 1.2,
                opacity: empty ? 0.35 : 0.62,
                listening: false,
            }),
        );
    }

    for (let i = 0; i < 10; i += 1) {
        const ty = y + 10 + i * ((h - 20) / 9);
        layer.add(
            new Konva.Line({
                points: [x + 5, ty, x + w - 5, ty + 3],
                stroke: '#0f172a',
                strokeWidth: 1,
                opacity: empty ? 0.28 : 0.55,
                listening: false,
            }),
        );
    }
}

function drawSpareWheel(layer, x, y, size, p, empty) {
    const radius = size / 2;
    layer.add(
        new Konva.Circle({
            x: x + radius,
            y: y + radius,
            radius,
            fillRadialGradientStartPoint: { x: x + radius, y: y + radius },
            fillRadialGradientStartRadius: 8,
            fillRadialGradientEndPoint: { x: x + radius, y: y + radius },
            fillRadialGradientEndRadius: radius,
            fillRadialGradientColorStops: [0, '#d1d5db', 0.28, '#f8fafc', 0.42, '#111827', 1, empty ? '#4b5563' : '#020617'],
            stroke: empty ? '#94a3b8' : '#0f172a',
            strokeWidth: 2,
            dash: empty ? [6, 4] : undefined,
            shadowColor: 'rgba(15, 23, 42, 0.24)',
            shadowBlur: 7,
            shadowOffsetY: 3,
            listening: false,
        }),
    );

    layer.add(
        new Konva.Circle({
            x: x + radius,
            y: y + radius,
            radius: 19,
            fill: '#e5e7eb',
            stroke: '#6b7280',
            strokeWidth: 2,
            listening: false,
        }),
    );

    for (let i = 0; i < 8; i += 1) {
        const angle = (Math.PI * 2 * i) / 8;
        layer.add(
            new Konva.Circle({
                x: x + radius + Math.cos(angle) * 12,
                y: y + radius + Math.sin(angle) * 12,
                radius: 2.1,
                fill: '#374151',
                listening: false,
            }),
        );
    }
}

function drawAxle(layer, y, p) {
    layer.add(
        new Konva.Line({
            points: [122, y, 398, y],
            stroke: p.axle,
            strokeWidth: 9,
            lineCap: 'round',
            listening: false,
        }),
    );

    layer.add(
        new Konva.Circle({
            x: 260,
            y,
            radius: 28,
            fillRadialGradientStartPoint: { x: 260, y },
            fillRadialGradientStartRadius: 4,
            fillRadialGradientEndPoint: { x: 260, y },
            fillRadialGradientEndRadius: 28,
            fillRadialGradientColorStops: [0, p.axleHi, 0.4, p.axle, 1, '#111827'],
            stroke: '#0f172a',
            strokeWidth: 2,
            listening: false,
        }),
    );

    [160, 190, 330, 360].forEach((x) => {
        layer.add(
            new Konva.Rect({
                x,
                y: y - 12,
                width: 13,
                height: 24,
                fill: p.axle,
                stroke: '#111827',
                strokeWidth: 1,
                cornerRadius: 3,
                listening: false,
            }),
        );
    });
}

function drawTrailerBody(layer, p) {
    layer.add(
        new Konva.Line({
            points: [206, 62, 192, 700, 328, 700, 314, 62],
            closed: true,
            fill: p.bodyFill,
            stroke: p.bodyLine,
            strokeWidth: 2,
            listening: false,
        }),
    );

    [
        [BODY_X + 10, 78, 635],
        [BODY_X + BODY_W - 10, 78, 635],
    ].forEach(([x, y, h]) => {
        layer.add(
            new Konva.Line({
                points: [x, y, x, y + h],
                stroke: p.bodyLine,
                strokeWidth: 5,
                lineCap: 'round',
                listening: false,
            }),
        );
    });

    layer.add(
        new Konva.Circle({
            x: 260,
            y: 62,
            radius: 12,
            fill: p.axle,
            stroke: '#111827',
            strokeWidth: 2,
            listening: false,
        }),
    );

    [210, 400, 590].forEach((y) => drawAxle(layer, y, p));

    layer.add(
        new Konva.Rect({
            x: BODY_X - 18,
            y: 710,
            width: BODY_W + 36,
            height: 20,
            fill: p.bodyShade,
            stroke: p.bodyLine,
            strokeWidth: 2,
            listening: false,
        }),
    );

    [
        [BODY_X - 26, 710],
        [BODY_X + BODY_W + 8, 710],
    ].forEach(([x, y]) => {
        layer.add(
            new Konva.Rect({
                x,
                y,
                width: 24,
                height: 17,
                fill: '#dc2626',
                stroke: '#7f1d1d',
                strokeWidth: 1,
                listening: false,
            }),
        );
    });
}

function drawVehicleBody(layer, p, mode, slots, design) {
    if (mode === 'trailer') {
        drawTrailerBody(layer, p);
        return;
    }

    const bodyBottom = design.height - 32;
    const axleCenters = axleCentersForSlots(slots, mode);

    layer.add(
        new Konva.Rect({
            x: BODY_X,
            y: 168,
            width: BODY_W,
            height: bodyBottom - 198,
            fill: p.bodyFill,
            stroke: p.bodyLine,
            strokeWidth: 2,
            cornerRadius: 4,
            listening: false,
        }),
    );

    layer.add(
        new Konva.Line({
            points: [
                BODY_X - 22,
                160,
                BODY_X - 10,
                88,
                BODY_X + 18,
                BODY_TOP + 10,
                BODY_X + BODY_W - 18,
                BODY_TOP + 10,
                BODY_X + BODY_W + 10,
                88,
                BODY_X + BODY_W + 22,
                160,
            ],
            closed: true,
            fill: p.bodyFill,
            stroke: p.bodyLine,
            strokeWidth: 2,
            listening: false,
        }),
    );

    layer.add(
        new Konva.Rect({
            x: BODY_X + 8,
            y: 95,
            width: BODY_W - 16,
            height: 50,
            fill: p.glass,
            stroke: p.bodyLine,
            strokeWidth: 2,
            cornerRadius: 7,
            listening: false,
        }),
    );

    for (let i = 0; i < 5; i += 1) {
        layer.add(
            new Konva.Line({
                points: [BODY_X + 22 + i * 22, 48, BODY_X + 22 + i * 22, 82],
                stroke: p.bodyLine,
                strokeWidth: 1,
                opacity: 0.7,
                listening: false,
            }),
        );
    }

    [
        [BODY_X - 44, 94],
        [BODY_X + BODY_W + 24, 94],
    ].forEach(([x, y]) => {
        layer.add(
            new Konva.Rect({
                x,
                y,
                width: 24,
                height: 43,
                fill: p.glass,
                stroke: p.bodyLine,
                strokeWidth: 2,
                cornerRadius: 5,
                listening: false,
            }),
        );
    });

    [
        [BODY_X + 10, 170, bodyBottom - 210],
        [BODY_X + BODY_W - 10, 170, bodyBottom - 210],
    ].forEach(([x, y, h]) => {
        layer.add(
            new Konva.Line({
                points: [x, y, x, y + h],
                stroke: p.bodyLine,
                strokeWidth: 5,
                lineCap: 'round',
                listening: false,
            }),
        );
    });

    axleCenters.forEach((y) => drawAxle(layer, y, p));

    layer.add(
        new Konva.Rect({
            x: BODY_X - 18,
            y: bodyBottom - 22,
            width: BODY_W + 36,
            height: 20,
            fill: p.bodyShade,
            stroke: p.bodyLine,
            strokeWidth: 2,
            listening: false,
        }),
    );

    [
        [BODY_X - 26, bodyBottom - 22],
        [BODY_X + BODY_W + 8, bodyBottom - 22],
    ].forEach(([x, y]) => {
        layer.add(
            new Konva.Rect({
                x,
                y,
                width: 24,
                height: 17,
                fill: '#dc2626',
                stroke: '#7f1d1d',
                strokeWidth: 1,
                listening: false,
            }),
        );
    });
}

function drawSlot(layer, slot, selected, onSelect, p, mode) {
    const code = String(slot.code);
    const displayCode = displayCodeFor(slot);
    const spec = layoutFor(slot, mode);
    const empty = !slot.tyre_code;
    const hitGroup = new Konva.Group({ listening: true });

    if (spec.kind === 'spare') {
        const [boxX, boxY, boxW, boxH] = spec.box;
        const [wheelX, wheelY, wheelSize] = spec.wheel;

        layer.add(
            new Konva.Rect({
                x: boxX,
                y: boxY,
                width: boxW,
                height: boxH,
                fill: 'rgba(255, 255, 255, 0.36)',
                stroke: selected ? SELECT.stroke : '#2563eb',
                strokeWidth: selected ? 3 : 1.5,
                dash: [8, 6],
                cornerRadius: 9,
                listening: false,
            }),
        );
        drawSpareWheel(layer, wheelX, wheelY, wheelSize, p, empty);

        hitGroup.add(
            new Konva.Rect({
                x: boxX,
                y: boxY,
                width: boxW,
                height: boxH,
                fill: 'transparent',
            }),
        );
        layer.add(hitGroup);
    } else {
        const [x, y, w, h] = spec.tire;
        drawTreadedTyre(layer, x, y, w, h, p, empty);
        hitGroup.add(
            new Konva.Rect({
                x,
                y,
                width: w,
                height: h,
                fill: selected ? SELECT.fill : 'transparent',
                stroke: selected ? SELECT.stroke : 'transparent',
                strokeWidth: 3,
                cornerRadius: 12,
            }),
        );
        layer.add(hitGroup);
    }

    drawBadge(layer, spec.badge[0], spec.badge[1], displayCode, selected, empty, () => onSelect(code));

    hitGroup.on('mouseenter', () => {
        layer.getStage().container().style.cursor = 'pointer';
    });
    hitGroup.on('mouseleave', () => {
        layer.getStage().container().style.cursor = 'default';
    });
    hitGroup.on('click tap', () => onSelect(code));

    const accent = statusFor(slot);
    if (empty) {
        const [x, y] = spec.kind === 'spare' ? spec.box : spec.tire;
        layer.add(
            new Konva.Circle({
                x: x + 8,
                y: y + 8,
                radius: 4,
                fill: accent,
                listening: false,
            }),
        );
    }
}

function createTyreMap(container, config) {
    const mode = config.assetType === 'trailer' ? 'trailer' : 'truck';
    const design = designFor(mode, config.slots ?? []);
    const stage = new Konva.Stage({
        container,
        width: design.width,
        height: design.height,
    });
    const layer = new Konva.Layer();
    stage.add(layer);

    function redraw() {
        const p = palette();
        layer.destroyChildren();
        drawFrame(layer, p, design);
        drawVehicleBody(layer, p, mode, config.slots ?? [], design);
        (config.slots ?? []).forEach((slot) => {
            drawSlot(
                layer,
                slot,
                String(config.selectedPosition ?? '') === String(slot.code),
                (code) => {
                    config.selectedPosition = code;
                    redraw();
                    config.onSelect?.(code);
                },
                p,
                mode,
            );
        });
        layer.draw();
    }

    function resize() {
        const wrap = container.parentElement;
        if (!wrap) {
            return;
        }

        const scale = Math.min(wrap.clientWidth / design.width, 1);
        stage.width(design.width * scale);
        stage.height(design.height * scale);
        container.style.minHeight = `${design.height * scale}px`;
        stage.scale({ x: scale, y: scale });
        stage.batchDraw();
    }

    redraw();
    resize();

    const ro = new ResizeObserver(resize);
    ro.observe(container.parentElement ?? container);

    return {
        stage,
        select(code) {
            config.selectedPosition = code;
            redraw();
        },
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
        assetType: config.assetType ?? null,
        onSelect(code) {
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
    document.querySelectorAll('[data-tyre-map-konva]').forEach((host) => delete host.dataset.mounted);
}

document.addEventListener('DOMContentLoaded', initAll);
document.addEventListener('livewire:navigated', () => {
    teardownAll();
    initAll();
});

window.TmsTyreMapKonva = { initAll, teardownAll };
