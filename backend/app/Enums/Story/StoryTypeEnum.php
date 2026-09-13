<?php

namespace App\Enums\Story;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum StoryTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case IMAGE       = 'image';
    case VIDEO       = 'video';
    case TEXT        = 'text';
    case SHARED_POST = 'shared_post';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::IMAGE       => 'Image',
            self::VIDEO       => 'Video',
            self::TEXT        => 'Text',
            self::SHARED_POST => 'Shared post',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::IMAGE       => 'Hình ảnh',
            self::VIDEO       => 'Video',
            self::TEXT        => 'Văn bản',
            self::SHARED_POST => 'Bài viết được chia sẻ',
        };
    }

    /**
     * Whether this story type carries its own story_media rows (image/video,
     * including image carousels). Text stories carry no media; shared-post
     * stories render the referenced post's own media instead.
     */
    public function hasOwnMedia(): bool
    {
        return in_array($this, [self::IMAGE, self::VIDEO], true);
    }
}
