<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Invoice\ExpresDoklad;

use PHPUnit\Framework\TestCase;

class InvoiceLanguageTest extends TestCase
{
    /**
     * @dataProvider byValueProvider
     */
    public function testByValue(?string $value, string $expectedResult): void
    {
        $result = InvoiceLanguage::byValue($value);

        $this->assertSame($result, $expectedResult);
    }

    public function byValueProvider(): array
    {
        return [
            ['cs', InvoiceLanguage::CS],
            ['en', InvoiceLanguage::EN],
            [null, InvoiceLanguage::default],
            ['', InvoiceLanguage::default],
            ['asdf', InvoiceLanguage::default],
        ];
    }
}
