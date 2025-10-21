@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">CV Cover Letter Generator</h1>
        <p class="text-lg text-gray-600">Upload your CV and job description to generate a tailored cover letter</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-lg p-8 mb-8" x-data="coverLetterApp()">
        <form @submit.prevent="submit()" enctype="multipart/form-data">
            @csrf
            
            <!-- PDF Upload -->
            <div class="mb-6">
                <label for="cv" class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Your CV (PDF)
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="cv" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload a PDF file</span>
                                <input id="cv" name="cv" type="file" class="sr-only" accept=".pdf" @change="handleFileSelect($event)">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF up to 10MB</p>
                    </div>
                </div>
                <div x-show="selectedFile" class="mt-2 text-sm text-green-600">
                    <span x-text="selectedFile"></span>
                </div>
            </div>

            <!-- Job Description -->
            <div class="mb-6">
                <label for="job_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Job Description
                </label>
                <textarea 
                    id="job_description" 
                    name="job_description" 
                    rows="6" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Paste the job description here..."
                    x-model="jobDescription"
                    required
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">Minimum 50 characters, maximum 10,000 characters</p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center">
                <button 
                    type="submit" 
                    class="px-8 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="loading"
                >
                    <span x-show="!loading">Generate Cover Letter</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generating your cover letter...
                    </span>
                </button>
            </div>
        </form>

        <!-- Loading State -->
        <div x-show="loading" class="mt-6 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-md">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing your CV and generating cover letter...
            </div>
        </div>

        <!-- Error Alert -->
        <div x-show="error" class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p x-text="error"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Card -->
        <div x-show="result" class="mt-8 bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-medium text-green-800">Generated Cover Letter</h3>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span x-text="wordCount"></span> words
                    </span>
                    <button 
                        @click="copyToClipboard()"
                        class="inline-flex items-center px-3 py-1 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copy
                    </button>
                </div>
            </div>
            <div class="prose max-w-none">
                <div class="whitespace-pre-wrap text-gray-800" x-text="result"></div>
            </div>
            <div class="mt-4 flex justify-center">
                <button 
                    @click="reset()"
                    class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Generate Another
                </button>
            </div>
        </div>
    </div>

    <!-- Privacy Notice -->
    <div class="text-center text-sm text-gray-500">
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
        selectedFile: null,
        jobDescription: '',
        copied: false,
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file.name;
            }
        },
        
        async submit() {
            this.loading = true;
            this.error = null;
            this.result = null;
            
            const formData = new FormData();
            const cvFile = document.getElementById('cv').files[0];
            formData.append('cv', cvFile);
            formData.append('job_description', this.jobDescription);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            try {
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
                this.error = 'An error occurred while processing your request.';
            } finally {
                this.loading = false;
            }
        },
        
        copyToClipboard() {
            navigator.clipboard.writeText(this.result).then(() => {
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            });
        },
        
        reset() {
            this.result = null;
            this.error = null;
            this.wordCount = 0;
            this.selectedFile = null;
            this.jobDescription = '';
            document.getElementById('cv').value = '';
        }
    }))
})
</script>
@endsection
