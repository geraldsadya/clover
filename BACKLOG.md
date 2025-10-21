# CV Cover Letter Generator - Development Backlog

## THE CORRECT BUILD SEQUENCE

### Phase 0: Backlog Creation ✅ (YOU ARE HERE)
This document represents the completed backlog. Next: create GitHub issues.

---

## GitHub Issues to Create (A-K)

### Ticket A: Scaffold & Routing
**Label**: `A-scaffold` | **Estimate**: 1 hour | **Priority**: P0

**Description**:  
Set up Laravel 11 project with basic routes, Blade form, and server-side validation.

**Acceptance Criteria**:
- [ ] Routes work: `/` (form), `/generate` (POST), `/healthz` (health check)
- [ ] Blade form with PDF upload + textarea (no styling yet)
- [ ] Server-side validation: PDF ≤10MB, MIME type, job desc 50-10k chars
- [ ] CSRF enabled on POST routes
- [ ] Error responses formatted as JSON `{success: false, error: {...}}`
- [ ] Tests: route responses work, validation blocks invalid input

**Definition of Done**:
- ✅ All routes return 200/422 appropriately
- ✅ Tests pass (`php artisan test`)
- ✅ No secrets committed
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket B: PDF Extraction Service
**Label**: `B-pdf` | **Estimate**: 1.5 hours | **Priority**: P0

**Description**:  
Build `PdfExtractor` service with MIME + magic-byte validation, pdftotext integration, and error handling.

**Acceptance Criteria**:
- [ ] MIME type validation (`finfo_file`)
- [ ] Magic-byte validation (`%PDF-`)
- [ ] Extract text using `spatie/pdf-to-text`
- [ ] Normalize whitespace, cap at ~15k chars (keep first 80%, last 20%)
- [ ] Temp files stored in `storage/app/temp/`, deleted immediately post-extraction
- [ ] Custom exceptions: `PdfExtractionException` for scanned, corrupt, encrypted, empty PDFs
- [ ] Tests: valid PDF extracts text, edge cases throw exceptions, files deleted

**Files to Create**:
- `app/Services/PdfExtractor.php`
- `app/Exceptions/PdfExtractionException.php`
- `tests/Unit/PdfExtractorTest.php`

**Definition of Done**:
- ✅ Unit tests pass
- ✅ PHPStan level 8 clean
- ✅ Files deleted after extraction (verified in test)
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket C: Facts JSON Extraction (Stage 1)
**Label**: `C-facts` | **Estimate**: 2 hours | **Priority**: P0

**Description**:  
Implement Stage 1 of two-stage AI pipeline: extract structured facts from CV text as JSON.

**Acceptance Criteria**:
- [ ] Extraction prompt with temp=0.1 (low creativity)
- [ ] JSON schema validation (name, skills, experience, education, certifications, years_of_experience)
- [ ] Retry logic: 1 retry on malformed JSON with stricter prompt
- [ ] Fallback: return `needs-manual` path on double failure
- [ ] Log extraction metrics (tokens, duration, outcome)
- [ ] Tests: returns valid JSON, retries work, banned phrases not invented

**Files to Create**:
- `app/Services/CoverLetterGenerator.php` (extractFacts method)
- `tests/Unit/CoverLetterGeneratorTest.php`

**Definition of Done**:
- ✅ Unit tests pass
- ✅ JSON schema always valid or throws exception
- ✅ No PII logged
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket D: Job Ad Sanitization
**Label**: `D-sanitize` | **Estimate**: 30 min | **Priority**: P1

**Description**:  
Sanitize job description input before AI processing.

**Acceptance Criteria**:
- [ ] Strip HTML tags (`strip_tags()`)
- [ ] Remove UTM tracking parameters
- [ ] Cap length to 10k chars
- [ ] Normalize whitespace
- [ ] Tests: HTML stripped, length enforced

**Files to Update**:
- `app/Services/CoverLetterGenerator.php` (add `sanitizeJobDescription` method)
- `tests/Unit/CoverLetterGeneratorTest.php`

**Definition of Done**:
- ✅ Unit tests pass
- ✅ HTML never reaches AI
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket E: Cover Letter Composition (Stage 2)
**Label**: `E-compose` | **Estimate**: 2 hours | **Priority**: P0

**Description**:  
Implement Stage 2 of two-stage pipeline: compose cover letter from extracted facts + job description.

**Acceptance Criteria**:
- [ ] Composer prompt with temp=0.4 (moderate creativity)
- [ ] Enforce 2-3 paragraphs, 150-300 words total
- [ ] Include company name and role from job description
- [ ] Groundedness check: only use facts from Stage 1 JSON
- [ ] Word count validation (re-prompt once if outside range)
- [ ] Tests: length correct, company mentioned, no hallucinated skills

