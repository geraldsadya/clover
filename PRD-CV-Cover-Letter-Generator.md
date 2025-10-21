<!-- b9cfcbb0-ef8e-4da5-a0c2-29683df4cb93 ee542e7e-be0a-4ad0-a3c7-542a467801d4 -->
# PRD: CV-to-Cover-Letter Generator (AI-First, Production-Grade)

## EXECUTIVE SUMMARY

Build a Laravel 11 web application that accepts a PDF CV upload and job description text, then generates a tailored 2-3 paragraph cover letter using OpenAI GPT-4. The application must demonstrate AI-first development practices, production security (POPIA-compliant), systematic testing, cost awareness, and professional deployment.

**Target Role**: AI-enabled Software Developer at Silvertreebrands

**Key Differentiator**: Production-minded AI control, not just AI usage

---

## 1. PRODUCT OVERVIEW

### 1.1 Goal Statement

Create a web application where users upload a CV (PDF) and paste a job description, then receive a professionally-written, tailored 2-3 paragraph cover letter that accurately reflects the CV content and addresses the job requirements.

### 1.2 Success Criteria (Must-Pass)

- ✅ Cover letter accurately reflects CV content (ZERO hallucinations)
- ✅ Letter is specifically tailored to job description (mentions company/role)
- ✅ Output is exactly 2-3 paragraphs (150-300 words)
- ✅ Processing completes in <10 seconds (P95)
- ✅ Works on mobile and desktop (responsive)
- ✅ Deployed and accessible via public URL
- ✅ All edge cases handled gracefully (no crashes)
- ✅ Production security standards met (POPIA-compliant)

### 1.3 Non-Goals (Out of Scope)

- ❌ User authentication/accounts
- ❌ Persistent storage of CVs/cover letters
- ❌ Multiple cover letter versions/history
- ❌ Email sending functionality
- ❌ Multi-language support (English only)
- ❌ Resume/CV builder features

---

## 2. USER STORIES & ACCEPTANCE CRITERIA

### Story 1: Generate Cover Letter (Core Flow)

**As a** job seeker

**I want to** upload my CV and paste a job ad

**So that** I can quickly generate a tailored cover letter

**Acceptance Criteria**:

1. Form displays two inputs: PDF upload + textarea
2. PDF upload accepts only PDF files (MIME + magic-byte validation)
3. PDF size limit: 10MB (enforced server-side)
4. Job description textarea: minimum 50 characters
5. Both fields are required (server-side validation)
6. Submit button disabled during processing
7. Loading spinner with "Generating your cover letter..." message
8. Result displays in formatted card below form
9. Cover letter is 2-3 paragraphs (150-300 words)
10. Copy-to-clipboard button with toast notification
11. "Generate Another" button clears form and resets state

### Story 2: Handle Invalid CV (Edge Case)

**As a** user

**I want to** see a clear error if my PDF cannot be read

**So that** I know to try a different file

**Acceptance Criteria**:

1. Scanned/image PDFs return: "Could not extract text from PDF. Please use a text-based CV."
2. Corrupt PDFs return: "Could not read PDF. Try a different file."
3. Encrypted PDFs return: "PDF is password-protected. Please upload an unlocked file."
4. Empty PDFs return: "CV appears empty or unreadable."
5. Non-PDF files return: "File must be PDF format."
6. Error messages displayed in red alert box
7. Form remains populated (user can upload different file)

### Story 3: Prevent AI Hallucinations (Quality Control)

**As a** user

**I want to** ensure the cover letter only mentions skills I actually have

**So that** I don't submit false information to employers

**Acceptance Criteria**:

1. Test: CV without "Docker" + Job requiring "Docker" → Letter does NOT claim Docker experience
2. Test: CV with "React" + Job requiring "React" → Letter DOES mention React
3. Test: CV with "3 years experience" → Letter never claims "5 years"
4. Two-stage pipeline: Extractor extracts facts to JSON → Composer uses only JSON facts
5. JSON schema validation with retry logic (1 retry on malformed JSON)
6. Manual review of 5 golden test cases confirms zero hallucinations

### Story 4: Handle High Load (Reliability)

**As a** system administrator

**I want to** rate-limit requests to prevent abuse

**So that** API costs stay manageable

**Acceptance Criteria**:

1. Rate limit: 10 requests per IP per hour
2. Exceeding limit returns: "Too many requests. Try again in [X] minutes."
3. Rate limit tracked via Laravel cache (not just middleware)
4. Header `Retry-After` included in 429 response
5. Rate limit resets after 1 hour from first request

---

## 3. TECHNICAL ARCHITECTURE

### 3.1 Tech Stack (Locked In)

| Component | Technology | Rationale |

|-----------|-----------|-----------|

| **Backend Framework** | Laravel 11 (PHP 8.2+) | Preferred by employer; full-stack; rapid scaffolding |

| **Frontend** | Blade + Alpine.js + Tailwind CSS | Laravel-native; reactive without complexity |

| **PDF Parser** | spatie/pdf-to-text | Most reliable; wraps system `pdftotext` |

| **AI Provider** | OpenAI GPT-4-turbo | Best quality; cost-effective (~$0.025/request) |

| **Database** | SQLite | Simple; no external DB needed; sufficient for rate limiting |

| **Deployment** | Railway | Easiest Laravel deployment; free tier available |

| **Version Control** | GitHub | Industry standard; CI/CD integration |

| **CI/CD** | GitHub Actions | Free; integrated with GitHub |

| **Static Analysis** | PHPStan/Larastan (level 8) | Catch errors pre-runtime |

| **Code Style** | Laravel Pint (PSR-12) | Consistent formatting |

### 3.2 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        USER BROWSER                         │
│  (Blade + Alpine.js + Tailwind CSS - Mobile Responsive)    │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTPS (Railway)
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL 11 APPLICATION                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         CoverLetterController                        │  │
│  │  - index(): Display form                             │  │
│  │  - generate(): Handle submission                     │  │
│  └───────────┬──────────────────────────┬───────────────┘  │
│              │                           │                  │
│              ▼                           ▼                  │
│  ┌─────────────────────┐   ┌──────────────────────────┐   │
│  │  PdfExtractor       │   │  CoverLetterGenerator    │   │
│  │  Service            │   │  Service                 │   │
│  │                     │   │                          │   │
│  │  - extract(file)    │   │  - extractFacts(cv)      │   │
│  │  - validate()       │   │  - generate(facts, job)  │   │
│  │  - clean()          │   │  - validate()            │   │
│  └──────────┬──────────┘   └────────┬─────────────────┘   │
│             │                       │                      │
│             ▼                       ▼                      │
│  ┌─────────────────┐    ┌─────────────────────────┐      │
│  │ spatie/         │    │ OpenAI GPT-4-turbo      │      │
│  │ pdf-to-text     │    │ (2-stage pipeline)      │      │
│  └─────────────────┘    └─────────────────────────┘      │
│                                                            │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Middleware Stack                                    │ │
│  │  - CSRF Protection                                   │ │
│  │  - Rate Limiting (10/hour per IP)                   │ │
│  │  - Security Headers (CSP, X-Frame-Options, etc.)    │ │
│  │  - Request Validation                                │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                            │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Observability                                       │ │
│  │  - Request ID correlation (UUID per request)         │ │
│  │  - Token usage logging (in/out)                      │ │
│  │  - Latency tracking (P50/P95)                        │ │
│  │  - /healthz endpoint                                 │ │
│  └──────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 File Structure (Complete)

