<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CV Cover Letter Generator') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Enhanced styles with accessibility and mobile responsiveness -->
    <style>
            :root {
                --primary-color: #2E7D32;
                --primary-hover: #1B5E20;
                --secondary-color: #4CAF50;
                --accent-color: #8BC34A;
                --light-green: #6A9C6A;
                --success-color: #4CAF50;
                --success-bg: #E8F5E8;
                --error-color: #721c24;
                --error-bg: #f8d7da;
                --text-color: #333;
                --text-muted: #666;
                --border-color: #ddd;
                --background: #ffffff;
                --white: #ffffff;
                --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                --border-radius: 8px;
                --spacing-sm: 10px;
                --spacing-md: 20px;
                --spacing-lg: 30px;
            }

        * {
            box-sizing: border-box;
        }

        body { 
            font-family: 'Figtree', Arial, sans-serif; 
            margin: 0; 
            padding: var(--spacing-md); /* Add padding back */
            background-color: var(--background);
            line-height: 1.6;
            color: var(--text-color);
            min-height: 100vh;
        }

        .container { 
            max-width: 1200px; /* Reasonable max width */
            margin: 0 auto; /* Center the container */
            background: var(--white); 
            padding: var(--spacing-lg); 
            border-radius: 16px; /* Curved edges */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            min-height: calc(100vh - 2 * var(--spacing-md)); /* Account for body padding */
        }

        .header-section {
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }

        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            width: 100%;
            position: relative;
        }

        .company-logo {
            height: 40px;
            width: auto;
            position: absolute;
            left: 20px;
        }

        .app-logo {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: center;
        }

        .logo-text {
            font-size: 3rem;
            font-weight: 700;
            color: var(--light-green);
            letter-spacing: 3px;
        }

        .green-l {
            color: var(--text-color);
            font-weight: 800;
        }

        h1 { 
            text-align: center; 
            color: var(--text-muted); 
            margin-bottom: 0; /* No space after title */
            font-size: 1.2rem;
            font-weight: 400;
        }

        .tagline, .subtitle {
            text-align: center; 
            color: var(--text-muted); 
            margin-top: 0; /* No space before subtitle */
            margin-bottom: var(--spacing-lg);
            font-size: 1.1rem;
        }

        .form-group { 
            margin-bottom: var(--spacing-lg); /* More space between sections */
        }

        .form-section {
            margin-bottom: var(--spacing-lg); /* More space between form sections */
        }

        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 600;
            color: var(--text-color);
            text-align: center; /* Center the labels */
        }

        input[type="file"] {
            display: none; /* Hide the native file input - we use custom drag-and-drop */
        }

        textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid var(--border-color); 
            border-radius: 4px;
            font-size: 16px; /* Prevents zoom on iOS */
            transition: border-color 0.2s ease;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }

        .file-upload-container {
            max-width: 660px; /* 10% larger (600px * 1.10) */
            margin: 0 auto; /* Center it */
        }

        .textarea-container {
            max-width: 660px; /* 10% larger (600px * 1.10) */
            margin: 0 auto; /* Center it */
        }

        .submit-section {
            text-align: center; /* Center the button */
            margin-top: var(--spacing-lg); /* More space before submit button */
        }

        textarea { 
            height: 88px; /* 10% larger (80px * 1.10) */
            resize: vertical;
            font-family: inherit;
            min-height: 88px; /* 10% larger (80px * 1.10) */
            max-height: 220px; /* 10% larger (200px * 1.10) */
            border-radius: 12px; /* More rounded corners */
        }

        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px; /* More rounded corners */
            padding: var(--spacing-sm);
            text-align: center;
            background: #fafafa;
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 66px; /* 10% larger (60px * 1.10) */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: row;
            gap: var(--spacing-sm);
        }

        .file-upload-area:hover {
            border-color: var(--light-green);
            background: var(--success-bg);
        }

        .upload-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .upload-primary {
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }

        .upload-secondary {
            font-size: 12px;
            color: var(--text-muted);
        }

        .upload-icon {
            color: var(--light-green);
            flex-shrink: 0;
        }

        /* File Info Display (after file is selected) */
        .file-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--white);
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            gap: var(--spacing-sm);
            min-height: 60px;
        }

        .file-details {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex: 1;
        }

        .file-details::before {
            content: '';
            display: inline-block;
            width: 32px;
            height: 32px;
            background: var(--light-green);
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3C/svg%3E") no-repeat center;
            mask-size: contain;
            flex-shrink: 0;
        }

        .file-name {
            font-weight: 500;
            color: var(--text-color);
            font-size: 14px;
        }

        .file-size {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: 8px;
        }

        .remove-file {
            width: 32px;
            height: 32px;
            min-width: 32px;
            min-height: 32px;
            border-radius: 4px;
            background: var(--light-green);
            color: var(--white);
            border: none;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            line-height: 1;
            padding: 0;
            aspect-ratio: 1 / 1;
        }

        .remove-file:hover {
            background: #558855;
            transform: scale(1.1);
        }

        .remove-file:focus {
            outline: 2px solid var(--light-green);
            outline-offset: 2px;
        }

        button { 
            background: var(--light-green); 
            color: var(--white); 
            padding: 12px 24px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.2s ease;
            min-height: 44px; /* Touch-friendly */
        }

        button:hover:not(:disabled) { 
            background: var(--primary-color); 
        }

        button:disabled { 
            background: #ccc; 
            cursor: not-allowed; 
        }

        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.3);
        }

        .error { 
            background: var(--error-bg); 
            color: var(--error-color); 
            padding: var(--spacing-sm); 
            border-radius: 4px; 
            margin: var(--spacing-sm) 0;
            border-left: 4px solid var(--error-color);
        }

        .success { 
            background: var(--success-bg); 
            color: var(--success-color); 
            padding: var(--spacing-sm); 
            border-radius: 4px; 
            margin: var(--spacing-sm) 0;
            border-left: 4px solid var(--success-color);
        }

        .loading { 
            text-align: center; 
            padding: var(--spacing-md);
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .result-actions {
            margin-top: var(--spacing-md);
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .word-count-badge {
            background: var(--success-bg);
            color: var(--success-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-top: var(--spacing-sm);
        }

        .cover-letter-content {
            white-space: pre-line;
            line-height: 1.8;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--white);
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 4px;
            box-shadow: var(--shadow);
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .privacy-notice {
            margin-top: 150px; /* Push even more down */
            padding: var(--spacing-sm);
            background: var(--success-bg);
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            text-align: left;
            font-size: 10px;
        }

        .privacy-content {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }

        .privacy-icon {
            color: var(--light-green);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .privacy-text {
            flex: 1;
            font-size: 12px;
            color: var(--text-color);
            line-height: 1.4;
        }

        .privacy-text p {
            margin: 0 0 6px 0;
        }

        .privacy-text p:last-child {
            margin-bottom: 0;
        }

        .footer {
            margin-top: 40px; /* More space before footer */
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .footer-links {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12px;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: var(--text-color);
            text-decoration: underline;
        }

        .footer-separator {
            color: var(--text-muted);
            font-size: 12px;
        }

        .footer-copyright {
            color: var(--text-muted);
            font-size: 11px;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            .container {
                padding: var(--spacing-sm);
            }

            h1 {
                font-size: 1.5rem;
            }

            .result-actions {
                flex-direction: column;
            }

            .result-actions button {
                width: 100%;
            }

            .cover-letter-content {
                max-height: 300px;
                font-size: 14px;
                padding: 8px;
            }

            .toast {
                right: var(--spacing-sm);
                left: var(--spacing-sm);
                transform: translateY(-100%);
            }

            .toast.show {
                transform: translateY(0);
            }
        }

        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus indicators */
        *:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --border-color: #000;
                --text-color: #000;
                --background: #fff;
            }
        }

        /* Loading Card Styles */
        .loading-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: calc(var(--spacing-xl) + 20px) var(--spacing-xl); /* Extra padding at top */
            padding-bottom: calc(var(--spacing-xl) + 40px); /* Extra padding at bottom to prevent cutoff */
            margin-top: var(--spacing-lg);
            text-align: center;
            min-height: 400px; /* Ensure enough height for all content */
        }

        .loading-spinner-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: var(--spacing-md);
        }

        .loading-spinner {
            position: relative;
            width: 60px;
            height: 60px;
        }

        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--light-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin: var(--spacing-md) 0 var(--spacing-sm) 0;
        }

        .loading-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-lg);
            line-height: 1.6;
        }

        .loading-steps {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            max-width: 600px;
            margin: 0 auto;
            text-align: left;
        }

        .step {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius);
            background: #f9f9f9;
            opacity: 0.4;
            transition: all 0.3s ease;
        }

        .step.active {
            opacity: 1;
            background: var(--success-bg);
            border-left: 3px solid var(--light-green);
        }

        .step-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .step.active .step-icon {
            background: var(--light-green);
        }

        .step-icon svg {
            color: var(--white);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .step.active .step-icon svg {
            opacity: 1;
        }

        .step-text {
            font-size: 0.95rem;
            color: var(--text-color);
            font-weight: 500;
        }

        /* Error Card Styles */
        .alert-card {
            position: relative;
            background: var(--white);
            border: 2px solid #ef4444;
            border-radius: var(--border-radius);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-lg);
            display: flex;
            gap: var(--spacing-md);
        }

        .alert-close {
            position: absolute;
            top: var(--spacing-sm);
            right: var(--spacing-sm);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--text-muted);
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-close:hover {
            color: var(--text-color);
        }

        .alert-close:focus {
            outline: 2px solid var(--light-green);
            outline-offset: 2px;
            border-radius: 4px;
        }

        .alert-icon {
            flex-shrink: 0;
            color: #ef4444;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h4 {
            margin: 0 0 var(--spacing-sm) 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .alert-content > p {
            margin: 0 0 var(--spacing-md) 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .error-suggestions {
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }

        .error-help-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
            margin: 0 0 var(--spacing-sm) 0;
        }

        .error-help-list {
            margin: 0;
            padding-left: 20px;
            list-style-type: disc;
        }

        .error-help-list li {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 4px;
            line-height: 1.4;
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
