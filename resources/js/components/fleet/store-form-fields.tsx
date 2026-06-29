import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";

export type StoreFormData = {
    code: string;
    name: string;
    address: string;
    phone: string;
    is_default: boolean;
    status: string;
    notes: string;
};

type StoreFormFieldsProps = {
    errors: Partial<Record<keyof StoreFormData, string>>;
    data: StoreFormData;
    setData: <K extends keyof StoreFormData>(key: K, value: StoreFormData[K]) => void;
};

export function StoreFormFields({ errors, data, setData }: StoreFormFieldsProps) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="code">Code</Label>
                    <Input
                        id="code"
                        value={data.code}
                        onChange={(e) => setData("code", e.target.value)}
                        required
                    />
                    <InputError message={errors.code} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                    />
                    <InputError message={errors.name} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="address">Address</Label>
                <Textarea
                    id="address"
                    value={data.address}
                    onChange={(e) => setData("address", e.target.value)}
                    rows={3}
                />
                <InputError message={errors.address} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone</Label>
                    <Input
                        id="phone"
                        value={data.phone}
                        onChange={(e) => setData("phone", e.target.value)}
                    />
                    <InputError message={errors.phone} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <Input
                        id="status"
                        value={data.status}
                        onChange={(e) => setData("status", e.target.value)}
                    />
                    <InputError message={errors.status} />
                </div>
            </div>

            <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="space-y-0.5">
                    <Label htmlFor="is_default">Default store</Label>
                    <p className="text-sm text-muted-foreground">
                        Used for new tyre registration and store defaults.
                    </p>
                </div>
                <Switch
                    id="is_default"
                    checked={data.is_default}
                    onCheckedChange={(checked) => setData("is_default", checked === true)}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData("notes", e.target.value)}
                    rows={3}
                />
                <InputError message={errors.notes} />
            </div>
        </>
    );
}
