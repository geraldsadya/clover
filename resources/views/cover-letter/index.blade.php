@extends('layouts.app')

@section('content')
<div class="app-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="logo-container">
            <img src="/images/logos/STBlogo.svg" 
                 alt="Silvertreebrands" 
                 class="company-logo">
            <div class="app-logo">
                <span class="logo-text">C<span class="green-l">L</span>OVER</span>
            </div>
        </div>
        <h1>CV Cover Letter Generator</h1>
        <p class="subtitle">Transform your CV into a tailored cover letter with AI-powered precision</p>
    </div>

    <!-- Main Form Card -->
    <div class="form-card">
        <form id="coverLetterForm" enctype="multipart/form-data">
            @csrf
            
            <!-- CV Upload Section -->
            <div class="form-section">
                <label for="cv" class="section-label">
                    Upload Your CV
                </label>
                <div class="file-upload-container">
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="upload-text">
                            <span class="upload-primary">Click to upload or drag and drop</span>
                            <span class="upload-secondary">PDF files only, up to 10MB</span>
                        </div>
                    </div>
                    <input type="file" 
                           id="cv" 
                           name="cv" 
                           accept=".pdf" 
                           required
                           class="file-input">
                    <div class="file-info" id="fileInfo" style="display: none;">
                        <div class="file-details">
                            <span class="file-name" id="fileName"></span>
                            <span class="file-size" id="fileSize"></span>
                        </div>
                        <button type="button" class="remove-file" id="removeFile">×</button>
                    </div>
                </div>
            </div>

            <!-- Job Description Section -->
            <div class="form-section">
                <label for="job_description" class="section-label">
                    Job Description
                </label>
                <div class="textarea-container">
                    <textarea id="job_description" 
                              name="job_description" 
                              placeholder="Paste the job description here... Include key requirements, responsibilities, and company information."
                              required
                              rows="6"
                              class="job-textarea"></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 10,000 characters
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="submit-section">
                <button type="submit" id="submitBtn" class="submit-btn">
                    <span class="btn-text">Generate Cover Letter</span>
                    <span class="btn-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="loading-card" style="display: none;">
        <div class="loading-content">
            <div class="loading-spinner-wrapper">
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                </div>
            </div>
            <h3 class="loading-title">Generating Your Cover Letter</h3>
            <p class="loading-subtitle">Our AI is carefully analyzing your CV and crafting a personalized cover letter tailored to your job requirements...</p>
            <div class="loading-steps">
                <div class="step" id="step1">
                    <div class="step-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="step-text">Extracting CV content and identifying key qualifications</span>
                </div>
                <div class="step" id="step2">
                    <div class="step-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="step-text">Analyzing job requirements and matching your experience</span>
                </div>
                <div class="step" id="step3">
                    <div class="step-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="step-text">Writing your personalized cover letter with AI precision</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <div id="errorAlert" class="alert-card error-card" style="display: none;">
        <button type="button" class="alert-close" id="closeErrorBtn" aria-label="Close error message">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <div class="alert-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="16" r="1" fill="currentColor"/>
            </svg>
        </div>
        <div class="alert-content">
            <h4>Unable to Generate Cover Letter</h4>
            <p id="errorMessage"></p>
            <div class="error-suggestions">
                <p class="error-help-title">Possible solutions:</p>
                <ul class="error-help-list">
                    <li>Ensure your PDF contains readable text (not scanned images)</li>
                    <li>Check that the PDF is not password-protected or corrupted</li>
                    <li>Verify the job description contains relevant information</li>
                    <li>Try uploading a different version of your CV</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Success Result Card -->
    <div id="resultCard" class="result-card" style="display: none;">
        <div class="result-header">
            <div class="result-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>
            <div class="result-title">
                <h3>Cover Letter Generated Successfully!</h3>
                <div class="word-count-badge" id="wordCountBadge"></div>
            </div>
        </div>
        
        <div class="cover-letter-container">
            <div id="coverLetterContent" class="cover-letter-content"></div>
        </div>
        
        <div class="result-actions">
            <button type="button" id="copyBtn" class="action-btn primary">
                <span class="btn-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M5 15H4C3.46957 15 2.96086 14.7893 2.58579 14.4142C2.21071 14.0391 2 13.5304 2 13V4C2 3.46957 2.21071 2.96086 2.58579 2.58579C2.96086 2.21071 3.46957 2 4 2H13C13.5304 2 14.0391 2.21071 14.4142 2.58579C14.7893 2.96086 15 3.46957 15 4V5" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                Copy to Clipboard
            </button>
            <button type="button" id="downloadBtn" class="action-btn secondary">
                <span class="btn-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                Download PDF
            </button>
            <button type="button" id="resetBtn" class="action-btn outline">
                <span class="btn-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                Generate Another
            </button>
        </div>
    </div>

    <!-- Privacy Notice -->
    <div class="privacy-notice">
        <div class="privacy-content">
            <div class="privacy-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22S2 18 2 12V5L12 2L22 5V12C22 18 12 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="privacy-text">
                <p><strong>Your privacy is protected:</strong> Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter. We comply with POPIA (South African privacy law).</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#" class="footer-link">Privacy Policy</a>
                <span class="footer-separator">•</span>
                <a href="#" class="footer-link">Terms of Service</a>
            </div>
            <div class="footer-copyright">
                © GeraldSadya All rights reserved.
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast" style="display: none;">
    <div class="toast-content">
        <span class="toast-icon">Success</span>
        <span class="toast-message">Cover letter copied to clipboard!</span>
    </div>
</div>

<script>
// File upload handling
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('cv');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const removeFile = document.getElementById('removeFile');

// Character counter
const jobDescription = document.getElementById('job_description');
const charCount = document.getElementById('charCount');

