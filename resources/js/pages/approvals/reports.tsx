import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head } from "@inertiajs/react";
import { Download } from "lucide-react";
import { useState } from "react";

type VehicleOption = { id: number; label: string };

type ReportDefinition = {
    type: string;
    title: string;
    description: string;
    needsDateRange: boolean;
    needsVehicle: boolean;
    requiresAudit?: boolean;
};

const reports: ReportDefinition[] = [
    {
        type: "stock",
        title: "Tyre Stock",
        description: "Current inventory with location and position.",
        needsDateRange: false,
        needsVehicle: false,
    },
    {
        type: "movements",
        title: "Tyre Movements",
        description: "Movement vouchers in the selected date range.",
        needsDateRange: true,
        needsVehicle: false,
    },
    {
        type: "disposals",
        title: "Tyre Disposals",
        description: "Disposal vouchers in the selected date range.",
        needsDateRange: true,
        needsVehicle: false,
    },
    {
        type: "trailer-transfers",
        title: "Trailer Transfers",
        description: "Power–trailer transfer history.",
        needsDateRange: true,
        needsVehicle: false,
    },
    {
        type: "tyres-by-vehicle",
        title: "Tyres by Vehicle",
        description: "Active assignments for one vehicle or all vehicles.",
        needsDateRange: false,
        needsVehicle: true,
    },
    {
        type: "km-performance",
        title: "KM Performance",
        description: "Total KM and cost per KM for each tyre.",
        needsDateRange: false,
        needsVehicle: false,
    },
    {
        type: "lifecycle",
        title: "Tyre Lifecycle",
        description: "Assignment, movement, and disposal counts per tyre.",
        needsDateRange: false,
        needsVehicle: false,
    },
    {
        type: "audit-trail",
        title: "Audit Trail",
        description: "System activity log export (requires audit access).",
        needsDateRange: true,
        needsVehicle: false,
        requiresAudit: true,
    },
];

export default function Reports({
    vehicles,
    filters,
    canExport,
    canExportAudit,
}: {
    vehicles: VehicleOption[];
    filters: { date_from: string; date_to: string; vehicle_id: number | null };
    canExport: boolean;
    canExportAudit: boolean;
}) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [vehicleId, setVehicleId] = useState<string>(
        filters.vehicle_id ? String(filters.vehicle_id) : "all",
    );

    const buildExportUrl = (type: string) => {
        const params = new URLSearchParams();
        if (dateFrom) {
            params.set("date_from", dateFrom);
        }
        if (dateTo) {
            params.set("date_to", dateTo);
        }
        if (vehicleId !== "all") {
            params.set("vehicle_id", vehicleId);
        }
        const query = params.toString();

        return route("approvals.reports.export", { type }) + (query ? `?${query}` : "");
    };

    const canDownload = (report: ReportDefinition) => {
        if (!canExport) {
            return false;
        }
        if (report.requiresAudit && !canExportAudit) {
            return false;
        }

        return true;
    };

    return (
        <AuthenticatedLayout header="Reports">
            <Head title="Reports" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Export filters</CardTitle>
                    <CardDescription>
                        Date range applies to movement, disposal, transfer, and audit exports.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="date_from">From date</Label>
                        <Input
                            id="date_from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => setDateFrom(event.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="date_to">To date</Label>
                        <Input
                            id="date_to"
                            type="date"
                            value={dateTo}
                            onChange={(event) => setDateTo(event.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Vehicle (tyres-by-vehicle)</Label>
                        <Select value={vehicleId} onValueChange={setVehicleId}>
                            <SelectTrigger>
                                <SelectValue placeholder="All vehicles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All vehicles</SelectItem>
                                {vehicles.map((vehicle) => (
                                    <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                        {vehicle.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            {!canExport && (
                <p className="mb-4 text-sm text-muted-foreground">
                    You can view this page but do not have permission to export reports.
                </p>
            )}

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {reports.map((report) => (
                    <Card key={report.type}>
                        <CardHeader>
                            <CardTitle className="text-base">{report.title}</CardTitle>
                            <CardDescription>{report.description}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!canDownload(report)}
                                asChild={canDownload(report)}
                            >
                                {canDownload(report) ? (
                                    <a href={buildExportUrl(report.type)}>
                                        <Download className="mr-2 h-4 w-4" />
                                        Download CSV
                                    </a>
                                ) : (
                                    <span>
                                        <Download className="mr-2 h-4 w-4" />
                                        Download CSV
                                    </span>
                                )}
                            </Button>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
