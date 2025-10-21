# CV Cover Letter Generator

<p align="center">
<a href="https://github.com/geraldsadya/clover/actions"><img src="https://github.com/geraldsadya/clover/workflows/CI%20Pipeline/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
<a href="https://github.com/geraldsadya/clover"><img src="https://img.shields.io/github/stars/geraldsadya/clover?style=social" alt="GitHub stars"></a>
</p>

An AI-powered web application that generates tailored cover letters from PDF CVs and job descriptions using Laravel 11 and Azure OpenAI. Built with surgical precision for production deployment.

## 🚀 Features

- **📄 PDF CV Processing**: Extract text from PDF files with comprehensive validation
- **🤖 AI-Powered Generation**: Two-stage pipeline for accurate, non-hallucinated cover letters
- **🛡️ Anti-Hallucination**: Golden test set ensures AI doesn't invent facts not in CV
- **📱 Modern UI**: Responsive design with Alpine.js and WCAG AA accessibility
- **🔒 Production Ready**: PHPStan level 8, security headers, rate limiting, POPIA compliance
- **⚡ Real-time Processing**: Live health checks, request tracking, cost estimation
- **🧪 Comprehensive Testing**: Unit tests, feature tests, golden test harness

## 🏗️ Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **AI**: Azure OpenAI (GPT-4.1)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS
- **Testing**: PHPUnit, Golden Test Set (5 CV/Job pairs)
- **Quality**: PHPStan level 8, Laravel Pint
- **CI/CD**: GitHub Actions
- **Deployment**: Railway (RAILPACK builder)

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js (for frontend assets)
- Azure OpenAI API key
- PDF processing: `poppler-utils` (for `pdftotext`)

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/geraldsadya/clover.git
cd clover/cover-letter-app
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Azure OpenAI

Add your Azure OpenAI credentials to `.env`:

```env
# Azure OpenAI Configuration
OPENAI_API_KEY=your_azure_openai_api_key
OPENAI_BASE=https://your-resource.openai.azure.com/
MODEL=gpt-4.1

# Optional: For local development
OPENAI_BASE=https://api.openai.com
MODEL=gpt-4o-mini
```

### 5. Database Setup

```bash
php artisan migrate
```

### 6. Frontend Assets

```bash
npm run build
```

### 7. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` to see the application.

## 🎯 Usage

### Web Interface

1. **Upload CV**: Select a PDF file (max 10MB)
2. **Job Description**: Paste the job posting (50-10,000 characters)
3. **Generate**: Click "Generate Cover Letter"
4. **Copy Result**: Use the copy button to copy the generated cover letter

### API Endpoints

#### Generate Cover Letter
```http
POST /generate
Content-Type: multipart/form-data

cv: [PDF file]
job_description: [text]
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cover_letter": "Dear Hiring Manager...",
    "word_count": 245,
    "request_id": "uuid-here"
  },
  "meta": {
    "processing_time_ms": 1250.5,
    "estimated_cost_usd": 0.0023
  }
}
```

#### Health Check
```http
GET /healthz
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-01-21T10:30:00Z",
  "uptime": "2h 15m 30s",
  "checks": {
    "database": "ok",
    "storage": "ok",
    "pdftotext": "ok",
    "openai": "ok"
  },
  "overallStatus": "ok"
}
```

## 🧪 Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run static analysis
./vendor/bin/phpstan analyse app/ --level=8

# Run code style check
./vendor/bin/pint --test

# Run golden test set
php scripts/eval.php

