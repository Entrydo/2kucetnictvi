<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use Entrydo\Domain\Model\Geography\CountryCode;

class InvoiceLanguage
{
    public const CS = 'cs';

    public const EN = 'en';

    public const default = self::CS;

    public static function byValue(?string $value): string
    {
        $value = $value ? strtoupper($value) : null;

        if (defined("self::${value}")) {
            return constant("self::${value}");
        }

        return self::default;
    }

    public static function byCountryCode(CountryCode $countryCode): string
    {
        if (empty($countryCode->value())) {
            return self::default;
        }

        return $countryCode->value() === 'CZ' ? self::CS : self::EN;
    }
}
