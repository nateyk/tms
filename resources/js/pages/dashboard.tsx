import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowActionCard, WorkflowHeader } from "@/components/workflow/workflow-ui";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/react";
import { ClipboardCheck, Gauge, MoveRight, ShieldCheck } from "lucide-react";
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
} from "recharts";

type StatCard = {
    label: string;
    value: number;
    href?: string;
};

type TodayWorkCard = {
    title: string;
    description: string;
    value: number;
    href: string;
    actionLabel: string;
    tone: "default" | "success" | "warning" | "danger" | "info";
};

type ChartPoint = { name: string; value: number; fill?: string };

export default function Dashboard({
    stats,
    todayWork,
    tyreStatusChart,
    movementsTrend,
    tyresByLocation,
    fleetUtilization,
}: {
    stats: StatCard[];
    todayWork: TodayWorkCard[];
    tyreStatusChart: { labels: string[]; data: number[] };
    movementsTrend: { labels: string[]; data: number[] };
    tyresByLocation: { labels: string[]; data: number[]; colors: string[] };
    fleetUtilization: { filled: number; empty: number };
}) {
    const statusData: ChartPoint[] = tyreStatusChart.labels.map((label, index) => ({
        name: label,
        value: tyreStatusChart.data[index] ?? 0,
    }));

    const trendData = movementsTrend.labels.map((label, index) => ({
        name: label,
        movements: movementsTrend.data[index] ?? 0,
    }));

    const locationData: ChartPoint[] = tyresByLocation.labels.map((label, index) => ({
        name: label,
        value: tyresByLocation.data[index] ?? 0,
        fill: tyresByLocation.colors[index],
    }));

    const utilizationData = [
        { name: "Filled positions", value: fleetUtilization.filled, fill: "#16a34a" },
        { name: "Empty positions", value: fleetUtilization.empty, fill: "#94a3b8" },
    ];

    const workIcons = [Gauge, ClipboardCheck, MoveRight, ShieldCheck];

    return (
        <AuthenticatedLayout header="Fleet Tyre Operations">
            <Head title="Fleet Tyre Operations" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Today's Work"
                    description="Record KM first, inspect tyre maps, set missing baselines, complete movements, then approve and report."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {todayWork.map((item, index) => (
                        <WorkflowActionCard
                            key={item.title}
                            title={item.title}
                            description={item.description}
                            value={item.value.toLocaleString()}
                            href={item.href}
                            actionLabel={item.actionLabel}
                            tone={item.tone}
                            icon={workIcons[index]}
                        />
                    ))}
                </div>

                <div>
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Fleet Snapshot
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {stats.map((stat) => (
                            <Card key={stat.label}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {stat.href ? (
                                            <Link href={stat.href} className="hover:underline">
                                                {stat.label}
                                            </Link>
                                        ) : (
                                            stat.label
                                        )}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold">
                                        {stat.value.toLocaleString()}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                <div>
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Review Charts
                    </h2>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Tyres by status</CardTitle>
                            </CardHeader>
                            <CardContent className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={statusData}
                                            dataKey="value"
                                            nameKey="name"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={90}
                                            label
                                        />
                                        <Tooltip />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Tyres by location</CardTitle>
                            </CardHeader>
                            <CardContent className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={locationData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={90} label>
                                            {locationData.map((entry) => (
                                                <Cell key={entry.name} fill={entry.fill ?? "#8884d8"} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Completed movements (8 weeks)</CardTitle>
                            </CardHeader>
                            <CardContent className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={trendData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" />
                                        <YAxis allowDecimals={false} />
                                        <Tooltip />
                                        <Line type="monotone" dataKey="movements" stroke="#2563eb" strokeWidth={2} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Fleet position fill rate</CardTitle>
                            </CardHeader>
                            <CardContent className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={utilizationData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" />
                                        <YAxis allowDecimals={false} />
                                        <Tooltip />
                                        <Bar dataKey="value">
                                            {utilizationData.map((entry) => (
                                                <Cell key={entry.name} fill={entry.fill} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
