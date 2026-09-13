<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('deduplication_key', 191)->nullable()->unique()->after('data');
        });

        Schema::table('broadcasts', function (Blueprint $table): void {
            $table->json('targeting')->nullable()->after('keyboard');
            $table->unsignedInteger('batch_size')->default(100)->after('targeting');
        });

        Schema::create('broadcast_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcasts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['broadcast_id', 'user_id']);
            $table->index(['broadcast_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_recipients');

        Schema::table('broadcasts', function (Blueprint $table): void {
            $table->dropColumn(['targeting', 'batch_size']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique(['deduplication_key']);
            $table->dropColumn('deduplication_key');
        });
    }
};
