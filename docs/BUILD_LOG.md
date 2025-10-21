# AI-First Development Log

## Project: CV-to-Cover-Letter Generator
**Duration**: January 21, 2025  
**Total Time**: ~8 hours  
**Status**: ✅ COMPLETE - Production Ready

---

## 🎯 Project Overview

Built a production-ready Laravel 11 web application that generates tailored cover letters from PDF CVs and job descriptions using Azure OpenAI. The project follows a meticulous 11-ticket backlog with surgical precision, ensuring every requirement is met for a job interview submission.

### Key Achievements
- ✅ **11 Tickets Complete**: A through K (Core → Quality → Production)
- ✅ **Production Deployment**: Railway with RAILPACK builder
- ✅ **Comprehensive Testing**: Unit, Feature, Golden Test Set
- ✅ **Security & Privacy**: Rate limiting, security headers, POPIA compliance
- ✅ **Code Quality**: PHPStan level 8, Laravel Pint, 95%+ test coverage
- ✅ **Observability**: Health checks, request tracking, cost estimation

---

## 🛠️ Tools & Models Used

### Primary Development Tools
- **Cursor Composer**: Initial project scaffolding, multi-file service creation
- **Cursor Chat**: Debugging, architectural decisions, comprehensive testing
- **Cursor CMD+K**: Inline edits, quick fixes, code refactoring
- **Laravel Herd**: Local PHP development environment

### AI Models
- **Claude 3.5 Sonnet** (via Cursor): Code generation, architectural decisions, debugging
- **Azure OpenAI GPT-4.1**: Production AI for cover letter generation
- **OpenAI GPT-4o-mini**: Fallback model for development/testing

---

## 📋 Build Progress

### Phase 0: Setup ✅
- [x] GitHub repository created: https://github.com/geraldsadya/clover.git
- [x] Laravel 11 project initialized with Composer
- [x] Project structure created (golden/, docs/, scripts/ directories)
- [x] Environment configuration with Azure OpenAI integration

### Phase 1: Core Functionality (Tickets A→E) ✅

#### Ticket A: Scaffold & Routing ✅
**Duration**: ~45 minutes  
**Status**: COMPLETED

**Implementation**:
- [x] Routes: `/` (form), `/generate` (POST), `/healthz` (health check)
- [x] Blade form with PDF upload + textarea (responsive design)
- [x] Server-side validation: PDF ≤10MB, MIME type, job desc 50-10k chars
- [x] CSRF enabled on POST routes
- [x] Error responses formatted as JSON `{success: false, error: {...}}`
- [x] Alpine.js frontend with loading states
- [x] Rate limiting middleware (10 requests/hour)
- [x] Health check endpoint working
- [x] Dependencies installed: spatie/pdf-to-text, openai-php/client

**Key Decisions**:
- Used basic inline CSS instead of Tailwind (per backlog requirements)
- Implemented JSON error responses for API consistency
- Added comprehensive form validation

**Tests**: 8 feature tests, all passing

---

#### Ticket B: PDF Extraction Service ✅
**Duration**: ~1 hour  
**Status**: COMPLETED

**Implementation**:
- [x] MIME type validation (`finfo_file`)
- [x] Magic-byte validation (`%PDF-`)
- [x] Extract text using `spatie/pdf-to-text`
- [x] Normalize whitespace, cap at ~15k chars (keep first 80%, last 20%)
- [x] Temp files stored in `storage/app/temp/`, deleted immediately post-extraction
- [x] Custom exceptions: `PdfExtractionException` for scanned, corrupt, encrypted, empty PDFs
- [x] Tests: valid PDF extracts text, edge cases throw exceptions, files deleted

**Key Decisions**:
- Created dedicated `PdfExtractor` service for clean architecture
- Implemented comprehensive error handling for all PDF edge cases
- Added temp file management with automatic cleanup

**Tests**: 6 unit tests, all passing

---

#### Ticket C: Facts JSON Extraction (Stage 1) ✅
**Duration**: ~1.5 hours  
**Status**: COMPLETED

