<?php

namespace App\Http\Resources\Api\Story;

use App\Http\Resources\BaseJsonResource;

class StoryMediaResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'type' => $this->type->value,
            'order' => $this->order,
            'upload_file_uuid' => $this->file?->uuid,
        ];
    }
}
