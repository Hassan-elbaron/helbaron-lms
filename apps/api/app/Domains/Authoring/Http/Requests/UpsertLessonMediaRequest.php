<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

/**
 * Media METADATA only (no upload/playback). All fields optional; store what is known.
 */
class UpsertLessonMediaRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'mux_asset_id' => ['nullable', 'string', 'max:255'],
            'mux_playback_id' => ['nullable', 'string', 'max:255'],
            's3_key' => ['nullable', 'string', 'max:1024'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'filesize' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
