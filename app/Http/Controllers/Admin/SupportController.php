<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Jobs\SendSupportReplyJob;
use App\Models\AdminUser;
use App\Models\SupportContent;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class SupportController
{
    public function departments(): JsonResponse
    {
        return response()->json(SupportDepartment::query()->orderBy('sort_order')->orderBy('id')->paginate(50));
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();

        $department = SupportDepartment::query()->create([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($department, 201);
    }

    public function updateDepartment(Request $request, SupportDepartment $department): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();
        $department->update($data);
        return response()->json($department->refresh());
    }

    public function destroyDepartment(SupportDepartment $department): JsonResponse
    {
        $department->delete();
        return response()->json(['deleted' => true]);
    }

    public function contents(Request $request): JsonResponse
    {
        return response()->json(
            SupportContent::query()->with('department')->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))->orderBy('sort_order')->orderBy('id')->paginate(50)
        );
    }

    public function storeContent(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'type' => ['required', 'in:faq,tutorial'],
            'department_id' => ['nullable', 'exists:support_departments,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();
        $content = SupportContent::query()->create($data);
        return response()->json($content, 201);
    }

    public function updateContent(Request $request, SupportContent $content): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'type' => ['sometimes', 'in:faq,tutorial'],
            'department_id' => ['nullable', 'exists:support_departments,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();
        $content->update($data);
        return response()->json($content->refresh());
    }

    public function destroyContent(SupportContent $content): JsonResponse
    {
        $content->delete();
        return response()->json(['deleted' => true]);
    }

    public function tickets(Request $request): JsonResponse
    {
        return response()->json(
            SupportTicket::query()->with(['user', 'department'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->when($request->filled('department_id'), fn ($q) => $q->where('department_id', (int) $request->input('department_id')))->latest('id')->paginate(50)
        );
    }

    public function showTicket(SupportTicket $ticket): JsonResponse
    {
        return response()->json($ticket->load(['user', 'department', 'assignedAdmin', 'messages']));
    }

    public function reply(Request $request, SupportTicket $ticket, SupportTicketService $service): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'message' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.type' => ['required_with:attachments', 'in:photo,video,document,file'],
            'attachments.*.file_id' => ['nullable', 'string', 'max:4096'],
            'attachments.*.value' => ['nullable', 'string', 'max:4096'],
            'attachments.*.url' => ['nullable', 'url', 'max:4096'],
        ])->validate();

        $adminId = (int) AdminUser::query()->where('email', auth()->user()->email)->value('id');
        $message = $service->addAdminReply($ticket, $adminId, $data['message'] ?? null, $data['attachments'] ?? []);
        SendSupportReplyJob::dispatch($message->id);

        return response()->json($message->load('ticket'));
    }

    public function status(Request $request, SupportTicket $ticket, SupportTicketService $service): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'status' => ['required', 'in:open,pending,answered,closed'],
        ])->validate();

        return response()->json($service->setStatus($ticket, $data['status']));
    }
}
