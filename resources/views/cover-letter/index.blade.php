@extends('layouts.app')

@section('content')
<div x-data="coverLetterApp()" x-init="init()">
    <h1>CV Cover Letter Generator</h1>
    <p class="tagline">Upload your CV and job description to generate a tailored cover letter</p>

    <!-- Form Card -->
    <form @submit.prevent="submit()" enctype="multipart/form-data" role="form" aria-label="Cover letter generation form">
        @csrf
        
        <div class="form-group">
            <label for="cv" id="cv-label">Upload Your CV (PDF)</label>
            <div class="file-upload-area" 
                 @click="$refs.cvInput.click()"
                 @dragover.prevent="dragover = true"
                 @dragleave.prevent="dragover = false"
                 @drop.prevent="handleFileDrop($event)"
                 :class="{ 'dragover': dragover }"
                 role="button"
                 tabindex="0"
                 @keydown.enter="$refs.cvInput.click()"
                 aria-describedby="cv-help">
                <input type="file" 
                       id="cv" 
                       name="cv" 
                       x-ref="cvInput"
                       @change="handleFileSelect($event)"
                       accept=".pdf" 
                       required
                       aria-labelledby="cv-label"
                       aria-describedby="cv-help"
                       style="display: none;">
                <div x-show="!selectedFile" class="upload-placeholder">
                    <strong>Click to upload</strong> or drag and drop your PDF here
                </div>
                <div x-show="selectedFile" class="file-selected">
                    <strong>Selected:</strong> <span x-text="selectedFile"></span>
                </div>
            </div>
            <small id="cv-help" style="color: var(--text-muted);">PDF up to 10MB</small>
        </div>

        <div class="form-group">
            <label for="job_description" id="job-label">Job Description</label>
            <textarea id="job_description" 
                      name="job_description" 
                      x-model="jobDescription"
                      @input="autoExpand($event.target)"
                      placeholder="Paste the job description here..." 
                      required
                      aria-labelledby="job-label"
                      aria-describedby="job-help"
                      rows="6"></textarea>
            <small id="job-help" style="color: var(--text-muted);">Minimum 50 characters, maximum 10,000 characters</small>
        </div>

        <div style="text-align: center;">
            <button type="submit" 
                    :disabled="loading"
                    :aria-busy="loading"
                    aria-describedby="submit-help">
                <span x-show="!loading">Generate Cover Letter</span>
                <span x-show="loading">
                    <span class="spinner" aria-hidden="true"></span>
                    Generating your cover letter...
                </span>
            </button>
            <div id="submit-help" class="sr-only" x-show="loading">
                Please wait while your cover letter is being generated.
            </div>
        </div>
    </form>

    <!-- Loading State -->
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="loading" 
         role="status" 
         aria-live="polite"
         aria-label="Generating cover letter">
        <div class="spinner" aria-hidden="true"></div>
        <p>Generating your cover letter...</p>
    </div>

    <!-- Error Alert -->
    <div x-show="error" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="error" 
         role="alert"
         aria-live="assertive">
        <span x-text="error"></span>
    </div>

    <!-- Result Card -->
    <div x-show="result" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="success"
         role="region"
         aria-labelledby="result-heading">
        <h3 id="result-heading">Generated Cover Letter</h3>
        <div class="cover-letter-content" x-text="result"></div>
        
        <div class="word-count-badge" x-text="`${wordCount} words`"></div>
        
        <div class="result-actions">
            <button @click="copyToClipboard()" 
                    :disabled="copied"
                    :aria-label="copied ? 'Copied to clipboard' : 'Copy cover letter to clipboard'">
                <span x-show="!copied">Copy to Clipboard</span>
                <span x-show="copied">Copied!</span>
            </button>
            <button @click="reset()" aria-label="Generate another cover letter">
                Generate Another
            </button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="showToast" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="toast show"
         role="status"
         aria-live="polite">
        <span x-text="toastMessage"></span>
    </div>

    <!-- Privacy Notice -->
    <div class="privacy-notice">
        <p>Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter.</p>
        <p>We comply with POPIA (South African privacy law).</p>
    </div>
</div>

<script nonce="{{ app('csp_nonce') }}">
document.addEventListener('alpine:init', () => {
    Alpine.data('coverLetterApp', () => ({
        loading: false,
        result: null,
        error: null,
        wordCount: 0,
        copied: false,
        selectedFile: null,
        jobDescription: '',
        dragover: false,
        showToast: false,
        toastMessage: '',

        init() {
            // Focus management for accessibility
            this.$watch('result', (value) => {
                if (value) {
                    // Focus on result after generation
                    this.$nextTick(() => {
                        const resultElement = this.$el.querySelector('[role="region"]');
                        if (resultElement) {
                            resultElement.focus();
                        }
                    });
                }
            });
        },

        async submit() {
            if (this.loading) return;

            this.loading = true;
            this.error = null;
            this.result = null;
            this.copied = false;

            try {
                const formData = new FormData();
                const cvFile = this.$refs.cvInput.files[0];
                
                if (!cvFile) {
                    throw new Error('Please upload a CV file');
                }
                
                if (!this.jobDescription.trim()) {
                    throw new Error('Please enter a job description');
                }

                formData.append('cv', cvFile);
                formData.append('job_description', this.jobDescription);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const response = await fetch('/generate', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.result = data.data.cover_letter;
                    this.wordCount = data.data.word_count;
                } else {
                    this.error = data.error.message;
                }
            } catch (err) {
                this.error = 'An error occurred while processing your request. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file.name;
            }
        },

        autoExpand(textarea) {
            // Reset height to auto to get the correct scrollHeight
            textarea.style.height = 'auto';
            // Set height to scrollHeight to expand
            textarea.style.height = textarea.scrollHeight + 'px';
        },

        handleFileDrop(event) {
            this.dragover = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'application/pdf') {
                    this.$refs.cvInput.files = files;
                    this.selectedFile = file.name;
                } else {
                    this.error = 'Please upload a PDF file';
                }
            }
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.result);
                this.copied = true;
                this.showToastMessage('Cover letter copied to clipboard!');
                
                // Reset copied state after 2 seconds
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (err) {
                this.showToastMessage('Failed to copy to clipboard');
            }
        },

        showToastMessage(message) {
            this.toastMessage = message;
            this.showToast = true;
            
            setTimeout(() => {
                this.showToast = false;
            }, 3000);
        },

        reset() {
            this.result = null;
            this.error = null;
            this.selectedFile = null;
            this.jobDescription = '';
            this.copied = false;
            this.$refs.cvInput.value = '';
            
            // Focus on first input after reset
            this.$nextTick(() => {
                this.$refs.cvInput.focus();
            });
        }
    }));
});
</script>
@endsection