<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateKpiAssessmentsChangeAssessmentTypeAndNullableEvaluatorPosition extends Migration
{
    public function up()
    {
        // Modify enum values for assessment_type and make evaluator_position_id nullable with SET NULL on delete
        // Use raw SQL because altering enum requires direct SQL on MySQL
        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `assessment_type` ENUM('specific_position','direct_parent','bottom_up') NOT NULL DEFAULT 'direct_parent'");

        // Drop existing foreign key on evaluator_position_id, modify column to nullable, then re-add FK with ON DELETE SET NULL
        // Constraint name is the default Laravel naming convention
        DB::statement("ALTER TABLE `kpi_assessments` DROP FOREIGN KEY kpi_assessments_evaluator_position_id_foreign");
        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `evaluator_position_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `kpi_assessments` ADD CONSTRAINT kpi_assessments_evaluator_position_id_foreign FOREIGN KEY (`evaluator_position_id`) REFERENCES `hrd_position`(`id`) ON DELETE SET NULL");
    }

    public function down()
    {
        // Revert enum and evaluator_position_id nullability and FK behavior
        DB::statement("ALTER TABLE `kpi_assessments` DROP FOREIGN KEY kpi_assessments_evaluator_position_id_foreign");
        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `evaluator_position_id` BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE `kpi_assessments` ADD CONSTRAINT kpi_assessments_evaluator_position_id_foreign FOREIGN KEY (`evaluator_position_id`) REFERENCES `hrd_position`(`id`) ON DELETE CASCADE");

        DB::statement("ALTER TABLE `kpi_assessments` MODIFY `assessment_type` ENUM('parent_to_child','child_to_parent') NOT NULL DEFAULT 'parent_to_child'");
    }
}
