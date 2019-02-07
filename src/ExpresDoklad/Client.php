<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\Json;

class Client
{
    /** @var AccessTokenProvider */
    private $accessTokenProvider;

    /** @var string */
    private $endpointBaseUri;

    /** @var string */
    private $companyId;

    public function __construct(
        string $endpointBaseUri,
        string $companyId,
        AccessTokenProvider $accessTokenProvider
    ) {
        $this->endpointBaseUri = $endpointBaseUri;
        $this->companyId = $companyId;
        $this->accessTokenProvider = $accessTokenProvider;
    }

    public function getPdf(string $invoiceId): string
    {
        $guzzleClient = $this->createGuzzleClient();

        $url = Endpoint::getPdf($this->companyId, $invoiceId);
        $res = $guzzleClient->get($url);

        // @TODO handle errors

        return (string) $res->getBody();
    }

    public function updateInvoice(EditInvoiceRequest $request): string
    {
        $guzzleClient = $this->createGuzzleClient();
        $url = Endpoint::editInvoice($this->companyId, $request->getInvoiceId());

        $res = $guzzleClient->put($url, [
            'body' => Json::encode($request->getData()),
        ]);

        // @TODO handle errors

        $result = Json::decode($res->getBody());

        return (string) $result->id;
    }

    public function changeInvoiceStatus(ChangeInvoiceStatusRequest $request): string
    {
        $guzzleClient = $this->createGuzzleClient();
        $url = Endpoint::changeInvoiceStatus($this->companyId, $request->getInvoiceId(), $request->getStatus());

        $res = $guzzleClient->put($url);

        // @TODO handle errors

        $result = Json::decode($res->getBody());

        return (string) ($result->generated_invoice ?? $request->getInvoiceId());
    }

    public function createInvoice(CreateInvoiceRequest $request): string
    {
        $guzzleClient = $this->createGuzzleClient();
        $url = Endpoint::createInvoice($this->companyId);

        try {
            $res = $guzzleClient->post($url, [
                'body' => Json::encode($request->getData()),
            ]);

            $result = Json::decode($res->getBody());

            return (string) $result->id;
        }
        catch (ClientException $e) {
            if ($response = $e->getResponse()) {
                throw new \Exception((string) $response->getBody());
            }

            throw $e;
        }
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->endpointBaseUri,
            'headers' => [
                'content-type' => 'application/json',
                'authorization' => 'Bearer ' . $this->accessTokenProvider->getToken(),
            ],
        ]);
    }
}
