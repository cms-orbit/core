import { cn } from '../lib/cn';
import type { LayoutMode } from './branding';
import type { LayoutPreviewColors } from './layout-themes';

export interface LayoutPreviewProps {
    mode: LayoutMode;
    colors: LayoutPreviewColors;
    className?: string;
    /** Miniature card vs large live preview frame. */
    variant?: 'snippet' | 'live';
}

const SNIPPET = { width: 140, height: 88 };
const LIVE = { width: 480, height: 300 };

function Bar({
    x,
    y,
    w,
    h,
    fill,
    rx = 2,
    stroke,
    strokeWidth = 0,
}: {
    x: number;
    y: number;
    w: number;
    h: number;
    fill: string;
    rx?: number;
    stroke?: string;
    strokeWidth?: number;
}) {
    return <rect x={x} y={y} width={w} height={h} rx={rx} fill={fill} stroke={stroke} strokeWidth={strokeWidth} />;
}

function PaletteSplitPreview({
    colors,
    w,
    h,
    scale,
}: {
    colors: LayoutPreviewColors;
    w: number;
    h: number;
    scale: number;
}) {
    const rail = 14 * scale;
    const side = 36 * scale;
    const header = 14 * scale;

    return (
        <>
            <Bar x={0} y={0} w={rail} h={h} fill={colors.railBg} rx={0} />
            <Bar x={3 * scale} y={6 * scale} w={8 * scale} h={8 * scale} fill={colors.railSymbolBg} rx={2} />
            <Bar x={3 * scale} y={20 * scale} w={8 * scale} h={8 * scale} fill={colors.railIcon} rx={2} />
            <Bar x={rail} y={0} w={side} h={h} fill={colors.navBg} stroke={colors.navBorder} strokeWidth={scale} />
            <Bar x={rail + 6 * scale} y={8 * scale} w={side - 12 * scale} h={4 * scale} fill={colors.primary} rx={1} />
            <Bar x={rail + 6 * scale} y={20 * scale} w={10 * scale} h={2 * scale} fill={colors.navGroupFg} rx={1} />
            <Bar x={rail + 4 * scale} y={26 * scale} w={side - 8 * scale} h={8 * scale} fill={colors.navActiveBg} rx={2 * scale} />
            <Bar x={rail + 8 * scale} y={29 * scale} w={side - 22 * scale} h={2 * scale} fill={colors.navActiveFg} rx={1} />
            <Bar x={rail + side} y={0} w={w - rail - side} h={header} fill={colors.headerBg} stroke={colors.headerBorder} strokeWidth={scale} />
            <Bar
                x={rail + side + 8 * scale}
                y={header + 10 * scale}
                w={w - rail - side - 16 * scale}
                h={h - header - 20 * scale}
                fill={colors.panelBg}
                stroke={colors.panelBorder}
                strokeWidth={scale}
                rx={3 * scale}
            />
            <Bar x={rail + side + 14 * scale} y={header + 18 * scale} w={24 * scale} h={4 * scale} fill={colors.accent} rx={1} />
            <Bar
                x={rail + side + 14 * scale}
                y={header + 28 * scale}
                w={w - rail - side - 32 * scale}
                h={3 * scale}
                fill={colors.secondary}
                rx={1}
            />
        </>
    );
}

function SinglePreview({ colors, w, h, scale }: { colors: LayoutPreviewColors; w: number; h: number; scale: number }) {
    const side = 44 * scale;
    const header = 14 * scale;

    return (
        <>
            <Bar x={0} y={0} w={side} h={h} fill={colors.navBg} stroke={colors.navBorder} strokeWidth={scale} />
            <Bar x={8 * scale} y={8 * scale} w={side - 16 * scale} h={6 * scale} fill={colors.primary} rx={2} />
            <Bar x={8 * scale} y={20 * scale} w={side - 16 * scale} h={scale} fill={colors.navSectionBorder} rx={1} />
            <Bar x={8 * scale} y={23 * scale} w={5 * scale} h={5 * scale} fill={colors.primary} rx={1.5 * scale} />
            <Bar x={16 * scale} y={24 * scale} w={14 * scale} h={2 * scale} fill={colors.navSectionFg} rx={1} />
            <Bar x={8 * scale} y={30 * scale} w={12 * scale} h={2 * scale} fill={colors.navGroupFg} rx={1} />
            <Bar x={8 * scale} y={35 * scale} w={side - 16 * scale} h={4 * scale} fill={colors.navActiveBg} rx={1} />
            <Bar x={side} y={0} w={w - side} h={header} fill={colors.headerBg} stroke={colors.headerBorder} strokeWidth={scale} />
            <Bar
                x={side + 8 * scale}
                y={header + 10 * scale}
                w={w - side - 16 * scale}
                h={h - header - 20 * scale}
                fill={colors.panelBg}
                stroke={colors.panelBorder}
                strokeWidth={scale}
                rx={3 * scale}
            />
            <Bar x={side + 14 * scale} y={header + 18 * scale} w={28 * scale} h={4 * scale} fill={colors.accent} rx={1} />
            <Bar x={side + 14 * scale} y={header + 28 * scale} w={w - side - 30 * scale} h={3 * scale} fill={colors.secondary} rx={1} />
        </>
    );
}

