<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

class ExpresDokladToken
{
    /** @var ExpresDokladTokenId */
    private $tokenId;

    /** @var string */
    private $accessToken;

    /** @var string */
    private $refreshToken;

    /** @var \DateTimeImmutable */
    private $expiresAt;

    /** @var bool */
    private $invalid = FALSE;

    public function __construct(ExpresDokladTokenId $tokenId, string $accessToken, string $refreshToken, \DateTimeImmutable $expiresAt)
    {
        $this->tokenId = $tokenId;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
    }

    public function tokenId(): ExpresDokladTokenId
    {
        return $this->tokenId;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isInvalid(): bool
    {
        return $this->invalid;
    }
}
