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
        // Skip this test if pdftotext is not available
        if (!$this->isPdftotextAvailable()) {
            $this->markTestSkipped('pdftotext binary not available');
        }

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
                'cover_letter' => 'This is a placeholder cover letter. The actual implementation will generate a tailored cover letter using AI based on the extracted CV text and job description.',
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

    /** @test */
    public function it_includes_accessibility_features()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for ARIA labels and accessibility attributes
        $response->assertSee('aria-label=&amp;amp;quot;Cover letter generation form&amp;amp;quot;');
        $response->assertSee('aria-labelledby=&amp;amp;quot;cv-label&amp;amp;quot;');
        $response->assertSee('aria-labelledby=&amp;amp;quot;job-label&amp;amp;quot;');
        $response->assertSee('aria-describedby=&amp;amp;quot;cv-help&amp;amp;quot;');
        $response->assertSee('aria-describedby=&amp;amp;quot;job-help&amp;amp;quot;');
        $response->assertSee('role=&amp;amp;quot;form&amp;amp;quot;');
        $response->assertSee('role=&amp;amp;quot;button&amp;amp;quot;');
        $response->assertSee('role=&amp;amp;quot;status&amp;amp;quot;');
        $response->assertSee('role=&amp;amp;quot;alert&amp;amp;quot;');
        $response->assertSee('role=&amp;amp;quot;region&amp;amp;quot;');
        $response->assertSee('aria-live=&amp;amp;quot;polite&amp;amp;quot;');
        $response->assertSee('aria-live=&amp;amp;quot;assertive&amp;amp;quot;');
        
        // Check for screen reader only content
        $response->assertSee('class=&amp;quot;sr-only&amp;quot;');
        
        // Check for proper form labels
        $response->assertSee('&amp;lt;label for=&amp;quot;cv&amp;quot; id=&amp;quot;cv-label&amp;quot;&amp;gt;');
        $response->assertSee('&amp;lt;label for=&amp;quot;job_description&amp;quot; id=&amp;quot;job-label&amp;quot;&amp;gt;');
    }

    /** @test */
    public function it_includes_mobile_responsive_features()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for mobile-responsive CSS
        $response->assertSee('@media (max-width: 768px)');
        $response->assertSee('font-size: 16px'); // Prevents zoom on iOS
        $response->assertSee('min-height: 44px'); // Touch-friendly buttons
        
        // Check for responsive layout classes
        $response->assertSee('result-actions');
        $response->assertSee('flex-direction: column'); // Mobile stack
    }

    /** @test */
    public function it_includes_alpine_js_state_management()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for Alpine.js integration
        $response->assertSee('x-data=&amp;quot;coverLetterApp()&amp;quot;');
        $response->assertSee('x-init=&amp;quot;init()&amp;quot;');
        $response->assertSee('x-model=&amp;quot;jobDescription&amp;quot;');
        $response->assertSee('x-show=&amp;quot;loading&amp;quot;');
        $response->assertSee('x-show=&amp;quot;error&amp;quot;');
        $response->assertSee('x-show=&amp;quot;result&amp;quot;');
        $response->assertSee('@submit.prevent=&amp;quot;submit()&amp;quot;');
        $response->assertSee('@click=&amp;quot;copyToClipboard()&amp;quot;');
        $response->assertSee('@click=&amp;quot;reset()&amp;quot;');
        
        // Check for Alpine.js transitions
        $response->assertSee('x-transition:enter');
        $response->assertSee('x-transition:leave');
    }

    /** @test */
    public function it_includes_loading_state_features()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for loading spinner
        $response->assertSee('class=&amp;quot;spinner&amp;quot;');
        $response->assertSee('@keyframes spin');
        
        // Check for loading state management
        $response->assertSee(':disabled=&amp;quot;loading&amp;quot;');
        $response->assertSee(':aria-busy=&amp;quot;loading&amp;quot;');
        $response->assertSee('Generating your cover letter...');
        
        // Check for loading state transitions
        $response->assertSee('x-transition:enter=&amp;quot;transition ease-out duration-300&amp;quot;');
    }

    /** @test */
    public function it_includes_copy_to_clipboard_functionality()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for copy functionality
        $response->assertSee('copyToClipboard()');
        $response->assertSee('navigator.clipboard.writeText');
        $response->assertSee('Copy to Clipboard');
        $response->assertSee('Copied!');
        
        // Check for toast notification
        $response->assertSee('class=&amp;quot;toast&amp;quot;');
        $response->assertSee('x-show=&amp;quot;showToast&amp;quot;');
        $response->assertSee('showToastMessage');
    }

    /** @test */
    public function it_includes_drag_and_drop_file_upload()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for drag and drop functionality
        $response->assertSee('@dragover.prevent=&amp;quot;dragover = true&amp;quot;');
        $response->assertSee('@dragleave.prevent=&amp;quot;dragover = false&amp;quot;');
        $response->assertSee('@drop.prevent=&amp;quot;handleFileDrop($event)&amp;quot;');
        $response->assertSee(':class=&amp;quot;{ \'dragover\': dragover }&amp;quot;');
        
        // Check for file upload area styling
        $response->assertSee('class=&amp;quot;file-upload-area&amp;quot;');
        $response->assertSee('Click to upload');
        $response->assertSee('drag and drop');
    }

    /** @test */
    public function it_includes_focus_management()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for focus management
        $response->assertSee('tabindex=&amp;quot;0&amp;quot;');
        $response->assertSee('@keydown.enter');
        $response->assertSee('focus()');
        
        // Check for focus indicators
        $response->assertSee('*:focus {');
        $response->assertSee('outline: 2px solid');
    }

    /** @test */
    public function it_includes_wcag_aa_compliance_features()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for WCAG AA compliance features
        $response->assertSee('@media (prefers-contrast: high)');
        $response->assertSee('@media (prefers-reduced-motion: reduce)');
        
        // Check for proper color contrast variables
        $response->assertSee('--primary-color: #007cba');
        $response->assertSee('--text-color: #333');
        $response->assertSee('--background: #f5f5f5');
        
        // Check for semantic HTML structure
        $response->assertSee('&amp;lt;h1&amp;gt;');
        $response->assertSee('&amp;lt;h3&amp;gt;');
        $response->assertSee('&amp;lt;label&amp;gt;');
        $response->assertSee('&amp;lt;button&amp;gt;');
    }

    /** @test */
    public function it_includes_csp_nonce_support()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for CSP nonce support
        $response->assertSee('nonce=&amp;quot;{{ app(\'csp_nonce\') }}&amp;quot;');
    }

    /** @test */
    public function it_includes_word_count_display()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for word count functionality
        $response->assertSee('wordCount');
        $response->assertSee('word-count-badge');
        $response->assertSee('x-text=&amp;quot;`${wordCount} words`&amp;quot;');
    }

    /** @test */
    public function it_includes_error_handling_ui()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check for error handling UI
        $response->assertSee('x-show=&amp;quot;error&amp;quot;');
        $response->assertSee('role=&amp;quot;alert&amp;quot;');
        $response->assertSee('aria-live=&amp;quot;assertive&amp;quot;');
        $response->assertSee('x-text=&amp;quot;error&amp;quot;');
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