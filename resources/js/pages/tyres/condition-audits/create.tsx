import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Head, Link, useForm } from "@inertiajs/react";
import { AlertTriangle, Info } from "lucide-react";
import { FormEventHandler, type ReactNode } from "react";

type TyreAuditContext = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    vehicle_label: string;
    position: string;
    current_tread_depth: number | null;
    usage_summary: {
        calculated_remaining_percentage: number | null;
        latest_audited_remaining_percentage: number | null;
        effective_remaining_percentage: number | null;
        current_vehicle_odometer: number | null;
        baseline_percentage: number | null;
    };
    latest_audit: {
        audited_remaining_percentage: number | null;
        inspection_date: string | null;
        audit_odometer: number | null;
        condition: string | null;
        calculated_remaining_percentage: number | null;
        variance_percentage: number | null;
    } | null;
};

const conditionOptions = [
    "Good",
    "Watch",
    "Low",
    "End of Life",
    "Damaged",
    "Uneven Wear",
    "Needs Alignment Check",
    "Needs Rotation",
];

const reasonOptions = [
    "Routine inspection",
    "Uneven wear",
    "Road damage",
    "Low tread depth",
    "Puncture/repair",
    "Alignment issue",
    "Driver report",
    "Other",
];

export default function ConditionAuditCreate({ tyre }: { tyre: TyreAuditContext }) {
    const calculated = tyre.usage_summary.calculated_remaining_percentage;
    const { data, setData, post, processing, errors } = useForm({
        audited_remaining_percentage: "" as string | number,
        inspection_date: new Date().toISOString().slice(0, 10),
        audit_odometer: tyre.usage_summary.current_vehicle_odometer ?? "",
        tread_depth: tyre.current_tread_depth ?? "",
        condition: "",
        reason: "Routine inspection",
        notes: "",
    });
    const audited = data.audited_remaining_percentage === "" ? null : Number(data.audited_remaining_percentage);
    const variance = typeof calculated === "number" && typeof audited === "number"
        ? audited - calculated
        : null;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route("tyres.condition-audits.store", tyre.id));
    };

    return (
        <AuthenticatedLayout header="Record Tyre Condition Audit">
            <Head title="Record Tyre Condition Audit" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Record condition audit"
                    description={`${tyre.tyre_code} on ${tyre.vehicle_label}, position ${tyre.position}. Save the manual inspected remaining percentage without changing baseline records.`}
                    backHref={route("tyres.show", tyre.id)}
                    backLabel="Back to Tyre"
                />

                <Card className="max-w-3xl overflow-hidden">
                    <CardHeader>
                        <CardTitle>Audit checkpoint</CardTitle>
                        <CardDescription>
                            Record the inspected tyre condition. Vehicle, position, KM, and calculated remaining life are captured automatically.
                        </CardDescription>
                    </CardHeader>
                    <form onSubmit={submit}>
                        <CardContent>
                    <div className="space-y-6">
                        <div className="grid gap-3 md:grid-cols-5">
                            <Metric label="Tyre" value={tyre.tyre_code} />
                            <Metric label="Vehicle" value={tyre.vehicle_label} />
                            <Metric label="Position" value={tyre.position} />
                            <Metric label="Current vehicle KM" value={formatKm(tyre.usage_summary.current_vehicle_odometer)} />
                            <Metric label="Calculated now" value={formatPercent(calculated)} />
                        </div>
                        <div className="rounded-lg border bg-muted/20 p-4 text-sm">
                            <p className="font-semibold">Inspection snapshot</p>
                            <p className="mt-1 text-muted-foreground">The system captures vehicle, position, odometer, calculated remaining, and the recording user automatically. Baseline and assignment data remain unchanged.</p>
                            {tyre.latest_audit && <p className="mt-2 text-xs text-muted-foreground">Latest audited remaining: {formatPercent(tyre.latest_audit.audited_remaining_percentage)} on {tyre.latest_audit.inspection_date || "-"}</p>}
                        </div>

                        <section className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Audited Remaining % *" error={errors.audited_remaining_percentage}>
                                    <Input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value={data.audited_remaining_percentage}
                                        onChange={(event) => setData("audited_remaining_percentage", event.target.value)}
                                        placeholder="Example: 50"
                                    />
                                </Field>
                                <Field label="Audit Date *" error={errors.inspection_date}>
                                    <Input
                                        type="date"
                                        value={data.inspection_date}
                                        onChange={(event) => setData("inspection_date", event.target.value)}
                                    />
                                </Field>
                                <Field label="Audit Odometer (KM)" error={errors.audit_odometer}>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="1"
                                        value={data.audit_odometer}
                                        onChange={(event) => setData("audit_odometer", event.target.value)}
                                        placeholder={formatKm(tyre.usage_summary.current_vehicle_odometer)}
                                    />
                                    <p className="text-xs text-muted-foreground">Prefilled from the vehicle. It cannot be lower than the current KM.</p>
                                </Field>
                                <Field label="Tread Depth MM" error={errors.tread_depth}>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.tread_depth}
                                        onChange={(event) => setData("tread_depth", event.target.value)}
                                    />
                                </Field>
                                <Field label="Condition Status" error={errors.condition}>
                                    <Select value={data.condition} onValueChange={(value) => setData("condition", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select condition" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {conditionOptions.map((option) => (
                                                <SelectItem key={option} value={option}>{option}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field label="Reason" error={errors.reason}>
                                    <Select value={data.reason} onValueChange={(value) => setData("reason", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select reason" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {reasonOptions.map((option) => (
                                                <SelectItem key={option} value={option}>{option}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>
                            </div>

                            <div className="grid gap-3 md:grid-cols-3">
                                <Metric label="Calculated now" value={formatPercent(calculated)} />
                                <Metric label="Audited input" value={formatPercent(audited)} />
                                <Metric
                                    label="Audit variance"
                                    value={variance === null ? "-" : `${variance > 0 ? "+" : ""}${variance.toFixed(1)}%`}
                                    strong
                                />
                            </div>

                            {variance !== null && variance < 0 && (
                                <Alert className="border-amber-200 bg-amber-50 text-amber-900">
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertDescription>
                                        <span className="font-semibold">Audit lower than system estimate.</span>{" "}
                                        Manual audit is lower than the system estimate. Check for wear, damage, overload, alignment, or road conditions.
                                    </AlertDescription>
                                </Alert>
                            )}
                            {variance !== null && variance > 0 && (
                                <Alert className="border-blue-200 bg-blue-50 text-blue-900">
                                    <Info className="h-4 w-4" />
                                    <AlertDescription>
                                        <span className="font-semibold">Audit higher than system estimate.</span>{" "}
                                        Manual audit is higher than system estimate. This audit becomes the latest condition checkpoint.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <Field label="Notes" error={errors.notes}>
                                <Textarea
                                    value={data.notes}
                                    onChange={(event) => setData("notes", event.target.value)}
                                    placeholder="Add inspection notes..."
                                    rows={4}
                                />
                            </Field>
                        </section>
                    </div>
                        </CardContent>
                        <div className="flex flex-col-reverse gap-2 border-t bg-muted/20 px-6 py-4 sm:flex-row sm:justify-end">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route("tyres.show", tyre.id)}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Saving..." : "Save Condition Audit"}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

function Metric({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) {
    return (
        <div className={strong ? "rounded-md border border-primary bg-primary/5 p-3" : "rounded-md border p-3"}>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 font-semibold">{value}</p>
        </div>
    );
}

function formatPercent(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toFixed(1)}%` : "-";
}

function formatKm(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toLocaleString()} KM` : "-";
}
