<?php

namespace Tests\Unit;

use App\Services\CoverLetterGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;

class CoverLetterGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private CoverLetterGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CoverLetterGenerator();
    }

    /**
     * @test
     */
    public function it_validates_facts_schema_correctly()
    {
        // Test valid facts schema
        $validFacts = [
            'name' => 'John Doe',
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'experience' => [
                [
                    'company' => 'Tech Corp',
                    'role' => 'Senior Developer',
                    'duration' => '2020-2023'
                ]
            ],
            'education' => [
                [
                    'institution' => 'University of Tech',
                    'degree' => 'Computer Science',
                    'year' => '2018'
                ]
            ],
            'certifications' => ['AWS Certified'],
            'years_of_experience' => 5
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('validateFactsSchema');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($this->generator, $validFacts);
        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * @test
     */
    public function it_rejects_invalid_facts_schema()
    {
        // Test invalid facts schema (missing required fields)
        $invalidFacts = [
            'name' => 'John Doe',
            'skills' => ['PHP'],
            // Missing required fields: experience, education, certifications, years_of_experience
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('validateFactsSchema');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid facts schema');
        $method->invoke($this->generator, $invalidFacts);
    }

    /**
     * @test
     */
    public function it_rejects_banned_phrases()
    {
        // Test facts with banned phrases
        $factsWithBannedPhrase = [
            'name' => 'John Doe',
            'skills' => ['PHP'],
            'experience' => [],
            'education' => [],
            'certifications' => [],
            'years_of_experience' => 3,
            'note' => 'Experience not specified' // Banned phrase
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('checkForBannedPhrases');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Banned phrase detected: not specified');
        $method->invoke($this->generator, $factsWithBannedPhrase);
    }

    /**
     * @test
     */
    public function it_accepts_facts_without_banned_phrases()
    {
        // Test facts without banned phrases
        $validFacts = [
            'name' => 'John Doe',
            'skills' => ['PHP', 'Laravel'],
            'experience' => [
                [
                    'company' => 'Tech Corp',
                    'role' => 'Senior Developer',
                    'duration' => '2020-2023'
                ]
            ],
            'education' => [
                [
                    'institution' => 'University of Tech',
                    'degree' => 'Computer Science',
                    'year' => '2018'
                ]
            ],
            'certifications' => ['AWS Certified'],
            'years_of_experience' => 5
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('checkForBannedPhrases');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($this->generator, $validFacts);
        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * @test
     */
    public function it_parses_json_response_correctly()
    {
        $validJson = json_encode([
            'name' => 'John Doe',
            'skills' => ['PHP'],
            'experience' => [],
            'education' => [],
            'certifications' => [],
            'years_of_experience' => 3
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('parseAndValidateResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, $validJson);
        
        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals(3, $result['years_of_experience']);
    }

    /**
     * @test
     */
    public function it_handles_markdown_formatted_responses()
    {
        $markdownJson = '```json' . "\n" . json_encode([
            'name' => 'John Doe',
            'skills' => ['PHP'],
            'experience' => [],
            'education' => [],
            'certifications' => [],
            'years_of_experience' => 3
        ]) . "\n```";

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('parseAndValidateResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, $markdownJson);
        
        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
    }

    /**
     * @test
     */
    public function it_rejects_malformed_json()
    {
        $malformedJson = '{"name": "John Doe", "skills": ["PHP", "Laravel"'; // Missing closing bracket

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('parseAndValidateResponse');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON response');
        $method->invoke($this->generator, $malformedJson);
    }

    /**
     * @test
     */
    public function it_validates_years_of_experience_range()
    {
        $factsWithInvalidYears = [
            'name' => 'John Doe',
            'skills' => ['PHP'],
            'experience' => [],
            'education' => [],
            'certifications' => [],
            'years_of_experience' => 100 // Invalid range
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('validateFactsSchema');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid facts schema');
        $method->invoke($this->generator, $factsWithInvalidYears);
    }

    /**
     * @test
     */
    public function it_builds_extraction_prompt_correctly()
    {
        $cvText = "John Doe is a developer with PHP skills.";
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildExtractionPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->generator, $cvText, 0);
        
        $this->assertStringContainsString('Extract the following information', $prompt);
        $this->assertStringContainsString('name', $prompt);
        $this->assertStringContainsString('skills', $prompt);
        $this->assertStringContainsString('experience', $prompt);
        $this->assertStringContainsString('education', $prompt);
        $this->assertStringContainsString('certifications', $prompt);
        $this->assertStringContainsString('years_of_experience', $prompt);
        $this->assertStringContainsString($cvText, $prompt);
    }

    /**
     * @test
     */
    public function it_builds_stricter_prompt_on_retry()
    {
        $cvText = "John Doe is a developer with PHP skills.";
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildExtractionPrompt');
        $method->setAccessible(true);

        $retryPrompt = $method->invoke($this->generator, $cvText, 1);
        
        $this->assertStringContainsString('IMPORTANT: This is a retry attempt', $retryPrompt);
        $this->assertStringContainsString('perfectly valid', $retryPrompt);
    }

    /**
     * @test
     */
    public function it_strips_html_tags_from_job_description()
    {
        $jobDescription = '<h1>Software Engineer</h1><p>We are looking for a <strong>talented</strong> developer with <em>PHP</em> skills.</p><ul><li>Laravel experience</li></ul>';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertEquals('Software EngineerWe are looking for a talented developer with PHP skills.Laravel experience', $result);
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringNotContainsString('<em>', $result);
        $this->assertStringNotContainsString('<ul>', $result);
        $this->assertStringNotContainsString('<li>', $result);
    }

    /**
     * @test
     */
    public function it_removes_utm_tracking_parameters()
    {
        $jobDescription = 'Software Engineer position https://company.com/jobs?utm_source=linkedin&utm_medium=social&utm_campaign=recruitment&other=param';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertStringNotContainsString('utm_source', $result);
        $this->assertStringNotContainsString('utm_medium', $result);
        $this->assertStringNotContainsString('utm_campaign', $result);
        $this->assertStringContainsString('other=param', $result); // Other params should remain
    }

    /**
     * @test
     */
    public function it_removes_other_tracking_parameters()
    {
        $jobDescription = 'Software Engineer position https://company.com/jobs?fbclid=123&gclid=456&msclkid=789&normal=param';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertStringNotContainsString('fbclid=123', $result);
        $this->assertStringNotContainsString('gclid=456', $result);
        $this->assertStringNotContainsString('msclkid=789', $result);
        $this->assertStringContainsString('normal=param', $result); // Normal params should remain
    }

    /**
     * @test
     */
    public function it_normalizes_whitespace()
    {
        $jobDescription = "Software   Engineer\n\n\nPosition\r\n\r\nWith\t\t\tMultiple    Spaces";
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertEquals('Software Engineer Position With Multiple Spaces', $result);
        $this->assertStringNotContainsString('   ', $result); // No multiple spaces
        $this->assertStringNotContainsString("\n", $result); // No newlines
        $this->assertStringNotContainsString("\r", $result); // No carriage returns
        $this->assertStringNotContainsString("\t", $result); // No tabs
    }

    /**
     * @test
     */
    public function it_caps_length_to_10k_chars()
    {
        $longJobDescription = str_repeat('Software Engineer position. ', 1000); // ~30k chars
        
        $result = $this->generator->sanitizeJobDescription($longJobDescription);
        
        $this->assertLessThanOrEqual(10000, strlen($result));
        $this->assertGreaterThan(9000, strlen($result)); // Should be close to 10k
    }

    /**
     * @test
     */
    public function it_does_not_cut_off_words_when_capping_length()
    {
        $longJobDescription = str_repeat('Software Engineer position. ', 1000); // ~30k chars
        
        $result = $this->generator->sanitizeJobDescription($longJobDescription);
        
        // Should not end with a partial word
        $this->assertStringEndsWith('.', $result);
        // Should be close to 10k characters
        $this->assertGreaterThan(9000, strlen($result));
    }

    /**
     * @test
     */
    public function it_handles_empty_job_description()
    {
        $result = $this->generator->sanitizeJobDescription('');
        
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function it_handles_job_description_with_only_html()
    {
        $jobDescription = '<div><span></span></div>';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function it_handles_job_description_with_mixed_content()
    {
        $jobDescription = '<h1>Software Engineer</h1>We need someone with <strong>PHP</strong> skills. https://company.com?utm_source=linkedin&normal=param';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertEquals('Software EngineerWe need someone with PHP skills. https://company.com&normal=param', $result);
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringNotContainsString('utm_source', $result);
        $this->assertStringContainsString('normal=param', $result);
    }

    /**
     * @test
     */
    public function it_trims_whitespace()
    {
        $jobDescription = '   Software Engineer position   ';
        
        $result = $this->generator->sanitizeJobDescription($jobDescription);
        
        $this->assertEquals('Software Engineer position', $result);
        $this->assertFalse(str_starts_with($result, ' '));
        $this->assertFalse(str_ends_with($result, ' '));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
