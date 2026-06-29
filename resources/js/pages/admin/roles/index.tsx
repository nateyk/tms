import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Head } from "@inertiajs/react";
import { Check } from "lucide-react";

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    permissions: string[];
};

export default function RolesIndex({
    roles,
    allPermissions,
}: {
    roles: RoleRow[];
    allPermissions: string[];
}) {
    return (
        <AuthenticatedLayout header="Roles">
            <Head title="Roles" />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Roles</CardTitle>
                        <CardDescription>
                            View roles, user counts, and assigned permissions. Permissions are
                            managed via database seeders (read-only).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead>Users</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="font-medium">{role.name}</TableCell>
                                        <TableCell>{role.permissions_count}</TableCell>
                                        <TableCell>{role.users_count}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Permission matrix</CardTitle>
                        <CardDescription>
                            Which permissions each role has (✓ = granted).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[180px] sticky left-0 bg-background">
                                        Permission
                                    </TableHead>
                                    {roles.map((role) => (
                                        <TableHead key={role.id} className="min-w-[120px] text-center">
                                            <span className="text-xs font-medium">{role.name}</span>
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {allPermissions.map((permission) => (
                                    <TableRow key={permission}>
                                        <TableCell className="sticky left-0 bg-background font-mono text-xs">
                                            {permission}
                                        </TableCell>
                                        {roles.map((role) => {
                                            const granted = role.permissions.includes(permission);

                                            return (
                                                <TableCell key={role.id} className="text-center">
                                                    {granted ? (
                                                        <Check className="mx-auto h-4 w-4 text-primary" />
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </TableCell>
                                            );
                                        })}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Permissions by role</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {roles.map((role) => (
                            <div key={role.id} className="rounded-lg border p-4">
                                <h3 className="mb-2 font-semibold">{role.name}</h3>
                                <div className="flex flex-wrap gap-1">
                                    {role.permissions.map((permission) => (
                                        <Badge key={permission} variant="outline" className="font-mono text-xs">
                                            {permission}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