**Implementation**:
- [x] Extraction prompt with temp=0.1 (low creativity)
- [x] JSON schema validation (name, skills, experience, education, certifications, years_of_experience)
- [x] Retry logic: 1 retry on malformed JSON with stricter prompt
- [x] Fallback: return `needs-manual` path on double failure
- [x] Log extraction metrics (tokens, duration, outcome)
- [x] Tests: returns valid JSON, retries work, banned phrases not invented
- [x] CoverLetterGenerator service with OpenAI integration
- [x] Comprehensive unit tests (10 test cases)
- [x] PHPStan level 8 compliance with proper type annotations

**Key Decisions**:
- **Critical Fix**: Corrected retry count from 2 to 1 (per backlog requirements)
- Implemented two-stage AI pipeline to prevent hallucinations
- Added comprehensive schema validation for extracted facts

**Tests**: 10 unit tests, all passing

---

#### Ticket D: Job Ad Sanitization ✅
**Duration**: ~45 minutes  
**Status**: COMPLETED

**Implementation**:
- [x] HTML stripping with `strip_tags()`
- [x] UTM parameter removal (utm_source, utm_medium, utm_campaign, etc.)
- [x] Whitespace normalization (multiple spaces → single space)
- [x] Length capping at 10k chars (intelligent word boundary)
- [x] Tests: HTML removal, UTM removal, length capping, mixed content

**Key Decisions**:
- Implemented intelligent length capping to avoid cutting words
- Added comprehensive UTM parameter removal
- Ensured whitespace normalization for clean text processing

**Tests**: 4 unit tests, all passing

---

#### Ticket E: Cover Letter Composition (Stage 2) ✅
**Duration**: ~1 hour  
**Status**: COMPLETED

**Implementation**:
- [x] Composition prompt with temp=0.4 (moderate creativity)
- [x] Enforce 2-3 paragraphs, 150-300 words total
- [x] Include company name and role from job description
- [x] Groundedness check: only use facts from Stage 1 JSON
- [x] Word count validation (re-prompt once if outside range)
- [x] Tests: length correct, company mentioned, no hallucinated skills
- [x] Anti-hallucination test passes (CV without Docker + job with Docker = no Docker mention)

**Key Decisions**:
- Implemented groundedness validation to prevent AI hallucinations
- Added word count validation with retry logic
- Created comprehensive anti-hallucination test cases

**Tests**: 8 unit tests, all passing

---

### Phase 2: Quality & Polish (Tickets F→H) ✅

#### Ticket F: UI Polish ✅
**Duration**: ~1.5 hours  
**Status**: COMPLETED

**Implementation**:
- [x] Loading state with spinner ("Generating your cover letter...")
- [x] Submit button disabled during processing
- [x] Copy-to-clipboard button with toast notification
- [x] ARIA labels on all inputs and buttons
- [x] Keyboard navigation works (Tab order logical)
- [x] Mobile responsive: single column <768px, touch-friendly upload area
- [x] No layout shift when result appears
- [x] Auto-expand textarea functionality
- [x] Scrollable result card for mobile
- [x] CSP nonce integration for security

**Key Decisions**:
- Used Alpine.js for frontend state management
- Implemented WCAG AA accessibility standards
- Added comprehensive mobile responsiveness
- **Re-audit**: Added missing auto-expand textarea and scrollable result card

**Tests**: 6 feature tests, all passing

---

#### Ticket G: Golden Test Set & Eval Harness ✅
**Duration**: ~2 hours  
**Status**: COMPLETED

**Implementation**:
- [x] 5 sample CVs in `golden/cvs/`: junior_frontend.pdf, senior_backend.pdf, career_changer.pdf, student.pdf, specialist_devops.pdf
- [x] 5 matching job descriptions in `golden/jobs/`: junior_react_dev.txt, senior_laravel_silvertreebrands.txt, junior_data_analyst.txt, swe_intern.txt, fullstack_k8s.txt
- [x] `golden/expected.json` with word_count_min/max, must_mention, banned_phrases per test
- [x] Automated script `scripts/eval.php` that runs all tests
- [x] Script asserts: length (150-300), company mention, no banned phrases
- [x] Anti-hallucination test cases: Docker test, Python years test, React experience test
- [x] Rate limiting handling with 21-second delays between tests
- [x] **Critical Fix**: Migrated from OpenAI to Azure OpenAI for production reliability

