import type { CSSProperties } from 'react';
import { cn } from '../lib/cn';
import { Icon } from '../ui/icon';
import { Table, TableBody, TableCell, TableHead, TableHeaderCell, TableRow } from '../ui/table';
import type { LayoutPreviewColors } from './layout-themes';

const SAMPLE_ROWS = [
    { name: 'Alice Kim', email: 'alice@example.com', joined: '2시간 전' },
    { name: 'Bob Lee', email: 'bob@example.com', joined: '2026-06-28 (14:20)' },
];

/** Mini table preview for design-settings colour presets. */
export function TablePreviewSnippet({
    colors,
    className,
}: {
    colors: LayoutPreviewColors;
    className?: string;
}) {
    const shellStyle = {
        borderColor: colors.panelBorder,
        backgroundColor: colors.panelBg,
        '--color-orbit-panel-bg': colors.panelBg,
        '--color-orbit-panel-border': colors.panelBorder,
        '--color-orbit-table-row-border': colors.tableRowBorder,
        '--color-orbit-nav-group-fg': colors.navGroupFg,
        '--color-orbit-secondary': colors.secondary,
        '--color-orbit-nav-muted': colors.navMuted,
        '--color-orbit-primary': colors.primary,
    } as CSSProperties;

    return (
        <div className={cn('space-y-2', className)}>
            <p className="text-xs font-medium text-gray-500 dark:text-gray-400">테이블 미리보기</p>
            <div className="overflow-hidden rounded-lg border" style={shellStyle}>
                <Table>
                    <TableHead>
                        <TableRow interactive={false}>
                            <TableHeaderCell>이름</TableHeaderCell>
                            <TableHeaderCell>이메일</TableHeaderCell>
                            <TableHeaderCell align="right">가입일</TableHeaderCell>
                            <TableHeaderCell align="right">기능</TableHeaderCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {SAMPLE_ROWS.map((row) => (
                            <TableRow key={row.email}>
                                <TableCell>{row.name}</TableCell>
                                <TableCell>{row.email}</TableCell>
                                <TableCell align="right">
                                    <span className="whitespace-nowrap tabular-nums">{row.joined}</span>
                                </TableCell>
                                <TableCell align="right">
                                    <div className="flex items-center justify-end gap-0.5">
                                        <span
                                            className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-[color-mix(in_srgb,var(--color-orbit-nav-muted,#f8fafc)_72%,transparent)]"
                                            aria-hidden
                                        >
                                            <Icon name="bs.eye" className="text-base" />
                                        </span>
                                        <span
                                            className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-[color-mix(in_srgb,var(--color-orbit-nav-muted,#f8fafc)_72%,transparent)]"
                                            aria-hidden
                                        >
                                            <Icon name="bs.pencil" className="text-base" />
                                        </span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
