<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoverLetterGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function it_displays_form_on_homepage()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('cover-letter.index');
        $response->assertSee('CV Cover Letter Generator');
        $response->assertSee('Upload Your CV');
        $response->assertSee('Job Description');
    }

    /** @test */
    public function it_validates_pdf_file_is_required()
    {
        $response = $this->post('/generate', [
            'job_description' => 'This is a test job description with more than 50 characters to meet the minimum requirement.'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'cv' => ['Please upload a CV.']
            ]
        ]);
    }

    /** @test */
    public function it_validates_job_description_is_required()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post('/generate', [
            'cv' => $file
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'job_description' => ['Please enter a job description.']
            ]
        ]);
    }

    /** @test */
    public function it_rejects_non_pdf_files()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->post('/generate', [
            'cv' => $file,
            'job_description' => 'This is a test job description with more than 50 characters to meet the minimum requirement.'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'cv' => ['The CV must be a PDF file.']
            ]
        ]);
    }

    /** @test */
    public function it_rejects_files_over_10mb()
    {
        $file = UploadedFile::fake()->create('test.pdf', 11000, 'application/pdf'); // 11MB

        $response = $this->post('/generate', [
            'cv' => $file,
            'job_description' => 'This is a test job description with more than 50 characters to meet the minimum requirement.'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'cv' => ['The CV file must not be larger than 10MB.']
            ]
        ]);
    }

    /** @test */
    public function it_validates_job_description_minimum_length()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post('/generate', [
            'cv' => $file,
            'job_description' => 'Short' // Less than 50 characters
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'job_description' => ['Job description must be at least 50 characters.']
            ]
        ]);
    }

    /** @test */
    public function it_validates_job_description_maximum_length()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $longDescription = str_repeat('a', 10001); // More than 10,000 characters

        $response = $this->post('/generate', [
            'cv' => $file,
            'job_description' => $longDescription
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'job_description' => ['Job description must not exceed 10,000 characters.']
            ]
        ]);
    }

    /** @test */
    public function it_returns_placeholder_response_with_valid_input()
    {
        // Create a fake PDF file with proper PDF headers
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n174\n%%EOF";
        
        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        $response = $this->post('/generate', [
            'cv' => $file,
            'job_description' => 'This is a test job description with more than 50 characters to meet the minimum requirement.'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'cover_letter' => 'This is a placeholder cover letter. The actual implementation will extract text from the PDF and generate a tailored cover letter using AI.',
                'word_count' => 25,
                'request_id' => true // Just check it exists
            ],
            'meta' => [
                'tokens_used' => 0,
                'processing_time_ms' => 100
            ]
        ]);
    }

    /** @test */
    public function health_check_endpoint_returns_ok()
    {
        $response = $this->get('/healthz');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'app' => 'cover-letter-generator',
            'version' => '1.0.0',
            'checks' => [
                'database' => 'ok',
                'openai' => 'not_configured',
                'storage' => 'ok'
            ]
        ]);
        $response->assertJsonStructure([
            'timestamp'
        ]);
    }
}