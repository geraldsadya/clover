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

## Build Progress

### Phase 0: Setup ✅
- [x] GitHub repository created: https://github.com/geraldsadya/clover.git
- [x] Laravel 11 project initialized
- [x] Project structure created (golden/, docs/ directories)

### Phase 1: Basic Scaffolding ✅
- [x] **Ticket A: Scaffold & Routing** - COMPLETED
  - [x] Routes: `/` (form), `/generate` (POST), `/healthz` (health check)
  - [x] Blade form with PDF upload + textarea (responsive design)
  - [x] Server-side validation: PDF ≤10MB, MIME type, job desc 50-10k chars
  - [x] CSRF enabled on POST routes
  - [x] Error responses formatted as JSON `{success: false, error: {...}}`
  - [x] Alpine.js frontend with loading states
  - [x] Rate limiting middleware (10 requests/hour)
  - [x] Health check endpoint working
  - [x] Dependencies installed: spatie/pdf-to-text, openai-php/client
  - [x] Initial commit pushed to GitHub
  - [x] **Ticket A Complete**: Basic Laravel scaffolding with form validation and JSON error responses - all tests passing

### Phase 2: PDF Extraction Service ✅
- [x] **Ticket B: PDF Extraction Service** - COMPLETED
  - [x] MIME type validation (`finfo_file`)
  - [x] Magic-byte validation (`%PDF-`)
  - [x] Extract text using `spatie/pdf-to-text`
  - [x] Normalize whitespace, cap at ~15k chars (keep first 80%, last 20%)
  - [x] Temp files stored in `storage/app/temp/`, deleted immediately post-extraction
  - [x] Custom exceptions: `PdfExtractionException` for scanned, corrupt, encrypted, empty PDFs
  - [x] Tests: valid PDF extracts text, edge cases throw exceptions, files deleted
  - [x] **Ticket B Complete**: PDF extraction service with comprehensive error handling, temp file management, and PHPStan level 8 compliance - all tests passing

### Phase 3: AI Service Integration ✅
- [x] **Ticket C: Facts JSON Extraction (Stage 1)** - COMPLETED
  - [x] Extraction prompt with temp=0.1 (low creativity)
  - [x] JSON schema validation (name, skills, experience, education, certifications, years_of_experience)
  - [x] Retry logic: 1 retry on malformed JSON with stricter prompt
  - [x] Fallback: return `needs-manual` path on double failure
  - [x] Log extraction metrics (tokens, duration, outcome)
  - [x] Tests: returns valid JSON, retries work, banned phrases not invented
  - [x] CoverLetterGenerator service with OpenAI integration
  - [x] Comprehensive unit tests (10 test cases)
  - [x] PHPStan level 8 compliance with proper type annotations
  - [x] **Ticket C Complete**: Facts extraction service with robust error handling and anti-hallucination measures
  - [x] **Ticket D Complete**: Job ad sanitization with HTML stripping, UTM removal, and length capping

### Phase 4: Cover Letter Composition ✅
- [x] **Ticket E: Cover Letter Composition (Stage 2)** - COMPLETED
  - [x] Composition prompt with temp=0.4 (moderate creativity)
  - [x] Enforce 2-3 paragraphs, 150-300 words total
  - [x] Include company name and role from job description
  - [x] Groundedness check: only use facts from Stage 1 JSON
  - [x] Word count validation (re-prompt once if outside range)
  - [x] Tests: length correct, company mentioned, no hallucinated skills
  - [x] Anti-hallucination test passes (CV without Docker + job with Docker = no Docker mention)
  - [x] **Ticket E Complete**: Complete cover letter generation with AI integration, word count validation, and anti-hallucination measures
- [x] **Ticket F: UI Polish** - COMPLETED
  - [x] Loading state with spinner ("Generating your cover letter...")
  - [x] Submit button disabled during processing
  - [x] Copy-to-clipboard button with toast notification
  - [x] ARIA labels on all inputs and buttons
  - [x] Keyboard navigation works (Tab order logical)
  - [x] Mobile responsive: single column <768px, touch-friendly upload area
  - [x] No layout shift when result appears
  - [x] Tests: accessibility audit passes (manual), mobile works (manual)
  - [x] **Ticket F Complete**: Complete UI polish with Alpine.js, accessibility, mobile responsiveness, and comprehensive testing
- [x] **Ticket F Re-Audit Complete**: Added missing auto-expand textarea functionality and scrollable result card for mobile
- [x] **Ticket F Final Audit Complete**: All requirements verified, PHPStan level 8 passes, core functionality tests pass

### Phase 5: Golden Test Set & Evaluation ✅
- [x] **Ticket G: Golden Test Set & Eval Harness** - COMPLETED
  - [x] 5 sample CVs in `golden/cvs/`: junior_frontend.pdf, senior_backend.pdf, career_changer.pdf, student.pdf, specialist_devops.pdf
  - [x] 5 matching job descriptions in `golden/jobs/`: junior_react_dev.txt, senior_laravel_silvertreebrands.txt, junior_data_analyst.txt, swe_intern.txt, fullstack_k8s.txt
  - [x] `golden/expected.json` with word_count_min/max, must_mention, banned_phrases per test
  - [x] Automated script `scripts/eval.php` that runs all tests
  - [x] Script asserts: length (150-300), company mention, no banned phrases
  - [x] Anti-hallucination test cases: Docker test, Python years test, React experience test
  - [x] Rate limiting handling with 30-second delays between tests
  - [x] **Note**: Golden tests require OpenAI API access. Currently hitting rate limits during testing, but all infrastructure is in place
  - [x] **Ticket G Complete**: Complete golden test set with evaluation harness, ready for production testing

---

## Lessons Learned

### What Worked Well
✅ Cursor Composer for initial scaffolding (saved 2-3 hours)

### What Required Human Oversight
⚠️ AI suggested generic approaches (needed specific Laravel patterns)

### If I Built This Again
🔄 Start with golden test set FIRST (TDD approach)

---

## Metrics
- **Lines of Code**: ~0 (just started)
- **AI-Generated**: ~0 lines (0%)
- **Hand-Written**: ~0 lines (0%)
- **Time Saved**: Estimated 0 hours vs. manual coding
- **Cost**: ~$0 in AI API costs during development
