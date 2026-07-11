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
import { Link } from "@inertiajs/react";

type UsageHistoryItem = {
    id: number;
    vehicle_code: string | null;
    vehicle_plate: string | null;
    position_code: string;
    installed_odometer: number | null;
    removed_odometer: number | null;
    km_used: number | null;
    installed_date: string | null;
    removed_date: string | null;
    status: string;
    movement_id: number | null;
    is_active: boolean;
};

export function TyreUsageHistoryList({ history }: { history: UsageHistoryItem[] }) {
    if (history.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Usage History</CardTitle>
                    <CardDescription>
                        Tyre assignment and usage history
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground py-4">
                        No usage history available for this tyre.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Usage History</CardTitle>
                <CardDescription>
                    Tyre assignment and usage history based on TyreAssignment records
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Position</TableHead>
                                <TableHead>Installed Odometer</TableHead>
                                <TableHead>Removed Odometer</TableHead>
                                <TableHead>KM Used</TableHead>
                                <TableHead>Installed Date</TableHead>
                                <TableHead>Removed Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Movement</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {history.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>
                                        {item.vehicle_code && item.vehicle_plate ? (
                                            <span className="font-medium">
                                                {item.vehicle_code} / {item.vehicle_plate}
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>{item.position_code}</TableCell>
                                    <TableCell>
                                        {item.installed_odometer !== null ? (
                                            <span>{item.installed_odometer.toLocaleString()}</span>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {item.removed_odometer !== null ? (
                                            <span>{item.removed_odometer.toLocaleString()}</span>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {item.km_used !== null ? (
                                            <span className="font-medium">{item.km_used.toLocaleString()}</span>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {item.installed_date || <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                    <TableCell>
                                        {item.removed_date || <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                    <TableCell>
                                        {item.is_active ? (
                                            <Badge className="bg-green-500">Active</Badge>
                                        ) : (
                                            <Badge variant="outline">{item.status}</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {item.movement_id ? (
                                            <Link
                                                href={route("tyres.movements.show", item.movement_id)}
                                                className="text-blue-600 hover:underline text-sm"
                                            >
                                                #{item.movement_id}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
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
