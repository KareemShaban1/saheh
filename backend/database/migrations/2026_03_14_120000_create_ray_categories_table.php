<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ray_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->nullableMorphs('organization');
            $table->timestamps();

            $table->unique(['organization_id', 'organization_type', 'name'], 'ray_categories_org_name_unique');
        });

        if (Schema::hasTable('types')) {
            $legacyRows = DB::table('types')
                ->where('type', 'ray_category')
                ->get(['name', 'description', 'organization_id', 'organization_type', 'created_at', 'updated_at']);

            if ($legacyRows->isNotEmpty()) {
                $now = now();
                $payload = $legacyRows->map(function ($row) use ($now) {
                    return [
                        'name' => $row->name,
                        'description' => $row->description,
                        'organization_id' => $row->organization_id,
                        'organization_type' => $row->organization_type,
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $row->updated_at ?? $now,
                    ];
                })->all();

                foreach (array_chunk($payload, 500) as $chunk) {
                    DB::table('ray_categories')->insertOrIgnore($chunk);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ray_categories');
    }
};
