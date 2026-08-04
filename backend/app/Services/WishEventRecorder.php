<?php

namespace App\Services;

use App\Models\Wish;

class WishEventRecorder
{
    public function created(Wish $wish): void
    {
        $wish->events()->create([
            'event_type' => 'created',
            'to_value' => $wish->status,
        ]);
    }

    public function changed(Wish $wish, array $original, array $changes): void
    {
        $tracked = [
            'status' => 'status_changed',
            'moderation_status' => 'moderation_changed',
            'visibility' => 'visibility_changed',
        ];

        foreach ($tracked as $field => $eventType) {
            if (! array_key_exists($field, $changes)) {
                continue;
            }

            $wish->events()->create([
                'event_type' => $eventType,
                'from_value' => $original[$field] ?? null,
                'to_value' => $changes[$field],
            ]);
        }

        $contentFields = array_values(array_intersect(
            ['title', 'description', 'category', 'author_type', 'author_name'],
            array_keys($changes),
        ));

        if ($contentFields !== []) {
            $wish->events()->create([
                'event_type' => 'updated',
                'metadata' => ['fields' => $contentFields],
            ]);
        }
    }

    public function deleted(Wish $wish): void
    {
        $wish->events()->create(['event_type' => 'deleted']);
    }

    public function restored(Wish $wish): void
    {
        $wish->events()->create(['event_type' => 'restored']);
    }
}
