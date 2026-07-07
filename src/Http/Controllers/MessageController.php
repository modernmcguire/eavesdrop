<?php

namespace ModernMcGuire\Eavesdrop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use ModernMcGuire\Eavesdrop\Eavesdrop;

class MessageController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return Eavesdrop::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('content', 'like', "%{$search}%")
                        ->orWhere('to', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('notification', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('channel') && $request->channel !== 'all', function ($query) use ($request) {
                $query->where('channel', $request->channel);
            })
            ->latest()
            ->paginate(10, ['id', 'type', 'to', 'subject', 'content', 'channel', 'created_at']);
    }

    public function show(Eavesdrop $message): Eavesdrop
    {
        return $message;
    }

    public function destroy(): JsonResponse
    {
        Eavesdrop::query()->delete();

        return response()->json(['cleared' => true]);
    }
}
