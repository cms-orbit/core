import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { LayoutComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { readCookie, writeCookie } from '../lib/cookies';
import { Icon } from '../ui/icon';

export const SETTINGS_ACCORDION_COOKIE_KEY = 'settings-accordion-state';

interface SettingsGroup {
    title: string;
    description?: string | null;
    icon?: string | null;
    uriKey: string;
    url: string;
    section?: string | null;
}

interface SettingsSection {
    id: string;
    title: string;
}

type AccordionState = Record<string, boolean>;

function asGroups(value: unknown): SettingsGroup[] {
    return Array.isArray(value) ? (value as SettingsGroup[]) : [];
}

function asSections(value: unknown): SettingsSection[] {
    return Array.isArray(value) ? (value as SettingsSection[]) : [];
}

function defaultAccordionState(sectionIds: string[]): AccordionState {
    return Object.fromEntries(sectionIds.map((id) => [id, true]));
}

function parseAccordionState(raw: string | null, sectionIds: string[]): AccordionState {
    const defaults = defaultAccordionState(sectionIds);

    if (!raw) {
        return defaults;
    }

    try {
        const parsed = JSON.parse(raw) as Record<string, unknown>;

        return sectionIds.reduce<AccordionState>((state, id) => {
            state[id] = typeof parsed[id] === 'boolean' ? parsed[id] : true;

            return state;
        }, {});
    } catch {
        return defaults;
    }
}

function useSettingsAccordionState(sectionIds: string[]) {
    const sectionKey = sectionIds.join('|');
    const [openSections, setOpenSections] = useState<AccordionState>(() => defaultAccordionState(sectionIds));

    useEffect(() => {
        setOpenSections(parseAccordionState(readCookie(SETTINGS_ACCORDION_COOKIE_KEY), sectionIds));
    }, [sectionKey]);

    const toggleSection = useCallback((sectionId: string) => {
        setOpenSections((current) => {
            const next = { ...current, [sectionId]: !current[sectionId] };
            writeCookie(SETTINGS_ACCORDION_COOKIE_KEY, JSON.stringify(next));

            return next;
        });
    }, []);

    return { openSections, toggleSection };
}

function SettingsGroupCard({ group }: { group: SettingsGroup }) {
    return (
        <Link
            href={group.url}
            className="group flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:border-orbit-primary/40 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-orbit-primary/10 text-orbit-primary">
                <Icon name={group.icon ?? 'bs.gear'} className="text-xl" />
            </span>
            <span className="min-w-0">
                <span className="block font-medium text-gray-900 group-hover:text-orbit-primary dark:text-gray-100">
                    {group.title}
                </span>
                {group.description ? (
                    <span className="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">
                        {group.description}
                    </span>
                ) : null}
            </span>
        </Link>
    );
}

/**
 * Settings hub (`settings-hub` component layout). Renders grouped accordion
 * sections of every config group the current user may access.
 */
export function SettingsHubLayout({ data }: LayoutComponentProps) {
    const groups = asGroups(data.groups);
    const sections = asSections(data.sections);

    const visibleSections = useMemo(() => {
        if (sections.length === 0) {
            return [{ id: 'basic', title: '기본설정' }];
        }

        return sections.filter((section) => groups.some((group) => (group.section ?? 'basic') === section.id));
    }, [groups, sections]);

    const sectionIds = useMemo(() => visibleSections.map((section) => section.id), [visibleSections]);
    const { openSections, toggleSection } = useSettingsAccordionState(sectionIds);

    const groupsBySection = useMemo(() => {
        const grouped = new Map<string, SettingsGroup[]>();

        for (const section of visibleSections) {
            grouped.set(section.id, []);
        }

        for (const group of groups) {
            const sectionId = group.section ?? 'basic';

            if (!grouped.has(sectionId)) {
                grouped.set(sectionId, []);
            }

            grouped.get(sectionId)?.push(group);
        }

        return grouped;
    }, [groups, visibleSections]);

    if (groups.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-gray-400">
                No settings groups available.
            </p>
        );
    }

    return (
        <div className="divide-y divide-gray-200 dark:divide-gray-800">
            {visibleSections.map((section) => {
                const sectionGroups = groupsBySection.get(section.id) ?? [];

                if (sectionGroups.length === 0) {
                    return null;
                }

                const open = openSections[section.id] ?? true;

                return (
                    <section key={section.id} className="py-5 first:pt-0 last:pb-0">
                        <button
                            type="button"
                            onClick={() => toggleSection(section.id)}
                            aria-expanded={open}
                            className="flex w-full items-center justify-between gap-4 rounded-lg py-1 text-left transition hover:text-orbit-primary"
                        >
                            <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {section.title}
                            </span>
                            <Icon
                                name="bs.chevron-down"
                                className={cn(
                                    'shrink-0 text-gray-400 transition-transform',
                                    !open && '-rotate-90',
                                )}
                            />
                        </button>
                        {open ? (
                            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {sectionGroups.map((group) => (
                                    <SettingsGroupCard key={group.uriKey} group={group} />
                                ))}
                            </div>
                        ) : null}
                    </section>
                );
            })}
        </div>
    );
}
