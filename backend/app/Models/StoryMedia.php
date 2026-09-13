<?php

namespace App\Models;

use App\Enums\Media\MediaTypeEnum;
use App\Enums\Video\VideoEncodingStatusEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoryMedia extends Model
{
    use HasUuidObservable;
    use SoftDeletes;

    protected $table = 'story_media';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'story_id',
        'type',
        'upload_file_id',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaTypeEnum::class,
            'order' => 'integer',
        ];
    }

    /**
     * Get the story this media slide belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the underlying uploaded file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(UploadFile::class, 'upload_file_id');
    }

    public function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->type === MediaTypeEnum::HLS_VIDEO) {
                    $encoding = $this->file?->videoEncoding;

                    if ($encoding && $encoding->status === VideoEncodingStatusEnum::READY) {
                        return $encoding->master_playlist_url;
                    }
                }

                return $this->file?->url;
            }
        );
    }
}
