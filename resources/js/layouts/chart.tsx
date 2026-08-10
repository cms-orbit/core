import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import type { LayoutComponentProps } from '../contract';
import { useT } from '../lib/i18n';
import { Card, CardBody, CardHeader } from '../ui/card';
import { EmptyState } from '../ui/empty-state';

interface ChartDataset {
    name?: string;
    labels?: (string | number)[];
    values?: number[];
}

const DEFAULT_COLORS = [
    '#4f46e5', '#2ec7c9', '#b6a2de', '#5ab1ef', '#ffb980',
    '#d87a80', '#8d98b3', '#e5cf0d', '#97b552', '#dc69aa',
];

function parseJson<T>(value: unknown, fallback: T): T {
    if (typeof value === 'string') {
        try {
            return JSON.parse(value) as T;
        } catch {
            return fallback;
        }
    }

    return (value as T) ?? fallback;
}

/**
 * Recharts-backed chart layout. Consumes the serialized chart payload
 * (`{ type, data: ChartDataset[], colors }`). The backend serializes the chart
 * `data`/`labels`/`colors` keys (JSON strings are also accepted).
 */
export function ChartLayout({ node }: LayoutComponentProps) {
    const t = useT();
    const type = (node.data.type as string) ?? 'line';
    const title = node.data.title as string | null;
    const description = node.data.description as string | null;
    const height = Number(node.data.height ?? 250);
    const datasets = parseJson<ChartDataset[]>(node.data.data, []);
    const colors = parseJson<string[]>(node.data.colors, DEFAULT_COLORS);

    const categories = Array.from(
        new Set(datasets.flatMap((dataset) => dataset.labels ?? [])),
    );

    const rows = categories.map((category, index) => {
        const row: Record<string, string | number> = { name: String(category) };

        datasets.forEach((dataset) => {
            if (dataset.name) {
                row[dataset.name] = dataset.values?.[index] ?? 0;
            }
        });

        return row;
    });

    const seriesNames = datasets
        .map((dataset) => dataset.name)
        .filter((name): name is string => Boolean(name));

    return (
        <Card>
            <CardHeader title={title} />
            <CardBody>
                {description ? (
                    <p className="mb-3 text-sm text-gray-500 dark:text-gray-400">{description}</p>
                ) : null}
                {rows.length === 0 ? (
                    <EmptyState icon="bs.bar-chart" heading={t('No chart data.')} />
                ) : (
                    <ResponsiveContainer width="100%" height={height}>
                        {type === 'pie' || type === 'percentage' ? (
                            <PieChart>
                                <Tooltip />
                                <Pie
                                    data={rows}
                                    dataKey={seriesNames[0] ?? 'value'}
                                    nameKey="name"
                                    outerRadius={height / 2.6}
                                >
                                    {rows.map((_, index) => (
                                        <Cell key={index} fill={colors[index % colors.length]} />
                                    ))}
                                </Pie>
                            </PieChart>
                        ) : type === 'bar' ? (
                            <BarChart data={rows}>
                                <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis fontSize={12} />
                                <Tooltip />
                                <Legend />
                                {seriesNames.map((series, index) => (
                                    <Bar key={series} dataKey={series} fill={colors[index % colors.length]} />
                                ))}
                            </BarChart>
                        ) : (
                            <LineChart data={rows}>
                                <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis fontSize={12} />
                                <Tooltip />
                                <Legend />
                                {seriesNames.map((series, index) => (
                                    <Line
                                        key={series}
                                        type="monotone"
                                        dataKey={series}
                                        stroke={colors[index % colors.length]}
                                        dot={false}
                                    />
                                ))}
                            </LineChart>
                        )}
                    </ResponsiveContainer>
                )}
            </CardBody>
        </Card>
    );
}
