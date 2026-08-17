import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import { UserFormFields } from "@/components/admin/user-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { Loader2 } from "lucide-react";
import { FormEventHandler } from "react";

export default function UsersCreate({ roles }: { roles: string[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        password: "",
        roles: [] as string[],
        is_active: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("admin.users.store"));
    };

    return (
        <AuthenticatedLayout header="Create User">
            <Head title="Create User" />

            <div className="mx-auto max-w-4xl space-y-5">
                <WorkflowHeader
                    title="Create user"
                    description="Add a company account and assign only the access needed for the role."
                    backHref={route("admin.users.index")}
                    backLabel="Back to Users"
                />

                <Card>
                    <form onSubmit={submit}>
                        <CardContent className="p-5 sm:p-6">
                            <UserFormFields
                                roleOptions={roles}
                                errors={errors}
                                data={data}
                                setData={setData}
                                passwordRequired
                            />
                        </CardContent>
                        <div className="flex flex-col-reverse gap-2 border-t bg-muted/20 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                            <Button variant="outline" asChild>
                                <Link href={route("admin.users.index")}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing} className="min-w-32">
                                {processing && <Loader2 className="h-4 w-4 animate-spin" />}
                                {processing ? "Creating..." : "Create user"}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
