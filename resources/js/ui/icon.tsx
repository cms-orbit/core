import {
    ArrowClockwise,
    ArrowCounterclockwise,
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    BarChart,
    BarChartLine,
    Bell,
    BellFill,
    Bookmark,
    Bookmarks,
    BoxArrowInRight,
    BoxArrowRight,
    Brush,
    Bug,
    Calendar,
    Calendar3,
    Camera,
    CameraVideo,
    CardList,
    CaretDownFill,
    CaretRightFill,
    Check2,
    CheckCircle,
    CheckLg,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Clipboard,
    ClipboardCheck,
    Clock,
    Code,
    CodeSlash,
    Columns,
    Dash,
    DashLg,
    Database,
    Diagram3,
    Download,
    Envelope,
    EnvelopeFill,
    ExclamationCircle,
    ExclamationTriangle,
    Eye,
    EyeSlash,
    FileEarmark,
    FileEarmarkText,
    Film,
    Filter,
    Folder,
    Folder2Open,
    FolderFill,
    Funnel,
    FunnelFill,
    Gear,
    GearFill,
    Globe,
    Globe2,
    GraphUp,
    Grid,
    Heart,
    House,
    HouseDoor,
    type Icon as BootstrapIconComponent,
    Image,
    Images,
    InfoCircle,
    Justify,
    Key,
    Layers,
    Link45deg,
    List,
    ListUl,
    Lock,
    Mic,
    Megaphone,
    Moon,
    Palette,
    Paragraph,
    Pencil,
    PencilSquare,
    People,
    Person,
    PersonCircle,
    PieChart,
    PlayCircle,
    Plus,
    PlusCircle,
    PlusLg,
    Printer,
    QuestionCircle,
    Reply,
    Save,
    Search,
    Send,
    Server,
    Share,
    ShieldLock,
    Sliders,
    Star,
    StarFill,
    Sun,
    Table,
    Tag,
    Tags,
    Terminal,
    ThreeDots,
    ThreeDotsVertical,
    Tools,
    Translate,
    Trash,
    TrashFill,
    Type,
    Unlock,
    Upload,
    Window,
    WindowStack,
    Wrench,
    X,
    XCircle,
    XLg,
} from 'react-bootstrap-icons';

/**
 * Server-driven icons arrive as dotted names (e.g. "bs.plus-circle"), faithful
 * to the PHP contract that the previous Blade icon set used. We ship a curated,
 * tree-shaken set rather than the full ~2000-glyph package so the admin bundle
 * (and the Vite manifest) stay small. Unknown names render nothing.
 *
 * Need an icon that isn't here? Add the named import above and a registry entry,
 * or register a custom React component via `registerComponents` and reference it
 * as a component instead of an icon string.
 */
const registry: Record<string, BootstrapIconComponent> = {
    ArrowClockwise,
    ArrowCounterclockwise,
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    BarChart,
    BarChartLine,
    Bell,
    BellFill,
    Bookmark,
    Bookmarks,
    BoxArrowInRight,
    BoxArrowRight,
    Brush,
    Bug,
    Calendar,
    Calendar3,
    Camera,
    CameraVideo,
    CardList,
    CaretDownFill,
    CaretRightFill,
    Check2,
    CheckCircle,
    CheckLg,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Clipboard,
    ClipboardCheck,
    Clock,
    Code,
    CodeSlash,
    Columns,
    Dash,
    DashLg,
    Database,
    Diagram3,
    Download,
    Envelope,
    EnvelopeFill,
    ExclamationCircle,
    ExclamationTriangle,
    Eye,
    EyeSlash,
    FileEarmark,
    FileEarmarkText,
    Film,
    Filter,
    Folder,
    Folder2Open,
    FolderFill,
    Funnel,
    FunnelFill,
    Gear,
    GearFill,
    Globe,
    Globe2,
    GraphUp,
    Grid,
    Heart,
    House,
    HouseDoor,
    Image,
    Images,
    InfoCircle,
    Justify,
    Key,
    Layers,
    Link45deg,
    List,
    ListUl,
    Lock,
    Mic,
    Megaphone,
    Moon,
    Palette,
    Paragraph,
    Pencil,
    PencilSquare,
    People,
    Person,
    PersonCircle,
    PieChart,
    PlayCircle,
    Plus,
    PlusCircle,
    PlusLg,
    Printer,
    QuestionCircle,
    Reply,
    Save,
    Search,
    Send,
    Server,
    Share,
    ShieldLock,
    Sliders,
    Star,
    StarFill,
    Sun,
    Table,
    Tag,
    Tags,
    Terminal,
    ThreeDots,
    ThreeDotsVertical,
    Tools,
    Translate,
    Trash,
    TrashFill,
    Type,
    Unlock,
    Upload,
    Window,
    WindowStack,
    Wrench,
    X,
    XCircle,
    XLg,
};

const resolveCache = new Map<string, BootstrapIconComponent | null>();

function toPascalCase(value: string): string {
    return value
        .split(/[-_\s]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
}

function resolveIcon(name: string): BootstrapIconComponent | null {
    const cached = resolveCache.get(name);

    if (cached !== undefined) {
        return cached;
    }

    // Drop an optional set prefix ("bs.gear" -> "gear").
    const bare = name.includes('.') ? name.slice(name.indexOf('.') + 1) : name;
    const component = registry[toPascalCase(bare)] ?? null;

    resolveCache.set(name, component);

    return component;
}

export interface IconProps {
    name?: string | null;
    size?: number | string;
    className?: string;
}

export function Icon({ name, size = '1em', className }: IconProps) {
    if (!name) {
        return null;
    }

    const Resolved = resolveIcon(name);

    if (!Resolved) {
        if (import.meta.env?.DEV) {
             
            console.warn(`[orbit] No icon registered for "${name}".`);
        }

        return null;
    }

    return <Resolved size={size} className={className} aria-hidden focusable={false} />;
}
