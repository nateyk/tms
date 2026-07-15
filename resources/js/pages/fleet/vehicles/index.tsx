import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";

type VehicleRow = {
    id: number;
    vehicle_code: string;
    plate_number: string | null;
    asset_type_label: string;
    vehicle_type_name: string | null;
    status_label: string;
    current_location_name: string | null;
    odometer: number | null;
    attached_vehicle_id: number | null;
    attached_vehicle_label: string | null;
    attached_vehicle_role: string | null;
};

type PaginatedVehicles = {
    data: VehicleRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function VehiclesIndex({ vehicles }: { vehicles: PaginatedVehicles }) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const deleteVehicle = (id: number) => {
        setDeletingId(id);
        router.delete(route("fleet.vehicles.destroy", id), {
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <AuthenticatedLayout header="Vehicles">
            <Head title="Vehicles" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Vehicles</CardTitle>
                        <CardDescription>
                            Manage fleet vehicles and open interactive tyre maps.
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route("fleet.vehicles.create")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add vehicle
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle code</TableHead>
                                <TableHead>Plate</TableHead>
                                <TableHead>Asset type</TableHead>
                                <TableHead>Vehicle type</TableHead>
                                <TableHead>Attached vehicle</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.data.map((vehicle) => (
                                <TableRow key={vehicle.id}>
                                    <TableCell className="font-medium">
                                        {vehicle.vehicle_code}
                                    </TableCell>
                                    <TableCell>{vehicle.plate_number || "—"}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{vehicle.asset_type_label}</Badge>
                                    </TableCell>
                                    <TableCell>{vehicle.vehicle_type_name || "—"}</TableCell>
                                    <TableCell>
                                        {vehicle.attached_vehicle_id ? (
                                            <Link
                                                href={route("fleet.vehicles.show", vehicle.attached_vehicle_id)}
                                                className="text-sm font-medium text-primary hover:underline"
                                            >
                                                {vehicle.attached_vehicle_role}: {vehicle.attached_vehicle_label}
                                            </Link>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">Not attached</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{vehicle.status_label}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("fleet.vehicles.show", vehicle.id)}>
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("fleet.vehicles.edit", vehicle.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={deletingId === vehicle.id}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>
                                                            Delete vehicle?
                                                        </AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will soft-delete the vehicle record.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() => deleteVehicle(vehicle.id)}
                                                        >
                                                            Delete
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {vehicles.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {vehicles.links.map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        asChild
                                    >
                                        <Link href={link.url}>
                                            {link.label.replace(/&[^;]+;/g, "")}
                                        </Link>
                                    </Button>
                                ) : null,
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
