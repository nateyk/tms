<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\OdometerReadingSource;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\{Tyre, TyreAssignment, TyreBaseline, TyreBrand, TyreInspection, TyreSize, User, Vehicle, VehicleCombination, VehicleOdometerReading, VehicleType};
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FleetAuditAdditionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, FleetOperationalDefaultsSeeder::class]);
        DB::transaction(function (): void {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $powerType = VehicleType::query()->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')->firstOrFail();
            $trailerType = $this->trailerType();
            $size = TyreSize::query()->where('size_label', '315/80R22.5')->firstOrFail();
            foreach ($this->audits() as $audit) {
                $power = $this->vehicle($audit['power'], AssetType::PowerVehicle, $powerType, $audit['km'], $admin->id);
                $trailer = $this->vehicle($audit['trailer'], AssetType::Trailer, $trailerType, null, $admin->id);
                VehicleCombination::query()->updateOrCreate(['power_vehicle_id' => $power->id, 'trailer_vehicle_id' => $trailer->id], ['attached_date' => '2026-07-07', 'odometer_at_attach' => $audit['km'], 'status' => CombinationStatus::Active, 'attached_by' => $admin->id, 'approved_by' => $admin->id, 'notes' => 'Imported audited combination.']);
                VehicleOdometerReading::query()->updateOrCreate(['vehicle_id' => $power->id, 'odometer' => $audit['km'], 'source' => OdometerReadingSource::Import], ['reading_date' => '2026-07-07', 'recorded_by' => $admin->id, 'notes' => 'Imported from tyre audit.']);
                foreach ($this->rows($audit['rows']) as [$pos, $rawBrand, $serial, $percentage, $remark]) {
                    $isPower = $pos <= 'J'; $owner = $isPower ? $power : $trailer;
                    $brandName = match (strtoupper($rawBrand)) { 'BLACKHAWK', 'BLACKHWAK' => 'Black Hawk', 'SAILUN' => 'Sailun', 'DUPRO' => 'DUPRO', default => 'Triangle' };
                    $brand = TyreBrand::query()->firstOrCreate(['name' => $brandName], ['code' => strtoupper(substr($brandName, 0, 3)), 'status' => 'active']);
                    $locationType = $isPower ? TyreLocationType::PowerVehicle : TyreLocationType::Trailer;
                    $assignmentType = $isPower ? AssignmentAssetType::PowerVehicle : AssignmentAssetType::Trailer;
                    $tyre = Tyre::query()->firstOrCreate(['serial_number' => $serial], ['tyre_code' => $this->nextCode(), 'brand_id' => $brand->id, 'size_id' => $size->id, 'pattern' => 'Imported fleet audit', 'supplier' => 'Existing fleet fitment', 'initial_tread_depth' => 20, 'current_tread_depth' => $percentage / 5, 'source' => TyreSource::ExistingVehicle, 'current_location_type' => $locationType, 'current_location_id' => $owner->id, 'current_position_code' => $pos, 'status' => TyreStatus::Active, 'notes' => $remark ?: 'Imported from audited fleet sheet.']);
                    $tyre->update(['brand_id' => $brand->id, 'size_id' => $size->id, 'current_tread_depth' => $percentage / 5, 'current_location_type' => $locationType, 'current_location_id' => $owner->id, 'current_position_code' => $pos, 'status' => TyreStatus::Active, 'notes' => $remark ?: 'Imported from audited fleet sheet.']);
                    TyreAssignment::query()->updateOrCreate(['asset_type' => $assignmentType, 'asset_id' => $owner->id, 'position_code' => $pos, 'status' => TyreAssignmentStatus::Active], ['tyre_id' => $tyre->id, 'installed_date' => '2026-07-07', 'installed_odometer' => $audit['km'], 'installed_by' => $admin->id, 'notes' => 'Imported audited fitment.']);
                    TyreBaseline::query()->updateOrCreate(['tyre_id' => $tyre->id], ['baseline_location_type' => $locationType, 'baseline_location_id' => $owner->id, 'baseline_position_code' => $pos, 'baseline_odometer' => $audit['km'], 'baseline_percentage' => $percentage, 'expected_life_km' => 80000, 'baseline_date' => '2026-07-07', 'created_by' => $admin->id, 'notes' => 'Opening baseline from audit.']);
                    TyreInspection::query()->updateOrCreate(['tyre_id' => $tyre->id, 'inspection_date' => '2026-07-07', 'audit_odometer' => $audit['km']], ['vehicle_id' => $owner->id, 'position_code' => $pos, 'tread_depth' => $percentage / 5, 'audited_remaining_percentage' => $percentage, 'calculated_remaining_percentage_at_audit' => $percentage, 'variance_percentage' => 0, 'condition' => $percentage >= 80 ? 'Good' : ($percentage >= 50 ? 'Watch' : ($percentage >= 30 ? 'Low' : 'End of Life')), 'inspector' => 'Imported tyre audit', 'inspected_by' => $admin->id, 'audited_by' => $admin->id, 'reason' => 'Initial audited fleet import', 'notes' => $remark ?: 'Imported from audit.']);
                }
            }
        });
    }

    private function vehicle(string $plate, AssetType $assetType, VehicleType $type, ?int $km, int $adminId): Vehicle { $vehicle = Vehicle::query()->firstOrCreate(['plate_number' => $plate], ['asset_type' => $assetType, 'vehicle_type_id' => $type->id, 'status' => VehicleStatus::Active, 'odometer' => $km, 'notes' => 'Imported from audited fleet sheet.']); $vehicle->forceFill(['asset_type' => $assetType, 'vehicle_type_id' => $type->id, 'status' => VehicleStatus::Active, 'odometer' => $km, 'odometer_last_updated_at' => $km ? '2026-07-07 00:00:00' : null, 'odometer_last_updated_by' => $km ? $adminId : null])->save(); return $vehicle; }
    private function trailerType(): VehicleType { $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(12, 3, 'T'); foreach ($layout['positions'] as $i => &$p) { $code = chr(75 + $i); $p['code'] = $code; $p['display_code'] = $code; $p['label'] = 'Trailer position '.$code; } unset($p); $layout['positions'][] = ['code'=>'W','display_code'=>'W','legacy_code'=>null,'label'=>'Trailer spare W','axle'=>4,'side'=>'right','dual'=>'single','x'=>680,'y'=>346]; $layout['positions'][] = ['code'=>'X','display_code'=>'X','legacy_code'=>null,'label'=>'Trailer spare X','axle'=>4,'side'=>'left','dual'=>'single','x'=>200,'y'=>346]; return VehicleType::query()->updateOrCreate(['name'=>'Attached trailer - 12 tyres + W/X spares'], ['asset_type'=>AssetType::Trailer,'axle_count'=>3,'tyre_count'=>14,'layout_json'=>$layout,'status'=>'active']); }
    private function nextCode(): string { $i = (int) Tyre::withTrashed()->max('id') + 1; do { $code = sprintf('TYR-%04d', $i++); } while (Tyre::withTrashed()->where('tyre_code', $code)->exists()); return $code; }
    /** @return list<array{0:string,1:string,2:string,3:int,4:?string}> */ private function rows(string $rows): array { return array_map(fn ($line) => array_pad(explode('|', $line), 5, null), array_filter(explode("\n", trim($rows)))); }
    /** @return list<array{power:string,trailer:string,km:int,rows:string}> */ private function audits(): array { return [
        ['power'=>'ET-3-A27049','trailer'=>'ET-3-35766','km'=>95184,'rows'=>"A|TRIANGLE|RE06102G111|50\nB|TRIANGLE|RE06101J513|50\nC|BLACKHAWK|26C0053201|95\nD|BLACKHAWK|26C8299445|95\nE|BLACKHAWK|26C0092791|95\nF|BLACKHAWK|26C0124281|95\nG|BLACKHAWK|26C0124313|95\nH|BLACKHAWK|26C0197434|95\nI|BLACKHAWK|26C0124411|95\nJ|BLACKHAWK|26C0092202|95\nK|TRIANGLE|KE12185C313|55\nL|TRIANGLE|KE12206R501|55\nM|TRIANGLE|KE12235E106|55\nN|TRIANGLE|KE12186J702|55\nO|TRIANGLE|KD07165D707|35\nP|TRIANGLE|KD07156P911|30\nQ|TRIANGLE|KD07155E611|35\nR|TRIANGLE|J103273|35\nS|TRIANGLE|KE12206R701|55\nT|TRIANGLE|KE12217C607|55\nU|TRIANGLE|KE12197R509|55\nV|TRIANGLE|KE12207C303|55\nW|TRIANGLE|KD07157P902|35"],
        ['power'=>'ET-3-A21634','trailer'=>'ET-3-36812','km'=>105566,'rows'=>"A|TRIANGLE|RE06102V508|30\nB|TRIANGLE|RE06102K504|30\nC|SAILUN|26C0033473|90\nD|SAILUN|26C0030353|90\nE|SAILUN|25C8210100|90\nF|SAILUN|25C0603857|90\nG|BLACKHWAK|25C0768366|90\nH|BLACKHWAK|25C0782299|90\nI|BLACKHWAK|25C0782240|90\nJ|BLACKHWAK|25C0726714|90\nK|DUPRO|G236A23038|20\nL|TRIANGLE|KE07247E214|20\nM|TRIANGLE|KB07247E906|25\nN|TRIANGLE|KB07235J406|30\nO|TRIANGLE|RE02283O301|25\nP|TRIANGLE|KC07017F103|20\nQ|TRIANGLE|KB07236P709|20\nR|TRIANGLE|BP07021R605|20\nS|TRIANGLE|KE10266R703|45\nT|TRIANGLE|KE10296N712|45\nU|TRIANGLE|KE10267A406|45\nV|TRIANGLE|KE10246N703|45"],
        ['power'=>'ET-3-A27037','trailer'=>'ET-3-34951','km'=>95887,'rows'=>"A|TRIANGLE|RE06102J509|50\nB|TRIANGLE|RE06103V315|50\nC|BLACKHAWK|26C0030860|100\nD|BLACKHAWK|26C8030085|100\nE|BLACKHAWK|26C8030381|100\nF|BLACKHAWK|26C8029107|100\nG|BLACKHAWK|26C8303546|100\nH|BLACKHAWK|26C8044762|100\nI|BLACKHAWK|26C8029667|100\nJ|BLACKHAWK|26C8024576|100\nK|TRIANGLE|KD07155D710|35\nL|TRIANGLE|KB07227K101|35\nM|TRIANGLE|KB07236P711|45\nN|TRIANGLE|KC06026K713|45\nO|TRIANGLE|KE11215C601|60\nP|TRIANGLE|KE11226M507|60\nQ|TRIANGLE|KE11297K807|60\nR|TRIANGLE|KE11245C606|60\nS|TRIANGLE|KE11215E201|60|KE HAS BEEN DAMAGED\nT|TRIANGLE|KE11227K812|60\nU|TRIANGLE|KE11226J303|60\nV|TRIANGLE|KE11225J707|60\nW|TRIANGLE|KD07155A112|30|POWER\nX|TRIANGLE|KC06195D302|30|TRAILER CURRENTLY ON V"],
        ['power'=>'ET-3-A27036','trailer'=>'ET-3-34952','km'=>95287,'rows'=>"A|BLACKHAWK|26C0123023|95\nB|BLACKHAWK|26C0123805|95\nC|BLACKHAWK|26C8044992|95\nD|BLACKHAWK|26C8024636|95\nE|BLACKHAWK|26C8044960|95\nF|BLACKHAWK|26C8029015|95\nG|BLACKHAWK|26C8029475|95\nH|BLACKHAWK|26C0033946|95\nI|BLACKHAWK|26C8294989|95\nJ|BLACKHAWK|26C8029141|95\nK|TRIANGLE|E18024|30\nL|TRIANGLE|KC05056J512|30\nM|TRIANGLE|KD07157B608|30\nN|TRIANGLE|KD07157P901|30\nO|TRIANGLE|KE10147C301|50\nP|TRIANGLE|KE09257A406|50\nQ|TRIANGLE|KE10225C103|50\nR|TRIANGLE|KE10236M501|50\nS|TRIANGLE|KE10176R705|50\nT|TRIANGLE|KE10195E205|50\nU|TRIANGLE|KE10177E205|50\nV|TRIANGLE|KE10216J703|50\nW|TRIANGLE|CP03271I810|20|PASSANGER SIDE"],
    ]; }
}
