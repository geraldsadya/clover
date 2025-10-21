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
            
            <!-- Simple file input - no hidden tricks -->
            <input type="file" 
                   id="cv" 
                   name="cv" 
                   accept=".pdf" 
                   required
                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 16px;">
            
            <small>PDF up to 10MB</small>
        </div>

        <div class="form-group">
            <label for="job_description">Job Description</label>
            <textarea id="job_description" 
                      name="job_description" 
                      placeholder="Paste the job description here..." 
                      required
                      rows="6"
                      style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; font-size: 16px;"></textarea>
            <small>Minimum 1 character, maximum 10,000 characters</small>
        </div>

        <div style="text-align: center;">
            <button type="submit" id="submitBtn" style="background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                Generate Cover Letter
            </button>
        </div>
    </form>

    <!-- Loading State -->
    <div id="loadingState" style="display: none; text-align: center; padding: 20px;">
        <p>Generating your cover letter...</p>
    </div>

    <!-- Error Alert -->
    <div id="errorAlert" style="display: none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;">
        <span id="errorMessage"></span>
    </div>

    <!-- Result Card -->
    <div id="resultCard" style="display: none; background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;">
        <h3>Generated Cover Letter</h3>
        <div id="coverLetterContent" style="white-space: pre-line; line-height: 1.8; margin: 20px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white;"></div>
        
        <div style="margin-top: 20px;">
            <button onclick="copyToClipboard()" style="background: #007cba; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                Copy to Clipboard
            </button>
            <button onclick="resetForm()" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
                Generate Another
            </button>
        </div>
    </div>

    <!-- Privacy Notice -->
    <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
        <p>Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter.</p>
        <p>We comply with POPIA (South African privacy law).</p>
    </div>
</div>

<script>
function copyToClipboard() {
    const text = document.getElementById('coverLetterContent').textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Cover letter copied to clipboard!');
    }).catch(() => {
        alert('Failed to copy to clipboard');
    });
}

function resetForm() {
    document.getElementById('cv').value = '';
    document.getElementById('job_description').value = '';
    document.getElementById('resultCard').style.display = 'none';
    document.getElementById('errorAlert').style.display = 'none';
}

// Simple form submission
document.getElementById('coverLetterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cvFile = document.getElementById('cv').files[0];
    const jobDescription = document.getElementById('job_description').value.trim();
    
    if (!cvFile) {
        alert('Please upload a CV file');
        return;
    }
    
    if (!jobDescription) {
        alert('Please enter a job description');
        return;
    }

    if (jobDescription.length < 1) {
        alert('Job description must be at least 1 character');
        return;
    }

    // Show loading
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('errorAlert').style.display = 'none';
    document.getElementById('resultCard').style.display = 'none';

    const formData = new FormData();
    formData.append('cv', cvFile);
    formData.append('job_description', jobDescription);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch('/generate', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingState').style.display = 'none';
        
        if (data.success) {
            document.getElementById('coverLetterContent').textContent = data.data.cover_letter;
            document.getElementById('resultCard').style.display = 'block';
        } else {
            let errorMsg = data.error.message;
            if (data.errors) {
                // Show specific validation errors
                const errors = [];
                if (data.errors.cv) errors.push(...data.errors.cv);
                if (data.errors.job_description) errors.push(...data.errors.job_description);
                if (errors.length > 0) {
                    errorMsg = errors.join(', ');
                }
            }
            document.getElementById('errorMessage').textContent = errorMsg;
            document.getElementById('errorAlert').style.display = 'block';
        }
    })
    .catch(err => {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorMessage').textContent = 'An error occurred while processing your request. Please try again.';
        document.getElementById('errorAlert').style.display = 'block';
    });
});
</script>

<style>
.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

small {
    color: #666;
}

button:hover {
    opacity: 0.9;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
@endsection