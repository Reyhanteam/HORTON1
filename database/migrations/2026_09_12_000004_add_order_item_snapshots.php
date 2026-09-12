<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('product_name_snapshot')->nullable()->after('product_id');
            $table->string('plan_name_snapshot')->nullable()->after('plan_id');
            $table->unsignedInteger('duration_value_snapshot')->nullable();
            $table->string('duration_unit_snapshot', 16)->nullable();
            $table->unsignedBigInteger('capacity_value_snapshot')->nullable();
            $table->string('capacity_unit_snapshot', 16)->nullable();
            $table->boolean('is_trial_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'product_name_snapshot', 'plan_name_snapshot', 'duration_value_snapshot',
                'duration_unit_snapshot', 'capacity_value_snapshot', 'capacity_unit_snapshot', 'is_trial_snapshot',
            ]);
        });
    }
};