function TopbarPreview({ colors, w, h, scale }: { colors: LayoutPreviewColors; w: number; h: number; scale: number }) {
    const header = 16 * scale;
    const sub = 10 * scale;

    return (
        <>
            <Bar x={0} y={0} w={w} h={header} fill={colors.headerBg} stroke={colors.headerBorder} strokeWidth={scale} />
            <Bar x={8 * scale} y={5 * scale} w={20 * scale} h={6 * scale} fill={colors.primary} rx={2} />
            <Bar x={34 * scale} y={6 * scale} w={16 * scale} h={3 * scale} fill={colors.navSectionFg} rx={1} />
            <Bar x={54 * scale} y={6 * scale} w={14 * scale} h={3 * scale} fill={colors.navGroupFg} rx={1} />
            <Bar x={0} y={header} w={w} h={sub} fill={colors.navBg} stroke={colors.navBorder} strokeWidth={scale} />
            <Bar x={8 * scale} y={header + 3 * scale} w={20 * scale} h={4 * scale} fill={colors.navActiveBg} rx={1} />
            <Bar x={32 * scale} y={header + 4 * scale} w={16 * scale} h={2 * scale} fill={colors.navGroupFg} rx={1} />
            <Bar
                x={8 * scale}
                y={header + sub + 8 * scale}
                w={w - 16 * scale}
                h={h - header - sub - 16 * scale}
                fill={colors.panelBg}
                stroke={colors.panelBorder}
                strokeWidth={scale}
                rx={3 * scale}
            />
            <Bar x={14 * scale} y={header + sub + 16 * scale} w={32 * scale} h={4 * scale} fill={colors.accent} rx={1} />
            <Bar x={14 * scale} y={header + sub + 26 * scale} w={w - 28 * scale} h={3 * scale} fill={colors.secondary} rx={1} />
        </>
    );
}

function HybridPreview({ colors, w, h, scale }: { colors: LayoutPreviewColors; w: number; h: number; scale: number }) {
    const header = 16 * scale;
    const side = 40 * scale;

    return (
        <>
            <Bar x={0} y={0} w={w} h={header} fill={colors.headerBg} stroke={colors.headerBorder} strokeWidth={scale} />
            <Bar x={8 * scale} y={5 * scale} w={18 * scale} h={6 * scale} fill={colors.primary} rx={2} />
            <Bar x={32 * scale} y={6 * scale} w={14 * scale} h={3 * scale} fill={colors.navSectionFg} rx={1} />
            <Bar x={0} y={header} w={side} h={h - header} fill={colors.navBg} stroke={colors.navBorder} strokeWidth={scale} />
            <Bar x={8 * scale} y={header + 12 * scale} w={12 * scale} h={2 * scale} fill={colors.navGroupFg} rx={1} />
            <Bar x={8 * scale} y={header + 18 * scale} w={side - 16 * scale} h={4 * scale} fill={colors.navActiveBg} rx={1} />
            <Bar
                x={side + 8 * scale}
                y={header + 10 * scale}
                w={w - side - 16 * scale}
                h={h - header - 20 * scale}
                fill={colors.panelBg}
                stroke={colors.panelBorder}
                strokeWidth={scale}
                rx={3 * scale}
            />
            <Bar x={side + 14 * scale} y={header + 18 * scale} w={28 * scale} h={4 * scale} fill={colors.accent} rx={1} />
            <Bar x={side + 14 * scale} y={header + 28 * scale} w={w - side - 28 * scale} h={3 * scale} fill={colors.secondary} rx={1} />
        </>
    );
}

function PreviewSvg({
    mode,
    colors,
    dimensions,
}: {
    mode: LayoutMode;
    colors: LayoutPreviewColors;
    dimensions: { width: number; height: number };
}) {
    const { width: w, height: h } = dimensions;
    const scale = w / SNIPPET.width;

    const body = (() => {
        switch (mode) {
            case 'palette-split':
                return <PaletteSplitPreview colors={colors} w={w} h={h} scale={scale} />;
            case 'sidebar-single':
                return <SinglePreview colors={colors} w={w} h={h} scale={scale} />;
            case 'topbar':
                return <TopbarPreview colors={colors} w={w} h={h} scale={scale} />;
            case 'hybrid':
                return <HybridPreview colors={colors} w={w} h={h} scale={scale} />;
            default:
                return <PaletteSplitPreview colors={colors} w={w} h={h} scale={scale} />;
        }
    })();

    return (
        <svg
            viewBox={`0 0 ${w} ${h}`}
            width="100%"
            height="100%"
            className="block"
            aria-hidden
        >
            <rect width={w} height={h} fill={colors.pageBg} rx={scale * 4} />
            {body}
        </svg>
    );
}

/** Schematic admin-shell preview used in theme settings. */
export function LayoutPreview({ mode, colors, className, variant = 'snippet' }: LayoutPreviewProps) {
    const dimensions = variant === 'live' ? LIVE : SNIPPET;

    return (
        <div
            className={cn(
                'overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-white/10 dark:bg-gray-800',
                variant === 'snippet' ? 'aspect-[140/88] w-full' : 'w-full',
                className,
            )}
            style={variant === 'live' ? { height: LIVE.height } : undefined}
        >
            <PreviewSvg mode={mode} colors={colors} dimensions={dimensions} />
        </div>
    );
}

export const LAYOUT_MODE_LABELS: Record<LayoutMode, string> = {
    'palette-split': 'Palette split sidebar',
    'sidebar-single': 'Single sidebar',
    topbar: 'Top bar',
    hybrid: 'Hybrid',
};
