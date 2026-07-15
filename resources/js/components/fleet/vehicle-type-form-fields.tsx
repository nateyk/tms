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

export type LayoutPresetOption = {
    value: string;
    label: string;
    description: string;
    tyre_count: number;
    axle_count: number;
    spare_count: number;
    asset_type: string;
};

export type AssetTypeOption = {
    value: string;
    label: string;
};

export type VehicleTypeFormData = {
    name: string;
    asset_type: string;
    status: string;
    layout_preset: string;
    tyre_count: number;
    axle_count: number;
};

type VehicleTypeFormFieldsProps = {
    errors: Partial<Record<keyof VehicleTypeFormData, string>>;
    data: VehicleTypeFormData;
    setData: <K extends keyof VehicleTypeFormData>(
        key: K,
        value: VehicleTypeFormData[K],
    ) => void;
    assetTypes: AssetTypeOption[];
    layoutPresets: LayoutPresetOption[];
};

export function VehicleTypeFormFields({
    errors,
    data,
    setData,
    assetTypes,
    layoutPresets,
}: VehicleTypeFormFieldsProps) {
    const selectedPreset = layoutPresets.find((preset) => preset.value === data.layout_preset);

    return (
        <>
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

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Asset type</Label>
                    <Select
                        value={data.asset_type}
                        onValueChange={(value) => setData("asset_type", value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select asset type" />
                        </SelectTrigger>
                        <SelectContent>
                            {assetTypes.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.asset_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <Input
                        id="status"
                        value={data.status}
                        onChange={(e) => setData("status", e.target.value)}
                        required
                    />
                    <InputError message={errors.status} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label>Layout preset</Label>
                <Select
                    value={data.layout_preset}
                    onValueChange={(value) => {
                        const preset = layoutPresets.find((item) => item.value === value);
                        setData("layout_preset", value);
                        if (preset) {
                            setData("asset_type", preset.asset_type);
                            setData("tyre_count", preset.tyre_count);
                            setData("axle_count", preset.axle_count);
                        }
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Select layout" />
                    </SelectTrigger>
                    <SelectContent>
                        {layoutPresets.map((preset) => (
                            <SelectItem key={preset.value} value={preset.value}>
                                {preset.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {selectedPreset && (
                    <p className="text-sm text-muted-foreground">{selectedPreset.description}</p>
                )}
                <InputError message={errors.layout_preset} />
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="tyre_count">Tyre count</Label>
                    <Input id="tyre_count" value={data.tyre_count} readOnly />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="axle_count">Axle count</Label>
                    <Input id="axle_count" value={data.axle_count} readOnly />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="spare_count">Spare count</Label>
                    <Input
                        id="spare_count"
                        value={selectedPreset?.spare_count ?? 0}
                        readOnly
                    />
                </div>
            </div>
        </>
    );
}

export function defaultVehicleTypeForm(
    layoutPresets: LayoutPresetOption[],
    defaultPreset: string,
): VehicleTypeFormData {
    const preset =
        layoutPresets.find((item) => item.value === defaultPreset) ?? layoutPresets[0];

    return {
        name: "",
        asset_type: preset?.asset_type ?? "power_vehicle",
        status: "active",
        layout_preset: preset?.value ?? "heavy_truck_24",
        tyre_count: preset?.tyre_count ?? 24,
        axle_count: preset?.axle_count ?? 6,
    };
}
