# CV Cover Letter Generator

<p align="center">
<a href="https://github.com/geraldsadya/clover/actions"><img src="https://github.com/geraldsadya/clover/workflows/CI%20Pipeline/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

An AI-powered web application that generates tailored cover letters from PDF CVs and job descriptions using Laravel 11 and Azure OpenAI.

## Features

- **PDF CV Processing**: Extract text from PDF files with validation
- **AI-Powered Generation**: Two-stage pipeline for accurate, non-hallucinated cover letters
- **Anti-Hallucination**: Golden test set ensures AI doesn't invent facts not in CV
- **Modern UI**: Responsive design with Alpine.js and accessibility features
- **Production Ready**: PHPStan level 8, security headers, rate limiting

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **AI**: Azure OpenAI (GPT-4.1)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS
- **Testing**: PHPUnit, Golden Test Set
- **Quality**: PHPStan level 8, Laravel Pint
- **CI/CD**: GitHub Actions

## Installation

1. Clone the repository:
```bash
git clone https://github.com/geraldsadya/clover.git
cd clover/cover-letter-app
```

2. Install dependencies:
```bash
composer install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure OpenAI API:
```bash
# Add your Azure OpenAI credentials to .env
OPENAI_API_KEY=your_api_key
OPENAI_BASE=https://your-resource.openai.azure.com/
MODEL=gpt-4.1
```

5. Run migrations:
```bash
php artisan migrate
```

6. Start the development server:
```bash
php artisan serve
```

## Usage

1. Upload a PDF CV
2. Paste the job description
3. Click "Generate Cover Letter"
4. Copy the generated cover letter

## Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run static analysis
./vendor/bin/phpstan analyse app/ --level=8

# Run code style check
./vendor/bin/pint --test

# Run golden test set
php scripts/eval.php
```

### CI/CD

The project uses GitHub Actions for continuous integration:

- **Tests**: PHPUnit test suite
- **Static Analysis**: PHPStan level 8
- **Code Style**: Laravel Pint
- **Golden Tests**: Anti-hallucination validation
- **Security**: Composer audit

## Architecture

### Two-Stage AI Pipeline

1. **Facts Extraction**: Extract structured facts from CV text
2. **Cover Letter Composition**: Generate cover letter using only extracted facts

This prevents AI hallucinations and ensures accuracy.

### Golden Test Set

Comprehensive test suite with 5 CV/Job pairs that validates:
- Word count (150-300 words)
- Company/role mentions
- No banned phrases (anti-hallucination)

## Security

- Rate limiting (10 requests/hour per IP)
- Security headers (CSP, HSTS, X-Frame-Options)
- POPIA compliance
- No PII in logs
- File validation (PDF only, 10MB max)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and static analysis
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).