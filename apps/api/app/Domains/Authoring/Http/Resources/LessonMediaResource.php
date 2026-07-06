<?php

namespace App\Domains\Authoring\Http\Resources;

use App\Domains\Authoring\Models\LessonMedia;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Media metadata for authoring (admin) views. Playback signing is NOT done here.
 *
 * @property LessonMedia $resource
 */
class LessonMediaResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'mux_asset_id' => $this->resource->mux_asset_id,
            'mux_playback_id' => $this->resource->mux_playback_id,
            's3_key' => $this->resource->s3_key,
            'mime_type' => $this->resource->mime_type,
            'duration' => $this->resource->duration,
            'filesize' => $this->resource->filesize,
        ];
    }
}