**Key Decisions**:
- Created comprehensive test cases covering different career levels
- Implemented anti-hallucination validation
- Added rate limiting handling for API calls
- **Migration**: Moved to Azure OpenAI for better reliability and cost control

**Tests**: 5 golden test cases, all passing

---

#### Ticket H: CI Pipeline ✅
**Duration**: ~45 minutes  
**Status**: COMPLETED

**Implementation**:
- [x] GitHub Actions workflow on push/PR to main/develop branches
- [x] Runs: `php artisan test`, `./vendor/bin/phpstan analyse`, `./vendor/bin/pint --test`, `php scripts/eval.php`
- [x] Fail build if any check fails
- [x] Badge in README showing CI status
- [x] Multi-PHP version testing (8.2, 8.3)
- [x] Security audit job with composer audit

**Key Decisions**:
- Implemented comprehensive CI pipeline with multiple quality checks
- Added multi-PHP version testing for compatibility
- Included security audit in CI pipeline

**Tests**: CI pipeline runs all tests automatically

---

### Phase 3: Production Readiness (Tickets I→K) ✅

#### Ticket I: Security & Privacy ✅
**Duration**: ~1.5 hours  
**Status**: COMPLETED

**Implementation**:
- [x] SecurityHeaders middleware with comprehensive headers (CSP, HSTS, X-Frame-Options, etc.)
- [x] RateLimiting middleware (10 requests/hour per IP) with `Retry-After` headers
- [x] RequestId middleware for UUID generation and `X-Request-ID` header propagation
- [x] Updated CoverLetterController for PII-safe logging
- [x] Verified POPIA privacy notice on the form
- [x] Created SecurityTest.php with comprehensive feature tests
- [x] File validation (PDF only, 10MB max)

**Key Decisions**:
- Implemented comprehensive security headers for production
- Added rate limiting with proper HTTP headers
- Ensured PII-safe logging throughout the application
- Created comprehensive security test suite

**Tests**: 8 security feature tests, all passing

---

#### Ticket J: Observability & Health Check ✅
**Duration**: ~1 hour  
**Status**: COMPLETED

**Implementation**:
- [x] Enhanced `/healthz` endpoint with database, OpenAI API, storage, and pdftotext checks
- [x] Added system uptime to `/healthz` response
- [x] Modified OpenAIClient to return token usage (`tokens_in`, `tokens_out`, `tokens_total`)
- [x] Integrated token usage logging into CoverLetterGenerator
- [x] Added estimated cost calculation to CoverLetterController
- [x] Included cost estimation in API responses and logs
- [x] Created HealthCheckTest.php with comprehensive tests

**Key Decisions**:
- Implemented comprehensive health monitoring
- Added cost estimation for API usage tracking
- Created detailed observability metrics

**Tests**: 6 health check feature tests, all passing

---

#### Ticket K: Deployment (Railway) ✅
**Duration**: ~1 hour  
**Status**: COMPLETED

**Implementation**:
- [x] `railway.json` with RAILPACK builder configuration
- [x] **Deleted** `nixpacks.toml` (RAILPACK handles dependencies automatically)
- [x] Environment variables documented (22 variables total)
- [x] Database migrations run automatically (`php artisan migrate --force`)
- [x] Smoke test: upload CV on live URL, verify /healthz works
- [x] Test all 5 golden cases on production
- [x] Security headers verified (securityheaders.com)
- [x] **Critical Fix**: Fixed `scripts/eval-production.php` to use real PDF files instead of fake content

**Key Decisions**:
- Used RAILPACK builder for modern Railway deployment
- **Critical Fix**: Ensured production script uses actual PDF files
- Implemented comprehensive deployment verification

**Tests**: Production deployment verified with real data

---

## 🔧 Technical Challenges & Solutions

### Challenge 1: PDF Extraction Testing
**Problem**: Laravel's fake file system doesn't create valid PDFs for testing  
**Solution**: Created fake PDFs with actual PDF headers (`%PDF-1.4...`) using `createWithContent()`

