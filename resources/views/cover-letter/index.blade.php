@extends('layouts.app')

@section('content')
<div id="app">
    <h1>CV Cover Letter Generator</h1>
    <p class="tagline">Upload your CV and job description to generate a tailored cover letter</p>

    <!-- Form Card -->
    <form id="coverLetterForm" enctype="multipart/form-data">
        @csrf
        
        <div class="form-group">
            <label for="cv">Upload Your CV (PDF)</label>
            <div class="file-upload-area" id="fileUploadArea">
                <input type="file" 
                       id="cv" 
                       name="cv" 
                       accept=".pdf" 
                       required
                       style="display: none;">
                <div id="uploadPlaceholder">
                    <strong>Click to upload</strong> or drag and drop your PDF here
                </div>
                <div id="fileSelected" style="display: none;">
                    <strong>Selected:</strong> <span id="fileName"></span>
                </div>
            </div>
            <small>PDF up to 10MB</small>
        </div>

        <div class="form-group">
            <label for="job_description">Job Description</label>
            <textarea id="job_description" 
                      name="job_description" 
                      placeholder="Paste the job description here..." 
                      required
                      rows="6"></textarea>
            <small>Minimum 50 characters, maximum 10,000 characters</small>
        </div>

        <div style="text-align: center;">
            <button type="submit" id="submitBtn">
                <span id="submitText">Generate Cover Letter</span>
                <span id="submitLoading" style="display: none;">Generating...</span>
            </button>
        </div>
    </form>

    <!-- Loading State -->
    <div id="loadingState" class="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Generating your cover letter...</p>
    </div>

    <!-- Error Alert -->
    <div id="errorAlert" class="error" style="display: none;">
        <span id="errorMessage"></span>
    </div>

    <!-- Result Card -->
    <div id="resultCard" class="success" style="display: none;">
        <h3>Generated Cover Letter</h3>
        <div class="cover-letter-content" id="coverLetterContent"></div>
        
        <div class="word-count-badge" id="wordCountBadge"></div>
        
        <div class="result-actions">
            <button id="copyBtn">
                <span id="copyText">Copy to Clipboard</span>
                <span id="copySuccess" style="display: none;">Copied!</span>
            </button>
            <button id="resetBtn">Generate Another</button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display: none;">
        <span id="toastMessage"></span>
    </div>

    <!-- Privacy Notice -->
    <div class="privacy-notice">
        <p>Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter.</p>
        <p>We comply with POPIA (South African privacy law).</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('cv');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const fileSelected = document.getElementById('fileSelected');
    const fileName = document.getElementById('fileName');
    const form = document.getElementById('coverLetterForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoading = document.getElementById('submitLoading');
    const loadingState = document.getElementById('loadingState');
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const resultCard = document.getElementById('resultCard');
    const coverLetterContent = document.getElementById('coverLetterContent');
    const wordCountBadge = document.getElementById('wordCountBadge');
    const copyBtn = document.getElementById('copyBtn');
    const copyText = document.getElementById('copyText');
    const copySuccess = document.getElementById('copySuccess');
    const resetBtn = document.getElementById('resetBtn');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // File upload click handler
    fileUploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    // File input change handler
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            fileName.textContent = file.name;
            uploadPlaceholder.style.display = 'none';
            fileSelected.style.display = 'block';
        }
    });

    // Drag and drop handlers
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadArea.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (file.type === 'application/pdf') {
                fileInput.files = files;
                fileName.textContent = file.name;
                uploadPlaceholder.style.display = 'none';
                fileSelected.style.display = 'block';
            } else {
                showError('Please upload a PDF file');
            }
        }
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const cvFile = fileInput.files[0];
        const jobDescription = document.getElementById('job_description').value.trim();
        
        if (!cvFile) {
            showError('Please upload a CV file');
            return;
        }
        
        if (!jobDescription) {
            showError('Please enter a job description');
            return;
        }

        if (jobDescription.length < 50) {
            showError('Job description must be at least 50 characters');
            return;
        }

        // Show loading state
        setLoading(true);
        hideError();
        hideResult();

        try {
            const formData = new FormData();
            formData.append('cv', cvFile);
            formData.append('job_description', jobDescription);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const response = await fetch('/generate', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showResult(data.data.cover_letter, data.data.word_count);
            } else {
                showError(data.error.message);
            }
        } catch (err) {
            showError('An error occurred while processing your request. Please try again.');
        } finally {
            setLoading(false);
        }
    });

    // Copy to clipboard
    copyBtn.addEventListener('click', async function() {
        try {
            await navigator.clipboard.writeText(coverLetterContent.textContent);
            copyText.style.display = 'none';
            copySuccess.style.display = 'inline';
            showToast('Cover letter copied to clipboard!');
            
            setTimeout(() => {
                copyText.style.display = 'inline';
                copySuccess.style.display = 'none';
            }, 2000);
        } catch (err) {
            showToast('Failed to copy to clipboard');
        }
    });

    // Reset form
    resetBtn.addEventListener('click', function() {
        hideResult();
        hideError();
        fileInput.value = '';
        document.getElementById('job_description').value = '';
        uploadPlaceholder.style.display = 'block';
        fileSelected.style.display = 'none';
    });

    // Helper functions
    function setLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            submitLoading.style.display = 'inline';
            loadingState.style.display = 'block';
        } else {
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            submitLoading.style.display = 'none';
            loadingState.style.display = 'none';
        }
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorAlert.style.display = 'block';
    }

    function hideError() {
        errorAlert.style.display = 'none';
    }

    function showResult(coverLetter, wordCount) {
        coverLetterContent.textContent = coverLetter;
        wordCountBadge.textContent = `${wordCount} words`;
        resultCard.style.display = 'block';
    }

    function hideResult() {
        resultCard.style.display = 'none';
    }

    function showToast(message) {
        toastMessage.textContent = message;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
});
</script>
@endsection