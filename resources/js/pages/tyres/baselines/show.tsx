import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Head, Link, router } from "@inertiajs/react";
import { ArrowLeft, Edit, Trash2, Settings, MapPin, Gauge, Calendar, User } from "lucide-react";

type Baseline = {
    id: number;
    tyre_id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    baseline_location_type: string;
    baseline_location_id: number;
    baseline_position_code: string | null;
    baseline_odometer: number | null;
    baseline_percentage: number;
    expected_life_km: number;
    baseline_date: string;
    notes: string | null;
    location_display: string;
    created_by: string | null;
    created_at: string;
    updated_at: string;
};

export default function BaselineShow({ baseline }: { baseline: Baseline }) {
    return (
        <AuthenticatedLayout header="Tyre Baselines">
            <Head title={`Baseline - ${baseline.tyre_code}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('tyres.baselines.index')}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back to Baselines
                        </Link>
                    </Button>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('tyres.baselines.edit', baseline.id)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" size="sm" onClick={() => {
                            if (confirm('Are you sure you want to delete this baseline?')) {
                                router.delete(route('tyres.baselines.destroy', baseline.id));
                            }
                        }}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                {/* Tyre Information */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tyre Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Tyre Code</p>
                                <p className="font-medium">{baseline.tyre_code}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Serial Number</p>
                                <p className="font-mono text-sm">{baseline.serial_number}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Brand</p>
                                <p>{baseline.brand_name || '—'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Size</p>
                                <p>{baseline.size_label || '—'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Baseline Details */}
                <Card>
                    <CardHeader>
                        <CardTitle>Baseline Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground flex items-center gap-2">
                                    <MapPin className="h-4 w-4" />
                                    Location
                                </p>
                                <p className="font-medium">{baseline.location_display}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Position Code</p>
                                <p className="font-medium">{baseline.baseline_position_code || '—'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground flex items-center gap-2">
                                    <Gauge className="h-4 w-4" />
                                    Baseline Odometer
                                </p>
                                <p className="font-medium">
                                    {baseline.baseline_odometer ? `${baseline.baseline_odometer.toLocaleString()} KM` : '—'}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground flex items-center gap-2">
                                    <Settings className="h-4 w-4" />
                                    Baseline Percentage
                                </p>
                                <p className="font-medium">{baseline.baseline_percentage}%</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Expected Life</p>
                                <p className="font-medium">{baseline.expected_life_km.toLocaleString()} KM</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground flex items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    Baseline Date
                                </p>
                                <p className="font-medium">{baseline.baseline_date}</p>
                            </div>
                        </div>
                        {baseline.notes && (
                            <div className="mt-4 space-y-1">
                                <p className="text-sm text-muted-foreground">Notes</p>
                                <p className="text-sm">{baseline.notes}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Metadata */}
                <Card>
                    <CardHeader>
                        <CardTitle>Metadata</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground flex items-center gap-2">
                                    <User className="h-4 w-4" />
                                    Created By
                                </p>
                                <p>{baseline.created_by || '—'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">Created At</p>
                                <p>{baseline.created_at}</p>
                            </div>
                            <div className="space-y-1 md:col-span-2">
                                <p className="text-sm text-muted-foreground">Last Updated</p>
                                <p>{baseline.updated_at}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={route('tyres.show', baseline.tyre_id)}>
                                    View Tyre Details
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route('tyres.movements.create', { tyre_id: baseline.tyre_id })}>
                                    Create Movement
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
