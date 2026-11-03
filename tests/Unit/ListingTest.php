<?php
use PHPUnit\Framework\TestCase;

final class ListingTest extends TestCase
{
    public function testPagination(): void
    {
        $this->assertSame([1, 3, 0], paginate(45, 1, 20));
        $this->assertSame([3, 3, 40], paginate(45, 3, 20));
        $this->assertSame([3, 3, 40], paginate(45, 99, 20), 'a page past the end shows the last page');
        $this->assertSame([1, 3, 0], paginate(45, -4, 20), 'a negative page shows the first page');
        $this->assertSame([1, 1, 0], paginate(0, 1, 20), 'an empty list still has one page');
    }

    public function testCsvQuotesAndEscapes(): void
    {
        $this->assertSame("\"Bella\",\"Dog, Labrador\",\"say \"\"hi\"\"\"\r\n", csv_line(['Bella', 'Dog, Labrador', 'say "hi"']));
    }

    public function testCsvBlocksSpreadsheetFormulas(): void
    {
        // A donor named "=HYPERLINK(...)" must not become a live formula in Excel.
        $this->assertSame("\"'=HYPERLINK(\"\"x\"\")\",\"'@SUM(A1)\"\r\n", csv_line(['=HYPERLINK("x")', '@SUM(A1)']));
        $this->assertSame("\"-50\",\"\"\r\n", csv_line([-50, null]), 'negative numbers stay numbers');
    }

    public function testPagerKeepsFiltersAndMarksCurrentPage(): void
    {
        $html = pager_html(2, 5, ['q' => 'cat & dog']);
        $this->assertStringContainsString('aria-current="page">2<', $html);
        $this->assertStringContainsString('q=cat+%26+dog&amp;page=3', $html);
        $this->assertSame('', pager_html(1, 1));
    }
}
