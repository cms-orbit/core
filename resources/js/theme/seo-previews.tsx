import { cn } from '../lib/cn';

export interface SeoPreviewData {
    siteTitle: string;
    titleSeparator: string;
    siteDescription: string;
    snippet: string;
    thumbnailUrl: string | null;
    previewUrl: string;
    previewHostname: string;
    robots: string;
}

function resolveDescription(data: SeoPreviewData, preferSnippet: boolean): string {
    const snippet = data.snippet.trim();
    const description = data.siteDescription.trim();

    if (preferSnippet) {
        return snippet || description || '사이트 설명이 여기에 표시됩니다.';
    }

    return description || snippet || '사이트 설명이 여기에 표시됩니다.';
}

function truncate(text: string, max: number): string {
    if (text.length <= max) {
        return text;
    }

    return `${text.slice(0, max - 1).trim()}…`;
}

function PreviewImage({ url, className }: { url: string | null; className?: string }) {
    if (url) {
        return <img src={url} alt="" className={cn('w-full object-cover', className)} />;
    }

    return (
        <div
            className={cn(
                'flex w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-xs text-gray-400 dark:from-gray-800 dark:to-gray-900 dark:text-gray-500',
                className,
            )}
        >
            공유 이미지 없음
        </div>
    );
}

/** Google organic search result snippet. */
export function GoogleSearchPreview({ data }: { data: SeoPreviewData }) {
    const title = data.siteTitle.trim() || '사이트 제목';
    const description = truncate(resolveDescription(data, true), 160);
    const exampleTitle = `${title} ${data.titleSeparator.trim() || '|'} 예시 페이지`;

    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">Google 검색</p>
            <div className="space-y-1 font-[system-ui,sans-serif]">
                <p className="truncate text-sm text-[#202124] dark:text-gray-200">{data.previewHostname}</p>
                <p className="text-xl leading-snug text-[#1a0dab] hover:underline dark:text-[#8ab4f8]">{exampleTitle}</p>
                <p className="text-sm leading-relaxed text-[#4d5156] dark:text-gray-400">{description}</p>
            </div>
            <p className="mt-3 text-[11px] text-gray-400">robots: {data.robots || 'index,follow'}</p>
        </div>
    );
}

/** KakaoTalk link paste preview (Open Graph style). */
export function KakaoTalkPreview({ data }: { data: SeoPreviewData }) {
    const title = data.siteTitle.trim() || '사이트 제목';
    const description = truncate(resolveDescription(data, false), 90);

    return (
        <div className="overflow-hidden rounded-xl border border-[#e5e5e5] bg-[#f9f9f9] dark:border-white/10 dark:bg-gray-900">
            <p className="border-b border-[#ececec] px-4 py-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10">
                카카오톡 링크 미리보기
            </p>
            <div className="p-3">
                <div className="overflow-hidden rounded-lg border border-[#e8e8e8] bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <PreviewImage url={data.thumbnailUrl} className="aspect-[1.91/1] max-h-40" />
                    <div className="space-y-1 px-3 py-2.5">
                        <p className="line-clamp-2 text-sm font-semibold leading-snug text-[#191919] dark:text-gray-100">
                            {title}
                        </p>
                        <p className="line-clamp-2 text-xs leading-relaxed text-[#767676] dark:text-gray-400">
                            {description}
                        </p>
                        <p className="truncate pt-0.5 text-[11px] text-[#b3b3b3] dark:text-gray-500">
                            {data.previewHostname}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

/** Facebook, Notion and other Open Graph large-image cards. */
export function OpenGraphPreview({ data, label = 'Facebook / Notion' }: { data: SeoPreviewData; label?: string }) {
    const title = data.siteTitle.trim() || '사이트 제목';
    const description = truncate(resolveDescription(data, false), 120);

    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <p className="border-b border-gray-100 px-4 py-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10">
                {label}
            </p>
            <div className="p-3">
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-[#f2f3f5] dark:border-white/10 dark:bg-gray-800">
                    <PreviewImage url={data.thumbnailUrl} className="aspect-[1.91/1] max-h-44" />
                    <div className="space-y-0.5 border-t border-gray-200 bg-white px-3 py-2.5 dark:border-white/10 dark:bg-gray-900">
                        <p className="truncate text-[11px] uppercase tracking-wide text-[#606770] dark:text-gray-500">
                            {data.previewHostname}
                        </p>
                        <p className="line-clamp-2 text-base font-semibold leading-snug text-[#050505] dark:text-gray-100">
                            {title}
                        </p>
                        <p className="line-clamp-2 text-sm leading-relaxed text-[#606770] dark:text-gray-400">
                            {description}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
