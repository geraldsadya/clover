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
            --primary-color: #007cba;
            --primary-hover: #005a87;
            --success-color: #155724;
            --success-bg: #d4edda;
            --error-color: #721c24;
            --error-bg: #f8d7da;
            --text-color: #333;
            --text-muted: #666;
            --border-color: #ddd;
            --background: #f5f5f5;
            --white: #ffffff;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            padding: var(--spacing-md); 
            background-color: var(--background);
            line-height: 1.6;
            color: var(--text-color);
        }

        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: var(--white); 
            padding: var(--spacing-md); 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow);
        }

        h1 { 
            text-align: center; 
            color: var(--text-color); 
            margin-bottom: var(--spacing-lg);
            font-size: 2rem;
            font-weight: 600;
        }

        .tagline {
            text-align: center; 
            color: var(--text-muted); 
            margin-bottom: var(--spacing-lg);
            font-size: 1.1rem;
        }

        .form-group { 
            margin-bottom: var(--spacing-md); 
        }

        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 600;
            color: var(--text-color);
        }

        input[type="file"], textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid var(--border-color); 
            border-radius: 4px;
            font-size: 16px; /* Prevents zoom on iOS */
            transition: border-color 0.2s ease;
        }

        input[type="file"]:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
        }

        textarea { 
            height: 120px; 
            resize: vertical;
            font-family: inherit;
            min-height: 120px;
            max-height: 400px;
        }

        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 4px;
            padding: var(--spacing-md);
            text-align: center;
            background: #fafafa;
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: #f0f8ff;
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: #e6f3ff;
        }

        button { 
            background: var(--primary-color); 
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
            background: var(--primary-hover); 
        }

        button:disabled { 
            background: #ccc; 
            cursor: not-allowed; 
        }

        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.3);
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
            text-align: center; 
            margin-top: var(--spacing-lg); 
            font-size: 12px; 
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            body {
                padding: var(--spacing-sm);
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
