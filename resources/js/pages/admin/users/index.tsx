import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Head, Link } from "@inertiajs/react";
import { Pencil, Plus, UserRoundCog, UsersRound } from "lucide-react";

type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
    created_at: string;
    is_active: boolean;
};

type PaginatedUsers = {
    data: UserRow[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
};

function initials(name: string) {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join("");
}

export default function UsersIndex({ users }: { users: PaginatedUsers }) {
    return (
        <AuthenticatedLayout header="Users">
            <Head title="Users" />

            <div className="space-y-5">
                <WorkflowHeader
                    title="Users"
                    description="Manage company accounts, access status, and assigned roles."
                    actions={(
                        <Button asChild>
                            <Link href={route("admin.users.create")}>
                                <Plus className="h-4 w-4" />
                                Add user
                            </Link>
                        </Button>
                    )}
                />

                <div className="overflow-hidden rounded-md border bg-card">
                    <div className="flex items-center gap-3 border-b bg-muted/20 px-4 py-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-md border bg-background text-muted-foreground">
                            <UsersRound className="h-4 w-4" />
                        </div>
                        <div>
                            <h2 className="text-sm font-semibold">Account directory</h2>
                            <p className="text-xs text-muted-foreground">
                                {users.total} {users.total === 1 ? "account" : "accounts"}
                            </p>
                        </div>
                    </div>

                    {users.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/30 hover:bg-muted/30">
                                        <TableHead className="min-w-48">Name</TableHead>
                                        <TableHead className="min-w-56">Email</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="min-w-52">Roles</TableHead>
                                        <TableHead className="w-20 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold uppercase text-muted-foreground">
                                                        {initials(user.name)}
                                                    </span>
                                                    <span className="font-medium">{user.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">{user.email}</TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={user.is_active
                                                        ? "border-green-200 bg-green-50 text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300"
                                                        : "border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300"}
                                                >
                                                    <span
                                                        className={`mr-1.5 h-1.5 w-1.5 rounded-full ${user.is_active ? "bg-green-500" : "bg-slate-400"}`}
                                                        aria-hidden="true"
                                                    />
                                                    {user.is_active ? "Active" : "Inactive"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {user.roles.length ? (
                                                        user.roles.map((role) => (
                                                            <Badge key={role} variant="secondary" className="font-medium">
                                                                {role.replaceAll("_", " ")}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">No roles assigned</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="ghost" size="icon" asChild title={`Edit ${user.name}`}>
                                                    <Link
                                                        href={route("admin.users.edit", user.id)}
                                                        aria-label={`Edit ${user.name}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center px-6 py-12 text-center">
                            <UserRoundCog className="h-8 w-8 text-muted-foreground" />
                            <h2 className="mt-3 text-sm font-semibold">No user accounts</h2>
                            <p className="mt-1 text-sm text-muted-foreground">Add the first account to assign system access.</p>
                            <Button asChild size="sm" className="mt-4">
                                <Link href={route("admin.users.create")}>
                                    <Plus className="h-4 w-4" />
                                    Add user
                                </Link>
                            </Button>
                        </div>
                    )}

                    {users.last_page > 1 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
                            <p className="text-xs text-muted-foreground">
                                Page {users.current_page} of {users.last_page}
                            </p>
                            <div className="flex flex-wrap gap-2">
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
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
