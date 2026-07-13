import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";

type OdometerReading = {
    id: number;
    odometer: number;
    reading_date: string;
    source: string;
    source_label?: string;
    source_id: number | null;
    recorded_by: number | string | null;
    recorded_by_name?: string | null;
    notes: string | null;
    created_at: string;
};

type OdometerReadingHistoryProps = {
    readings?: OdometerReading[];
};

export function OdometerReadingHistory({ readings = [] }: OdometerReadingHistoryProps) {
    const getSourceLabel = (source: string) => {
        const labelMap: Record<string, string> = {
            manual: "Manual",
            movement: "Movement",
            baseline: "Baseline",
            import: "Import",
        };
        return labelMap[source] || source;
    };

    const getSourceBadgeColor = (source: string) => {
        const colorMap: Record<string, string> = {
            manual: "bg-blue-500",
            movement: "bg-green-500",
            baseline: "bg-purple-500",
            import: "bg-orange-500",
        };
        return colorMap[source] || "bg-gray-500";
    };

    if (readings.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Odometer Reading History</CardTitle>
                    <CardDescription>
                        Historical odometer readings for this vehicle
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground py-4">
                        No odometer readings recorded yet.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Odometer Reading History</CardTitle>
                <CardDescription>
                    Historical odometer readings for this vehicle
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Odometer (KM)</TableHead>
                                <TableHead>Source</TableHead>
                                <TableHead>Recorded By</TableHead>
                                <TableHead>Notes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {readings.map((reading) => (
                                <TableRow key={reading.id}>
                                    <TableCell>{reading.reading_date}</TableCell>
                                    <TableCell className="font-medium">
                                        {reading.odometer.toLocaleString()}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={getSourceBadgeColor(reading.source)}>
                                            {reading.source_label || getSourceLabel(reading.source)}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {reading.recorded_by_name || <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                    <TableCell>
                                        {reading.notes || <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
