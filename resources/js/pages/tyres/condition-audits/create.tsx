import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
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
import { AlertTriangle, ArrowLeft, Info } from "lucide-react";
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
    };
    latest_audit: {
        audited_remaining_percentage: number | null;
        inspection_date: string | null;
        audit_odometer: number | null;
        condition: string | null;
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

            <div className="max-w-4xl space-y-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route("tyres.show", tyre.id)}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Record Tyre Condition Audit</h1>
                        <p className="text-sm text-muted-foreground">
                            Save a manual inspected remaining percentage without changing baseline or movement records.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Audit Context</CardTitle>
                        <CardDescription>{tyre.tyre_code} on {tyre.vehicle_label}, position {tyre.position}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-4">
                        <Metric label="Tyre" value={tyre.tyre_code} />
                        <Metric label="Current Vehicle KM" value={formatKm(tyre.usage_summary.current_vehicle_odometer)} />
                        <Metric label="Calculated Remaining" value={formatPercent(calculated)} />
                        <Metric label="Last Audited" value={formatPercent(tyre.usage_summary.latest_audited_remaining_percentage)} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Inspection Result</CardTitle>
                        <CardDescription>Enter the mechanic/manual audited remaining percentage.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
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
                                <Field label="Audit Odometer" error={errors.audit_odometer}>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={data.audit_odometer}
                                        onChange={(event) => setData("audit_odometer", event.target.value)}
                                    />
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
                                <Metric label="Calculated" value={formatPercent(calculated)} />
                                <Metric label="Audited" value={formatPercent(audited)} />
                                <Metric
                                    label="Variance"
                                    value={variance === null ? "-" : `${variance > 0 ? "+" : ""}${variance.toFixed(1)}%`}
                                    strong
                                />
                            </div>

                            {variance !== null && variance <= -5 && (
                                <Alert className="border-amber-200 bg-amber-50 text-amber-900">
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertDescription>
                                        <span className="font-semibold">Audit lower than system estimate.</span>{" "}
                                        Manual audit is significantly lower than calculated estimate. Check for uneven wear, damage, or alignment issue.
                                    </AlertDescription>
                                </Alert>
                            )}
                            {variance !== null && variance >= 5 && (
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

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" asChild>
                                    <Link href={route("tyres.show", tyre.id)}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Saving..." : "Save Condition Audit"}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
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
