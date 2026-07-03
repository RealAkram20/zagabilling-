<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    public function push(string $type, string $title, string $body, ?string $link = null, ?int $exceptUserId = null): void
    {
        $ids = User::query()
            ->when($exceptUserId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();

        Notification::insert($ids->map(fn ($id) => [
            'user_id' => $id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function recent(User $user, int $limit = 6): Collection
    {
        return Notification::where('user_id', $user->id)->latest()->limit($limit)->get();
    }

    public function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    }

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::where('user_id', $user->id)->latest()->paginate($perPage);
    }
}
