import AuthenticatedLayout from "@/layouts/authenticated-layout";
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
        router.get(route('audit-logs.index'), {
            from: formData.get('from'),
            to: formData.get('to'),
        }, { preserveState: true });
    };

    const getSubjectLabel = (subjectType: string | null) => {
        if (!subjectType) return '—';
        const parts = subjectType.split('\\');
        return parts[parts.length - 1];
    };

    return (
        <AuthenticatedLayout header="Audit Logs">
            <Head title="Audit Logs" />

            <div className="space-y-6">
                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-4">
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium mb-1">From Date</label>
                                <Input
                                    type="date"
                                    name="from"
                                    defaultValue={filters.from || ''}
                                />
                            </div>
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium mb-1">To Date</label>
                                <Input
                                    type="date"
                                    name="to"
                                    defaultValue={filters.to || ''}
                                />
                            </div>
                            <Button type="submit">
                                <Search className="mr-2 h-4 w-4" />
                                Search
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Logs Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>System Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {logs.data.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                No audit logs found
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
                                            <TableCell>
                                                {new Date(log.created_at).toLocaleString()}
                                            </TableCell>
                                            <TableCell>
                                                {log.causer?.name || log.causer?.email || 'System'}
                                            </TableCell>
                                            <TableCell>
                                                {log.event || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {getSubjectLabel(log.subject_type)}
                                            </TableCell>
                                            <TableCell>
                                                {log.subject_id ? `#${log.subject_id}` : '—'}
                                            </TableCell>
                                            <TableCell className="max-w-md truncate">
                                                {log.description}
                                            </TableCell>
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
