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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
