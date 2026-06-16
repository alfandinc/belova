<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1) create pivot table
        Schema::create('hrd_employee_position', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('position_id');
            $table->tinyInteger('is_primary')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'position_id']);

            // foreign keys (best-effort, may rely on existing schema)
            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('hrd_position')->onDelete('cascade');
        });

        // 2) migrate existing position_id values into pivot table as primary
        if (Schema::hasTable('hrd_employee')) {
            $now = now();
            DB::table('hrd_employee')
                ->select('id', 'position_id')
                ->whereNotNull('position_id')
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($now) {
                    $insert = [];
                    foreach ($rows as $r) {
                        if (empty($r->position_id)) continue;
                        $insert[] = [
                            'employee_id' => $r->id,
                            'position_id' => $r->position_id,
                            'is_primary' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($insert)) DB::table('hrd_employee_position')->insert($insert);
                });
        }

        // 3) remove position_id and division_id columns from hrd_employee (if exists)
        if (Schema::hasTable('hrd_employee')) {
            // Drop foreign key constraints if present (MySQL information_schema lookup)
            try {
                $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='hrd_employee' AND COLUMN_NAME IN ('position_id','division_id') AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach ($constraints as $c) {
                    if (!empty($c->CONSTRAINT_NAME)) {
                        DB::statement("ALTER TABLE hrd_employee DROP FOREIGN KEY `".$c->CONSTRAINT_NAME."`");
                    }
                }
            } catch (\Throwable $e) {
                // ignore; some environments may not allow information_schema read or constraint may not exist
            }

            Schema::table('hrd_employee', function (Blueprint $table) {
                if (Schema::hasColumn('hrd_employee', 'position_id')) {
                    $table->dropColumn('position_id');
                }
                if (Schema::hasColumn('hrd_employee', 'division_id')) {
                    $table->dropColumn('division_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Attempt to recreate columns (best-effort)
        if (Schema::hasTable('hrd_employee')) {
            Schema::table('hrd_employee', function (Blueprint $table) {
                if (!Schema::hasColumn('hrd_employee', 'position_id')) {
                    $table->unsignedBigInteger('position_id')->nullable()->after('village_id');
                }
                if (!Schema::hasColumn('hrd_employee', 'division_id')) {
                    $table->unsignedBigInteger('division_id')->nullable()->after('position_id');
                }
            });

            // Restore position_id values from pivot (set primary ones)
            $primaries = DB::table('hrd_employee_position')->where('is_primary', 1)->get();
            foreach ($primaries as $p) {
                DB::table('hrd_employee')->where('id', $p->employee_id)->update(['position_id' => $p->position_id]);
            }
        }

        Schema::dropIfExists('hrd_employee_position');
    }
};
