import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link } from "@inertiajs/react";
import { Eye, Trash2 } from "lucide-react";

type DisposalRow = {
    id: number;
    disposal_no: string;
    tyre_code: string | null;
    tyre_serial_number: string | null;
    disposal_reason: string | null;
    status: string;
    status_label: string;
    prepared_by: string | null;
    created_at: string | null;
    view_url: string;
};

export default function TyreDisposalsIndex({ disposals }: { disposals: { data: DisposalRow[] } }) {
    return (
        <AuthenticatedLayout header="Tyre Disposals">
            <Head title="Tyre Disposals" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Tyre Disposals"
                    description="Disposal vouchers are created from a tyre position, reviewed, approved, then completed."
                    backHref={route("tyres.index")}
                    backLabel="Back to Tyres"
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Trash2 className="h-4 w-4" />
                            Disposal vouchers
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {disposals.data.length === 0 ? (
                            <div className="px-6 py-12 text-center text-sm text-muted-foreground">
                                No disposal vouchers yet. Open a mounted tyre position and choose Tyre Disposal to start one.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[760px] text-sm">
                                    <thead className="border-y bg-muted/40 text-left text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-5 py-3 font-medium">Voucher</th>
                                            <th className="px-5 py-3 font-medium">Tyre</th>
                                            <th className="px-5 py-3 font-medium">Reason</th>
                                            <th className="px-5 py-3 font-medium">Status</th>
                                            <th className="px-5 py-3 font-medium">Prepared by</th>
                                            <th className="px-5 py-3 text-right font-medium">Open</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {disposals.data.map((disposal) => (
                                            <tr key={disposal.id} className="border-b">
                                                <td className="px-5 py-3 font-medium">{disposal.disposal_no}</td>
                                                <td className="px-5 py-3">
                                                    <div>{disposal.tyre_code ?? "-"}</div>
                                                    <div className="text-xs text-muted-foreground">{disposal.tyre_serial_number ?? "No serial"}</div>
                                                </td>
                                                <td className="px-5 py-3">{disposal.disposal_reason ?? "-"}</td>
                                                <td className="px-5 py-3"><Badge variant="outline">{disposal.status_label}</Badge></td>
                                                <td className="px-5 py-3">{disposal.prepared_by ?? "-"}</td>
                                                <td className="px-5 py-3 text-right">
                                                    <Button asChild variant="ghost" size="icon" aria-label={`Open ${disposal.disposal_no}`}>
                                                        <Link href={disposal.view_url}><Eye className="h-4 w-4" /></Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