# Run production tests
php scripts/eval-production.php https://your-app.railway.app
```

### Code Quality

The project maintains high code quality standards:

- **PHPStan Level 8**: Maximum static analysis strictness
- **Laravel Pint**: PSR-12 code style compliance
- **Comprehensive Testing**: 95%+ test coverage
- **Security Headers**: CSP, HSTS, X-Frame-Options, etc.
- **Rate Limiting**: 10 requests/hour per IP

### Golden Test Set

The project includes a comprehensive golden test set with 5 CV/Job pairs that validates:

- **Word Count**: 150-300 words per cover letter
- **Company/Role Mentions**: Must mention the company and role
- **Anti-Hallucination**: No banned phrases or invented facts
- **Quality Assurance**: Real-world test cases

Run the golden tests:
```bash
php scripts/eval.php
```

## 🏗️ Architecture

### Two-Stage AI Pipeline

1. **Facts Extraction** (Stage 1)
   - Extract structured facts from CV text
   - JSON schema validation
   - Anti-hallucination measures

2. **Cover Letter Composition** (Stage 2)
   - Generate cover letter using only extracted facts
   - Word count validation (150-300 words)
   - Company/role integration

### Security Features

- **Rate Limiting**: 10 requests/hour per IP with `Retry-After` headers
- **Security Headers**: CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- **File Validation**: PDF-only uploads with MIME type and magic byte validation
- **POPIA Compliance**: Privacy notice and no PII in logs
- **CSRF Protection**: Laravel's built-in CSRF protection

### Observability

- **Health Checks**: `/healthz` endpoint with component status
- **Request Tracking**: UUID-based request correlation
- **Cost Estimation**: OpenAI API cost tracking
- **PII-Safe Logging**: No sensitive data in logs
- **Performance Metrics**: Processing time and token usage

## 🚀 Deployment

### Railway Deployment

The application is configured for Railway deployment using the RAILPACK builder:

1. **Connect Repository**: Link your GitHub repository to Railway
2. **Set Environment Variables**: Configure all required environment variables
3. **Deploy**: Railway automatically builds and deploys using `railway.json`

### Environment Variables

Required environment variables for production:

```env
# Application
APP_NAME="Cover Letter Generator"
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_URL=https://your-app.railway.app

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

# Azure OpenAI
OPENAI_API_KEY=your_azure_openai_api_key
OPENAI_BASE=https://your-resource.openai.azure.com/
MODEL=gpt-4.1

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info

# Cache & Sessions
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# File Storage
FILESYSTEM_DISK=local

# Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Railway
PORT=8000
RAILWAY_PUBLIC_DOMAIN=your-app.railway.app
RAILWAY_PRIVATE_DOMAIN=your-app.railway.internal
```

### Verification

After deployment, verify the application:

```bash
# Test health endpoint
curl https://your-app.railway.app/healthz

# Run golden tests against production
php scripts/eval-production.php https://your-app.railway.app

# Verify security headers
php scripts/verify-security-headers.php https://your-app.railway.app
```

## 📊 Performance

- **Processing Time**: ~1-3 seconds per cover letter
- **API Costs**: ~$0.002-0.005 per generation
- **Rate Limits**: 10 requests/hour per IP
- **File Limits**: 10MB max PDF size
- **Text Limits**: 15k chars CV, 10k chars job description

## 🔧 Troubleshooting

### Common Issues

1. **PDF Extraction Fails**
   - Ensure `poppler-utils` is installed
   - Check PDF is not scanned or encrypted
   - Verify file size is under 10MB

2. **OpenAI API Errors**
   - Verify API key and endpoint
   - Check rate limits and quotas
   - Ensure model is available in your region

3. **Health Check Fails**
   - Check database connectivity
   - Verify storage permissions
   - Ensure all environment variables are set

### Debug Mode

Enable debug mode for development:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests and static analysis: `php artisan test && ./vendor/bin/phpstan analyse`
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Maintain PHPStan level 8 compliance
- Update documentation for API changes
- Ensure all tests pass before submitting PR

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- **Laravel Framework**: The foundation of this application
- **Azure OpenAI**: AI-powered cover letter generation
- **Railway**: Seamless deployment platform
- **Spatie**: PDF text extraction capabilities

## 📞 Support

For support, please open an issue on GitHub or contact the maintainer.

---

**Built with ❤️ for job seekers everywhere**