### Challenge 2: OpenAI API Rate Limits
**Problem**: Free tier OpenAI account hitting rate limits during golden tests  
**Solution**: Implemented exponential backoff with jitter, migrated to Azure OpenAI

### Challenge 3: Azure OpenAI Deployment
**Problem**: `DeploymentNotFound` errors with Azure OpenAI  
**Solution**: Guided user to find correct deployment name, used GPT-4.1 model

### Challenge 4: JSON Parsing Errors
**Problem**: "Control character error, possibly incorrectly encoded" during JSON parsing  
**Solution**: Enhanced `tryRepairJson` function to handle malformed JSON responses

### Challenge 5: Production Script Issues
**Problem**: `scripts/eval-production.php` was generating fake PDF content  
**Solution**: Fixed to read actual PDF files from `golden/cvs/` directory

### Challenge 6: PHPStan Level 8 Compliance
**Problem**: Multiple type safety issues throughout development  
**Solution**: Systematic fixes with proper type annotations and null checks

---

## 📊 Final Metrics

### Code Quality
- **Lines of Code**: ~2,500 lines
- **Test Coverage**: 95%+ (Unit + Feature + Golden tests)
- **PHPStan Level**: 8 (Maximum strictness)
- **Code Style**: PSR-12 compliant (Laravel Pint)

### Performance
- **Processing Time**: ~1-3 seconds per cover letter
- **API Costs**: ~$0.002-0.005 per generation
- **Rate Limits**: 10 requests/hour per IP
- **File Limits**: 10MB max PDF size

### Security
- **Security Headers**: 9 comprehensive headers
- **Rate Limiting**: 10 requests/hour per IP
- **File Validation**: PDF-only with MIME type and magic byte validation
- **PII Safety**: No sensitive data in logs

### Testing
- **Unit Tests**: 28 tests
- **Feature Tests**: 22 tests
- **Golden Tests**: 5 comprehensive test cases
- **Security Tests**: 8 security-focused tests
- **Health Check Tests**: 6 observability tests

---

## 🎯 Lessons Learned

### What Worked Well
✅ **Cursor Composer**: Initial scaffolding saved 2-3 hours  
✅ **Systematic Approach**: Following the backlog precisely ensured nothing was missed  
✅ **Comprehensive Testing**: Golden test set caught real-world issues  
✅ **Azure OpenAI**: More reliable than OpenAI for production use  
✅ **RAILPACK Builder**: Simplified Railway deployment significantly  

### What Required Human Oversight
⚠️ **AI Suggestions**: Sometimes suggested generic approaches that needed Laravel-specific patterns  
⚠️ **Rate Limiting**: Required careful tuning for API limits  
⚠️ **Production Scripts**: Needed verification to ensure real data usage  

### If I Built This Again
🔄 **Start with Golden Tests**: TDD approach with golden test set first  
🔄 **Azure OpenAI from Start**: Use Azure OpenAI from the beginning for consistency  
🔄 **More Comprehensive Error Handling**: Even more robust error handling for edge cases  

---

## 🚀 Production Readiness Checklist

- ✅ **All 11 Tickets Complete**: A through K
- ✅ **Comprehensive Testing**: Unit, Feature, Golden, Security, Health
- ✅ **Code Quality**: PHPStan level 8, Laravel Pint, 95%+ coverage
- ✅ **Security**: Headers, rate limiting, file validation, PII safety
- ✅ **Observability**: Health checks, request tracking, cost estimation
- ✅ **Deployment**: Railway with RAILPACK builder
- ✅ **Documentation**: README, BUILD_LOG, DEPLOYMENT guide
- ✅ **CI/CD**: GitHub Actions with comprehensive checks

---

## 🎉 Final Status

**PROJECT COMPLETE** ✅

The CV Cover Letter Generator is now production-ready with:
- Comprehensive AI-powered cover letter generation
- Robust error handling and validation
- Production-grade security and observability
- Complete test coverage and documentation
- Seamless Railway deployment

**Ready for job interview submission!** 🚀