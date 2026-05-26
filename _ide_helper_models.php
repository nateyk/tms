<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property string $request_type
 * @property \App\Enums\ApprovalRequestStatus $status
 * @property int $requested_by
 * @property int|null $checked_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approvable
 * @property-read \App\Models\User $requestedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalStep> $steps
 * @property-read int|null $steps_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereUpdatedAt($value)
 */
	class ApprovalRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $approval_request_id
 * @property int $step_order
 * @property string $step_name
 * @property string $status
 * @property int|null $acted_by
 * @property \Illuminate\Support\Carbon|null $acted_at
 * @property string|null $comments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $actedByUser
 * @property-read \App\Models\ApprovalRequest $approvalRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereActedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereActedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereApprovalRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereStepName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereUpdatedAt($value)
 */
	class ApprovalStep extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $store_id
 * @property string $type
 * @property string|null $address
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Store|null $store
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicles
 * @property-read int|null $vehicles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereUpdatedAt($value)
 */
	class Location extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $action_type
 * @property string $syncable_type
 * @property int $syncable_id
 * @property array<array-key, mixed> $payload
 * @property \App\Enums\MatrixSyncStatus $status
 * @property array<array-key, mixed>|null $response
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $syncable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereLastAttemptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereSyncableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereSyncableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatrixSyncLog whereUpdatedAt($value)
 */
	class MatrixSyncLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string $status
 * @property bool $is_default
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Location> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tyre> $tyres
 * @property-read int|null $tyres_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereUpdatedAt($value)
 */
	class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereValue($value)
 */
	class SystemSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $transfer_no
 * @property int $trailer_vehicle_id
 * @property int|null $from_power_vehicle_id
 * @property int $to_power_vehicle_id
 * @property \Illuminate\Support\Carbon $transfer_date
 * @property int|null $from_odometer
 * @property int|null $to_odometer
 * @property int|null $location_id
 * @property string|null $reason
 * @property \App\Enums\VoucherStatus $status
 * @property int $prepared_by
 * @property int|null $checked_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\Vehicle|null $fromPowerVehicle
 * @property-read \App\Models\Location|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MatrixSyncLog> $matrixSyncLogs
 * @property-read int|null $matrix_sync_logs_count
 * @property-read \App\Models\User $preparedByUser
 * @property-read \App\Models\Vehicle|null $toPowerVehicle
 * @property-read \App\Models\Vehicle|null $trailer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereFromOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereFromPowerVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer wherePreparedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereToOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereToPowerVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereTrailerVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereTransferDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereTransferNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrailerTransfer whereUpdatedAt($value)
 */
	class TrailerTransfer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $tyre_code
 * @property string $serial_number
 * @property int|null $brand_id
 * @property int|null $size_id
 * @property string|null $pattern
 * @property string|null $supplier
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property numeric $purchase_price
 * @property string|null $invoice_number
 * @property numeric|null $initial_tread_depth
 * @property numeric|null $current_tread_depth
 * @property \App\Enums\TyreSource $source
 * @property \App\Enums\TyreLocationType $current_location_type
 * @property int|null $current_location_id
 * @property string|null $current_position_code
 * @property \App\Enums\TyreStatus $status
 * @property string|null $qr_code_path
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\TyreAssignment|null $activeAssignment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\TyreBrand|null $brand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreDisposal> $disposals
 * @property-read int|null $disposals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreInspection> $inspections
 * @property-read int|null $inspections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreMaintenance> $maintenanceRecords
 * @property-read int|null $maintenance_records_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreMovement> $movements
 * @property-read int|null $movements_count
 * @property-read \App\Models\TyreSize|null $size
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereCurrentLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereCurrentLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereCurrentPositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereCurrentTreadDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereInitialTreadDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre wherePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereQrCodePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereSizeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereTyreCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tyre withoutTrashed()
 */
	class Tyre extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tyre_id
 * @property \App\Enums\AssignmentAssetType $asset_type
 * @property int $asset_id
 * @property string $position_code
 * @property \Illuminate\Support\Carbon $installed_date
 * @property int|null $installed_odometer
 * @property \Illuminate\Support\Carbon|null $removed_date
 * @property int|null $removed_odometer
 * @property int|null $km_used
 * @property \App\Enums\TyreAssignmentStatus $status
 * @property int|null $installed_by
 * @property int|null $removed_by
 * @property int|null $movement_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $installedByUser
 * @property-read \App\Models\TyreMovement|null $movement
 * @property-read \App\Models\User|null $removedByUser
 * @property-read \App\Models\Tyre|null $tyre
 * @property-read \App\Models\Vehicle|null $vehicle
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereAssetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereInstalledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereInstalledDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereInstalledOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereKmUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereMovementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment wherePositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereRemovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereRemovedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereRemovedOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereTyreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreAssignment whereUpdatedAt($value)
 */
	class TyreAssignment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tyre> $tyres
 * @property-read int|null $tyres_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreBrand whereUpdatedAt($value)
 */
	class TyreBrand extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $disposal_no
 * @property int $tyre_id
 * @property \App\Enums\TyreLocationType|null $last_location_type
 * @property int|null $last_location_id
 * @property string|null $last_position_code
 * @property int|null $final_km_used
 * @property string|null $final_condition
 * @property \App\Enums\DisposalReason $disposal_reason
 * @property numeric|null $estimated_scrap_value
 * @property numeric|null $sold_amount
 * @property \App\Enums\VoucherStatus $status
 * @property int $prepared_by
 * @property int|null $checked_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\User $preparedByUser
 * @property-read \App\Models\Tyre|null $tyre
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereDisposalNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereDisposalReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereEstimatedScrapValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereFinalCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereFinalKmUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereLastLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereLastLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereLastPositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal wherePreparedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereSoldAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereTyreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreDisposal whereUpdatedAt($value)
 */
	class TyreDisposal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tyre_id
 * @property \Illuminate\Support\Carbon $inspection_date
 * @property numeric|null $tread_depth
 * @property numeric|null $pressure
 * @property string|null $condition
 * @property string|null $inspector
 * @property int|null $inspected_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Tyre|null $tyre
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereInspectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereInspectionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereInspector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereTreadDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereTyreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreInspection whereUpdatedAt($value)
 */
	class TyreInspection extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $maintenance_no
 * @property int $tyre_id
 * @property \App\Enums\AssignmentAssetType|null $asset_type
 * @property int|null $asset_id
 * @property string|null $position_code
 * @property \App\Enums\MaintenanceProblemType $problem_type
 * @property string|null $action_taken
 * @property numeric|null $cost
 * @property string|null $technician
 * @property \Illuminate\Support\Carbon $maintenance_date
 * @property \Illuminate\Support\Carbon|null $next_inspection_date
 * @property \App\Enums\MaintenanceStatus $status
 * @property int $prepared_by
 * @property int|null $approved_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\User $preparedByUser
 * @property-read \App\Models\Tyre|null $tyre
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereActionTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereAssetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereMaintenanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereMaintenanceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereNextInspectionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance wherePositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance wherePreparedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereProblemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereTechnician($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereTyreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMaintenance whereUpdatedAt($value)
 */
	class TyreMaintenance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $movement_no
 * @property \App\Enums\MovementType $movement_type
 * @property int $tyre_id
 * @property \App\Enums\TyreLocationType|null $from_location_type
 * @property int|null $from_location_id
 * @property string|null $from_position_code
 * @property int|null $from_odometer
 * @property \App\Enums\TyreLocationType|null $to_location_type
 * @property int|null $to_location_id
 * @property string|null $to_position_code
 * @property int|null $to_odometer
 * @property \Illuminate\Support\Carbon $movement_date
 * @property string|null $reason
 * @property \App\Enums\VoucherStatus $status
 * @property int $prepared_by
 * @property int|null $checked_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\User|null $approvedByUser
 * @property-read \App\Models\User|null $checkedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MatrixSyncLog> $matrixSyncLogs
 * @property-read int|null $matrix_sync_logs_count
 * @property-read \App\Models\User $preparedByUser
 * @property-read \App\Models\Tyre|null $tyre
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereFromLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereFromLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereFromOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereFromPositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereMovementDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereMovementNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereMovementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement wherePreparedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereToLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereToLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereToOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereToPositionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereTyreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreMovement whereUpdatedAt($value)
 */
	class TyreMovement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $size_label
 * @property string|null $code
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tyre> $tyres
 * @property-read int|null $tyres_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereSizeLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TyreSize whereUpdatedAt($value)
 */
	class TyreSize extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreMovement> $preparedMovements
 * @property-read int|null $prepared_movements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrailerTransfer> $preparedTrailerTransfers
 * @property-read int|null $prepared_trailer_transfers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User team($teams, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTeam($teams)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $vehicle_code
 * @property string|null $plate_number
 * @property \App\Enums\AssetType $asset_type
 * @property int $vehicle_type_id
 * @property \App\Enums\VehicleStatus $status
 * @property int|null $current_location_id
 * @property int|null $odometer
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\VehicleCombination|null $activeCombinationAsPower
 * @property-read \App\Models\VehicleCombination|null $activeCombinationAsTrailer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreAssignment> $activeTyreAssignments
 * @property-read int|null $active_tyre_assignments_count
 * @property-read \App\Models\Location|null $currentLocation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VehicleCombination> $powerCombinations
 * @property-read int|null $power_combinations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VehicleCombination> $trailerCombinations
 * @property-read int|null $trailer_combinations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TyreAssignment> $tyreAssignments
 * @property-read int|null $tyre_assignments_count
 * @property-read \App\Models\VehicleType $vehicleType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereAssetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCurrentLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereOdometer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle wherePlateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereVehicleCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereVehicleTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle withoutTrashed()
 */
	class Vehicle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $power_vehicle_id
 * @property int $trailer_vehicle_id
 * @property \Illuminate\Support\Carbon $attached_date
 * @property \Illuminate\Support\Carbon|null $detached_date
 * @property int|null $odometer_at_attach
 * @property int|null $odometer_at_detach
 * @property \App\Enums\CombinationStatus $status
 * @property int $attached_by
 * @property int|null $detached_by
 * @property int|null $approved_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedByUser
 * @property-read \App\Models\User $attachedByUser
 * @property-read \App\Models\User|null $detachedByUser
 * @property-read \App\Models\Vehicle|null $powerVehicle
 * @property-read \App\Models\Vehicle|null $trailer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereAttachedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereAttachedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereDetachedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereDetachedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereOdometerAtAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereOdometerAtDetach($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination wherePowerVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereTrailerVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleCombination whereUpdatedAt($value)
 */
	class VehicleCombination extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \App\Enums\AssetType $asset_type
 * @property int $axle_count
 * @property int $tyre_count
 * @property array<array-key, mixed>|null $layout_json
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicles
 * @property-read int|null $vehicles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereAssetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereAxleCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereLayoutJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereTyreCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleType whereUpdatedAt($value)
 */
	class VehicleType extends \Eloquent {}
}

