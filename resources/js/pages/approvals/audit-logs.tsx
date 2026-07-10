import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";

type AuditLogRow = {
    id: number;
    created_at: string | null;
    log_name: string | null;
    event: string | null;
    description: string | null;
    subject: string | null;
    causer: string;
    properties: Record<string, unknown>;
};

type PaginatedLogs = {
    data: AuditLogRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function AuditLogs({
    logs,
    logNames,
    filters,
}: {
    logs: PaginatedLogs;
    logNames: string[];
    filters: { date_from: string | null; date_to: string | null; log_name: string | null };
}) {
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? "");
    const [dateTo, setDateTo] = useState(filters.date_to ?? "");
    const [logName, setLogName] = useState(filters.log_name ?? "all");

    const applyFilters = () => {
        router.get(
            route("approvals.audit-logs"),
            {
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                log_name: logName === "all" ? undefined : logName,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        setDateFrom("");
        setDateTo("");
        setLogName("all");
        router.get(route("approvals.audit-logs"), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout header="Audit Logs">
            <Head title="Audit Logs" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap items-end gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="audit_from">From date</Label>
                        <Input
                            id="audit_from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => setDateFrom(event.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="audit_to">To date</Label>
                        <Input
                            id="audit_to"
                            type="date"
                            value={dateTo}
                            onChange={(event) => setDateTo(event.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Log name</Label>
                        <Select value={logName} onValueChange={setLogName}>
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="All logs" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All logs</SelectItem>
                                {logNames.map((name) => (
                                    <SelectItem key={name} value={name}>
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button onClick={applyFilters}>Apply</Button>
                    <Button variant="outline" onClick={clearFilters}>
                        Clear
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Log</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>User</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        No audit log entries found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="whitespace-nowrap text-sm">
                                            {log.created_at}
                                        </TableCell>
                                        <TableCell>{log.log_name ?? "—"}</TableCell>
                                        <TableCell>{log.event ?? "—"}</TableCell>
                                        <TableCell className="max-w-xs truncate">
                                            {log.description ?? "—"}
                                        </TableCell>
                                        <TableCell>{log.subject ?? "—"}</TableCell>
                                        <TableCell>{log.causer}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {logs.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {logs.links.map((link, index) =>
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