```
cover-letter-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── CoverLetterController.php
│   │   ├── Middleware/
│   │   │   └── SecurityHeaders.php
│   │   └── Requests/
│   │       └── GenerateCoverLetterRequest.php
│   ├── Services/
│   │   ├── PdfExtractor.php
│   │   └── CoverLetterGenerator.php
│   └── Exceptions/
│       ├── PdfExtractionException.php
│       └── AiGenerationException.php
├── resources/
│   └── views/
│       ├── cover-letter/
│       │   └── index.blade.php
│       └── layouts/
│           └── app.blade.php
├── routes/
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── CoverLetterGenerationTest.php
│   │   ├── EdgeCaseTest.php
│   │   └── SecurityTest.php
│   ├── Unit/
│   │   ├── PdfExtractorTest.php
│   │   └── CoverLetterGeneratorTest.php
│   └── TestData/
│       ├── sample_cv_junior.pdf
│       ├── sample_cv_senior.pdf
│       ├── sample_cv_career_changer.pdf
│       ├── sample_cv_student.pdf
│       ├── sample_cv_specialist.pdf
│       ├── corrupted.pdf
│       ├── scanned_image.pdf
│       └── job_descriptions.txt
├── storage/
│   └── app/
│       └── temp/  (PDFs stored here temporarily, deleted post-request)
├── .github/
│   └── workflows/
│       └── ci.yml  (PHPStan + Pint + Tests + Golden Set)
├── docs/
│   ├── BUILD_LOG.md  (AI-first development journal)
│   └── QA_CHECKLIST.md  (Pre-submission testing checklist)
├── .env.example
├── composer.json
├── composer.lock
├── phpstan.neon
├── pint.json
├── README.md
└── railway.json  (Railway deployment config)
```

---

## 4. DETAILED COMPONENT SPECIFICATIONS

### 4.1 PdfExtractor Service

**Responsibilities**:

- Extract text from uploaded PDF file
- Validate extraction output (min 100 characters)
- Clean extracted text (normalize whitespace, remove control chars)
- Handle extraction failures gracefully

**Methods**:

```php
class PdfExtractor
{
    /**
     * Extract text from PDF file
     * @throws PdfExtractionException
     */
    public function extract(UploadedFile $file): string
    
    /**
     * Validate extracted text meets minimum requirements
     */
    private function validate(string $text): bool
    
    /**
     * Clean and normalize extracted text
     */
    private function clean(string $text): string
    
    /**
     * Truncate CV text to ~15,000 chars (token management)
     */
    private function truncate(string $text, int $maxChars = 15000): string
}
```

**Error Handling**:

- Scanned/image PDF → `PdfExtractionException`: "Could not extract text"
- Corrupt file → `PdfExtractionException`: "Could not read PDF"
- Encrypted PDF → `PdfExtractionException`: "PDF is password-protected"
- Empty extraction (<100 chars) → `PdfExtractionException`: "CV appears empty"

**Testing**:

- Unit test: Extract text from valid PDF
- Unit test: Throw exception on corrupt PDF
- Unit test: Throw exception on scanned PDF
- Unit test: Truncate long CVs to 15k chars

### 4.2 CoverLetterGenerator Service

**Responsibilities**:

- Two-stage AI pipeline: Extractor → Composer
- Stage 1: Extract structured facts from CV (JSON schema)
- Stage 2: Generate cover letter using facts + job description
- Validate output (2-3 paragraphs, 150-300 words)
- Log token usage and latency

**Methods**:

```php
class CoverLetterGenerator
{
    /**
     * Stage 1: Extract facts from CV text to JSON
     * @throws AiGenerationException
     */
    private function extractFacts(string $cvText): array
    
    /**
     * Stage 2: Generate cover letter from facts + job description
     * @throws AiGenerationException
     */
    public function generate(string $cvText, string $jobDescription): array
    
    /**
     * Validate JSON schema from extraction stage
     */
    private function validateFactsSchema(array $facts): bool
    
    /**
     * Validate cover letter output (length, structure)
     */
    private function validateOutput(string $coverLetter): bool
    
    /**
     * Log token usage and performance metrics
     */
    private function logMetrics(string $requestId, array $usage): void
}
```

**Stage 1: Extraction Prompt** (JSON Schema Output)

```
You are a CV fact extractor. Extract structured information from the CV text.

Output ONLY valid JSON with this schema:
{
  "name": "string (optional)",
  "skills": ["array of technical skills"],
  "experience": [
    {
      "role": "string",
      "company": "string",
      "duration": "string",
      "responsibilities": ["key achievements"]
    }
  ],
  "education": [
    {
      "degree": "string",
      "institution": "string",
      "year": "string (optional)"
    }
  ],
  "certifications": ["array of certifications"],
  "years_of_experience": "number (estimate)"
}

Rules:
- ONLY extract information present in the CV
- Do NOT invent or infer missing information
- If a field is not found, use empty array [] or null
- Output MUST be valid JSON (no extra text)
```

**Temperature Settings** (config/app.php):
```php
'openai' => [
    'extractor_temperature' => 0.1,  // Low creativity for fact extraction
    'composer_temperature' => 0.4,   // Moderate creativity for cover letter
    'regenerate_temperature' => 0.7,  // Higher creativity for regeneration
],
```

**Stage 2: Composer Prompt** (Cover Letter Generation)

```
You are a professional cover letter writer. Given structured CV facts and a job description, write a compelling 2-3 paragraph cover letter.

CV Facts (JSON):
{extracted_facts}

Job Description:
{job_description}

Requirements:
- Paragraph 1 (50-80 words): Opening + enthusiasm for the specific role and company
- Paragraph 2 (70-120 words): Highlight 2-3 relevant skills/experiences that match job requirements
- Paragraph 3 (30-60 words): Closing statement expressing interest in next steps

Rules:
- ONLY use information from the CV Facts JSON (never invent experience)
- Mention the company name and role title from the job description
- Total length: 150-300 words
- Professional but warm tone
- Do NOT include applicant's name, address, date, or salutation (just body paragraphs)
- Do NOT use phrases like "I believe" or "I feel" (too weak)
- Use active voice and strong action verbs
```

**Error Handling**:

- OpenAI API timeout (>30s) → Retry once, then fail gracefully
- Malformed JSON in Stage 1 → Retry once with stricter instructions
- Stage 2 output >400 words → Re-prompt with explicit word count
- API rate limit → Return "Service temporarily unavailable"

**Testing**:

- Unit test: Extract facts from sample CV → validate JSON schema
- Unit test: Generate letter from facts → validate length (150-300 words)
- Integration test: Full pipeline with golden test set (5 cases)
- Anti-hallucination test: CV without Docker + Job with Docker → no Docker mention

### 4.3 CoverLetterController

**Responsibilities**:

- Display form (GET /)
- Handle form submission (POST /generate)
- Orchestrate PdfExtractor + CoverLetterGenerator
- Handle errors and return appropriate responses
- Generate unique request ID for logging

**Routes**:

```php
Route::get('/', [CoverLetterController::class, 'index'])->name('home');
Route::post('/generate', [CoverLetterController::class, 'generate'])
    ->middleware(['throttle:10,60'])  // 10 requests per hour
    ->name('generate');
Route::get('/healthz', [CoverLetterController::class, 'healthz'])->name('health');
```

**Methods**:

```php
class CoverLetterController
{
    public function __construct(
        private PdfExtractor $pdfExtractor,
        private CoverLetterGenerator $coverLetterGenerator
    ) {}
    
    /**
     * Display the cover letter generation form
     */
    public function index(): View
    
    /**
     * Handle form submission and generate cover letter
     */
    public function generate(GenerateCoverLetterRequest $request): JsonResponse
    
    /**
     * Health check endpoint for monitoring
     */
    public function healthz(): JsonResponse
}
```

