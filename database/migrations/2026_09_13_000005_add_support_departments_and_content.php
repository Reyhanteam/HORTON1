<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('support_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32)->default('faq')->index();
            $table->foreignId('department_id')->nullable()->constrained('support_departments')->nullOnDelete();
            $table->string('category')->nullable()->index();
            $table->string('title');
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained('support_departments')->nullOnDelete();
            $table->string('sensitivity', 16)->default('normal')->after('priority')->index();
            $table->index(['department_id', 'status', 'sensitivity']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id', 'status', 'sensitivity']);
            $table->dropColumn(['department_id', 'sensitivity']);
        });

        Schema::dropIfExists('support_contents');
        Schema::dropIfExists('support_departments');
    }
};
