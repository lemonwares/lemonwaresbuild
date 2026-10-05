<?php

use App\Models\IntegrationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('integration_settings')->orderBy('id')->each(function ($row) {
            $encoded = IntegrationSetting::encode((string) $row->key, $row->value);
            if ($encoded !== $row->value) {
                DB::table('integration_settings')->where('id', $row->id)->update(['value' => $encoded]);
            }
        });
    }

    public function down(): void
    {
        DB::table('integration_settings')->orderBy('id')->each(function ($row) {
            if (is_string($row->value) && str_starts_with($row->value, IntegrationSetting::ENCRYPTED_PREFIX)) {
                DB::table('integration_settings')->where('id', $row->id)->update(['value' => IntegrationSetting::decode($row->value)]);
            }
        });
    }
};
