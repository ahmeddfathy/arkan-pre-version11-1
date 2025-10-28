@extends('layouts.app')

@section('content')

<head>
    <link href="{{ asset('css/salary-sheets.css') }}" rel="stylesheet">
</head>
<div class="container-fluid py-4">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Salary Sheets Upload</h1>

        <!-- File Type Selector -->
        <div class="mb-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-3">Supported File Types:</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-file-pdf text-red-500 text-xl mr-2"></i>
                            <h3 class="font-medium">PDF Files</h3>
                        </div>
                        <p class="text-sm text-gray-600">
                            Upload individual PDF salary sheets. Each file should be named with the employee ID.
                        </p>
                    </div>
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-file-excel text-green-500 text-xl mr-2"></i>
                            <h3 class="font-medium">Excel Files</h3>
                        </div>
                        <p class="text-sm text-gray-600">
                            Upload Excel files containing multiple employee records. First column must contain employee IDs.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drag and Drop Zone -->
        <div
            id="dropzone"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors duration-200">
            <div class="space-y-4">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                <p class="text-gray-600">Drag and drop salary files here</p>
                <p class="text-sm text-gray-500">Supported formats: .pdf, .xlsx, .xls</p>
                <p class="text-sm text-gray-500">or</p>
                <button
                    type="button"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors duration-200"
                    onclick="document.getElementById('fileInput').click()">
                    Select Files
                </button>
                <input
                    type="file"
                    id="fileInput"
                    multiple
                    class="hidden"
                    accept=".pdf,.xlsx,.xls">
            </div>
        </div>

        <!-- Upload Progress -->
        <div id="uploadProgress" class="mt-4 hidden">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <p class="text-sm text-gray-600 mt-2">Uploading files... <span id="progressText">0%</span></p>
        </div>

        <!-- Processing Results -->
        <div id="processingResults" class="mt-4 hidden">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-2">Processing Results</h3>
                <div id="successResults" class="mb-3">
                    <h4 class="text-green-600 font-medium">Successfully Processed:</h4>
                    <ul class="list-disc list-inside text-sm"></ul>
                </div>
                <div id="failedResults" class="mb-3">
                    <h4 class="text-red-600 font-medium">Failed to Process:</h4>
                    <ul class="list-disc list-inside text-sm"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Sheets Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($salarySheets as $sheet)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->employee_id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->original_filename }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            {{ pathinfo($sheet->file_path, PATHINFO_EXTENSION) === 'pdf' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ strtoupper(pathinfo($sheet->file_path, PATHINFO_EXTENSION)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ Storage::url($sheet->file_path) }}"
                            class="text-blue-600 hover:text-blue-900"
                            target="_blank">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = uploadProgress.querySelector('.bg-blue-600');
        const progressText = document.getElementById('progressText');
        const processingResults = document.getElementById('processingResults');
        const successList = document.querySelector('#successResults ul');
        const failedList = document.querySelector('#failedResults ul');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropzone.classList.add('border-blue-500');
        }

        function unhighlight(e) {
            dropzone.classList.remove('border-blue-500');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles({
                target: {
                    files: files
                }
            });
        }

        function handleFiles(e) {
            const files = [...e.target.files];
            uploadFiles(files);
        }

        function displayResults(response) {
            processingResults.classList.remove('hidden');
            successList.innerHTML = '';
            failedList.innerHTML = '';

            if (response.processed) {
                response.processed.forEach(item => {
                    if (item.type === 'excel') {
                        item.processed.forEach(success => {
                            successList.innerHTML += `<li>Successfully sent to: ${success.email}</li>`;
                        });
                        item.failed.forEach(failure => {
                            failedList.innerHTML += `<li>Failed for ID ${failure.employee_id}: ${failure.error}</li>`;
                        });
                    } else {
                        successList.innerHTML += `<li>Processed: ${item.filename}</li>`;
                    }
                });
            }

            if (response.failed) {
                response.failed.forEach(failure => {
                    failedList.innerHTML += `<li>${failure.filename}: ${failure.error}</li>`;
                });
            }
        }

        function uploadFiles(files) {
            const formData = new FormData();
            files.forEach(file => {
                formData.append('files[]', file);
            });

            uploadProgress.classList.remove('hidden');
            processingResults.classList.add('hidden');

            axios.post('/salary-sheets/upload', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        progressBar.style.width = percentCompleted + '%';
                        progressText.textContent = percentCompleted + '%';
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        displayResults(response.data);
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                })
                .catch(error => {
                    showAlert(error.response.data.message || 'Upload failed', 'error');
                    displayResults(error.response.data);
                })
                .finally(() => {
                    setTimeout(() => {
                        uploadProgress.classList.add('hidden');
                        progressBar.style.width = '0%';
                        progressText.textContent = '0%';
                    }, 1500);
                });
        }

        function showAlert(message, type) {
            const alertClass = type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 p-4 rounded-lg ${alertClass}`;
            alert.textContent = message;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    });
</script>
@endpush
@endsection
