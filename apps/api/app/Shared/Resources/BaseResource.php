<?php

namespace App\Shared\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API Resource. A thin marker over JsonResource so every domain resource shares a
 * common ancestor and conventions (e.g. never exposing raw internal ids/storage keys).
 * No business logic.
 */
abstract class BaseResource extends JsonResource
{
    //
}
