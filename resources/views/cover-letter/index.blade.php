@extends('layouts.app')

@section('content')
<h1>CV Cover Letter Generator</h1>
<p style="text-align: center; color: #666; margin-bottom: 30px;">Upload your CV and job description to generate a tailored cover letter</p>

<form id="coverLetterForm" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label for="cv">Upload Your CV (PDF)</label>
        <input type="file" id="cv" name="cv" accept=".pdf" required>
        <small style="color: #666;">PDF up to 10MB</small>
    </div>

    <div class="form-group">
        <label for="job_description">Job Description</label>
        <textarea id="job_description" name="job_description" placeholder="Paste the job description here..." required></textarea>
        <small style="color: #666;">Minimum 50 characters, maximum 10,000 characters</small>
    </div>

    <div style="text-align: center;">
        <button type="submit" id="submitBtn">Generate Cover Letter</button>
    </div>
</form>

<div id="loading" class="loading" style="display: none;">
    <p>Generating your cover letter...</p>
</div>

<div id="error" class="error" style="display: none;"></div>

<div id="result" class="success" style="display: none;">
    <h3>Generated Cover Letter</h3>
    <div id="coverLetterText"></div>
    <div style="margin-top: 15px;">
        <button onclick="copyToClipboard()">Copy to Clipboard</button>
        <button onclick="resetForm()">Generate Another</button>
    </div>
</div>

<div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
    <p>Your CV is processed securely and never stored. Files are deleted immediately after generating your cover letter.</p>
    <p>We comply with POPIA (South African privacy law).</p>
</div>

<script>
document.getElementById('coverLetterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    const error = document.getElementById('error');
    const result = document.getElementById('result');
    
    // Show loading state
    submitBtn.disabled = true;
    loading.style.display = 'block';
    error.style.display = 'none';
    result.style.display = 'none';
    
    try {
        const response = await fetch('/generate', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('coverLetterText').textContent = data.data.cover_letter;
            result.style.display = 'block';
        } else {
            error.textContent = data.error.message;
            error.style.display = 'block';
        }
    } catch (err) {
        error.textContent = 'An error occurred while processing your request.';
        error.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        loading.style.display = 'none';
    }
});

function copyToClipboard() {
    const text = document.getElementById('coverLetterText').textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Cover letter copied to clipboard!');
    });
}

function resetForm() {
    document.getElementById('coverLetterForm').reset();
    document.getElementById('result').style.display = 'none';
    document.getElementById('error').style.display = 'none';
}
</script>
@endsection