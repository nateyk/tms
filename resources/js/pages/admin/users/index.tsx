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
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";

type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
    created_at: string;
};

type PaginatedUsers = {
    data: UserRow[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
};

export default function UsersIndex({ users }: { users: PaginatedUsers }) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const deleteUser = (id: number) => {
        setDeletingId(id);
        router.delete(route("admin.users.destroy", id), {
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <AuthenticatedLayout header="Users">
            <Head title="Users" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Users</CardTitle>
                        <CardDescription>
                            Manage user accounts and role assignments.
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route("admin.users.create")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add user
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email address</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">{user.name}</TableCell>
                                    <TableCell>{user.email}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.length ? (
                                                user.roles.map((role) => (
                                                    <Badge key={role} variant="secondary">
                                                        {role}
                                                    </Badge>
                                                ))
                                            ) : (
                                                <span className="text-muted-foreground text-sm">
                                                    No roles
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route("admin.users.edit", user.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={deletingId === user.id}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>
                                                            Delete user?
                                                        </AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will permanently remove{" "}
                                                            {user.name}. This action cannot be
                                                            undone.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() => deleteUser(user.id)}
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

                    {users.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {users.links.map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        asChild
                                    >
                                        <Link href={link.url}>{link.label.replace(/&[^;]+;/g, "")}</Link>
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
