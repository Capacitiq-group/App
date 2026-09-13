<?php

namespace App\Models;

use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Highlight extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'cover_upload_file_id',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Get the profile owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the custom cover file, if one was chosen.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function coverFile(): BelongsTo
    {
        return $this->belongsTo(UploadFile::class, 'cover_upload_file_id');
    }

    /**
     * Get the Stories in this Highlight, in order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The relationship instance.
     */
    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class, 'highlight_stories')
            ->withPivot(['position', 'added_at'])
            ->withTimestamps(false)
            ->orderBy('highlight_stories.position');
    }
}
