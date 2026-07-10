import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";

type VehicleOption = {
    id: number;
    label: string;
    from_power_vehicle_id?: number | null;
};

type LocationOption = { id: number; label: string };

type TransferFormData = {
    trailer_vehicle_id: number | null;
    from_power_vehicle_id: number | null;
    to_power_vehicle_id: number | null;
    transfer_date: string;
    from_odometer: number | null;
    to_odometer: number | null;
    location_id: number | null;
    reason: string;
    notes: string;
};

type TrailerTransferFormFieldsProps = {
    data: TransferFormData;
    setData: <K extends keyof TransferFormData>(key: K, value: TransferFormData[K]) => void;
    errors: Partial<Record<keyof TransferFormData, string>>;
    trailers: VehicleOption[];
    powerVehicles: VehicleOption[];
    locations: LocationOption[];
    readOnlyTrailer?: boolean;
};

export function TrailerTransferFormFields({
    data,
    setData,
    errors,
    trailers,
    powerVehicles,
    locations,
    readOnlyTrailer = false,
}: TrailerTransferFormFieldsProps) {
    const handleTrailerChange = (trailerId: number) => {
        const trailer = trailers.find((item) => item.id === trailerId);
        setData("trailer_vehicle_id", trailerId);
        setData("from_power_vehicle_id", trailer?.from_power_vehicle_id ?? null);
    };

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="transfer_date">Transfer date</Label>
                    <Input
                        id="transfer_date"
                        type="date"
                        value={data.transfer_date}
                        onChange={(e) => setData("transfer_date", e.target.value)}
                    />
                    {errors.transfer_date && (
                        <p className="text-sm text-destructive">{errors.transfer_date}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="trailer_vehicle_id">Trailer</Label>
                    {readOnlyTrailer ? (
                        <Input
                            value={
                                trailers.find((t) => t.id === data.trailer_vehicle_id)?.label ?? ""
                            }
                            disabled
                        />
                    ) : (
                        <Select
                            value={data.trailer_vehicle_id ? String(data.trailer_vehicle_id) : undefined}
                            onValueChange={(value) => handleTrailerChange(Number(value))}
                        >
                            <SelectTrigger id="trailer_vehicle_id">
                                <SelectValue placeholder="Select trailer" />
                            </SelectTrigger>
                            <SelectContent>
                                {trailers.map((trailer) => (
                                    <SelectItem key={trailer.id} value={String(trailer.id)}>
                                        {trailer.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {errors.trailer_vehicle_id && (
                        <p className="text-sm text-destructive">{errors.trailer_vehicle_id}</p>
                    )}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="from_power_vehicle_id">From power unit</Label>
                    <Select
                        value={
                            data.from_power_vehicle_id
                                ? String(data.from_power_vehicle_id)
                                : undefined
                        }
                        onValueChange={(value) =>
                            setData("from_power_vehicle_id", Number(value))
                        }
                    >
                        <SelectTrigger id="from_power_vehicle_id">
                            <SelectValue placeholder="Current power unit (if attached)" />
                        </SelectTrigger>
                        <SelectContent>
                            {powerVehicles.map((vehicle) => (
                                <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                    {vehicle.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.from_power_vehicle_id && (
                        <p className="text-sm text-destructive">{errors.from_power_vehicle_id}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="to_power_vehicle_id">To power unit</Label>
                    <Select
                        value={
                            data.to_power_vehicle_id ? String(data.to_power_vehicle_id) : undefined
                        }
                        onValueChange={(value) =>
                            setData("to_power_vehicle_id", Number(value))
                        }
                    >
                        <SelectTrigger id="to_power_vehicle_id">
                            <SelectValue placeholder="Destination power unit" />
                        </SelectTrigger>
                        <SelectContent>
                            {powerVehicles
                                .filter((vehicle) => vehicle.id !== data.from_power_vehicle_id)
                                .map((vehicle) => (
                                    <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                        {vehicle.label}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    {errors.to_power_vehicle_id && (
                        <p className="text-sm text-destructive">{errors.to_power_vehicle_id}</p>
                    )}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="space-y-2">
                    <Label htmlFor="from_odometer">Odometer out</Label>
                    <Input
                        id="from_odometer"
                        type="number"
                        min={0}
                        value={data.from_odometer ?? ""}
                        onChange={(e) =>
                            setData(
                                "from_odometer",
                                e.target.value === "" ? null : Number(e.target.value),
                            )
                        }
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="to_odometer">Odometer in</Label>
                    <Input
                        id="to_odometer"
                        type="number"
                        min={0}
                        value={data.to_odometer ?? ""}
                        onChange={(e) =>
                            setData(
                                "to_odometer",
                                e.target.value === "" ? null : Number(e.target.value),
                            )
                        }
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="location_id">Transfer location</Label>
                    <Select
                        value={data.location_id ? String(data.location_id) : undefined}
                        onValueChange={(value) => setData("location_id", Number(value))}
                    >
                        <SelectTrigger id="location_id">
                            <SelectValue placeholder="Optional location" />
                        </SelectTrigger>
                        <SelectContent>
                            {locations.map((location) => (
                                <SelectItem key={location.id} value={String(location.id)}>
                                    {location.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="reason">Reason</Label>
                    <Textarea
                        id="reason"
                        rows={3}
                        value={data.reason}
                        onChange={(e) => setData("reason", e.target.value)}
                    />
                    {errors.reason && (
                        <p className="text-sm text-destructive">{errors.reason}</p>
                    )}
                </div>
                <div className="space-y-2">
                    <Label htmlFor="notes">Internal notes</Label>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData("notes", e.target.value)}
                    />
                </div>
            </div>
        </div>
    );
}
