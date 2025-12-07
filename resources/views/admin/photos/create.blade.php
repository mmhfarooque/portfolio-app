<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Photos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category (Optional)</label>
                        <select name="category_id" id="category_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">No Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>
                        <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold text-blue-600">Click to upload</span> or drag and drop
                            </p>
                            <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG, HEIC, HEIF, WebP up to 50MB each</p>
                            <input type="file" id="photos" multiple accept="image/*,.heic,.heif" class="hidden">
                        </div>
                    </div>

                    <!-- Preview Area -->
                    <div id="preview-area" class="mb-6 hidden">
                        <!-- Header with controls -->
                        <div class="flex items-center justify-between mb-4 pb-3 border-b">
                            <div class="flex items-center gap-4">
                                <h3 class="text-sm font-medium text-gray-700">Selected Photos</h3>
                                <button type="button" id="select-all-btn" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition">
                                    Select All
                                </button>
                                <button type="button" id="remove-selected-btn" class="text-xs px-3 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-md transition hidden">
                                    Remove Selected
                                </button>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-500">
                                    <span id="selected-count">0</span>/<span id="file-count">0</span> selected &bull; <span id="total-size">0 MB</span>
                                </div>
                                <button type="button" id="upload-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-5 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Selected
                                </button>
                            </div>
                        </div>

                        <!-- Upload Progress Summary -->
                        <div id="upload-progress" class="mb-4 hidden">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Uploading: <span id="progress-text">0/0</span></span>
                                <span class="text-sm text-gray-500" id="progress-percent">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Photo Grid -->
                        <div id="preview-grid" class="grid grid-cols-4 gap-4"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.photos.index') }}" class="text-gray-600 hover:text-gray-800">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .photo-item.selected .select-ring {
            display: block;
        }
        .photo-item .select-ring {
            display: none;
        }
        .photo-item.selected .check-icon {
            display: flex;
        }
        .photo-item .check-icon {
            display: none;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dashoffset 0.3s;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('photos');
            const previewArea = document.getElementById('preview-area');
            const previewGrid = document.getElementById('preview-grid');
            const uploadBtn = document.getElementById('upload-btn');
            const selectAllBtn = document.getElementById('select-all-btn');
            const removeSelectedBtn = document.getElementById('remove-selected-btn');
            const categorySelect = document.getElementById('category_id');

            // File data storage
            let files = []; // {file, selected, status, element, dataUrl}

            // Click to select files
            dropZone.addEventListener('click', () => fileInput.click());

            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('border-blue-500', 'bg-blue-50'));
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-blue-500', 'bg-blue-50'));
            });

            dropZone.addEventListener('drop', (e) => {
                addFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', () => {
                addFiles(fileInput.files);
                fileInput.value = '';
            });

            function addFiles(fileList) {
                Array.from(fileList).forEach(file => {
                    if (file.type.startsWith('image/') || file.name.toLowerCase().endsWith('.heic') || file.name.toLowerCase().endsWith('.heif')) {
                        const exists = files.some(f => f.file.name === file.name && f.file.size === file.size);
                        if (!exists) {
                            files.push({
                                file: file,
                                selected: true,
                                status: 'pending', // pending, uploading, processing, done, error
                                progress: 0,
                                element: null,
                                dataUrl: null
                            });
                        }
                    }
                });
                renderPreviews();
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }

            function updateStats() {
                const totalCount = files.length;
                const selectedCount = files.filter(f => f.selected).length;
                const totalBytes = files.filter(f => f.selected).reduce((sum, f) => sum + f.file.size, 0);

                document.getElementById('file-count').textContent = totalCount;
                document.getElementById('selected-count').textContent = selectedCount;
                document.getElementById('total-size').textContent = formatFileSize(totalBytes);

                uploadBtn.disabled = selectedCount === 0;
                removeSelectedBtn.classList.toggle('hidden', selectedCount === 0);

                // Update select all button text
                const allSelected = files.length > 0 && files.every(f => f.selected);
                selectAllBtn.textContent = allSelected ? 'Deselect All' : 'Select All';
            }

            function toggleSelection(index) {
                files[index].selected = !files[index].selected;
                const element = files[index].element;
                if (element) {
                    element.classList.toggle('selected', files[index].selected);
                }
                updateStats();
            }

            function removeFile(index) {
                files.splice(index, 1);
                renderPreviews();
            }

            selectAllBtn.addEventListener('click', () => {
                const allSelected = files.every(f => f.selected);
                files.forEach((f, i) => {
                    f.selected = !allSelected;
                    if (f.element) {
                        f.element.classList.toggle('selected', f.selected);
                    }
                });
                updateStats();
            });

            removeSelectedBtn.addEventListener('click', () => {
                files = files.filter(f => !f.selected);
                renderPreviews();
            });

            function renderPreviews() {
                previewGrid.innerHTML = '';

                if (files.length === 0) {
                    previewArea.classList.add('hidden');
                    return;
                }

                previewArea.classList.remove('hidden');

                files.forEach((fileData, index) => {
                    const div = document.createElement('div');
                    div.className = `photo-item relative cursor-pointer ${fileData.selected ? 'selected' : ''}`;
                    div.dataset.index = index;
                    fileData.element = div;

                    const fileSize = formatFileSize(fileData.file.size);

                    div.innerHTML = `
                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden relative">
                            <img src="" alt="${fileData.file.name}" class="w-full h-full object-cover photo-img">

                            <!-- Selection ring -->
                            <div class="select-ring absolute inset-0 ring-4 ring-blue-500 ring-inset rounded-lg pointer-events-none"></div>

                            <!-- Check icon -->
                            <div class="check-icon absolute top-2 left-2 w-6 h-6 bg-blue-500 rounded-full items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <!-- Remove button -->
                            <button type="button" class="remove-btn absolute top-2 right-2 w-7 h-7 bg-gray-900/70 hover:bg-red-600 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-20">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <!-- Upload overlay -->
                            <div class="upload-overlay absolute inset-0 bg-white/80 flex items-center justify-center hidden">
                                <div class="text-center">
                                    <svg class="progress-ring w-16 h-16 mx-auto" viewBox="0 0 60 60">
                                        <circle class="text-gray-200" stroke="currentColor" stroke-width="4" fill="none" r="26" cx="30" cy="30"/>
                                        <circle class="progress-ring-circle text-blue-500" stroke="currentColor" stroke-width="4" fill="none" r="26" cx="30" cy="30"
                                                stroke-dasharray="163.36" stroke-dashoffset="163.36" stroke-linecap="round"/>
                                    </svg>
                                    <p class="status-text text-xs text-gray-600 mt-2">Waiting...</p>
                                </div>
                            </div>

                            <!-- Done overlay -->
                            <div class="done-overlay absolute inset-0 bg-green-500/20 flex items-center justify-center hidden">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Error overlay -->
                            <div class="error-overlay absolute inset-0 bg-red-500/20 flex items-center justify-center hidden">
                                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-1">${fileSize}</p>
                    `;

                    // Load image preview
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        fileData.dataUrl = e.target.result;
                        div.querySelector('.photo-img').src = e.target.result;
                    };
                    reader.readAsDataURL(fileData.file);

                    // Click to toggle selection
                    div.addEventListener('click', (e) => {
                        if (!e.target.closest('.remove-btn')) {
                            toggleSelection(index);
                        }
                    });

                    // Remove button
                    div.querySelector('.remove-btn').addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeFile(index);
                    });

                    // Show remove button on hover
                    div.addEventListener('mouseenter', () => {
                        div.querySelector('.remove-btn').classList.remove('opacity-0');
                        div.querySelector('.remove-btn').classList.add('opacity-100');
                    });
                    div.addEventListener('mouseleave', () => {
                        div.querySelector('.remove-btn').classList.add('opacity-0');
                        div.querySelector('.remove-btn').classList.remove('opacity-100');
                    });

                    previewGrid.appendChild(div);
                });

                updateStats();
            }

            // Upload functionality
            uploadBtn.addEventListener('click', startUpload);

            async function startUpload() {
                const selectedFiles = files.filter(f => f.selected && f.status === 'pending');
                if (selectedFiles.length === 0) return;

                const categoryId = categorySelect.value;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                // Show progress bar
                const progressArea = document.getElementById('upload-progress');
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                const progressPercent = document.getElementById('progress-percent');
                progressArea.classList.remove('hidden');

                // Disable controls
                uploadBtn.disabled = true;
                selectAllBtn.disabled = true;
                dropZone.style.pointerEvents = 'none';
                dropZone.classList.add('opacity-50');

                let completed = 0;
                const total = selectedFiles.length;

                for (const fileData of selectedFiles) {
                    // Update UI
                    progressText.textContent = `${completed + 1}/${total}`;

                    // Show upload overlay
                    const overlay = fileData.element.querySelector('.upload-overlay');
                    const statusText = fileData.element.querySelector('.status-text');
                    const progressCircle = fileData.element.querySelector('.progress-ring-circle');
                    overlay.classList.remove('hidden');
                    statusText.textContent = 'Uploading...';
                    fileData.status = 'uploading';

                    try {
                        // Upload the file
                        await uploadFile(fileData, categoryId, csrfToken, (progress) => {
                            // Update circular progress
                            const circumference = 163.36;
                            const offset = circumference - (progress / 100) * circumference;
                            progressCircle.style.strokeDashoffset = offset;

                            if (progress >= 100) {
                                statusText.textContent = 'Processing...';
                                fileData.status = 'processing';
                            }
                        });

                        // Success
                        fileData.status = 'done';
                        overlay.classList.add('hidden');
                        fileData.element.querySelector('.done-overlay').classList.remove('hidden');

                    } catch (error) {
                        console.error('Upload error:', error);
                        fileData.status = 'error';
                        overlay.classList.add('hidden');
                        fileData.element.querySelector('.error-overlay').classList.remove('hidden');
                    }

                    completed++;
                    const percent = Math.round((completed / total) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressPercent.textContent = `${percent}%`;
                }

                // All done
                setTimeout(() => {
                    window.location.href = '{{ route("admin.photos.index") }}';
                }, 1000);
            }

            function uploadFile(fileData, categoryId, csrfToken, onProgress) {
                return new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('photos[]', fileData.file);
                    if (categoryId) {
                        formData.append('category_id', categoryId);
                    }
                    formData.append('_token', csrfToken);

                    const xhr = new XMLHttpRequest();

                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            onProgress(percent);
                        }
                    });

                    xhr.addEventListener('load', () => {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            resolve(xhr.response);
                        } else {
                            reject(new Error(`Upload failed: ${xhr.status}`));
                        }
                    });

                    xhr.addEventListener('error', () => {
                        reject(new Error('Network error'));
                    });

                    xhr.open('POST', '{{ route("admin.photos.store") }}');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send(formData);
                });
            }
        });
    </script>
</x-app-layout>
