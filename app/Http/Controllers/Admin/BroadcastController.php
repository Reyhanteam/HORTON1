<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Broadcast;
use App\Services\Notifications\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class BroadcastController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));
        return response()->json(Broadcast::query()->latest('id')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'media' => ['nullable', 'array'],
            'media.type' => ['nullable', 'in:photo,document,video,animation,audio,voice'],
            'media.value' => ['nullable', 'string', 'max:4096'],
            'media.file_id' => ['nullable', 'string', 'max:4096'],
            'media.url' => ['nullable', 'string', 'max:4096'],
            'keyboard' => ['nullable', 'array'],
            'targeting' => ['nullable', 'array'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
        $validator->after(function ($validator): void {
            $message = request()->input('message');
            $media = request()->input('media');
            if (blank($message) && ! is_array($media)) {
                $validator->errors()->add('message', 'A broadcast requires a message or media.');
            }
        });
        $data = $validator->validate();

        $broadcast = Broadcast::query()->create([
            'name' => $data['name'],
            'message' => $data['message'] ?? null,
            'media' => $data['media'] ?? null,
            'keyboard' => $data['keyboard'] ?? null,
            'targeting' => $data['targeting'] ?? null,
            'batch_size' => $data['batch_size'] ?? 100,
            'status' => isset($data['scheduled_at']) ? 'scheduled' : 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        return response()->json($broadcast, 201);
    }

    public function show(Broadcast $broadcast): JsonResponse
    {
        return response()->json($broadcast->loadCount([
            'recipients as pending_recipients' => fn ($query) => $query->where('status', 'pending'),
            'recipients as sending_recipients' => fn ($query) => $query->where('status', 'sending'),
            'recipients as sent_recipients' => fn ($query) => $query->where('status', 'sent'),
            'recipients as failed_recipients' => fn ($query) => $query->where('status', 'failed'),
        ]));
    }

    public function queue(Broadcast $broadcast, BroadcastService $service): JsonResponse
    {
        return response()->json($service->queue($broadcast));
    }

    public function cancel(Broadcast $broadcast, BroadcastService $service): JsonResponse
    {
        return response()->json($service->cancel($broadcast));
    }
}
