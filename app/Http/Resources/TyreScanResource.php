<?php

namespace App\Http\Resources;

use App\Enums\AssignmentAssetType;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Services\TyreQrCodeService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tyre */
class TyreScanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $brand = $this->brand;
        $size = $this->size;
        $assignment = $this->activeAssignment;

        return [
            'tyre_code' => $this->tyre_code,
            'serial_number' => $this->serial_number,
            'brand' => $brand instanceof TyreBrand ? $brand->name : null,
            'size' => $size instanceof TyreSize ? ($size->size_label ?: $size->code) : null,
            'pattern' => $this->pattern,
            'status' => $this->status instanceof TyreStatus ? $this->status->value : null,
            'source' => $this->source instanceof TyreSource ? $this->source->value : null,
            'current_location_type' => $this->current_location_type instanceof TyreLocationType
                ? $this->current_location_type->value
                : null,
            'current_location_id' => $this->current_location_id,
            'current_position_code' => $this->current_position_code,
            'current_position_display' => $this->currentPositionDisplay(),
            'current_tread_depth' => $this->current_tread_depth,
            'purchase_price' => $this->purchase_price,
            'total_km_used' => $this->totalKmUsed(),
            'cost_per_km' => $this->costPerKm(),
            'qr_image_url' => app(TyreQrCodeService::class)->publicUrl($this->resource),
            'profile_url' => route('tyres.scan', $this->tyre_code),
            'active_assignment' => $assignment instanceof TyreAssignment ? [
                'asset_type' => $assignment->asset_type instanceof AssignmentAssetType
                    ? $assignment->asset_type->value
                    : null,
                'asset_id' => $assignment->asset_id,
                'position_code' => $assignment->position_code,
                'position_display' => $assignment->positionDisplay(),
                'installed_date' => $assignment->installed_date instanceof CarbonInterface
                    ? $assignment->installed_date->toIso8601String()
                    : null,
            ] : null,
        ];
    }
}