// Form elements
const form = document.getElementById('coverLetterForm');
const submitBtn = document.getElementById('submitBtn');
const loadingState = document.getElementById('loadingState');
const errorAlert = document.getElementById('errorAlert');
const resultCard = document.getElementById('resultCard');
const errorMessage = document.getElementById('errorMessage');

// Result elements
const coverLetterContent = document.getElementById('coverLetterContent');
const wordCountBadge = document.getElementById('wordCountBadge');
const copyBtn = document.getElementById('copyBtn');
const downloadBtn = document.getElementById('downloadBtn');
const resetBtn = document.getElementById('resetBtn');

// Toast
const toast = document.getElementById('toast');

// File upload events
fileUploadArea.addEventListener('click', () => fileInput.click());
fileUploadArea.addEventListener('dragover', handleDragOver);
fileUploadArea.addEventListener('dragleave', handleDragLeave);
fileUploadArea.addEventListener('drop', handleDrop);
fileInput.addEventListener('change', handleFileSelect);
removeFile.addEventListener('click', clearFile);

// Character counter
jobDescription.addEventListener('input', updateCharCount);

// Form submission
form.addEventListener('submit', handleFormSubmit);

// Result actions
copyBtn.addEventListener('click', copyToClipboard);
downloadBtn.addEventListener('click', downloadCoverLetter);
resetBtn.addEventListener('click', resetForm);

// File handling functions
function handleDragOver(e) {
    e.preventDefault();
    fileUploadArea.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect();
    }
}

function handleFileSelect() {
    const file = fileInput.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            showError('Please select a PDF file.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError('File size must be less than 10MB.');
            return;
        }
        showFileInfo(file);
    }
}

function showFileInfo(file) {
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileUploadArea.style.display = 'none';
    fileInfo.style.display = 'flex';
}

function clearFile() {
    fileInput.value = '';
    fileUploadArea.style.display = 'flex';
    fileInfo.style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Character counter
function updateCharCount() {
    const count = jobDescription.value.length;
    charCount.textContent = count;
    
    if (count > 10000) {
        charCount.style.color = '#dc3545';
    } else if (count > 8000) {
        charCount.style.color = '#ffc107';
    } else {
        charCount.style.color = '#6c757d';
    }
}

// Form submission
function handleFormSubmit(e) {
    e.preventDefault();
    
    const cvFile = fileInput.files[0];
    const jobDescriptionText = jobDescription.value.trim();
    
    // Validation
    if (!cvFile) {
        showError('Please upload a CV file.');
        return;
    }
    
    if (!jobDescriptionText) {
        showError('Please enter a job description.');
        return;
    }
    
    if (jobDescriptionText.length < 1) {
        showError('Job description must be at least 1 character.');
        return;
    }
    
    if (jobDescriptionText.length > 10000) {
        showError('Job description must not exceed 10,000 characters.');
        return;
    }

    // Show loading state
    showLoading();
    
    // Prepare form data
    const formData = new FormData();
    formData.append('cv', cvFile);
    formData.append('job_description', jobDescriptionText);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    // Submit request
    fetch('/generate', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showResult(data.data.cover_letter);
        } else {
            let errorMsg = data.error.message;
            if (data.errors) {
                const errors = [];
                if (data.errors.cv) errors.push(...data.errors.cv);
                if (data.errors.job_description) errors.push(...data.errors.job_description);
                if (errors.length > 0) {
                    errorMsg = errors.join(', ');
                }
            }
            showError(errorMsg);
        }
    })
    .catch(err => {
        hideLoading();
        showError('An error occurred while processing your request. Please try again.');
    });
}

// Loading state
function showLoading() {
    hideAllStates();
    loadingState.style.display = 'block';
    submitBtn.disabled = true;
    
    // Animate steps
    setTimeout(() => document.getElementById('step1').classList.add('active'), 500);
    setTimeout(() => document.getElementById('step2').classList.add('active'), 2000);
    setTimeout(() => document.getElementById('step3').classList.add('active'), 4000);
}

function hideLoading() {
    loadingState.style.display = 'none';
    submitBtn.disabled = false;
    
    // Reset steps
    document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
}

// Error handling
function showError(message) {
    hideAllStates();
    errorMessage.textContent = message;
    errorAlert.style.display = 'block';
}

// Result display
function showResult(coverLetter) {
    hideAllStates();
    coverLetterContent.textContent = coverLetter;
    
    // Update word count
    const wordCount = coverLetter.split(' ').length;
    wordCountBadge.textContent = `${wordCount} words`;
    
    resultCard.style.display = 'block';
}

// Utility functions
function hideAllStates() {
    loadingState.style.display = 'none';
    errorAlert.style.display = 'none';
    resultCard.style.display = 'none';
}

function copyToClipboard() {
    const text = coverLetterContent.textContent;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Cover letter copied to clipboard!');
    }).catch(() => {
        showToast('Failed to copy to clipboard', 'error');
    });
}

function downloadCoverLetter() {
    const text = coverLetterContent.textContent;
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'cover-letter.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('Cover letter downloaded!');
}

function resetForm() {
    clearFile();
    jobDescription.value = '';
    updateCharCount();
    hideAllStates();
}

function showToast(message, type = 'success') {
    const toastMessage = toast.querySelector('.toast-message');
    toastMessage.textContent = message;
    toast.className = `toast ${type}`;
    toast.style.display = 'block'; // Show the toast
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.style.display = 'none'; // Hide after transition
        }, 300);
    }, 3000);
}

// Close error alert
const closeErrorBtn = document.getElementById('closeErrorBtn');
closeErrorBtn.addEventListener('click', () => {
    errorAlert.style.display = 'none';
    resetForm();
});

// Initialize
updateCharCount();
</script>
@endsection
