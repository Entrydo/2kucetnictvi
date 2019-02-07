<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\Person\Person;

class InvoiceSubscriber
{
    /** @var string */
    private $subscriberName;

    /** @var string */
    private $companyId;

    /** @var string */
    private $companyVatId;

    /** @var string */
    private $street;

    /** @var string */
    private $city;

    /** @var string */
    private $postalCode;

    /** @var int */
    private $countryId;

    public function __construct(string $subscriberName, string $companyId, string $companyVatId, string $street, string $city, string $postalCode, int $countryId)
    {
        $this->subscriberName = $subscriberName;
        $this->companyId = $companyId;
        $this->companyVatId = $companyVatId;
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->countryId = $countryId;
    }

    public static function createFromPerson(Person $person): self
    {
        $subscriberName = (string) $person->company()->name();

        if (empty($subscriberName)) {
            $subscriberName = (string) $person->name();
        }

        return new self(
            $subscriberName,
            (string) $person->company()->id(),
            (string) $person->company()->vatId(),
            (string) $person->address()->street(),
            (string) $person->address()->city(),
            (string) $person->address()->postalCode(),
            CountryCodeId::getId($person->address()->country()->code())
        );
    }

    public function getData(): array
    {
        return [
            'ico' => $this->companyId,
            'name_company' => $this->subscriberName,
            'dic' => $this->companyVatId,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->postalCode,
            'country_id' => $this->countryId,
        ];
    }
}