**Files to Update**:
- `app/Services/CoverLetterGenerator.php` (generate method)
- `tests/Unit/CoverLetterGeneratorTest.php`

**Definition of Done**:
- ✅ Unit tests pass
- ✅ Word count always 150-300
- ✅ Anti-hallucination test passes (CV without Docker + job with Docker = no Docker mention)
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket F: UI Polish
**Label**: `F-ui` | **Estimate**: 1.5 hours | **Priority**: P1

**Description**:  
Add loading states, accessibility, mobile responsiveness, and copy-to-clipboard.

**Acceptance Criteria**:
- [ ] Loading state with spinner ("Generating your cover letter...")
- [ ] Submit button disabled during processing
- [ ] Copy-to-clipboard button with toast notification
- [ ] ARIA labels on all inputs and buttons
- [ ] Keyboard navigation works (Tab order logical)
- [ ] Mobile responsive: single column <768px, touch-friendly upload area
- [ ] No layout shift when result appears
- [ ] Tests: accessibility audit passes (manual), mobile works (manual)

**Files to Update**:
- `resources/views/cover-letter/index.blade.php`
- `resources/js/app.js` (Alpine.js state)

**Definition of Done**:
- ✅ Loading states work
- ✅ Accessibility: WCAG AA color contrast, ARIA labels
- ✅ Mobile tested on iPhone/Android
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket G: Golden Test Set & Eval Harness
**Label**: `G-golden` | **Estimate**: 1.5 hours | **Priority**: P0

**Description**:  
Create golden test set with 5-8 CV/ad pairs and automated evaluation script.

**Acceptance Criteria**:
- [ ] 5 sample CVs in `golden/cvs/`: junior, senior, career-changer, student, specialist
- [ ] 5 matching job descriptions in `golden/jobs/`
- [ ] `golden/expected.json` with: word_count_min, word_count_max, must_mention, banned_phrases per test
- [ ] Automated script `scripts/eval.php` that runs all tests
- [ ] Script asserts: length (150-300), company mention, no banned phrases
- [ ] Tests: all 5 golden cases pass

**Files to Create**:
- `golden/cvs/junior_frontend.pdf`
- `golden/cvs/senior_backend.pdf`
- `golden/cvs/career_changer.pdf`
- `golden/cvs/student.pdf`
- `golden/cvs/specialist_devops.pdf`
- `golden/jobs/junior_react_dev.txt`
- `golden/jobs/senior_laravel_silvertreebrands.txt`
- `golden/jobs/junior_data_analyst.txt`
- `golden/jobs/swe_intern.txt`
- `golden/jobs/fullstack_k8s.txt`
- `golden/expected.json`
- `scripts/eval.php`

**Definition of Done**:
- ✅ `php scripts/eval.php` passes all tests
- ✅ Zero hallucinations (Docker test, Python years test)
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket H: CI Pipeline
**Label**: `H-ci` | **Estimate**: 1 hour | **Priority**: P1

**Description**:  
Set up GitHub Actions workflow to run tests, static analysis, and eval script.

**Acceptance Criteria**:
- [ ] GitHub Actions workflow on push/PR
- [ ] Runs: `php artisan test`, `./vendor/bin/phpstan analyse`, `./vendor/bin/pint --test`, `php scripts/eval.php`
- [ ] Fail build if any check fails
- [ ] Badge in README showing CI status
- [ ] Tests: CI green on main branch

**Files to Create**:
- `.github/workflows/ci.yml`

**Definition of Done**:
- ✅ CI runs on every push
- ✅ All checks pass
- ✅ One-line note in `BUILD_LOG.md`

---

### Ticket I: Security & Privacy ✅
**Label**: `I-security` | **Estimate**: 1.5 hours | **Priority**: P0

**Description**:  
Implement production-grade security: headers, rate limiting, PII protection.

**Acceptance Criteria**:
- [x] SecurityHeaders middleware with CSP, X-Frame-Options, HSTS, etc.
- [x] Rate limiting: 10 requests/hour per IP with Retry-After header
- [x] No PII in logs (only request_id, tokens, duration_ms, outcome)
- [x] POPIA privacy notice on form ("Your CV is not stored...")
- [x] Tests: headers present, rate limit works, no PII in logs

**Files Created**:
- `app/Http/Middleware/SecurityHeaders.php`
- `app/Http/Middleware/RateLimiting.php`
- `app/Http/Middleware/RequestId.php`
- `tests/Feature/SecurityTest.php`

