import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreFormFields } from "@/components/tyres/tyre-form-fields";
import { TyreFormShell } from "@/components/tyres/tyre-form-shell";
import { Button } from "@/components/ui/button";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type FormOptions = {
    brands: { id: number; name: string }[];
    sizes: { id: number; size_label: string }[];
    sources: { value: string; label: string }[];
};

export default function TyresCreate({ brands, sizes, sources }: FormOptions) {
    const defaultSource = sources[0]?.value ?? "purchased_new_tyre";

    const { data, setData, post, processing, errors } = useForm({
        tyre_code: "",
        serial_number: "",
        brand_id: null as number | null,
        size_id: null as number | null,
        pattern: "",
        supplier: "",
        source: defaultSource,
        purchase_date: new Date().toISOString().slice(0, 10),
        purchase_price: 0,
        invoice_number: "",
        initial_tread_depth: null as number | null,
        current_tread_depth: null as number | null,
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("tyres.store"));
    };

    return (
        <AuthenticatedLayout header="Register Tyre">
            <Head title="Register Tyre" />

            <form onSubmit={submit}>
                <TyreFormShell
                    title="Register tyre"
                    description="Capture tyre identity, purchase details, and starting tread values. Approval can happen after registration."
                    backHref={route("tyres.index")}
                    backLabel="Back to Tyres"
                    footer={(
                        <>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.index")}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Registering..." : "Register tyre"}
                            </Button>
                        </>
                    )}
                >
                    <TyreFormFields
                        errors={errors}
                        data={data}
                        setData={setData}
                        brands={brands}
                        sizes={sizes}
                        sources={sources}
                    />
                </TyreFormShell>
            </form>
        </AuthenticatedLayout>
    );
}
