import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VehicleOdometerForm } from "@/components/fleet/vehicle-odometer-form";
import { OdometerReadingHistory } from "@/components/fleet/odometer-reading-history";
import { WorkflowHeader, WorkflowSteps } from "@/components/workflow/workflow-ui";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";

type VehicleOdometerData = {
    id: number;
    vehicle_code: string;
    plate_number: string | null;
    display_code?: string;
    display_code_with_plate?: string;
    current_odometer?: number | null;
    odometer?: number | null;
};

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

type VehicleOdometerProps = {
    vehicle: VehicleOdometerData & {
        latest_reading?: OdometerReading | null;
        readings?: OdometerReading[];
    };
    latest_reading?: OdometerReading | null;
    baseline_reading?: OdometerReading | null;
    reading_history?: OdometerReading[];
};

export default function VehicleOdometer({
    vehicle,
    baseline_reading = null,
    reading_history = [],
}: VehicleOdometerProps) {
    const displayCode = vehicle.display_code || vehicle.display_code_with_plate || vehicle.vehicle_code;
    const currentOdometer = vehicle.current_odometer ?? vehicle.odometer ?? null;
    const readings = vehicle.readings || reading_history || [];
    const baselineReading = baseline_reading || readings.find((reading) => reading.source === "baseline") || null;

    const {
        data: baselineData,
        setData: setBaselineData,
        put: putBaseline,
        processing: baselineProcessing,
        errors: baselineErrors,
    } = useForm({
        odometer: currentOdometer || "",
        source: "baseline",
        notes: "Initial vehicle baseline KM",
    });

    const {
        data,
        setData,
        put,
        processing,
        errors,
    } = useForm({
        odometer: currentOdometer || "",
        source: "manual",
        notes: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route("fleet.vehicles.odometer.update", vehicle.id));
    };

    const handleBaselineSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        putBaseline(route("fleet.vehicles.odometer.update", vehicle.id));
    };

    const handleChange = (field: string, value: string | number) => {
        setData(prev => ({ ...prev, [field]: value }));
    };

    const handleBaselineChange = (field: string, value: string | number) => {
        setBaselineData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <AuthenticatedLayout header={`Vehicle Odometer - ${displayCode}`}>
            <Head title={`Vehicle Odometer - ${displayCode}`} />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Vehicle Odometer"
                    description="Set first baseline KM once, then record current KM during daily operation or movement completion."
                    backHref={route("fleet.vehicles.show", vehicle.id)}
                    backLabel="Back to Vehicle"
                    badge={displayCode}
                />

                <WorkflowSteps
                    steps={[
                        { label: "First baseline KM", done: Boolean(baselineReading) },
                        { label: "Current KM", done: Boolean(currentOdometer) },
                        { label: "History recorded", done: readings.length > 0 },
                        { label: "Tyre usage ready", done: Boolean(baselineReading && currentOdometer) },
                    ]}
                />

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-6">
                        {!baselineReading ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>First Baseline KM</CardTitle>
                                    <CardDescription>
                                        Use this once when the vehicle enters tyre tracking.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleBaselineSubmit} className="space-y-6">
                                        <VehicleOdometerForm
                                            data={baselineData}
                                            errors={baselineErrors}
                                            currentOdometer={currentOdometer}
                                            onDataChange={handleBaselineChange}
                                        />

                                        <div className="flex justify-end">
                                            <Button type="submit" disabled={baselineProcessing}>
                                                {baselineProcessing ? "Saving..." : "Save Baseline KM"}
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle>First Baseline KM</CardTitle>
                                    <CardDescription>
                                        Locked starting point for vehicle tyre usage.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="rounded-lg border bg-muted/40 p-4">
                                        <p className="text-2xl font-semibold">
                                            {baselineReading.odometer.toLocaleString()} KM
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Saved on {baselineReading.reading_date}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Current KM Reading</CardTitle>
                                <CardDescription>
                                    Record the latest odometer reading for {displayCode}.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <VehicleOdometerForm
                                        data={data}
                                        errors={errors}
                                        currentOdometer={currentOdometer}
                                        onDataChange={handleChange}
                                    />

                                    <div className="flex justify-end gap-2">
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={route("fleet.vehicles.show", vehicle.id)}>Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? "Updating..." : "Update Odometer"}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <OdometerReadingHistory readings={readings} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
