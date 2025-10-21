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
