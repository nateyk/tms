import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
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
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";

type VehicleTypeRow = {
    id: number;
    name: string;
    asset_type: string;
    asset_type_label: string;
    axle_count: number;
    tyre_count: number;
    spare_count: number;
    status: string;
};

type PaginatedVehicleTypes = {
    data: VehicleTypeRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function VehicleTypesIndex({
    vehicleTypes,
}: {
    vehicleTypes: PaginatedVehicleTypes;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const deleteVehicleType = (id: number) => {
        setDeletingId(id);
        router.delete(route("fleet.vehicle-types.destroy", id), {
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <AuthenticatedLayout header="Vehicle Types">
            <Head title="Vehicle Types" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Vehicle Types"
                    description="Choose a preset layout, preview tyre counts, then save the vehicle type for fleet assignments."
                    actions={
                        <Button asChild>
                            <Link href={route("fleet.vehicle-types.create")}>
                                <Plus className="mr-2 h-4 w-4" />
                                Add vehicle type
                            </Link>
                        </Button>
                    }
                />

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle>Vehicle Types</CardTitle>
                            <CardDescription>
                                Manage axle layouts and tyre position configurations.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Asset type</TableHead>
                                <TableHead>Axles</TableHead>
                                <TableHead>Tyres</TableHead>
                                <TableHead>Spares</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicleTypes.data.map((type) => (
                                <TableRow key={type.id}>
                                    <TableCell className="font-medium">{type.name}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{type.asset_type_label}</Badge>
                                    </TableCell>
                                    <TableCell>{type.axle_count}</TableCell>
                                    <TableCell>{type.tyre_count}</TableCell>
                                    <TableCell>{type.spare_count}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{type.status}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link
                                                    href={route(
                                                        "fleet.vehicle-types.edit",
                                                        type.id,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={deletingId === type.id}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>
                                                            Delete vehicle type?
                                                        </AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will permanently remove {type.name}.
                                                            Types with assigned vehicles cannot be
                                                            deleted.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() =>
                                                                deleteVehicleType(type.id)
                                                            }
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

                        {vehicleTypes.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {vehicleTypes.links.map((link, index) =>
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
            </div>
        </AuthenticatedLayout>
    );
}