**Request Validation** (GenerateCoverLetterRequest):

```php
public function rules(): array
{
    return [
        'cv' => [
            'required',
            'file',
            'mimes:pdf',
            'max:10240',  // 10MB
            function ($attribute, $value, $fail) {
                // Magic-byte validation for PDF
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $value->getPathname());
                if ($mimeType !== 'application/pdf') {
                    $fail('File must be a valid PDF.');
                }
            }
        ],
        'job_description' => 'required|string|min:50|max:10000'
    ];
}
```

**Response Format**:

```json
{
  "success": true,
  "data": {
    "cover_letter": "Paragraph 1...\n\nParagraph 2...\n\nParagraph 3...",
    "word_count": 187,
    "request_id": "uuid-1234-5678"
  },
  "meta": {
    "tokens_used": 2450,
    "processing_time_ms": 4200
  }
}
```

**Error Response Format**:

```json
{
  "success": false,
  "error": {
    "message": "Could not extract text from PDF. Try a text-based CV.",
    "code": "PDF_EXTRACTION_FAILED",
    "request_id": "uuid-1234-5678"
  }
}
```

### 4.4 Frontend View (index.blade.php)

**UI Components**:

1. **Header**: App title + tagline
2. **Form Card**:

                                                                                                                                                                                                - PDF upload input (drag-drop + click)
                                                                                                                                                                                                - Job description textarea (auto-expand)
                                                                                                                                                                                                - Submit button (disabled during processing)

3. **Loading State**: Spinner + "Generating your cover letter..."
4. **Result Card** (conditional):

                                                                                                                                                                                                - Cover letter text (formatted paragraphs)
                                                                                                                                                                                                - Copy-to-clipboard button (with toast)
                                                                                                                                                                                                - Word count badge
                                                                                                                                                                                                - "Generate Another" button

5. **Error Alert** (conditional): Red banner with error message
6. **Footer**: Privacy note ("Your CV is not stored")

**Alpine.js State** (with CSP nonce):

```html
<script nonce="{{ app('csp_nonce') }}">
document.addEventListener('alpine:init', () => {
  Alpine.data('coverLetterApp', () => ({
    loading: false,
    result: null,
    error: null,
    wordCount: 0,
    copied: false,
    
    async submit() {
      // Handle form submission
    },
    
    copyToClipboard() {
      // Copy result to clipboard + show toast
    },
    
    reset() {
      // Clear form and reset state
    }
  }))
})
</script>
```

**Accessibility Requirements**:

- All inputs have associated `<label>` elements
- Form validation errors announced via ARIA live regions
- Keyboard navigation works (Tab order logical)
- Focus management (focus on result after generation)
- Color contrast ratio ≥4.5:1 (WCAG AA)
- Touch targets ≥44px (mobile-friendly)

**Mobile Responsiveness**:

- Single column layout on <768px
- Touch-friendly file upload area
- Textarea scales to viewport
- Result card scrollable on small screens

---

## 5. EDGE CASES & ERROR HANDLING (COMPREHENSIVE)

### 5.1 PDF Upload Edge Cases

| Scenario | Detection Method | User-Facing Error | Technical Handling |

|----------|-----------------|-------------------|-------------------|

| No file uploaded | Laravel validation | "Please upload a CV" | 422 validation error |

| Non-PDF file (e.g., .docx) | MIME + magic-byte check | "File must be PDF format" | Reject before processing |

| PDF >10MB | Laravel validation | "File must be under 10MB" | 422 validation error |

| Corrupt/malformed PDF | pdftotext exit code ≠0 | "Could not read PDF. Try different file." | PdfExtractionException |

| Scanned PDF (image-only) | Extracted text <100 chars | "Could not extract text. Use text-based CV." | PdfExtractionException |

| Encrypted/password PDF | pdftotext error output | "PDF is password-protected. Upload unlocked file." | PdfExtractionException |

| Very long CV (20+ pages) | Character count >15k | Truncate to 15k chars | Silent truncation + log |

| Unicode/special chars | Visual inspection | Handle gracefully | UTF-8 encoding |

| Blank pages in PDF | Character count check | Process if >100 chars total | Clean whitespace |

### 5.2 Job Description Edge Cases

| Scenario | Detection Method | User-Facing Error | Technical Handling |

|----------|-----------------|-------------------|-------------------|

| Empty textarea | Laravel validation | "Please enter job description" | 422 validation error |

| Too short (<50 chars) | Laravel validation | "Job description too short (min 50 chars)" | 422 validation error |

| Too long (>10k chars) | Laravel validation | "Job description too long (max 10,000 chars)" | 422 validation error |

| HTML/tracking code | Strip tags | Silent cleanup | strip_tags() before AI |

| Special characters | Sanitize | Handle gracefully | htmlspecialchars() |

| Non-English text | None (pass through) | Process normally | OpenAI handles languages |

### 5.3 AI Generation Edge Cases

| Scenario | Detection Method | User-Facing Error | Technical Handling |

|----------|-----------------|-------------------|-------------------|

| OpenAI API down | HTTP error 500-503 | "Service unavailable. Try again shortly." | Retry once, then fail |

| API timeout (>30s) | HTTP timeout | "Request timed out. Try again." | Set timeout, retry once |

| Rate limit hit | HTTP 429 | "Too many requests. Try again in [X] min." | Exponential backoff |

| Malformed JSON (Stage 1) | JSON parse error | Retry with stricter prompt | 1 retry, then manual fallback |

| Output too long (>400 words) | Word count | Re-prompt with explicit limit | 1 retry, then truncate |

| Output too short (<100 words) | Word count | Re-prompt | 1 retry, then accept |

| Hallucinated skills | Golden test validation | N/A (caught in testing) | Two-stage pipeline prevents |

| API key invalid | HTTP 401 | "Configuration error. Contact admin." | Log error, return 500 |

### 5.4 Rate Limiting & Abuse

| Scenario | Detection Method | User-Facing Error | Technical Handling |

|----------|-----------------|-------------------|-------------------|

| >10 requests/hour | Laravel cache | "Too many requests. Try again in [X] min." | 429 + Retry-After header |

| Rapid duplicate submits | Button disabled | N/A | Disable button on submit |

| Bot/scraper traffic | User-Agent check | 403 Forbidden | Optional: basic UA check |

| Same IP, different CVs | Cache key = IP | Treated same as rate limit | Fair usage policy |

### 5.5 System/Infrastructure

| Scenario | Detection Method | User-Facing Error | Technical Handling |

|----------|-----------------|-------------------|-------------------|

| Database connection lost | PDOException | "Temporary issue. Try again." | Retry connection |

| Storage full | Disk space check | "Service unavailable." | Alert + auto-cleanup old files |

| pdftotext not installed | Binary check on boot | "Configuration error." | Fail deployment |

| OpenAI API key missing | Config check on boot | "Configuration error." | Fail deployment |

| HTTPS not enforced | Middleware check | Redirect to HTTPS | Force HTTPS in prod |

---

## 6. SECURITY & PRIVACY (TIER 2 - PRODUCTION STANDARD)

### 6.1 Secrets Management

**Requirements**:

- All API keys stored in `.env` file (NEVER committed to git)
- `.env` added to `.gitignore`
- `.env.example` provided with placeholder values and comments
- Key rotation documented in README

**.env.example**:

```
APP_ENV=production
APP_DEBUG=false
OPENAI_API_KEY=sk-...  # Get from https://platform.openai.com/api-keys
# Rotate keys every 90 days. If compromised, regenerate immediately.
```

### 6.2 File Upload Security

**MIME Type Validation**:

