# Pre-Submission Quality Assurance Checklist

**Project**: CV-to-Cover-Letter Generator  
**Reviewer**: [Your Name]  
**Date**: [Submission Date]

---

## ✅ FUNCTIONALITY (Core Requirements)

- [ ] Generates 2-3 paragraph cover letters (150-300 words)
- [ ] Cover letter mentions company name from job description
- [ ] Cover letter mentions role title from job description
- [ ] No hallucinated skills (tested with Docker example)
- [ ] Works with 5/5 golden test cases
- [ ] Processing completes in <10 seconds (P95: 6.2s)
- [ ] Copy-to-clipboard works
- [ ] "Generate Another" resets form

---

## ✅ EDGE CASES (Robustness)

- [ ] Scanned PDF → "Could not extract text from PDF. Try text-based CV."
- [ ] Corrupt PDF → "Could not read PDF. Try a different file."
- [ ] Encrypted PDF → "PDF is password-protected. Upload unlocked file."
- [ ] 15-page CV → Truncated to 15k chars, no timeout
- [ ] Empty job description → "Please enter job description"
- [ ] Job description <50 chars → "Job description too short (min 50 chars)"
- [ ] Unicode names (João, François) → Handled correctly
- [ ] HTML in job ad → Stripped before processing
- [ ] Rapid duplicate submits → Button disabled during processing
- [ ] Non-PDF file (.docx) → "File must be PDF format"

---

## ✅ SECURITY (POPIA-Compliant)

### Secrets Management
- [ ] API keys in .env only (not committed)
- [ ] .env.example provided with comments
- [ ] .gitignore includes .env
- [ ] Key rotation instructions in README

### File Upload Security
- [ ] MIME type validation (finfo_file)
- [ ] Magic-byte validation (%PDF-)
- [ ] File size limit (10MB server-side)
- [ ] Temp storage outside web root (storage/app/temp/)
- [ ] Files deleted immediately after processing
- [ ] Unique filenames (UUID-based)

### Headers & Middleware
- [ ] CSRF protection enabled
- [ ] Content-Security-Policy header set (with nonce)
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] Referrer-Policy: no-referrer
- [ ] Strict-Transport-Security (HSTS)
- [ ] Rate limiting (10 requests/hour per IP)

### Privacy (POPIA)
- [ ] No PII in logs (only request_id, tokens, duration)
- [ ] CV text never logged
- [ ] Job description never logged
- [ ] Privacy notice displayed on form
- [ ] No persistent storage of CVs

### Dependencies
- [ ] composer.lock committed
- [ ] composer audit clean (no vulnerabilities)

---

## ✅ CODE QUALITY

### Static Analysis
- [ ] PHPStan level 8 passes (zero errors)
- [ ] Laravel Pint (PSR-12) formatted
- [ ] No unused imports
- [ ] Type hints on all methods

### Testing
- [ ] Unit tests pass (PdfExtractor, CoverLetterGenerator)
- [ ] Feature tests pass (form, validation, generation)
- [ ] Edge case tests pass (corrupt PDF, etc.)
- [ ] Security tests pass (CSRF, headers, file cleanup)
- [ ] Golden test set passes (5/5 cases, zero hallucinations)
- [ ] Test coverage >80% (services)

### Code Structure
- [ ] Service layer pattern (thin controllers)
- [ ] Custom exceptions (PdfExtractionException, AiGenerationException)
- [ ] Request validation classes
- [ ] Middleware for security headers
- [ ] No God objects (all classes <300 lines)

---

## ✅ UX/ACCESSIBILITY

### Responsiveness
- [ ] Works on mobile (tested iPhone 12, Pixel 5)
- [ ] Works on tablet (tested iPad)
- [ ] Works on desktop (tested Chrome, Firefox, Safari)
- [ ] Single column layout on <768px
- [ ] Touch-friendly file upload area (≥44px targets)

### Accessibility
- [ ] All inputs have <label> elements
- [ ] ARIA labels on buttons
- [ ] Keyboard navigation works (logical Tab order)
- [ ] Focus management (focus on result after generation)
- [ ] Color contrast ≥4.5:1 (WCAG AA)
- [ ] Error messages announced (ARIA live region)

### UX Polish
- [ ] Loading state with spinner
- [ ] Button disabled during processing
- [ ] Copy-to-clipboard with toast notification
- [ ] No layout shift when result appears
- [ ] Error messages styled consistently (red alert)
- [ ] Success state clear (green badge for word count)

---

## ✅ OBSERVABILITY & COST AWARENESS

### Logging
- [ ] Request ID correlation (UUID per request)
- [ ] Token usage logged (tokens_in, tokens_out, tokens_total)
- [ ] Latency tracked (duration_ms)
- [ ] Outcome tracked (success | error)
- [ ] No PII in logs (verified manually)

### Health Check
- [ ] /healthz endpoint returns 200 JSON
- [ ] Checks database connectivity
- [ ] Checks OpenAI API reachability
- [ ] Checks storage availability
- [ ] Returns 503 if degraded

### Cost Management
- [ ] CV text truncated to 15k chars
- [ ] Rate limiting protects budget (10/hour = max $7.20/day)
- [ ] Average cost per request documented (~$0.03)
- [ ] Token usage visible in logs

---

## ✅ DEPLOYMENT

### Railway
- [ ] Live URL accessible: https://[your-app].railway.app
- [ ] Environment variables set (OPENAI_API_KEY)
- [ ] pdftotext binary installed (verified)
- [ ] Database migrations run
- [ ] HTTPS enforced
- [ ] Health check endpoint reachable

### Configuration
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] Error logging enabled
- [ ] Rate limiting active

### Testing on Production
- [ ] Tested all 5 golden cases on live URL
- [ ] Tested edge cases (corrupt PDF, rate limit)
- [ ] Verified /healthz endpoint
- [ ] Verified security headers (checked with securityheaders.com)

---

## ✅ DOCUMENTATION

### README.md
- [ ] Project description
- [ ] Tech stack listed
- [ ] Installation instructions (<5 min setup)
- [ ] Environment variables documented
- [ ] Run instructions (php artisan serve)
- [ ] Test instructions (php artisan test)
- [ ] Deploy instructions (Railway)
- [ ] Cost & performance section
- [ ] Privacy/POPIA note
- [ ] E-commerce awareness section

### BUILD_LOG.md
- [ ] Tools used documented
- [ ] Prompt iterations documented
- [ ] AI vs hand-written code breakdown
- [ ] Debugging examples included
- [ ] Lessons learned section

### Other Docs
- [ ] .env.example with comments
- [ ] QA_CHECKLIST.md (this file)
- [ ] Code comments on complex logic

---

## ✅ SUBMISSION REQUIREMENTS

- [ ] GitHub repository created
- [ ] Clean commit history (not one giant commit)
- [ ] Live demo URL tested
- [ ] One-paragraph build summary written
- [ ] Email draft prepared with:
  - [ ] GitHub repository link
  - [ ] Live demo URL
  - [ ] Build summary
  - [ ] Screenshots (optional)

---

## 🎯 FINAL CHECKS

- [ ] No console errors in browser
- [ ] No PHP warnings/notices
- [ ] No 500 errors in logs
- [ ] All tests passing (php artisan test)
- [ ] PHPStan clean (./vendor/bin/phpstan analyse)
- [ ] Pint formatted (./vendor/bin/pint)
- [ ] composer audit clean

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
**Status**: ⏳ IN PROGRESS
