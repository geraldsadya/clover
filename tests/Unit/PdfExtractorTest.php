<?php

namespace Tests\Unit;

use App\Exceptions\PdfExtractionException;
use App\Services\PdfExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfExtractorTest extends TestCase
{
    private PdfExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->extractor = new PdfExtractor;
    }

    /** @test */
    public function it_extracts_text_from_valid_pdf()
    {
        // Skip this test if pdftotext is not available
        if (! $this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

        // Create a minimal valid PDF with text content
        $pdfContent = $this->createValidPdfWithText('This is a test CV with some content that should be extracted properly.');

        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        $result = $this->extractor->extract($file);

        $this->assertIsString($result);
        $this->assertStringContainsString('test CV', $result);
        $this->assertGreaterThan(50, strlen($result));
    }

    /** @test */
    public function it_throws_exception_on_non_pdf_file()
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'This is not a PDF file');

        $this->expectException(PdfExtractionException::class);
        $this->expectExceptionMessage('File must be a valid PDF.');

        $this->extractor->extract($file);
    }

    /** @test */
    public function it_throws_exception_on_corrupt_pdf()
    {
        // Create a file that looks like PDF but is corrupted
        $corruptPdf = '%PDF-1.4\ncorrupted content that will cause extraction to fail';

        $file = UploadedFile::fake()->createWithContent('corrupt.pdf', $corruptPdf);

        $this->expectException(PdfExtractionException::class);

        $this->extractor->extract($file);
    }

    /** @test */
    public function it_throws_exception_on_empty_pdf()
    {
        // Skip this test if pdftotext is not available
        if (! $this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

        // Create a minimal PDF with no text content
        $emptyPdf = '%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n174\n%%EOF';

        $file = UploadedFile::fake()->createWithContent('empty.pdf', $emptyPdf);

        $this->expectException(PdfExtractionException::class);
        $this->expectExceptionMessage('CV appears empty or unreadable.');

        $this->extractor->extract($file);
    }

    /** @test */
    public function it_cleans_whitespace_and_special_chars()
    {
        // Skip this test if pdftotext is not available
        if (! $this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

        $pdfContent = $this->createValidPdfWithText("This   has\n\n\nmultiple\t\tspaces\r\nand\0control\0chars");

        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        $result = $this->extractor->extract($file);

        $this->assertStringNotContainsString('   ', $result); // No multiple spaces
        $this->assertStringNotContainsString("\n\n\n", $result); // No multiple newlines
        $this->assertStringNotContainsString("\t\t", $result); // No tabs
        $this->assertStringNotContainsString("\0", $result); // No null chars
    }

    /** @test */
    public function it_truncates_long_cv_text()
    {
        // Skip this test if pdftotext is not available
        if (! $this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

        // Create a very long text content
        $longText = str_repeat('This is a very long CV content that should be truncated. ', 1000);
        $pdfContent = $this->createValidPdfWithText($longText);

        $file = UploadedFile::fake()->createWithContent('long.pdf', $pdfContent);

        $result = $this->extractor->extract($file);

        $this->assertLessThanOrEqual(15000, strlen($result));
        $this->assertStringContainsString('[... content truncated ...]', $result);
    }

    /** @test */
    public function it_deletes_temp_files_after_extraction()
    {
        // Skip this test if pdftotext is not available
        if (! $this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

        $pdfContent = $this->createValidPdfWithText('Test content');
        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        // Mock the temp directory to track file creation/deletion
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filesBefore = count(glob($tempDir.'/*'));

        $this->extractor->extract($file);

        $filesAfter = count(glob($tempDir.'/*'));

        // Should have same number of files (temp file created and deleted)
        $this->assertEquals($filesBefore, $filesAfter);
    }

    /** @test */
    public function it_deletes_temp_files_on_error()
    {
        // Create a file that will cause extraction to fail
        $corruptPdf = '%PDF-1.4\ncorrupted content';
        $file = UploadedFile::fake()->createWithContent('corrupt.pdf', $corruptPdf);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filesBefore = count(glob($tempDir.'/*'));

        try {
            $this->extractor->extract($file);
        } catch (PdfExtractionException $e) {
            // Expected exception
        }

        $filesAfter = count(glob($tempDir.'/*'));

        // Should have same number of files (temp file created and deleted even on error)
        $this->assertEquals($filesBefore, $filesAfter);
    }

    /** @test */
    public function it_validates_mime_type()
    {
        // Create a file with PDF extension but wrong MIME type
        $file = UploadedFile::fake()->create('test.pdf', 100, 'text/plain');

        $this->expectException(PdfExtractionException::class);
        $this->expectExceptionMessage('File must be a valid PDF.');

        $this->extractor->extract($file);
    }

    /** @test */
    public function it_validates_magic_bytes()
    {
        // Create a file with PDF MIME type but wrong magic bytes
        $file = UploadedFile::fake()->createWithContent('test.pdf', 'Not a PDF file');

        $this->expectException(PdfExtractionException::class);
        $this->expectExceptionMessage('File must be a valid PDF.');

        $this->extractor->extract($file);
    }

    /**
     * Create a minimal valid PDF with text content
     */
    private function createValidPdfWithText(string $text): string
    {
        // This creates a minimal PDF with the text content
        // In a real test, you might want to use a proper PDF generation library
        $pdfContent = "%PDF-1.4\n";
        $pdfContent .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
        $pdfContent .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
        $pdfContent .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n";
        $pdfContent .= "/Contents 4 0 R\n>>\nendobj\n";
        $pdfContent .= "4 0 obj\n<<\n/Length ".strlen($text)."\n>>\nstream\n";
        $pdfContent .= "BT\n/F1 12 Tf\n72 720 Td\n({$text}) Tj\nET\nendstream\nendobj\n";
        $pdfContent .= "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000200 00000 n \n";
        $pdfContent .= "trailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n300\n%%EOF";

        return $pdfContent;
    }

    /**
     * Check if pdftotext binary is available
     */
    private function isPdftotextAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('which pdftotext', $output, $returnCode);

        return $returnCode === 0;
    }
}
