<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\SupportContent;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_ticket_with_media_and_sensitivity(): void
    {
        $user = User::factory()->create();
        $department = SupportDepartment::query()->create([
            'name' => 'فنی',
            'slug' => 'technical',
            'is_active' => true,
        ]);

        $ticket = app(SupportTicketService::class)->createTicket($user, $department->id, 'high', 'مشکل من', [
            ['type' => 'photo', 'file_id' => 'photo-123'],
            ['type' => 'document', 'file_id' => 'doc-123'],
        ]);

        $this->assertSame('high', $ticket->sensitivity);
        $this->assertSame('open', $ticket->status);
        $this->assertCount(1, $ticket->messages);
        $this->assertSame('photo', $ticket->messages->first()->attachments[0]['type']);
    }

    public function test_faq_is_read_from_database(): void
    {
        SupportContent::query()->create([
            'type' => 'faq',
            'title' => 'سوال تست',
            'body' => 'پاسخ تست',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        SupportContent::query()->create([
            'type' => 'tutorial',
            'title' => 'آموزش تست',
            'body' => 'محتوای آموزش',
            'is_active' => true,
        ]);

        $faq = app(SupportTicketService::class)->faq();

        $this->assertCount(1, $faq);
        $this->assertSame('سوال تست', $faq->first()->title);
    }

    public function test_admin_reply_moves_ticket_to_answered_and_creates_notification(): void
    {
        $user = User::factory()->create();
        $department = SupportDepartment::query()->create([
            'name' => 'فروش',
            'slug' => 'sales',
            'is_active' => true,
        ]);
        $ticket = SupportTicket::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'department_id' => $department->id,
            'subject' => 'تست',
            'status' => 'open',
            'priority' => 'normal',
            'sensitivity' => 'normal',
        ]);

        $reply = app(SupportTicketService::class)->addAdminReply($ticket, 1, 'پاسخ پشتیبانی', []);

        $this->assertSame('admin', $reply->sender_type);
        $this->assertSame('answered', $ticket->refresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'support.ticket.replied',
        ]);
    }
}
