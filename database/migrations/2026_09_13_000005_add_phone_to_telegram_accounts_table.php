<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_accounts', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->index()->after('language_code');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_accounts', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
