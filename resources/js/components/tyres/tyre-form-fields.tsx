import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

export type TyreFormData = {
    tyre_code?: string;
    serial_number: string;
    brand_id: number | null;
    size_id: number | null;
    pattern: string;
    supplier: string;
    source: string;
    purchase_date: string;
    purchase_price: number;
    invoice_number: string;
    initial_tread_depth: number | null;
    current_tread_depth: number | null;
    notes: string;
};

type BrandOption = { id: number; name: string };
type SizeOption = { id: number; size_label: string };
type SourceOption = { value: string; label: string };

type TyreFormFieldsProps = {
    errors: Partial<Record<string, string>>;
    data: TyreFormData;
    setData: <K extends keyof TyreFormData>(key: K, value: TyreFormData[K]) => void;
    brands: BrandOption[];
    sizes: SizeOption[];
    sources: SourceOption[];
};

export function TyreFormFields({
    errors,
    data,
    setData,
    brands,
    sizes,
    sources,
}: TyreFormFieldsProps) {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="mb-4 text-sm font-semibold">Identity</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label>Tyre code</Label>
                        <div className="flex min-h-10 items-center rounded-md border bg-muted/40 px-3 text-sm font-medium">
                            {data.tyre_code || "Auto-generated after save"}
                        </div>
                        <InputError message={errors.tyre_code} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="serial_number">Serial number</Label>
                        <Input
                            id="serial_number"
                            value={data.serial_number}
                            onChange={(e) => setData("serial_number", e.target.value)}
                            required
                        />
                        <InputError message={errors.serial_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Brand</Label>
                        <Select
                            value={data.brand_id ? String(data.brand_id) : "none"}
                            onValueChange={(value) =>
                                setData("brand_id", value === "none" ? null : Number(value))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select brand" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {brands.map((brand) => (
                                    <SelectItem key={brand.id} value={String(brand.id)}>
                                        {brand.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.brand_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Size</Label>
                        <Select
                            value={data.size_id ? String(data.size_id) : "none"}
                            onValueChange={(value) =>
                                setData("size_id", value === "none" ? null : Number(value))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select size" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {sizes.map((size) => (
                                    <SelectItem key={size.id} value={String(size.id)}>
                                        {size.size_label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.size_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="pattern">Pattern</Label>
                        <Input
                            id="pattern"
                            value={data.pattern}
                            onChange={(e) => setData("pattern", e.target.value)}
                        />
                        <InputError message={errors.pattern} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="supplier">Supplier</Label>
                        <Input
                            id="supplier"
                            value={data.supplier}
                            onChange={(e) => setData("supplier", e.target.value)}
                        />
                        <InputError message={errors.supplier} />
                    </div>
                </div>
            </div>

            <div>
                <h3 className="mb-4 text-sm font-semibold">Purchase details</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label>Source</Label>
                        <Select
                            value={data.source}
                            onValueChange={(value) => setData("source", value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select source" />
                            </SelectTrigger>
                            <SelectContent>
                                {sources.map((source) => (
                                    <SelectItem key={source.value} value={source.value}>
                                        {source.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.source} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="purchase_date">Purchase date</Label>
                        <Input
                            id="purchase_date"
                            type="date"
                            value={data.purchase_date}
                            onChange={(e) => setData("purchase_date", e.target.value)}
                        />
                        <InputError message={errors.purchase_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="purchase_price">Purchase price (ETB)</Label>
                        <Input
                            id="purchase_price"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.purchase_price}
                            onChange={(e) =>
                                setData("purchase_price", Number(e.target.value) || 0)
                            }
                            required
                        />
                        <InputError message={errors.purchase_price} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="invoice_number">Invoice number</Label>
                        <Input
                            id="invoice_number"
                            value={data.invoice_number}
                            onChange={(e) => setData("invoice_number", e.target.value)}
                        />
                        <InputError message={errors.invoice_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="initial_tread_depth">Initial tread depth (mm)</Label>
                        <Input
                            id="initial_tread_depth"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.initial_tread_depth ?? ""}
                            onChange={(e) =>
                                setData(
                                    "initial_tread_depth",
                                    e.target.value ? Number(e.target.value) : null,
                                )
                            }
                        />
                        <InputError message={errors.initial_tread_depth} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="current_tread_depth">Current tread depth (mm)</Label>
                        <Input
                            id="current_tread_depth"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.current_tread_depth ?? ""}
                            onChange={(e) =>
                                setData(
                                    "current_tread_depth",
                                    e.target.value ? Number(e.target.value) : null,
                                )
                            }
                        />
                        <InputError message={errors.current_tread_depth} />
                    </div>
                </div>
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
        </div>
    );
}