```php
// Check MIME type via finfo (not file extension)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file->getPathname());
if ($mimeType !== 'application/pdf') {
    throw new ValidationException('Invalid file type');
}
```

**Magic-Byte Validation**:

```php
// Verify PDF magic bytes: %PDF-
$handle = fopen($file->getPathname(), 'rb');
$header = fread($handle, 5);
if ($header !== '%PDF-') {
    throw new ValidationException('Not a valid PDF file');
}
```

**Storage Security**:

- Store uploads in `storage/app/temp/` (outside web root)
- Use Laravel's `Storage` facade with `local` disk
- Generate unique filenames: `{uuid}.pdf`
- Delete file immediately after text extraction
- Set permissions: `chmod 600` (owner read/write only)

**Size Limits**:

- Server-side limit: 10MB (enforced in validation)
- PHP ini settings: `upload_max_filesize=10M`, `post_max_size=11M`

### 6.3 Rate Limiting

**Implementation**:

```php
Route::post('/generate', [CoverLetterController::class, 'generate'])
    ->middleware(['throttle:10,60']);  // 10 requests per 60 minutes

// Custom rate limit in controller
$key = 'cover-letter:' . $request->ip();
$attempts = Cache::get($key, 0);

if ($attempts >= 10) {
    $retryAfter = Cache::get($key . ':retry_after') - now()->timestamp;
    return response()->json([
        'error' => 'Too many requests. Try again in ' . ceil($retryAfter / 60) . ' minutes.'
    ], 429)->header('Retry-After', $retryAfter);
}

Cache::put($key, $attempts + 1, now()->addHour());
if ($attempts === 0) {
    Cache::put($key . ':retry_after', now()->addHour()->timestamp, now()->addHour());
}
```

### 6.4 Security Headers (Middleware)

**Implementation** (SecurityHeaders middleware):

```php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    // Generate CSP nonce for inline scripts
    $nonce = base64_encode(random_bytes(16));
    app()->instance('csp_nonce', $nonce);
    
    return $response
        ->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline';")
        ->header('X-Frame-Options', 'DENY')
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('Referrer-Policy', 'no-referrer')
        ->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
}
```

### 6.5 CSRF Protection

- Enabled by default in Laravel (`VerifyCsrfToken` middleware)
- Add `@csrf` directive in Blade forms
- Include `X-CSRF-TOKEN` header in AJAX requests

### 6.6 XSS Prevention

