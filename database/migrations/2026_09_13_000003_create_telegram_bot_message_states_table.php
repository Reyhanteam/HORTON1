<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_message_states', function (Blueprint $table): void {
            $table->id();
            $table->string('chat_id', 64)->unique();
            $table->unsignedBigInteger('message_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_message_states');
    }
};
