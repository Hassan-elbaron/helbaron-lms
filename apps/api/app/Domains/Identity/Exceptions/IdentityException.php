<?php

namespace App\Domains\Identity\Exceptions;

use App\Platform\Shared\Exceptions\BaseDomainException;

/**
 * Base for all Identity exceptions. Extends the shared renderable exception so every error
 * emits the ONE standard envelope. Concrete subclasses set errorCode + status.
 */
abstract class IdentityException extends BaseDomainException {}
