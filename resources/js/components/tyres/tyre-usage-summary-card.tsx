import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { AlertCircle } from "lucide-react";

type UsageSummary = {
    has_baseline: boolean;
    status: string;
    total_used_km: number | null;
    usage_percentage: number | null;
    estimated_remaining_percentage: number | null;
    baseline_percentage: number | null;
    expected_life_km: number | null;
};

export function TyreUsageSummaryCard({ usage }: { usage: UsageSummary }) {
    const getStatusColor = (status: string) => {
        const colorMap: Record<string, string> = {
            "Baseline Required": "bg-gray-500",
            Good: "bg-green-500",
            Watch: "bg-yellow-500",
            Low: "bg-orange-500",
            "End of Life": "bg-red-500",
            Finished: "bg-gray-500",
        };
        return colorMap[status] || "bg-gray-500";
    };

    const getProgressColor = (percentage: number | null) => {
        if (percentage === null) return "bg-gray-500";
        if (percentage >= 60) return "bg-green-500";
        if (percentage >= 30) return "bg-yellow-500";
        if (percentage >= 10) return "bg-orange-500";
        return "bg-red-500";
    };

    if (!usage.has_baseline) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-yellow-500" />
                        Usage Summary
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-2 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-md border border-yellow-200 dark:border-yellow-800">
                        <AlertCircle className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                        <div>
                            <p className="font-medium text-yellow-900 dark:text-yellow-100">
                                Baseline Required
                            </p>
                            <p className="text-sm text-yellow-700 dark:text-yellow-300">
                                Create a baseline to track tyre usage and remaining life.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    const progressValue = usage.estimated_remaining_percentage ?? 0;
    const baselineValue = usage.baseline_percentage ?? 100;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Usage Summary</CardTitle>
                <CardDescription>
                    Track tyre consumption and remaining life based on baseline
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Status</span>
                    <Badge className={getStatusColor(usage.status)}>
                        {usage.status}
                    </Badge>
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Baseline Percentage</span>
                        <span className="font-medium">{usage.baseline_percentage}%</span>
                    </div>
                    <div className="h-2 w-full bg-secondary rounded-full overflow-hidden">
                        <div
                            className="h-full bg-blue-500 transition-all"
                            style={{ width: `${baselineValue}%` }}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Estimated Remaining</span>
                        <span className="font-medium">{usage.estimated_remaining_percentage?.toFixed(1)}%</span>
                    </div>
                    <div className="h-2 w-full bg-secondary rounded-full overflow-hidden">
                        <div
                            className={`h-full transition-all ${getProgressColor(usage.estimated_remaining_percentage)}`}
                            style={{ width: `${progressValue}%` }}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                    <div>
                        <p className="text-sm text-muted-foreground">Expected Life KM</p>
                        <p className="text-lg font-semibold">
                            {usage.expected_life_km?.toLocaleString()}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Total Used KM</p>
                        <p className="text-lg font-semibold">
                            {usage.total_used_km?.toLocaleString() ?? "0"}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Usage Percentage</p>
                        <p className="text-lg font-semibold">
                            {usage.usage_percentage?.toFixed(1)}%
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Remaining KM</p>
                        <p className="text-lg font-semibold">
                            {usage.expected_life_km && usage.total_used_km !== null
                                ? (usage.expected_life_km - usage.total_used_km).toLocaleString()
                                : "—"}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
