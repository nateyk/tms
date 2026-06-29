import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreStatusBadge } from "@/components/tyres/tyre-status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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

type TyreRow = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    current_tread_depth: number | null;
    current_location_type: string;
    vehicle_plate: string;
    current_position_code: string;
    status: string;
    status_label: string;
    status_color: string;
};

type PaginatedTyres = {
    data: TyreRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

type StatusOption = { value: string; label: string };

export default function TyresIndex({
    tyres,
    filters,
    statusOptions,
}: {
    tyres: PaginatedTyres;
    filters: { status: string | null };
    statusOptions: StatusOption[];
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const deleteTyre = (id: number) => {
        setDeletingId(id);
        router.delete(route("tyres.destroy", id), {
            onFinish: () => setDeletingId(null),
        });
    };

    const filterByStatus = (status: string) => {
        router.get(
            route("tyres.index"),
            status === "all" ? {} : { status },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout header="Tyres">
            <Head title="Tyres" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Tyres</CardTitle>
                        <CardDescription>
                            Register tyres, manage lifecycle status, and QR codes.
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route("tyres.create")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Register tyre
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <span className="text-sm text-muted-foreground">Filter by status:</span>
                        <Select
                            value={filters.status ?? "all"}
                            onValueChange={filterByStatus}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {statusOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Serial</TableHead>
                                <TableHead>Brand</TableHead>
                                <TableHead>Tread (mm)</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Position</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tyres.data.map((tyre) => (
                                <TableRow key={tyre.id}>
                                    <TableCell className="font-medium">{tyre.tyre_code}</TableCell>
                                    <TableCell>{tyre.serial_number}</TableCell>
                                    <TableCell>{tyre.brand_name || "—"}</TableCell>
                                    <TableCell>{tyre.current_tread_depth ?? "—"}</TableCell>
                                    <TableCell>{tyre.current_location_type}</TableCell>
                                    <TableCell>{tyre.vehicle_plate}</TableCell>
                                    <TableCell>{tyre.current_position_code}</TableCell>
                                    <TableCell>
                                        <TyreStatusBadge
                                            label={tyre.status_label}
                                            color={tyre.status_color}
                                        />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("tyres.show", tyre.id)}>
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("tyres.edit", tyre.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={deletingId === tyre.id}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>
                                                            Delete tyre?
                                                        </AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will soft-delete the tyre record.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() => deleteTyre(tyre.id)}
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

                    {tyres.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {tyres.links.map((link, index) =>
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
