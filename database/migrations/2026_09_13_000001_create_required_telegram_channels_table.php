<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('required_telegram_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('chat_id', 255)->unique();
            $table->string('title');
            $table->string('username')->nullable()->index();
            $table->string('invite_url', 2048)->nullable();
            $table->boolean('is_required')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'is_required', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_telegram_channels');
    }
};
