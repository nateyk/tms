import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { PlannedWorkflow, WorkflowHeader } from "@/components/workflow/workflow-ui";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/react";

type PlaceholderProps = {
    title: string;
    description: string;
};

const moduleWorkflows: Record<string, { purpose: string; steps: string[]; primaryHref?: string; primaryLabel?: string }> = {
    "Trailer Transfers": {
        purpose: "Attach or detach trailers with KM, approval, and voucher steps visible from the start.",
        steps: ["Select power unit", "Select trailer", "Record KM", "Submit", "Approve", "Voucher"],
        primaryHref: "/fleet/vehicles",
        primaryLabel: "Choose vehicle",
    },
    "Tyre Disposals": {
        purpose: "Prepare disposal decisions with tyre identity, reason, condition, approval, and final disposal.",
        steps: ["Select tyre", "Reason", "Condition", "Submit", "Approve", "Dispose"],
        primaryHref: "/tyres",
        primaryLabel: "Choose tyre",
    },
};

export default function ModulePlaceholder({ title, description }: PlaceholderProps) {
    const workflow = moduleWorkflows[title] ?? {
        purpose: description,
        steps: ["Review", "Prepare", "Submit", "Approve"],
    };

    return (
        <AuthenticatedLayout header={title}>
            <Head title={title} />

            <div className="space-y-6">
                <WorkflowHeader
                    title={title}
                    description={workflow.purpose}
                    badge="Workflow ready"
                    actions={<Badge variant="secondary">Backend staged</Badge>}
                />

                <PlannedWorkflow
                    title={`${title} Flow`}
                    description={description}
                    steps={workflow.steps}
                    primaryAction={
                        workflow.primaryHref && (
                            <Button asChild>
                                <Link href={workflow.primaryHref}>{workflow.primaryLabel ?? "Start"}</Link>
                            </Button>
                        )
                    }
                />

                <Card>
                    <CardContent className="grid gap-3 p-5 text-sm text-muted-foreground md:grid-cols-3">
                        <div>
                            <p className="font-semibold text-foreground">Operator</p>
                            <p>Sees the next step and submits clean data without hunting through menus.</p>
                        </div>
                        <div>
                            <p className="font-semibold text-foreground">Approver</p>
                            <p>Reviews a focused request with approve, reject, or complete actions.</p>
                        </div>
                        <div>
                            <p className="font-semibold text-foreground">Report</p>
                            <p>Final state can be exported or audited once the backend action is completed.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
