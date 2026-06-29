import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { UserFormFields } from "@/components/admin/user-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type EditUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
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
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("admin.users.update", user.id));
    };

    return (
        <AuthenticatedLayout header="Edit User">
            <Head title="Edit User" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Edit user</CardTitle>
                    <CardDescription>Update account details and role assignments.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <form onSubmit={submit} className="space-y-6">
                        <UserFormFields
                            roleOptions={roles}
                            errors={errors}
                            data={data}
                            setData={setData}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
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
