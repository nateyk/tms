import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { StoreFormFields } from "@/components/fleet/store-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type EditStore = {
    id: number;
    code: string;
    name: string;
    address: string;
    phone: string;
    is_default: boolean;
    status: string;
    notes: string;
};

export default function StoresEdit({ store }: { store: EditStore }) {
    const { data, setData, put, processing, errors } = useForm({
        code: store.code,
        name: store.name,
        address: store.address,
        phone: store.phone,
        is_default: store.is_default,
        status: store.status,
        notes: store.notes,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("fleet.stores.update", store.id));
    };

    return (
        <AuthenticatedLayout header="Edit Store">
            <Head title="Edit Store" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Edit store</CardTitle>
                    <CardDescription>Update store details and default flag.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <StoreFormFields errors={errors} data={data} setData={setData} />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
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