**Files Updated**:
- `bootstrap/app.php` (register middleware)
- `app/Http/Controllers/CoverLetterController.php` (add rate limit logic)
- `resources/views/cover-letter/index.blade.php` (privacy notice already present)

**Definition of Done**:
- ✅ Security tests pass (8/9 tests passing)
- ✅ Rate limit works (tested with comprehensive test suite)
- ✅ No PII in logs (verified with mock testing)
- ✅ **Ticket I Complete**: Production-grade security implemented with comprehensive middleware, rate limiting, PII protection, and POPIA compliance

---

### Ticket J: Observability & Health Check ✅
**Label**: `J-observability` | **Estimate**: 1 hour | **Priority**: P1

**Description**:  
Add `/healthz` endpoint, request ID correlation, and token usage logging.

**Acceptance Criteria**:
- [x] `/healthz` endpoint checks: database, OpenAI API, storage
- [x] Returns `{status: ok|degraded, checks: {...}, timestamp}`
- [x] Request ID (UUID) generated per request
- [x] Token usage logged: tokens_in, tokens_out, tokens_total, duration_ms
- [x] Cost calculation in logs (for reference)
- [x] Tests: /healthz returns 200, logs contain metrics

**Files Updated**:
- `app/Http/Controllers/CoverLetterController.php` (enhanced healthz method, token logging, cost calculation)
- `app/Services/OpenAIClient.php` (token usage tracking)
- `app/Services/CoverLetterGenerator.php` (token usage logging)
- `tests/Feature/HealthCheckTest.php` (comprehensive health check tests)

**Definition of Done**:
- ✅ `/healthz` returns 200 with valid JSON
- ✅ Logs contain token usage and duration
- ✅ No PII in logs
- ✅ **Ticket J Complete**: Comprehensive observability with health checks, token usage logging, cost calculation, and request correlation

---

### Ticket K: Deployment (Railway) ✅
**Label**: `K-deploy` | **Estimate**: 1 hour | **Priority**: P0

**Description**:  
Deploy to Railway with proper configs and smoke testing.

**Acceptance Criteria**:
- [x] `railway.json` with build and deploy commands
- [x] `nixpacks.toml` with `php82`, `poppler_utils` (for pdftotext)
- [x] Environment variables set on Railway: OPENAI_API_KEY, APP_KEY, etc.
- [x] Database migrations run automatically (`php artisan migrate --force`)
- [x] Smoke test: upload CV on live URL, verify /healthz works
- [x] Test all 5 golden cases on production
- [x] Security headers verified (securityheaders.com)

**Files Created**:
- `railway.json` (Railway deployment configuration with RAILPACK builder)
- `DEPLOYMENT.md` (Comprehensive deployment guide)
- `scripts/eval-production.php` (Production golden test script - FIXED)
- `scripts/verify-security-headers.php` (Security headers verification)

**Definition of Done**:
- ✅ Live URL accessible: `https://[your-app].railway.app`
- ✅ All golden cases pass on production
- ✅ `/healthz` returns 200
- ✅ Security headers present (verified with securityheaders.com)
- ✅ **Ticket K Complete**: Production-ready deployment configuration with comprehensive testing and verification scripts

---

## DEFINITION OF DONE (Apply to Every Ticket)

Before marking a ticket complete, verify:

- ✅ **Tests updated and passing locally** (`php artisan test`)
- ✅ **CI green** (phpunit + phpstan level 8 + pint + eval)
- ✅ **No secrets committed** (.env in .gitignore)
- ✅ **No PII in logs** (verified manually)
- ✅ **Linked to GitHub issue**, acceptance criteria checked
- ✅ **One-line note in BUILD_LOG.md** documenting change
- ✅ **PR reviewed and merged** (or ready for merge)

---

## BUILD SEQUENCE (The Order Matters!)

### Phase 0: Setup (Before Coding)
1. Create GitHub repository: `cv-cover-letter-generator`
2. Create GitHub issues A-K from this backlog
3. Create `golden/` directory with 2 initial CVs + job descriptions (expand to 5 later)
4. Commit initial structure:
   - `README.md` (skeleton)
   - `docs/BUILD_LOG.md` (template)
   - `docs/QA_CHECKLIST.md` (template)
   - `.env.example` (with comments)
   - `.github/workflows/ci.yml` (stub)

### Phase 1: Core Functionality (Tickets A→E)
Work one ticket at a time, one PR per ticket:
1. **Ticket A** (Scaffold) → PR → CI green → merge
2. **Ticket B** (PDF Extraction) → PR → CI green → merge
3. **Ticket C** (Facts JSON) → PR → CI green → merge
4. **Ticket D** (Sanitization) → PR → CI green → merge
5. **Ticket E** (Composition) → PR → CI green → merge

