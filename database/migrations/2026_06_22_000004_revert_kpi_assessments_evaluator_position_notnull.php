<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RevertKpiAssessmentsEvaluatorPositionNotnull extends Migration
{
    public function up()
    {
        // Revert evaluator_position_id to NOT NULL and restore ON DELETE CASCADE
        try {
            DB::statement("ALTER TABLE `kpi_assessments` DROP FOREIGN KEY kpi_assessments_evaluator_position_id_foreign");
        } catch (\Throwable $e) {
            // ignore if fk does not exist or different name
        }
        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `evaluator_position_id` BIGINT UNSIGNED NOT NULL");
        try {
            DB::statement("ALTER TABLE `kpi_assessments` ADD CONSTRAINT kpi_assessments_evaluator_position_id_foreign FOREIGN KEY (`evaluator_position_id`) REFERENCES `hrd_position`(`id`) ON DELETE CASCADE");
        } catch (\Throwable $e) {
            // ignore if adding FK fails
        }
    }

    public function down()
    {
        // Make evaluator_position_id nullable again and set FK to SET NULL
        try {
            DB::statement("ALTER TABLE `kpi_assessments` DROP FOREIGN KEY kpi_assessments_evaluator_position_id_foreign");
        } catch (\Throwable $e) {
            // ignore
        }
        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `evaluator_position_id` BIGINT UNSIGNED NULL");
        try {
            DB::statement("ALTER TABLE `kpi_assessments` ADD CONSTRAINT kpi_assessments_evaluator_position_id_foreign FOREIGN KEY (`evaluator_position_id`) REFERENCES `hrd_position`(`id`) ON DELETE SET NULL");
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
