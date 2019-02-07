<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Nette\Utils\Json;

class AccessTokenProvider
{
    /** @var string */
    private $endpointBaseUri;

    /** @var string */
    private $clientId;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var ExpresDokladTokenRepository */
    private $tokenRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Logger */
    private $logger;

    public function __construct(
        string $clientId,
        string $username,
        string $password,
        string $endpointBaseUri,
        ExpresDokladTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        Logger $logger
    ) {
        $this->endpointBaseUri = $endpointBaseUri;
        $this->clientId = $clientId;
        $this->username = $username;
        $this->password = $password;
        $this->tokenRepository = $tokenRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function getToken(): string
    {
        $token = $this->tokenRepository->getValidToken();

        if (! $token || $token->expiresAt() < new \DateTimeImmutable()) {
            return $this->createAccessToken();
        }

        try {
            if ($token->expiresAt() < new \DateTimeImmutable('-24 hours')) {
                return $this->refreshAccessToken($token->refreshToken());
            }

            return $token->accessToken();
        }
        catch (\Throwable $e) {
            $this->logger->addError('Unable to refresh ExpresDoklad access token', ['exception' => $e]);

            return $this->createAccessToken();
        }
    }

    private function createAccessToken(): string
    {
        $data = [
            'grant_type' => GrantType::PASSWORD,
            'client_id' => $this->clientId,
            'username' => $this->username,
            'password' => $this->password,
        ];

        $this->logger->addDebug('POST ' . Endpoint::TOKEN_URL, ['data' => $data]);

        $client = $this->createGuzzleClient();
        $res = $client->post(Endpoint::TOKEN_URL, [
            'form_params' => $data,
        ]);

        $result = Json::decode($res->getBody());
        $this->saveToken($result->access_token, $result->refresh_token, $result->expires_in);

        return $result->access_token;
    }

    private function refreshAccessToken(string $refreshToken)
    {
        $client = $this->createGuzzleClient();
        $res = $client->post(Endpoint::TOKEN_URL, [
            'form_params' => [
                'grant_type' => GrantType::REFRESH_TOKEN,
                'client_id' => $this->clientId,
                'refresh_token' => $refreshToken,
            ],
        ]);

        $result = Json::decode($res->getBody());
        $this->saveToken($result->access_token, $result->refresh_token, $result->expires_in);

        return $result->access_token;
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->endpointBaseUri,
        ]);
    }

    private function saveToken(string $accessToken, string $refreshToken, int $expiresIn): ExpresDokladToken
    {
        $token = new ExpresDokladToken(
            ExpresDokladTokenId::generate(),
            $accessToken,
            $refreshToken,
            new \DateTimeImmutable("+${expiresIn} seconds")
        );

        $this->tokenRepository->save($token);
        $this->entityManager->flush();

        return $token;
    }
}
