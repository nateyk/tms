import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import { UserFormFields } from "@/components/admin/user-form-fields";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { Loader2 } from "lucide-react";
import { FormEventHandler } from "react";

type EditUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    is_active: boolean;
};

export default function UsersEdit({
    user,
    roles,
}: {
    user: EditUser;
    roles: string[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: "",
        roles: user.roles,
        is_active: user.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("admin.users.update", user.id));
    };

    return (
        <AuthenticatedLayout header="Edit User">
            <Head title="Edit User" />

            <div className="mx-auto max-w-4xl space-y-5">
                <WorkflowHeader
                    title={user.name}
                    description="Update identity, access status, password, and assigned roles."
                    backHref={route("admin.users.index")}
                    backLabel="Back to Users"
                    actions={(
                        <Badge
                            variant="outline"
                            className={data.is_active
                                ? "border-green-200 bg-green-50 text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300"
                                : "border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300"}
                        >
                            {data.is_active ? "Active account" : "Inactive account"}
                        </Badge>
                    )}
                />

                <Card>
                    <form onSubmit={submit}>
                        <CardContent className="p-5 sm:p-6">
                            <UserFormFields
                                roleOptions={roles}
                                errors={errors}
                                data={data}
                                setData={setData}
                            />
                        </CardContent>
                        <div className="flex flex-col-reverse gap-2 border-t bg-muted/20 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                            <Button variant="outline" asChild>
                                <Link href={route("admin.users.index")}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing} className="min-w-32">
                                {processing && <Loader2 className="h-4 w-4 animate-spin" />}
                                {processing ? "Saving..." : "Save changes"}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
