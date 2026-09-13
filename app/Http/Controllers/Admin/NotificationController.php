<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));
        $query = Notification::query()->with(['deliveries', 'user:id,name,username,phone']);

        if ($request->filled('type')) $query->where('type', $request->string('type')->toString());
        if ($request->filled('user_id')) $query->where('user_id', (int) $request->integer('user_id'));
        if ($request->filled('unread')) $query->whereNull('read_at');

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(Notification $notification): JsonResponse
    {
        return response()->json($notification->load(['deliveries', 'user:id,name,username,phone']));
    }
}
