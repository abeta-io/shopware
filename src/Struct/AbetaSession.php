<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AbetaSession extends Struct
{
    public const EXTENSION_NAME = 'abetaSession';

    public function __construct(
        protected string $sessionId,
        protected ?string $returnUrl = null,
        protected ?string $token = null,
    ) {
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }
}
