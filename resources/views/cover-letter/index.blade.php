@extends('layouts.app')

@section('content')
<div x-data="coverLetterApp()">
    <h1>CV Cover Letter Generator</h1>
    <p class="tagline">Upload your CV and job description to generate a tailored cover letter</p>

    <!-- Form Card -->
    <form @submit.prevent="submit()" enctype="multipart/form-data">
        @csrf
        
        <div class="form-group">
            <label for="cv">Upload Your CV (PDF)</label>
            <div class="file-upload-area" 
                 @click="$refs.cvInput.click()"
                 @dragover.prevent="dragover = true"
                 @dragleave.prevent="dragover = false"
                 @drop.prevent="handleFileDrop($event)"
                 :class="{ 'dragover': dragover }">
                <input type="file" 
                       id="cv" 
                       name="cv" 
                       x-ref="cvInput"
                       @change="handleFileSelect($event)"
                       accept=".pdf" 
                       required
                       style="display: none;">
                <div x-show="!selectedFile">
                    <strong>Click to upload</strong> or drag and drop your PDF here
                </div>
                <div x-show="selectedFile">
                    <strong>Selected:</strong> <span x-text="selectedFile"></span>
                </div>
            </div>
            <small>PDF up to 10MB</small>
        </div>

        <div class="form-group">
            <label for="job_description">Job Description</label>
            <textarea id="job_description" 
                      name="job_description" 
                      x-model="jobDescription"
                      placeholder="Paste the job description here..." 
                      required
                      rows="6"></textarea>
            <small>Minimum 50 characters, maximum 10,000 characters</small>
        </div>

        <div style="text-align: center;">
            <button type="submit" :disabled="loading">
                <span x-show="!loading">Generate Cover Letter</span>
                <span x-show="loading">Generating...</span>
            </button>
        </div>
    </form>

    <!-- Loading State -->
    <div x-show="loading" class="loading">
        <div class="spinner"></div>
        <p>Generating your cover letter...</p>
    </div>

    <!-- Error Alert -->
    <div x-show="error" class="error">
        <span x-text="error"></span>
    </div>

    <!-- Result Card -->
    <div x-show="result" class="success">
        <h3>Generated Cover Letter</h3>
        <div class="cover-letter-content" x-text="result"></div>
        
        <div class="word-count-badge" x-text="`${wordCount} words`"></div>
        
        <div class="result-actions">
            <button @click="copyToClipboard()" :disabled="copied">
                <span x-show="!copied">Copy to Clipboard</span>
                <span x-show="copied">Copied!</span>
            </button>
            <button @click="reset()">Generate Another</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="showToast" class="toast show">
        <span x-text="toastMessage"></span>
    </div>

    <!-- Privacy Notice -->
    <div class="privacy-notice">
        <p>Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter.</p>
        <p>We comply with POPIA (South African privacy law).</p>
    </div>
</div>

<script>
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
        }
    }));
});
</script>
@endsection