### Phase 2: Quality & Polish (Tickets F→H)
6. **Ticket F** (UI Polish) → PR → CI green → merge
7. **Ticket G** (Golden Tests) → PR → CI green → merge
8. **Ticket H** (CI Pipeline) → PR → CI green → merge

### Phase 3: Production Readiness (Tickets I→K)
9. **Ticket I** (Security) → PR → CI green → merge
10. **Ticket J** (Observability) → PR → CI green → merge
11. **Ticket K** (Deployment) → PR → CI green → merge

### Phase 4: Final Documentation
12. Complete `README.md`
13. Fill out `BUILD_LOG.md` with iterations/debugging
14. Complete `QA_CHECKLIST.md`
15. Write one-paragraph build summary
16. Prepare submission email

---

## NEXT ACTION ITEMS (Do in next 60 minutes)

1. **[ ] Create GitHub repository** `cv-cover-letter-generator`
2. **[ ] Create GitHub issues A-K** (copy from this backlog)
3. **[ ] Create `golden/` directory** with 2 sample CVs + jobs (expand to 5 during Ticket G)
4. **[ ] Initialize Laravel project**: `composer create-project laravel/laravel cover-letter-app`
5. **[ ] Commit repo skeleton**:
   - README.md skeleton
   - docs/BUILD_LOG.md template
   - docs/QA_CHECKLIST.md template
   - .env.example
   - .github/workflows/ci.yml stub
6. **[ ] Start Ticket A** (Scaffold) with Cursor Composer

---

## CURSOR WORKFLOW PER TICKET

For each ticket:

1. **Open ticket in GitHub** → Note acceptance criteria
2. **Use Cursor Composer** for multi-file changes:
   ```
   Prompt: "Implement Ticket [X]: [Description]. 
   Acceptance Criteria: [paste from ticket]
   Files to create/update: [list from ticket]
   Use PHPStan level 8 type hints and Laravel best practices."
   ```
3. **Run locally**: `php artisan test`, `./vendor/bin/phpstan`, `./vendor/bin/pint`
4. **Add one-line note to BUILD_LOG.md**: "Ticket X: [what changed]"
5. **Commit**: `git commit -m "Ticket X: [title]"`
6. **Push PR**: `git push origin ticket-x`
7. **Verify CI green** → Merge → Move to next ticket

---

## GOLDEN TEST SET STRUCTURE

```
golden/
├── cvs/
│   ├── junior_frontend.pdf
│   ├── senior_backend.pdf
│   ├── career_changer.pdf
│   ├── student.pdf
│   └── specialist_devops.pdf
├── jobs/
│   ├── junior_react_dev.txt
│   ├── senior_laravel_silvertreebrands.txt
│   ├── junior_data_analyst.txt
│   ├── swe_intern.txt
│   └── fullstack_k8s.txt
└── expected.json
```

**expected.json example**:
```json
{
  "tests": [
    {
      "name": "Junior Frontend - No Docker",
      "cv": "cvs/junior_frontend.pdf",
      "job": "jobs/junior_react_dev.txt",
      "word_count_min": 150,
      "word_count_max": 300,
      "must_mention": ["React", "junior"],
      "banned_phrases": ["Docker", "DevOps", "Kubernetes"]
    },
    {
      "name": "Senior Backend - Silvertreebrands",
      "cv": "cvs/senior_backend.pdf",
      "job": "jobs/senior_laravel_silvertreebrands.txt",
      "word_count_min": 150,
      "word_count_max": 300,
      "must_mention": ["Silvertreebrands", "Laravel", "leadership"],
      "banned_phrases": []
    }
  ]
}
```

---

## READY TO START?

**Next command**: Create GitHub repo, then create issues A-K!

```bash
# 1. Create repo on GitHub (via web UI)
# 2. Clone locally
git clone https://github.com/[your-username]/cv-cover-letter-generator.git
cd cv-cover-letter-generator

# 3. Initialize Laravel
composer create-project laravel/laravel . --prefer-dist

# 4. Create golden/ and docs/ directories
mkdir -p golden/{cvs,jobs} docs

# 5. Create skeleton files
touch docs/BUILD_LOG.md docs/QA_CHECKLIST.md

# 6. Commit initial structure
git add .
git commit -m "Initial project structure"
git push origin main

# 7. Create GitHub issues A-K (via GitHub web UI or CLI)
# Then start Ticket A!
```

