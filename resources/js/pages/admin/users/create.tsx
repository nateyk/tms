import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { UserFormFields } from "@/components/admin/user-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function UsersCreate({ roles }: { roles: string[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        password: "",
        roles: [] as string[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("admin.users.store"));
    };

    return (
        <AuthenticatedLayout header="Create User">
            <Head title="Create User" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Create user</CardTitle>
                    <CardDescription>Add a new TMS user and assign roles.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <form onSubmit={submit} className="space-y-6">
                        <UserFormFields
                            roleOptions={roles}
                            errors={errors}
                            data={data}
                            setData={setData}
                            passwordRequired
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Create user
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("admin.users.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
