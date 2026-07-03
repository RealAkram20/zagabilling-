<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => $this->notifications->paginate(auth()->user()),
        ]);
    }

    public function read(Notification $notification): JsonResponse
    {
        $this->authorizeOwner($notification);
        $notification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function unread(Notification $notification): JsonResponse
    {
        $this->authorizeOwner($notification);
        $notification->update(['read_at' => null]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorizeOwner($notification);
        $notification->delete();

        return response()->json(['ok' => true]);
    }

    public function readAll(): RedirectResponse
    {
        Notification::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function authorizeOwner(Notification $notification): void
    {
        abort_unless($notification->user_id === auth()->id(), 403);
    }
}
