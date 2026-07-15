import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, router } from "@inertiajs/react";
import { Search } from "lucide-react";

type LogItem = {
    id: number;
    description: string;
    event: string | null;
    created_at: string;
    causer: {
        name: string | null;
        email: string | null;
    } | null;
    subject_type: string | null;
    subject_id: number | null;
};

type PaginatedLogs = {
    data: LogItem[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function AuditLogsIndex({
    logs,
    filters,
}: {
    logs: PaginatedLogs;
    filters: { from: string | null; to: string | null };
}) {
    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);

        router.get(route("audit-logs.index"), {
            from: formData.get("from"),
            to: formData.get("to"),
        }, { preserveState: true });
    };

    const getSubjectLabel = (subjectType: string | null) => {
        if (!subjectType) {
            return "-";
        }

        const parts = subjectType.split("\\");
        return parts[parts.length - 1];
    };

    return (
        <AuthenticatedLayout header="Audit Logs">
            <Head title="Audit Logs" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Audit Logs"
                    description="Search system activity by date, user, action, module, and record reference."
                    badge={`${logs.data.length} visible`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-4">
                            <div className="min-w-[200px] flex-1">
                                <label className="mb-1 block text-sm font-medium">From Date</label>
                                <Input type="date" name="from" defaultValue={filters.from || ""} />
                            </div>
                            <div className="min-w-[200px] flex-1">
                                <label className="mb-1 block text-sm font-medium">To Date</label>
                                <Input type="date" name="to" defaultValue={filters.to || ""} />
                            </div>
                            <Button type="submit">
                                <Search className="mr-2 h-4 w-4" />
                                Search
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>System Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {logs.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed py-10 text-center text-sm text-muted-foreground">
                                No audit logs found.
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>Module</TableHead>
                                        <TableHead>Subject</TableHead>
                                        <TableHead>Description</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.data.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell>{new Date(log.created_at).toLocaleString()}</TableCell>
                                            <TableCell>{log.causer?.name || log.causer?.email || "System"}</TableCell>
                                            <TableCell>{log.event || "-"}</TableCell>
                                            <TableCell>{getSubjectLabel(log.subject_type)}</TableCell>
                                            <TableCell>{log.subject_id ? `#${log.subject_id}` : "-"}</TableCell>
                                            <TableCell className="max-w-md truncate">{log.description}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
