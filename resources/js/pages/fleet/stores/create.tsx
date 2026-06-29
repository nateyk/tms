import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { StoreFormFields } from "@/components/fleet/store-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function StoresCreate() {
    const { data, setData, post, processing, errors } = useForm({
        code: "",
        name: "",
        address: "",
        phone: "",
        is_default: false,
        status: "active",
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("fleet.stores.store"));
    };

    return (
        <AuthenticatedLayout header="Create Store">
            <Head title="Create Store" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Create store</CardTitle>
                    <CardDescription>Add a tyre storage location.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <StoreFormFields errors={errors} data={data} setData={setData} />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Create store
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("fleet.stores.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
