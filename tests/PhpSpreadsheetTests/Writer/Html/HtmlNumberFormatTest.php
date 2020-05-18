<?php

namespace PhpOffice\PhpSpreadsheetTests\Writer\Html;

use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheetTests\Functional;

class HtmlNumberFormatTest extends Functional\AbstractFunctional
{
    private $decsep;

    private $thosep;

    protected function setUp(): void
    {
        $this->decsep = StringHelper::getDecimalSeparator();
        StringHelper::setDecimalSeparator('.');
        $this->thosep = StringHelper::getThousandsSeparator();
        StringHelper::setThousandsSeparator(',');
    }

    protected function tearDown(): void
    {
        StringHelper::setDecimalSeparator($this->decsep);
        StringHelper::setThousandsSeparator($this->thosep);
    }

    public function testColorNumberFormat()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', -50);
        $sheet->setCellValue('A2', 3000);
        $sheet->setCellValue('A3', 0);
        $sheet->setCellValue('A4', '<br>');
        $fmt = '[Blue]$#,##0;[Red]$#,##0;$#,##0';
        $sheet->getStyle('A1:A4')->getNumberFormat()->setFormatCode($fmt);

        $writer = new Html($spreadsheet);
        $html = $writer->generateHTMLAll();
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')[0];
        $divs = $body->getElementsByTagName('div');

        $tabl = $divs[0]->getElementsByTagName('table');
        $tbod = $tabl[0]->getElementsByTagName('tbody');
        $rows = $tbod[0]->getElementsByTagName('tr');
        self::assertCount(4, $rows);

        $tds = $rows[0]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(1, $spans);
        $style = $spans[0]->getAttribute('style');
        self::assertEquals(1, preg_match('/color:red/', $style));
        self::assertEquals('$50', $spans[0]->textContent);

        $tds = $rows[1]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(1, $spans);
        $style = $spans[0]->getAttribute('style');
        self::assertEquals(1, preg_match('/color:blue/', $style));
        self::assertEquals('$3,000', $spans[0]->textContent);

        $tds = $rows[2]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(0, $spans);
        self::assertEquals('$0', $tds[0]->textContent);

        $tds = $rows[3]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(0, $spans);
        self::assertEquals('<br>', $tds[0]->textContent);

        $this->writeAndReload($spreadsheet, 'Html');
    }

    public function testColorNumberFormatComplex()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', -50);
        $sheet->setCellValue('A2', 3000.75);
        $sheet->setCellValue('A3', 0);
        $sheet->setCellValue('A4', 3000.25);
        $fmt = '[Blue][>=3000.5]$#,##0.00;[Red][<0]$#,##0.00;$#,##0.00';
        $sheet->getStyle('A1:A4')->getNumberFormat()->setFormatCode($fmt);

        $writer = new Html($spreadsheet);
        $html = $writer->generateHTMLAll();
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')[0];
        $divs = $body->getElementsByTagName('div');

        $tabl = $divs[0]->getElementsByTagName('table');
        $tbod = $tabl[0]->getElementsByTagName('tbody');
        $rows = $tbod[0]->getElementsByTagName('tr');
        self::assertCount(4, $rows);

        $tds = $rows[0]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(1, $spans);
        $style = $spans[0]->getAttribute('style');
        self::assertEquals(1, preg_match('/color:red/', $style));
        self::assertEquals('$50.00', $spans[0]->textContent);

        $tds = $rows[1]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(1, $spans);
        $style = $spans[0]->getAttribute('style');
        self::assertEquals(1, preg_match('/color:blue/', $style));
        self::assertEquals('$3,000.75', $spans[0]->textContent);

        $tds = $rows[2]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(0, $spans);
        self::assertEquals('$0.00', $tds[0]->textContent);

        $tds = $rows[3]->getElementsByTagName('td');
        self::assertCount(1, $tds);
        $spans = $tds[0]->getElementsByTagName('span');
        self::assertCount(0, $spans);
        self::assertEquals('$3,000.25', $tds[0]->textContent);

        $this->writeAndReload($spreadsheet, 'Html');
    }

    /**
     * @dataProvider providerNumberFormat
     *
     * @param mixed $expectedResult
     * @param mixed $val
     * @param mixed $fmt
     */
    public function testFormatValueWithMask($expectedResult, $val, $fmt)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getCell('A1')->setValue($val)->getStyle()->getNumberFormat()->setFormatCode($fmt);

        $writer = new Html($spreadsheet);
        $html = $writer->generateHTMLAll();
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')[0];
        $divs = $body->getElementsByTagName('div');

        $tabl = $divs[0]->getElementsByTagName('table');
        $tbod = $tabl[0]->getElementsByTagName('tbody');
        $rows = $tbod[0]->getElementsByTagName('tr');

        $tds = $rows[0]->getElementsByTagName('td');
        $nbsp = html_entity_decode('&nbsp;');
        self::assertEquals($expectedResult, str_replace($nbsp, ' ', $tds[0]->textContent));

        $this->writeAndReload($spreadsheet, 'Html');
    }

    public function providerNumberFormat()
    {
        return require __DIR__ . '/../../../data/Style/NumberFormat.php';
    }

    /**
     * @dataProvider providerNumberFormatDates
     *
     * @param mixed $expectedResult
     * @param mixed $val
     * @param mixed $fmt
     */
    public function testFormatValueWithMaskDate($expectedResult, $val, $fmt)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getCell('A1')->setValue($val)->getStyle()->getNumberFormat()->setFormatCode($fmt);

        $writer = new Html($spreadsheet);
        $html = $writer->generateHTMLAll();
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')[0];
        $divs = $body->getElementsByTagName('div');

        $tabl = $divs[0]->getElementsByTagName('table');
        $tbod = $tabl[0]->getElementsByTagName('tbody');
        $rows = $tbod[0]->getElementsByTagName('tr');

        $tds = $rows[0]->getElementsByTagName('td');
        $nbsp = html_entity_decode('&nbsp;');
        self::assertEquals($expectedResult, str_replace($nbsp, ' ', $tds[0]->textContent));

        $this->writeAndReload($spreadsheet, 'Html');
    }

    public function providerNumberFormatDates()
    {
        return require __DIR__ . '/../../../data/Style/NumberFormatDates.php';
    }
}