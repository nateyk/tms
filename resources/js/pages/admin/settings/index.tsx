import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import { Head, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type SettingsData = {
    company_name: string;
    max_trailers_per_power: number;
};

export default function SettingsIndex({ settings }: { settings: SettingsData }) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        company_name: settings.company_name,
        max_trailers_per_power: settings.max_trailers_per_power,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("admin.settings.update"));
    };

    return (
        <AuthenticatedLayout header="System Settings">
            <Head title="System Settings" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>System Settings</CardTitle>
                    <CardDescription>
                        Configure company details and fleet operational limits.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="company_name">Company name</Label>
                            <Input
                                id="company_name"
                                value={data.company_name}
                                onChange={(e) => setData("company_name", e.target.value)}
                                required
                            />
                            <InputError message={errors.company_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="max_trailers_per_power">
                                Max active trailers per power unit
                            </Label>
                            <Input
                                id="max_trailers_per_power"
                                type="number"
                                min={1}
                                value={data.max_trailers_per_power}
                                onChange={(e) =>
                                    setData(
                                        "max_trailers_per_power",
                                        parseInt(e.target.value, 10) || 1,
                                    )
                                }
                                required
                            />
                            <InputError message={errors.max_trailers_per_power} />
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Save settings
                            </Button>
                            {recentlySuccessful && (
                                <span className="text-sm text-muted-foreground">Saved.</span>
                            )}
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
