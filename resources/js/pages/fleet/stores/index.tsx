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
import { Check, Pencil, Plus, Trash2, X } from "lucide-react";
import { useState } from "react";

type StoreRow = {
    id: number;
    code: string;
    name: string;
    address: string | null;
    phone: string | null;
    is_default: boolean;
    status: string;
};

type PaginatedStores = {
    data: StoreRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function StoresIndex({ stores }: { stores: PaginatedStores }) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const deleteStore = (id: number) => {
        setDeletingId(id);
        router.delete(route("fleet.stores.destroy", id), {
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <AuthenticatedLayout header="Stores">
            <Head title="Stores" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Stores</CardTitle>
                        <CardDescription>
                            Manage tyre storage locations and default store settings.
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route("fleet.stores.create")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add store
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Default</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {stores.data.map((store) => (
                                <TableRow key={store.id}>
                                    <TableCell className="font-mono text-sm">{store.code}</TableCell>
                                    <TableCell className="font-medium">{store.name}</TableCell>
                                    <TableCell>
                                        {store.is_default ? (
                                            <Check className="h-4 w-4 text-primary" />
                                        ) : (
                                            <X className="h-4 w-4 text-muted-foreground" />
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{store.status}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("fleet.stores.edit", store.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={deletingId === store.id}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete store?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will permanently remove {store.name}.
                                                            Stores with assigned tyres cannot be deleted.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() => deleteStore(store.id)}
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

                    {stores.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {stores.links.map((link, index) =>
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
