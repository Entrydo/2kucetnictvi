<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\BankAccount\BankAccount;

class Bank
{
    /** @var string|null */
    private $partNumber;

    /** @var string */
    private $number;

    /** @var string */
    private $code;

    /** @var string */
    private $iban;

    /** @var string */
    private $swiftCode;

    public function __construct(?string $partNumber, string $number, string $code, string $iban, string $swiftCode)
    {

        $this->partNumber = $partNumber;
        $this->number = $number;
        $this->code = $code;
        $this->iban = $iban;
        $this->swiftCode = $swiftCode;
    }

    public static function createFromBankAccount(BankAccount $bankAccount): self
    {
        $parsedNumber = explode('-', (string) $bankAccount->accountNumber());

        if (count($parsedNumber) === 2) {
            [$partNumber, $number] = $parsedNumber;
        }
        else {
            $partNumber = null;
            $number = $parsedNumber[0];
        }

        return new self(
            $partNumber,
            $number,
            $bankAccount->bankCode(),
            $bankAccount->iban(),
            $bankAccount->bicCode()
        );
    }

    public function getData(): array
    {
        return [
            'part_number' => $this->partNumber ?: '',
            'number' => $this->number,
            'code' => $this->code,
            'iban' => $this->iban,
            'swift_code' => $this->swiftCode,
        ];
    }
}