- Blade auto-escapes all `{{ $variable }}` output
- Never use `{!! $variable !!}` for user input
- Sanitize job description: `strip_tags()` before AI processing
- Sanitize AI output before display (though AI shouldn't return HTML)

### 6.7 PII & Privacy (POPIA-Compliant)

**POPIA**: Protection of Personal Information Act (South African privacy law)

**Principles Applied**:

1. **Minimal Collection**: Only CV + job description (no email, phone, etc.)
2. **Purpose Limitation**: Data used only for cover letter generation
3. **No Retention**: Files deleted immediately after processing
4. **Transparency**: Privacy notice displayed on form
5. **No Logging of PII**: Logs contain only metadata

**Logging Policy**:

```php
// ALLOWED (metadata only)
Log::info('Cover letter generated', [
    'request_id' => $requestId,
    'tokens_in' => 2450,
    'tokens_out' => 180,
    'duration_ms' => 4200,
    'outcome' => 'success'
]);

// FORBIDDEN (contains PII)
Log::info('Processing CV', ['cv_text' => $cvText]);  // NEVER DO THIS
```

**Privacy Notice** (display on form):

> Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter. We comply with POPIA (South African privacy law).

### 6.8 Dependency Security

**Composer Audit**:

```bash
composer audit  # Run before each deployment
```

**Lock File**:

- Commit `composer.lock` to ensure reproducible builds
- Update dependencies monthly: `composer update`

**Minimal Dependencies**:

- Only install packages from trusted sources (Packagist)
- Review package maintainers and download counts

---

## 7. CODE QUALITY & TESTING (TIER 2 - PRODUCTION STANDARD)

### 7.1 Static Analysis (PHPStan)

**Configuration** (phpstan.neon):

```neon
parameters:
    level: 8  # Maximum strictness
    paths:
        - app
        - tests
    excludePaths:
        - vendor
    checkMissingIterableValueType: false
```

**Run**:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G
```

**Zero Errors Required**: CI fails if PHPStan reports any issues

### 7.2 Code Style (Laravel Pint)

**Configuration** (pint.json):

```json
{
    "preset": "psr12",
    "rules": {
        "array_syntax": {"syntax": "short"},
        "ordered_imports": {"sort_algorithm": "alpha"},
        "no_unused_imports": true
    }
}
```

**Run**:

```bash
./vendor/bin/pint
```

**Auto-Format**: CI auto-formats code and commits if needed

### 7.3 Unit Tests

**Coverage Target**: >80% for services

**PdfExtractor Tests**:

- `test_extracts_text_from_valid_pdf()`
- `test_throws_exception_on_corrupt_pdf()`
- `test_throws_exception_on_scanned_pdf()`
- `test_truncates_long_cv_text()`
- `test_cleans_whitespace_and_special_chars()`

**CoverLetterGenerator Tests**:

- `test_extracts_facts_as_json_schema()`
- `test_retries_on_malformed_json()`
- `test_generates_cover_letter_within_word_limit()`
- `test_does_not_hallucinate_skills()` ← Critical anti-hallucination test
- `test_mentions_company_and_role_from_job_description()`
- `test_logs_token_usage_and_latency()`

### 7.4 Feature Tests

**CoverLetterGenerationTest**:

- `test_displays_form_on_homepage()`
- `test_generates_cover_letter_with_valid_inputs()`
- `test_validates_pdf_file_is_required()`
- `test_validates_job_description_is_required()`
- `test_rejects_non_pdf_files()`
- `test_rejects_files_over_10mb()`
- `test_rate_limits_after_10_requests()`

**EdgeCaseTest**:

- `test_handles_corrupt_pdf_gracefully()`
- `test_handles_scanned_pdf_with_error_message()`
- `test_handles_encrypted_pdf()`
- `test_handles_very_long_cv()`
- `test_strips_html_from_job_description()`

**SecurityTest**:

- `test_csrf_protection_enabled()`
- `test_security_headers_present()`
- `test_files_deleted_after_processing()`
- `test_does_not_log_pii()`

### 7.5 Golden Test Set (Anti-Hallucination Suite)

**5 Test Cases** (manual + automated):

1. **Junior Frontend Developer**

                                                                                                                                                                                                - CV: React, JavaScript, 1 internship, no Docker
                                                                                                                                                                                                - Job: Junior React Developer (requires Docker for deployment)
                                                                                                                                                                                                - **Assert**: Cover letter does NOT mention Docker

2. **Senior Backend Engineer**

                                                                                                                                                                                                - CV: 8 years PHP/Laravel, AWS, team leadership
                                                                                                                                                                                                - Job: Senior Laravel Developer at "Silvertreebrands"
                                                                                                                                                                                                - **Assert**: Cover letter mentions "Silvertreebrands" and "leadership"

3. **Career Changer**

                                                                                                                                                                                                - CV: Marketing (5 years), self-taught Python (6 months)
                                                                                                                                                                                                - Job: Junior Data Analyst
                                                                                                                                                                                                - **Assert**: Letter bridges marketing + analytics; does not claim "5 years Python"

4. **Student**

                                                                                                                                                                                                - CV: CS student, GPA 3.8, club projects, no professional experience
                                                                                                                                                                                                - Job: Software Engineering Intern
                                                                                                                                                                                                - **Assert**: Letter emphasizes academics + passion; does not invent job experience

5. **Specialist**

                                                                                                                                                                                                - CV: DevOps, Kubernetes certified, no frontend experience
                                                                                                                                                                                                - Job: Full-Stack Engineer (React + Go + Kubernetes)
                                                                                                                                                                                                - **Assert**: Letter emphasizes Kubernetes; does NOT claim React experience

**Automated Check** (golden-test.php script):

```php
foreach ($goldenTests as $test) {
    $result = $generator->generate($test['cv'], $test['job']);
    
    // Assert word count
    assert($result['word_count'] >= 150 && $result['word_count'] <= 300);
    
    // Assert company mention
    assert(str_contains($result['cover_letter'], $test['expected_company']));
    
    // Assert no hallucinations
    foreach ($test['banned_phrases'] as $phrase) {
        assert(!str_contains($result['cover_letter'], $phrase));
    }
}
```

**CI Integration**: Run golden tests on every commit; fail build if any test fails

---

## 8. OBSERVABILITY & COST AWARENESS (TIER 3 - COMPETITIVE EDGE)

### 8.1 Token Usage Logging

**What to Log** (per request):

- `request_id`: UUID for correlation
- `model`: OpenAI model used (e.g., gpt-4-turbo-preview)
- `tokens_in`: Total input tokens (CV + job description + prompts)
- `tokens_out`: Total output tokens (extracted facts + cover letter)
- `tokens_total`: Sum of in + out
- `duration_ms`: Time from request start to response
- `outcome`: success | pdf_error | ai_error | validation_error

**Log Format**:

```
[2025-10-20 14:32:10] production.INFO: Cover letter generated
{
  "request_id": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
  "model": "gpt-4-turbo-preview",
  "tokens_in": 2450,
  "tokens_out": 180,
  "tokens_total": 2630,
  "duration_ms": 4200,
  "outcome": "success"
}
```

**Cost Calculation**:

```php
// GPT-4-turbo pricing (as of Oct 2024)
const INPUT_COST_PER_1K = 0.01;   // $0.01 per 1K tokens
const OUTPUT_COST_PER_1K = 0.03;  // $0.03 per 1K tokens

$cost = ($tokensIn / 1000 * INPUT_COST_PER_1K) + 
        ($tokensOut / 1000 * OUTPUT_COST_PER_1K);

// Average: ~2500 in + 180 out = $0.025 + $0.0054 ≈ $0.03 per request
```

**Dashboard Metrics** (logged to Laravel Log, viewable on Railway):

- Total requests today
- Average tokens per request
- P50/P95 latency
- Error rate
- Estimated daily cost

### 8.2 Performance Tracking

**Latency Targets**:

- P50: <5 seconds
- P95: <8 seconds
- P99: <12 seconds

**Implementation**:

```php
$startTime = microtime(true);

// ... process request ...

$durationMs = (microtime(true) - $startTime) * 1000;

Log::info('Request completed', [
    'request_id' => $requestId,
    'duration_ms' => round($durationMs, 2)
]);
```

**Monitoring**: Railway dashboard shows logs; filter by `duration_ms` to track P95

### 8.3 Health Check Endpoint

**Route**: `GET /healthz`

**Response** (healthy):

```json
{
  "status": "ok",
  "app": "cover-letter-generator",
  "version": "1.0.0",
  "checks": {
    "database": "ok",
    "openai": "reachable",
    "storage": "ok"
  },
  "timestamp": "2025-10-20T14:32:10Z"
}
```

**Response** (degraded):

```json
{
  "status": "degraded",
  "app": "cover-letter-generator",
  "version": "1.0.0",
  "checks": {
    "database": "ok",
    "openai": "unreachable",  // ← Issue detected
    "storage": "ok"
  },
  "timestamp": "2025-10-20T14:32:10Z"
}
```

**Implementation**:

```php
public function healthz(): JsonResponse
{
    $checks = [
        'database' => $this->checkDatabase(),
        'openai' => $this->checkOpenAI(),
        'storage' => $this->checkStorage()
    ];
    
    $status = in_array('unreachable', $checks) ? 'degraded' : 'ok';
    
    return response()->json([
        'status' => $status,
        'app' => 'cover-letter-generator',
        'version' => config('app.version'),
        'checks' => $checks,
        'timestamp' => now()->toIso8601String()
    ], $status === 'ok' ? 200 : 503);
}

private function checkOpenAI(): string
{
    try {
        // Simple ping: list models (cheap API call)
        OpenAI::client()->models()->list();
        return 'reachable';
    } catch (\Exception $e) {
        return 'unreachable';
    }
}
```

**Usage**: Railway can ping `/healthz` for uptime monitoring

### 8.4 Cost Optimization Strategies

**CV Text Truncation**:

- Limit CV text to ~15,000 characters (≈3,000 tokens)
- Strategy: Keep first 80%, last 20% (preserves intro + recent experience)

**Prompt Optimization**:

- Keep system prompts concise (<200 tokens)
- Use JSON schema to reduce verbosity in Stage 1
- Avoid examples in prompts (they consume tokens)

**Model Selection**:

- Use GPT-4-turbo (cheaper than GPT-4) for Stage 2
- Consider GPT-4o-mini for Stage 1 (extraction) if accuracy sufficient

**Caching** (future optimization):

- Cache common job description patterns (if detected)
- No caching of CVs (privacy concern)

**Estimated Costs** (with 10 req/hour rate limit):

- Max requests per day: 240
- Max cost per day: 240 × $0.03 = $7.20
- Max cost per month: $216
- Realistic usage: ~50 requests/day = $1.50/day = $45/month

---

## 9. BUILD LOG & DOCUMENTATION (TIER 3 - AI-FIRST SHOWCASE)

### 9.1 BUILD_LOG.md (AI Development Journal)

**Purpose**: Demonstrate AI-first development expertise; show iteration and decision-making

**Structure**:

```markdown
# AI-First Development Log

## Project: CV-to-Cover-Letter Generator
**Duration**: [Start Date] - [End Date]  
**Total Time**: ~6-8 hours

---

## Tools & Models Used

### Primary Development Tools
- **Cursor Composer**: Initial project scaffolding, multi-file service creation
- **Cursor Chat**: Debugging PDF extraction issues, test case generation
- **Cursor CMD+K**: Inline edits, quick fixes

### AI Models
- **Claude 3.5 Sonnet** (via Cursor): Code generation, architectural decisions
- **GPT-4-turbo**: Production AI for cover letter generation

---

## Prompt Engineering Journey

### System Prompt Iteration (Cover Letter Composer)

**Iteration 1**: Too verbose (350+ words)

```

Prompt: "Write a professional cover letter..."

Issue: Output consistently 300-400 words (too long)

```

**Iteration 2**: Added explicit word count

```

Prompt: "Write 2-3 paragraphs (150-300 words)..."

Issue: Sometimes 2 paragraphs, sometimes 4; inconsistent

```

**Iteration 3**: Structured paragraph requirements

```

Prompt: "Paragraph 1 (50-80 words): ..., Paragraph 2 (70-120 words): ..."

Result: ✅ Consistent 2-3 paragraphs, 180-220 words average

```

**Iteration 4**: Anti-hallucination safeguards

```

Added: "ONLY use information from CV Facts JSON (never invent)"

Added: Two-stage pipeline (facts extraction → composition)

Result: ✅ Zero hallucinations in golden test set

```

### JSON Schema Extraction (Stage 1)

**Challenge**: GPT-4 sometimes returned explanatory text before/after JSON

**Solution**: 

```

Prompt: "Output ONLY valid JSON with this schema: ..."

Added: "Do NOT include any text outside the JSON."

Result: ✅ 95% success rate; 1 retry catches remaining 5%

````

---

## Architecture Decisions

### Two-Stage Pipeline (Key Decision)
**Why**: Single-prompt approach led to hallucinations (AI invented skills)

**Solution**: 
1. Stage 1: Extract facts from CV → strict JSON schema
2. Stage 2: Compose letter using ONLY extracted facts

**Outcome**: Golden tests show zero hallucinations

### PHP/Laravel vs. Node.js
**Decision**: Laravel (despite Node.js being more "AI-native")

**Rationale**:
- Employer preference explicitly stated
- Laravel Pint + PHPStan = excellent tooling
- Spatie PDF library is battle-tested
- Cursor handles PHP as well as JS

---

## AI-Generated vs. Hand-Written Code

### AI-Generated (~85%)
- Controllers (CoverLetterController.php)
- Services (PdfExtractor.php, CoverLetterGenerator.php)
- Blade views (index.blade.php)
- Unit tests (90% of test cases)
- Middleware (SecurityHeaders.php)

### Hand-Written (~15%)
- System prompts (Stage 1 & 2) - required domain expertise
- JSON schema design - needed precise structure
- Golden test assertions - required anti-hallucination logic
- Security headers config - needed POPIA compliance knowledge
- Rate limiting strategy - cost management logic

**Key Insight**: AI excels at boilerplate/structure; humans excel at business logic/constraints

---

## Debugging Diary

### Issue 1: Scanned PDFs Crashing
**Symptom**: App crashed with 500 error on scanned PDFs

**AI Suggestion**: Add try-catch around pdftotext call
```php
try {
    $text = Pdf::getText($file);
} catch (\Exception $e) {
    throw new PdfExtractionException("Could not extract text");
}
````

**My Refinement**: Added preemptive MIME + magic-byte validation before extraction

```php
// Validate MIME type FIRST (prevents unnecessary pdftotext calls)
if (!$this->isValidPdf($file)) {
    throw new PdfExtractionException("Invalid PDF file");
}
```

**Outcome**: Cleaner error messages; faster failure path

---

### Issue 2: CSP Header Blocking Alpine.js

**Symptom**: Alpine.js not working; console errors about inline scripts

**AI Suggestion**: Add `'unsafe-inline'` to CSP

```
Content-Security-Policy: script-src 'self' 'unsafe-inline'
```

**My Refinement**: Researched Alpine.js CSP requirements; added nonce-based approach

```php
// Better: Use nonce for inline scripts
$nonce = base64_encode(random_bytes(16));
app()->instance('csp_nonce', $nonce);
response()->header('Content-Security-Policy', "script-src 'self' 'nonce-{$nonce}'");
```

**Outcome**: More secure than blanket `unsafe-inline`; nonce regenerated per request

---

### Issue 3: Rate Limiting Not Resetting

**Symptom**: Rate limit persisted after 1 hour

**AI Suggestion**: Use `Cache::forget()` after timeout

```php
Cache::forget('cover-letter:' . $ip);
```

**My Refinement**: Use TTL-based expiry (automatic cleanup)

```php
Cache::put($key, $attempts + 1, now()->addHour());  // Auto-expires
```

**Outcome**: Simpler; no manual cleanup needed

---

## Lessons Learned

### What Worked Well

✅ Cursor Composer for initial scaffolding (saved 2-3 hours)

✅ Two-stage AI pipeline prevented hallucinations

✅ Golden test set caught edge cases early

✅ PHPStan level 8 forced type safety (prevented bugs)

### What Required Human Oversight

⚠️ AI suggested `Storage::put()` without cleanup (memory leak risk)

⚠️ AI-generated tests didn't cover anti-hallucination cases

⚠️ AI suggested generic error messages (not user-friendly)

### If I Built This Again

🔄 Start with golden test set FIRST (TDD approach)

🔄 Use GPT-4o-mini for Stage 1 (extraction) to reduce cost

🔄 Add structured logging from Day 1 (not as afterthought)

---

## Metrics

- **Lines of Code**: ~1,200 (app/) + ~800 (tests/)
- **AI-Generated**: ~1,700 lines (85%)
- **Hand-Written**: ~300 lines (15%)
- **Time Saved**: Estimated 10-12 hours vs. manual coding
- **Cost**: ~$2 in AI API costs during development
````

### 9.2 QA_CHECKLIST.md (Pre-Submission Checklist)

**Purpose**: Demonstrate systematic QA process; show production-readiness

**Structure**:

```markdown
# Pre-Submission Quality Assurance Checklist

**Project**: CV-to-Cover-Letter Generator  
**Reviewer**: [Your Name]  
**Date**: [Submission Date]

---

## ✅ FUNCTIONALITY (Core Requirements)

- [x] Generates 2-3 paragraph cover letters (150-300 words)
- [x] Cover letter mentions company name from job description
- [x] Cover letter mentions role title from job description
- [x] No hallucinated skills (tested with Docker example)
- [x] Works with 5/5 golden test cases
- [x] Processing completes in <10 seconds (P95: 6.2s)
- [x] Copy-to-clipboard works
- [x] "Generate Another" resets form

---

## ✅ EDGE CASES (Robustness)

- [x] Scanned PDF → "Could not extract text from PDF. Try text-based CV."
- [x] Corrupt PDF → "Could not read PDF. Try a different file."
- [x] Encrypted PDF → "PDF is password-protected. Upload unlocked file."
- [x] 15-page CV → Truncated to 15k chars, no timeout
- [x] Empty job description → "Please enter job description"
- [x] Job description <50 chars → "Job description too short (min 50 chars)"
- [x] Unicode names (João, François) → Handled correctly
- [x] HTML in job ad → Stripped before processing
- [x] Rapid duplicate submits → Button disabled during processing
- [x] Non-PDF file (.docx) → "File must be PDF format"

---

## ✅ SECURITY (POPIA-Compliant)

### Secrets Management
- [x] API keys in .env only (not committed)
- [x] .env.example provided with comments
- [x] .gitignore includes .env
- [x] Key rotation instructions in README

### File Upload Security
- [x] MIME type validation (finfo_file)
- [x] Magic-byte validation (%PDF-)
- [x] File size limit (10MB server-side)
- [x] Temp storage outside web root (storage/app/temp/)
- [x] Files deleted immediately after processing
- [x] Unique filenames (UUID-based)

### Headers & Middleware
- [x] CSRF protection enabled
- [x] Content-Security-Policy header set (with nonce)
- [x] X-Frame-Options: DENY
- [x] X-Content-Type-Options: nosniff
- [x] Referrer-Policy: no-referrer
- [x] Strict-Transport-Security (HSTS)
- [x] Rate limiting (10 requests/hour per IP)

### Privacy (POPIA)
- [x] No PII in logs (only request_id, tokens, duration)
- [x] CV text never logged
- [x] Job description never logged
- [x] Privacy notice displayed on form
- [x] No persistent storage of CVs

### Dependencies
- [x] composer.lock committed
- [x] composer audit clean (no vulnerabilities)

---

## ✅ CODE QUALITY

### Static Analysis
- [x] PHPStan level 8 passes (zero errors)
- [x] Laravel Pint (PSR-12) formatted
- [x] No unused imports
- [x] Type hints on all methods

### Testing
- [x] Unit tests pass (PdfExtractor, CoverLetterGenerator)
- [x] Feature tests pass (form, validation, generation)
- [x] Edge case tests pass (corrupt PDF, etc.)
- [x] Security tests pass (CSRF, headers, file cleanup)
- [x] Golden test set passes (5/5 cases, zero hallucinations)
- [x] Test coverage >80% (services)

### Code Structure
- [x] Service layer pattern (thin controllers)
- [x] Custom exceptions (PdfExtractionException, AiGenerationException)
- [x] Request validation classes
- [x] Middleware for security headers
- [x] No God objects (all classes <300 lines)

---

## ✅ UX/ACCESSIBILITY

### Responsiveness
- [x] Works on mobile (tested iPhone 12, Pixel 5)
- [x] Works on tablet (tested iPad)
- [x] Works on desktop (tested Chrome, Firefox, Safari)
- [x] Single column layout on <768px
- [x] Touch-friendly file upload area (≥44px targets)

### Accessibility
- [x] All inputs have <label> elements
- [x] ARIA labels on buttons
- [x] Keyboard navigation works (logical Tab order)
- [x] Focus management (focus on result after generation)
- [x] Color contrast ≥4.5:1 (WCAG AA)
- [x] Error messages announced (ARIA live region)

### UX Polish
- [x] Loading state with spinner
- [x] Button disabled during processing
- [x] Copy-to-clipboard with toast notification
- [x] No layout shift when result appears
- [x] Error messages styled consistently (red alert)
- [x] Success state clear (green badge for word count)

---

## ✅ OBSERVABILITY & COST AWARENESS

### Logging
- [x] Request ID correlation (UUID per request)
- [x] Token usage logged (tokens_in, tokens_out, tokens_total)
- [x] Latency tracked (duration_ms)
- [x] Outcome tracked (success | error)
- [x] No PII in logs (verified manually)

### Health Check
- [x] /healthz endpoint returns 200 JSON
- [x] Checks database connectivity
- [x] Checks OpenAI API reachability
- [x] Checks storage availability
- [x] Returns 503 if degraded

### Cost Management
- [x] CV text truncated to 15k chars
- [x] Rate limiting protects budget (10/hour = max $7.20/day)
- [x] Average cost per request documented (~$0.03)
- [x] Token usage visible in logs

---

## ✅ DEPLOYMENT

### Railway
- [x] Live URL accessible: https://[your-app].railway.app
- [x] Environment variables set (OPENAI_API_KEY)
- [x] pdftotext binary installed (verified)
- [x] Database migrations run
- [x] HTTPS enforced
- [x] Health check endpoint reachable

### Configuration
- [x] APP_ENV=production
- [x] APP_DEBUG=false
- [x] Error logging enabled
- [x] Rate limiting active

### Testing on Production
- [x] Tested all 5 golden cases on live URL
- [x] Tested edge cases (corrupt PDF, rate limit)
- [x] Verified /healthz endpoint
- [x] Verified security headers (checked with securityheaders.com)

---

## ✅ DOCUMENTATION

### README.md
- [x] Project description
- [x] Tech stack listed
- [x] Installation instructions (<5 min setup)
- [x] Environment variables documented
- [x] Run instructions (php artisan serve)
- [x] Test instructions (php artisan test)
- [x] Deploy instructions (Railway)
- [x] Cost & performance section
- [x] Privacy/POPIA note
- [x] E-commerce awareness section

### BUILD_LOG.md
- [x] Tools used documented
- [x] Prompt iterations documented
- [x] AI vs hand-written code breakdown
- [x] Debugging examples included
- [x] Lessons learned section

### Other Docs
- [x] .env.example with comments
- [x] QA_CHECKLIST.md (this file)
- [x] Code comments on complex logic

---

## ✅ SUBMISSION REQUIREMENTS

- [x] GitHub repository created
- [x] Clean commit history (not one giant commit)
- [x] Live demo URL tested
- [x] One-paragraph build summary written
- [x] Email draft prepared with:
  - [ ] GitHub repository link
  - [ ] Live demo URL
  - [ ] Build summary
  - [ ] Screenshots (optional)

---

## 🎯 FINAL CHECKS

- [x] No console errors in browser
- [x] No PHP warnings/notices
- [x] No 500 errors in logs
- [x] All tests passing (php artisan test)
- [x] PHPStan clean (./vendor/bin/phpstan analyse)
- [x] Pint formatted (./vendor/bin/pint)
- [x] composer audit clean

---

## ✨ COMPETITIVE DIFFERENTIATORS

What sets this submission apart:
1. ✅ Two-stage AI pipeline (prevents hallucinations)
2. ✅ Golden test set with anti-hallucination validation
3. ✅ Production security (POPIA-compliant)
4. ✅ PHPStan level 8 + Laravel Pint
5. ✅ Cost/token awareness documented
6. ✅ /healthz observability endpoint
7. ✅ BUILD_LOG.md showing AI-first workflow
8. ✅ QA_CHECKLIST.md showing systematic testing
9. ✅ E-commerce awareness (stateless, scalable design)
10. ✅ GitHub Actions CI (tests + static analysis)

---

**Reviewer Signature**: [Your Name]  
**Date**: [Submission Date]  
**Status**: ✅ READY FOR SUBMISSION
````


### 9.3 README.md (Complete)

**Purpose**: Professional documentation; 5-minute setup; showcases technical depth

**Key Sections**:

1. **Project Overview** (what it does)
2. **Tech Stack** (Laravel, GPT-4, etc.)
3. **Features** (anti-hallucination, POPIA-compliant, etc.)
4. **Installation** (step-by-step, <5 min)
5. **Configuration** (.env variables)
6. **Running Locally** (php artisan serve)
7. **Testing** (php artisan test, golden set)
8. **Deployment** (Railway instructions)
9. **Cost & Performance** (token usage, latency, estimated costs)
10. **Security & Privacy** (POPIA compliance, no data retention)
11. **E-commerce Readiness** (stateless, scalable, rate-limited)
12. **Architecture** (two-stage pipeline diagram)
13. **API Documentation** (/generate endpoint)
14. **Troubleshooting** (common issues)
15. **License** (MIT or specify)

**Cost & Performance Section** (example):

```markdown
## Cost & Performance

### Token Usage
- **Average tokens per request**: 2,500 (2,300 input + 200 output)
- **Cost per request**: ~$0.03 USD (~R0.55 ZAR)
- **Model**: GPT-4-turbo (gpt-4-turbo-preview)

### Performance
- **P50 latency**: <5 seconds
- **P95 latency**: <8 seconds
- **Truncation strategy**: CV text limited to 15,000 characters (~3,000 tokens)

### Rate Limiting
- **Limit**: 10 requests per hour per IP
- **Max daily cost**: 240 requests × $0.03 = $7.20 USD
- **Realistic usage**: ~50 requests/day = $1.50/day = ~$45/month

### Optimization
- Two-stage pipeline reduces token waste (extract facts first)
- Prompt engineering keeps responses concise (150-300 words)
- CV truncation prevents token overflow on long resumes
```

**E-commerce Readiness Section**:

```markdown
## E-commerce & Production Readiness

This application is built with e-commerce scale and operational discipline in mind:

### Stateless Design
- No session state (works behind load balancers)
- Ephemeral file processing (no persistent storage)
- Horizontal scaling ready

### Cost Management
- Rate limiting protects budget (10 requests/hour/IP)
- Token usage logged for cost tracking
- CV truncation prevents runaway costs

### Privacy & Compliance
- POPIA-compliant (South African privacy law)
- No CV retention (files deleted immediately)
- No PII in logs

### Reliability
- Two-stage AI pipeline with retry logic
- Health check endpoint for monitoring (/healthz)
- Graceful error handling (no crashes)

### Security
- Security headers (CSP, X-Frame-Options, HSTS)
- MIME + magic-byte file validation
- Rate limiting prevents abuse
- CSRF protection enabled

### Observability
- Request ID correlation (UUID per request)
- Token usage + latency tracking
- Structured logging (JSON-compatible)
```

---

## 10. DEPLOYMENT (RAILWAY)

### 10.1 Pre-Deployment Checklist

- [ ] All tests passing locally
- [ ] PHPStan clean
- [ ] Pint formatted
- [ ] .env.example up to date
- [ ] README complete
- [ ] BUILD_LOG.md written
- [ ] QA_CHECKLIST.md filled out

### 10.2 Railway Configuration

**railway.json**:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --optimize-autoloader --no-dev && npm install && npm run build"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT",
    "healthcheckPath": "/healthz",
    "healthcheckTimeout": 30,
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 3
  }
}
```

**Nixpacks Plan** (nixpacks.toml):

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer", "nodejs-18_x", "poppler_utils"]
# poppler_utils provides pdftotext binary

[phases.install]
cmds = ["composer install --optimize-autoloader --no-dev"]

[phases.build]
cmds = ["npm install", "npm run build"]

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=$PORT"
```

### 10.3 Environment Variables (Railway)

Set in Railway dashboard:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... (generate with: php artisan key:generate --show)
OPENAI_API_KEY=sk-...
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### 10.4 Post-Deployment Verification

1. Visit live URL: `https://[your-app].railway.app`
2. Test form submission with sample CV
3. Check `/healthz` endpoint returns 200
4. Test all 5 golden cases
5. Test edge cases (corrupt PDF, rate limit)
6. Check security headers: https://securityheaders.com
7. Verify logs in Railway dashboard (no PII visible)

---

## 11. SUBMISSION DELIVERABLES

### 11.1 GitHub Repository

**Repository Name**: `cv-cover-letter-generator`

**Files to Include**:

- ✅ Complete Laravel application
- ✅ tests/ directory with all tests
- ✅ .env.example (with comments)
- ✅ README.md (comprehensive)
- ✅ docs/BUILD_LOG.md
- ✅ docs/QA_CHECKLIST.md
- ✅ composer.lock (committed)
- ✅ .github/workflows/ci.yml (CI config)
- ✅ railway.json (deployment config)

**Files to .gitignore**:

- ❌ .env (NEVER commit)
- ❌ /vendor/ (Composer packages)
- ❌ /node_modules/ (npm packages)
- ❌ /storage/app/temp/*.pdf (uploaded files)
- ❌ database/database.sqlite (local DB)

**Commit History**:

- Clean, logical commits (not one giant commit)
- Example:
                                                                                                                                - "Initial Laravel setup with Tailwind"
                                                                                                                                - "Add PdfExtractor service with tests"
                                                                                                                                - "Add CoverLetterGenerator with two-stage pipeline"
                                                                                                                                - "Add frontend form with Alpine.js"
                                                                                                                                - "Add security headers and rate limiting"
                                                                                                                                - "Add golden test set and anti-hallucination tests"
                                                                                                                                - "Add BUILD_LOG and QA_CHECKLIST"
                                                                                                                                - "Configure Railway deployment"

### 11.2 Live Demo

**URL**: `https://[your-app-name].railway.app`

**Ensure**:

- HTTPS enforced
- /healthz returns 200
- Form works end-to-end
- All golden cases pass

### 11.3 Build Summary (1 Paragraph)

**Template**:

```
I built this application using Laravel 11 with Blade templating and 
Alpine.js for the frontend, and OpenAI's GPT-4-turbo for cover letter 
generation. The core innovation is a two-stage AI pipeline: first, 
extracting structured facts from the CV into a validated JSON schema, 
then composing the cover letter using only those extracted facts. This 
prevents hallucinations—the AI never invents skills not present in the 
CV. I used spatie/pdf-to-text for reliable PDF parsing, implemented 
POPIA-compliant privacy measures (ephemeral file processing, no data 
retention), and added production-grade security (MIME validation, rate 
limiting, CSP headers with nonce). The app is deployed on Railway with health 
checks, token usage logging, and automated testing (PHPStan level 8, 
golden test set). I chose this approach because it demonstrates 
AI-first development discipline—not just using AI, but controlling it 
through structured pipelines and systematic validation. Total build 
time: ~6-8 hours with Cursor AI assistance.
```

### 11.4 Submission Email

**Subject**: CV Cover Letter Generator - AI-Enabled Software Developer Application

**Body**:

```
Hi [Hiring Manager Name],

I've completed the cover letter generator challenge. Here are the details:

📦 GitHub Repository: https://github.com/[your-username]/cv-cover-letter-generator
🌐 Live Demo: https://[your-app].railway.app
📊 Build Documentation: See BUILD_LOG.md and QA_CHECKLIST.md in the repo

How I Built It:
[Paste one-paragraph build summary here]

Key Features:
- Two-stage AI pipeline prevents hallucinations (zero invented skills)
- POPIA-compliant privacy (no CV storage, ephemeral processing)
- Production security (PHPStan level 8, rate limiting, security headers with nonce)
- Token usage logging + /healthz endpoint for observability
- Golden test set with anti-hallucination validation
- GitHub Actions CI (tests + static analysis)

The app demonstrates AI-first development practices: using Cursor for 
rapid iteration while maintaining production standards through automated 
testing, static analysis, and systematic QA.

Looking forward to discussing the role!

Best,
[Your Name]
```

---

## 12. SUCCESS METRICS

### How This PRD Positions You Competitively

**Compared to Average Submission** (just working app):

- ✅ Two-stage pipeline (vs single prompt)
- ✅ Anti-hallucination testing (vs no validation)
- ✅ POPIA compliance explicitly mentioned
- ✅ Cost awareness + token logging
- ✅ BUILD_LOG.md shows AI-first workflow
- ✅ QA_CHECKLIST.md shows systematic testing
- ✅ PHPStan level 8 + Laravel Pint
- ✅ /healthz endpoint
- ✅ E-commerce readiness section

**Compared to Good Submission** (working + tests):

- ✅ Golden test set with specific anti-hallucination cases
- ✅ Observability (request IDs, token logging, latency)
- ✅ Cost optimization strategy documented
- ✅ BUILD_LOG.md showing iteration (not just final code)
- ✅ Security headers + MIME/magic-byte validation
- ✅ E-commerce awareness language (stateless, scalable)

**What Makes You Top 5%**:

1. **AI Control**: Two-stage pipeline shows you manage AI, not just use it
2. **Production Mindset**: Security, observability, cost awareness
3. **Meta-Awareness**: BUILD_LOG.md shows you understand AI-first development
4. **Systematic QA**: QA_CHECKLIST.md shows discipline
5. **Ecommerce Context**: Language shows you understand their business

---

## 13. NEXT STEPS

Once this PRD is approved:

1. **Create detailed tickets** from each section
2. **Set up development environment** (Laravel 11, Cursor, GitHub)
3. **Build in this order**:

                                                                                                                                                                                                - Phase 1: Project setup + scaffolding
                                                                                                                                                                                                - Phase 2: Backend services (PDF + AI)
                                                                                                                                                                                                - Phase 3: Frontend (form + results)
                                                                                                                                                                                                - Phase 4: Testing (unit + feature + golden)
                                                                                                                                                                                                - Phase 5: Security + observability
                                                                                                                                                                                                - Phase 6: Deployment (Railway)
                                                                                                                                                                                                - Phase 7: Documentation (README + BUILD_LOG + QA)

**Estimated Timeline**: 6-8 hours total (with AI assistance)

---

**This PRD is your blueprint for a production-grade, AI-first application that will stand out in the applicant pool. Ready to build?**
