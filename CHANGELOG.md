# Changelog

All notable changes to the CV Cover Letter Generator project will be documented in this file.

## [1.1.0] - 2025-01-22

### 🚀 Major Features

#### CV Validation System (Zero-Cost Edge Case Protection)
- **NEW**: Implemented deterministic CV validation using keyword scoring
- **NEW**: Pre-extraction validation rejects non-CV documents before expensive AI calls
- **NEW**: Post-extraction validation prevents legal terminology in CV fields
- **NEW**: Detailed logging with validation scores for debugging
- **NEW**: Specific error messages for different rejection reasons

#### Technical Implementation
- **Added**: `validateIsCV()` method with weighted keyword scoring
- **Added**: Strong CV indicators (work experience, skills, education) with +2 to +5 points
- **Added**: Weak CV indicators (references, profile, certifications) with +1 points
- **Added**: Non-CV penalties (MOU, contracts, invoices) with -3 to -15 points
- **Added**: Threshold-based validation (score ≥ 5 = valid CV)
- **Added**: Enhanced `validateFactsSchema()` with stricter requirements
- **Added**: Name validation to reject legal entities ("Party A", "Company XYZ")
- **Added**: Skills validation to reject legal terms ("whereas", "hereby", "agreement")

#### User Experience Improvements
- **Enhanced**: Error messages with specific, actionable feedback
- **Enhanced**: Dismissible error alerts with 'X' button
- **Enhanced**: Loading states with detailed progress steps
- **Enhanced**: File upload with drag-and-drop and visual feedback
- **Enhanced**: Word count flexibility (100-450 words for different experience levels)

#### UI/UX Redesign
- **NEW**: CLOVER branding with green/black 'L' logo
- **NEW**: Silvertreebrands company logo integration
- **NEW**: Modern curved container design (1200px max-width)
- **NEW**: White background with green accent color scheme (#6A9C6A)
- **NEW**: Centered layout with improved spacing and decluttering
- **NEW**: Enhanced file preview with icon, name, size, and square remove button
- **NEW**: More rounded corners on input elements (12px border-radius)
- **NEW**: Improved privacy notice and footer positioning

### 🔧 Technical Improvements

#### Performance & Cost Optimization
- **IMPROVED**: Zero API cost for rejected non-CV documents
- **IMPROVED**: <1ms validation time using pure PHP
- **IMPROVED**: Early rejection saves processing time and resources
- **IMPROVED**: Enhanced logging for observability and debugging

#### Code Quality
- **IMPROVED**: PHPStan level 8 compliance maintained
- **IMPROVED**: Comprehensive error handling with specific error codes
- **IMPROVED**: Layered validation approach (pre + post extraction)
- **IMPROVED**: Defensive programming against edge cases

#### Dependencies
- **UPDATED**: Smalot/PdfParser for pure PHP PDF processing
- **UPDATED**: OpenAI max_tokens increased to 600 for longer outputs
- **UPDATED**: Word count range expanded to 100-450 words
- **REMOVED**: External PDF dependencies (poppler-utils no longer required)

### 🐛 Bug Fixes

- **FIXED**: Toast notification visibility bug
- **FIXED**: Loading state box text cutoff issues
- **FIXED**: File upload icon transparency
- **FIXED**: Square remove button styling (was circular)
- **FIXED**: Conflicting CSS between view and layout files
- **FIXED**: Rate limit persistence issues with session clearing

### 📚 Documentation

- **UPDATED**: README.md with CV validation feature documentation
- **UPDATED**: BUILD_SUMMARY.md with technical stack details
- **UPDATED**: Architecture documentation with three-stage pipeline
- **UPDATED**: Prerequisites to reflect Smalot/PdfParser usage
- **NEW**: CHANGELOG.md for version tracking

### 🧪 Testing

- **NEW**: Test cases for CV validation system
- **NEW**: Edge case testing (MOU, contracts, invoices)
- **NEW**: Validation score logging verification
- **NEW**: Error message specificity testing

## [1.0.0] - 2025-01-21

### 🚀 Initial Release

#### Core Features
- **NEW**: Laravel 11 web application for cover letter generation
- **NEW**: Two-stage AI pipeline (facts extraction + cover letter composition)
- **NEW**: Azure OpenAI GPT-4.1 integration
- **NEW**: PDF text extraction with Smalot/PdfParser
- **NEW**: Anti-hallucination measures with golden test set
- **NEW**: Comprehensive security implementation (rate limiting, headers, POPIA)
- **NEW**: Production deployment on Railway with RAILPACK builder

#### Technical Foundation
- **NEW**: PHPStan level 8 static analysis
- **NEW**: Comprehensive testing suite (Unit + Feature tests)
- **NEW**: Health check endpoints with component monitoring
- **NEW**: Request tracking with UUID correlation
- **NEW**: Cost estimation and performance metrics
- **NEW**: Security headers and CSRF protection
- **NEW**: Rate limiting (10 requests/hour per IP)

#### Quality Assurance
- **NEW**: Golden test set with 5 CV/Job pairs
- **NEW**: Anti-hallucination validation
- **NEW**: Word count validation (150-300 words)
- **NEW**: Company/role mention requirements
- **NEW**: Banned phrase detection

---

## Version Format

This project uses [Semantic Versioning](https://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

## Release Notes

Each release includes:
- 🚀 **Major Features**: New functionality and capabilities
- 🔧 **Technical Improvements**: Performance, security, and code quality enhancements
- 🐛 **Bug Fixes**: Issues resolved and improvements made
- 📚 **Documentation**: Updates to README, docs, and guides
- 🧪 **Testing**: New tests, test improvements, and quality assurance
