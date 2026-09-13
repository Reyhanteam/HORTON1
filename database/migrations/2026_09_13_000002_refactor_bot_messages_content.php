<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_messages', function (Blueprint $table): void {
            $table->string('type', 32)->default('message')->after('text');
            $table->string('button_type', 32)->nullable()->after('type');
            $table->string('description')->nullable()->after('button_type');
        });

        $legacyMessages = DB::table('bot_settings')
            ->where('key', 'like', 'messages.%')
            ->get(['key', 'value']);

        foreach ($legacyMessages as $setting) {
            $key = substr((string) $setting->key, strlen('messages.'));
            if ($key === '') {
                continue;
            }

            $text = (string) $setting->value;
            $decoded = json_decode($text, true);
            if (is_string($decoded)) {
                $text = $decoded;
            }

            $isButton = str_ends_with($key, '_button') || $key === 'membership.recheck';

            DB::table('bot_messages')->updateOrInsert(
                ['key' => $key, 'locale' => 'fa'],
                [
                    'text' => $text,
                    'type' => $isButton ? 'button' : 'message',
                    'button_type' => $isButton
                        ? ($key === 'registration.share_phone_button' ? 'reply' : 'inline')
                        : null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('bot_settings')
            ->where('key', 'like', 'messages.%')
            ->delete();
    }

    public function down(): void
    {
        $messages = DB::table('bot_messages')
            ->where('locale', 'fa')
            ->get(['key', 'text']);

        foreach ($messages as $message) {
            DB::table('bot_settings')->updateOrInsert(
                ['key' => 'messages.' . $message->key],
                [
                    'value' => json_encode($message->text, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'type' => 'string',
                    'is_public' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::table('bot_messages', function (Blueprint $table): void {
            $table->dropColumn(['type', 'button_type', 'description']);
        });
    }
};
