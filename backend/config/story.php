<?php

return [
    // How long an ephemeral story stays visible before Story::active() excludes it.
    'ttl_hours' => (int) env('STORY_TTL_HOURS', 24),

    'max_carousel_items' => (int) env('STORY_MAX_CAROUSEL_ITEMS', 10),
    'min_duration_seconds' => (int) env('STORY_MIN_DURATION_SECONDS', 1),
    'max_duration_seconds' => (int) env('STORY_MAX_DURATION_SECONDS', 30),
    'default_duration_seconds' => (int) env('STORY_DEFAULT_DURATION_SECONDS', 5),

    'max_title_length' => (int) env('HIGHLIGHT_MAX_TITLE_LENGTH', 30),

    // Ephemeral (non-permanent) stories are soft-deleted this many days after
    // they expire — a grace window for anything still reading view analytics
    // before the row goes away. Permanent (highlighted) stories are never touched.
    'prune_after_days' => (int) env('STORY_PRUNE_AFTER_DAYS', 7),
];
