@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Product Export Management" />
    
    <div class="space-y-6">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Flash messages with Swal
                @if(session('success'))
                    Swal.fire({ title: 'Success!', text: "{{ session('success') }}", icon: 'success', confirmButtonColor: '#3085d6' });
                @endif
                @if(session('error'))
                    Swal.fire({ title: 'Error!', text: "{{ session('error') }}", icon: 'error', confirmButtonColor: '#d33' });
                @endif

                let activeXHR = null;

                window.addEventListener('beforeunload', function (e) {
                    if (activeXHR) {
                        activeXHR.abort(); // Cancel active upload on reload
                    }
                });

                // AJAX Upload helper function
                function uploadFile(file, action, progressBar, progressContainer) {
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    progressContainer.classList.remove('hidden');
                    const progressText = progressContainer.previousElementSibling.querySelector('.progress-text');
                    if (progressText) progressText.classList.remove('hidden');
                    
                    // Show a global "Uploading..." state with cancel button and progress
                    Swal.fire({
                        title: 'Uploading...',
                        html: `
                            <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">Please wait until the upload is complete.</div>
                            <div class="flex justify-between mb-1">
                                <span class="text-xs font-medium text-blue-700 dark:text-white">Progress</span>
                                <span id="upload-percent-text" class="text-xs font-medium text-blue-700 dark:text-white">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div id="upload-progress-bar-swal" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showCancelButton: true,
                        cancelButtonText: 'Cancel Upload',
                        cancelButtonColor: '#d33',
                        showConfirmButton: false,
                        didOpen: () => { 
                            // We don't use showLoading() here to keep the HTML visible
                        }
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            if (activeXHR) {
                                activeXHR.abort();
                                activeXHR = null;
                                progressContainer.classList.add('hidden');
                                if (progressText) progressText.classList.add('hidden');
                                progressBar.style.width = '0%';
                                Swal.fire('Cancelled', 'Upload cancelled by user.', 'info');
                            }
                        }
                    });
                    
                    const xhr = new XMLHttpRequest();
                    activeXHR = xhr; // Track active XHR
                    
                    xhr.open('POST', action, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            
                            // Update row progress bar and text
                            progressBar.style.width = percentComplete + '%';
                            if (progressText) progressText.innerText = percentComplete + '%';
                            
                            // Update Swal progress bar and text
                            const swalBar = document.getElementById('upload-progress-bar-swal');
                            const swalText = document.getElementById('upload-percent-text');
                            if (swalBar) swalBar.style.width = percentComplete + '%';
                            if (swalText) swalText.innerText = percentComplete + '%';
                        }
                    };

                    xhr.onload = function() {
                        activeXHR = null; // Clear active XHR
                        if (xhr.status === 200) {
                            Swal.fire({
                                title: 'Uploaded!',
                                text: 'Image has been uploaded.',
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire('Error!', 'Upload failed.', 'error');
                        }
                    };
                    
                    xhr.onerror = function() {
                        activeXHR = null;
                    };

                    xhr.onabort = function() {
                        activeXHR = null;
                    };
                    
                    xhr.send(formData);
                }

                // Drag & Drop logic for table cells
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    zone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                    });

                    zone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                    });

                    zone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                        
                        const file = e.dataTransfer.files[0];
                        if (file && file.type.startsWith('image/')) {
                            const action = this.dataset.action;
                            const progressBar = this.querySelector('.progress-bar');
                            const progressContainer = this.querySelector('.progress-container');
                            uploadFile(file, action, progressBar, progressContainer);
                        } else {
                            Swal.fire('Invalid File', 'Please drop an image file.', 'error');
                        }
                    });
                });

                // Single Image Upload (Input Change)
                document.querySelectorAll('.upload-input').forEach(input => {
                    input.addEventListener('change', function() {
                        const file = this.files[0];
                        if (file) {
                            const dropZone = this.closest('.drop-zone');
                            const progressBar = dropZone.querySelector('.progress-bar');
                            const progressContainer = dropZone.querySelector('.progress-container');
                            uploadFile(file, dropZone.dataset.action, progressBar, progressContainer);
                        }
                    });
                });

                // Consolidated Merge Logic
                async function handleMerge(ids = null) {
                    const isBulk = !ids;
                    const title = isBulk ? 'Merging Images...' : 'Merging Selected Images...';
                    const payload = ids ? { ids } : {};

                    try {
                        const response = await fetch('{{ route('product-export.process') }}', {
                            method: 'POST', // Fixed typo: replaced 'route' with 'method'
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });
                        
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Failed to start process');

                        const batchId = data.batchId;
                        const total = data.total;
                        
                        Swal.fire({
                            title: title,
                            html: `
                                <div class="mb-2 text-sm">Processing <span id="processed-count">0</span> of ${total} images</div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div id="merge-progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const interval = setInterval(async () => {
                            try {
                                const statusResponse = await fetch(`/product-export/batch-status/${batchId}`);
                                if (!statusResponse.ok) return;
                                
                                const batch = await statusResponse.json();
                                if (!batch) return;

                                const processed = batch.processedJobs;
                                const progress = batch.progress;
                                
                                const progressBar = document.getElementById('merge-progress-bar');
                                if (progressBar) progressBar.style.width = progress + '%';
                                const countLabel = document.getElementById('processed-count');
                                if (countLabel) countLabel.innerText = processed;

                                if (batch.finishedAt) {
                                    clearInterval(interval);
                                    if (batch.failedJobs > 0) {
                                        Swal.fire('Warning!', 'Some jobs failed to process. Check Horizon.', 'warning')
                                            .then(() => window.location.reload());
                                    } else {
                                        Swal.fire('Success!', 'Images merged successfully.', 'success')
                                            .then(() => window.location.reload());
                                    }
                                }
                            } catch (e) {
                                console.error('Polling error:', e);
                            }
                        }, 2000);

                    } catch (error) {
                        Swal.fire('Error!', error.message, 'error');
                    }
                }

                // Process All Merge
                const mergeBtn = document.getElementById('btn-process-merge');
                if (mergeBtn) {
                    mergeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        handleMerge();
                    });
                }

                // Bulk Upload Image Drag & Drop logic
                const bulkImageDropZone = document.getElementById('bulk-image-drop-zone');
                const bulkImageInput = document.getElementById('bulk_image_input');

                if (bulkImageDropZone && bulkImageInput) {
                    bulkImageDropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.classList.add('bg-green-50', 'dark:bg-green-900/20', 'border-green-500');
                    });

                    bulkImageDropZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-500');
                    });

                    bulkImageDropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-500');
                        handleBulkUpload(e.dataTransfer.files);
                    });

                    bulkImageInput.addEventListener('change', function() {
                        handleBulkUpload(this.files);
                    });
                }

                function handleBulkUpload(files) {
                    if (files.length === 0) return;

                    const formData = new FormData();
                    for (let i = 0; i < files.length; i++) {
                        formData.append('images[]', files[i]);
                    }
                    formData.append('_token', '{{ csrf_token() }}');

                    const container = document.getElementById('bulk-upload-progress-container');
                    const bar = document.getElementById('bulk-upload-progress-bar');
                    const count = document.getElementById('bulk-upload-count');
                    const percent = document.getElementById('bulk-upload-percent');

                    container.classList.remove('hidden');
                    count.innerText = files.length;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route('product-export.bulk-upload') }}', true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = function(e) {
                        if (e.lengthComputable) {
                            const p = Math.round((e.loaded / e.total) * 100);
                            bar.style.width = p + '%';
                            percent.innerText = p + '%';
                        }
                    };

                    xhr.onload = function() {
                        const response = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && response.success) {
                            const batchId = response.batchId;
                            
                            // Poll for bulk upload batch status
                            const interval = setInterval(async () => {
                                const statusResponse = await fetch(`/product-export/batch-status/${batchId}`);
                                const batch = await statusResponse.json();

                                if (batch) {
                                    const progress = batch.progress;
                                    bar.style.width = progress + '%';
                                    percent.innerText = progress + '%';

                                    if (batch.finishedAt) {
                                        clearInterval(interval);
                                        Swal.fire('Success!', 'Bulk upload and processing completed.', 'success')
                                            .then(() => window.location.reload());
                                    }
                                }
                            }, 2000);
                        } else {
                            Swal.fire('Error!', response.error || 'Bulk upload failed.', 'error');
                            container.classList.add('hidden');
                        }
                    };
                    
                    xhr.send(formData);
                }

                // Drag & Drop logic for Excel Import
                const excelDropZone = document.getElementById('excel-drop-zone');
                const excelInput = document.getElementById('file_input');

                if (excelDropZone && excelInput) {
                    excelDropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                    });

                    excelDropZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                    });

                    excelDropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        this.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
                        handleExcelImport(e.dataTransfer.files[0]);
                    });

                    excelInput.addEventListener('change', function() {
                        handleExcelImport(this.files[0]);
                    });
                }

                async function handleExcelImport(file) {
                    if (!file) return;
                    
                    const extension = file.name.split('.').pop().toLowerCase();
                    if (!['xlsx', 'xls', 'csv'].includes(extension)) {
                        Swal.fire('Invalid File', 'Please select an Excel or CSV file.', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    // Show initial loading
                    Swal.fire({
                        title: 'Uploading Excel...',
                        text: 'Preparing data for import...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    try {
                        const response = await fetch('{{ route('product-export.import') }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Import failed');

                        const batchId = data.batchId;
                        const total = data.total;

                        Swal.fire({
                            title: 'Importing Data...',
                            html: `
                                <div class="mb-2 text-sm">Processing <span id="import-count">0</span> of ${total} rows</div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div id="import-progress-bar" class="bg-green-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        // Poll for import batch status
                        const interval = setInterval(async () => {
                            const statusResponse = await fetch(`/product-export/batch-status/${batchId}`);
                            const batch = await statusResponse.json();

                            if (batch) {
                                const processed = batch.processedJobs;
                                const progress = batch.progress;
                                
                                const progressBar = document.getElementById('import-progress-bar');
                                if (progressBar) progressBar.style.width = progress + '%';
                                const countLabel = document.getElementById('import-count');
                                if (countLabel) countLabel.innerText = processed;

                                if (batch.finishedAt) {
                                    clearInterval(interval);
                                    Swal.fire('Success!', 'Excel data imported successfully.', 'success')
                                        .then(() => window.location.reload());
                                }
                            }
                        }, 2000);

                    } catch (error) {
                        Swal.fire('Error!', error.message, 'error');
                    }
                }

                // Bulk Actions logic
                const selectAll = document.getElementById('select-all');
                const bulkDeleteBtn = document.getElementById('btn-bulk-delete');
                const mergeSelectedBtn = document.getElementById('btn-merge-selected');
                const selectedCountLabel = document.getElementById('selected-count');
                const mergeSelectedCountSpan = document.getElementById('merge-selected-count');

                function updateBulkActions() {
                    const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
                    const count = checkedCheckboxes.length;
                    
                    if (selectedCountLabel) selectedCountLabel.innerText = count;
                    if (mergeSelectedCountSpan) mergeSelectedCountSpan.innerText = count;

                    if (count > 0) {
                        if (bulkDeleteBtn) bulkDeleteBtn.classList.remove('hidden');
                        if (mergeSelectedBtn) mergeSelectedBtn.classList.remove('hidden');
                    } else {
                        if (bulkDeleteBtn) bulkDeleteBtn.classList.add('hidden');
                        if (mergeSelectedBtn) mergeSelectedBtn.classList.add('hidden');
                    }
                }

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.product-checkbox:not(:disabled)');
                        checkboxes.forEach(cb => {
                            cb.checked = this.checked;
                        });
                        updateBulkActions();
                    });
                }

                // Delegate checkbox change events
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('product-checkbox')) {
                        updateBulkActions();
                    }
                });

                if (mergeSelectedBtn) {
                    mergeSelectedBtn.addEventListener('click', function() {
                        const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                            .map(cb => cb.value);

                        if (selectedIds.length > 0) {
                            handleMerge(selectedIds);
                        }
                    });
                }

                if (bulkDeleteBtn) {
                    bulkDeleteBtn.addEventListener('click', function() {
                        const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                            .map(cb => cb.value);

                        if (selectedIds.length === 0) return;

                        Swal.fire({
                            title: 'Are you sure?',
                            text: `You are about to delete ${selectedIds.length} items and their images!`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete all!'
                        }).then(async (result) => {
                            if (result.isConfirmed) {
                                try {
                                    const response = await fetch('{{ route('product-export.bulk-delete') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: JSON.stringify({ ids: selectedIds })
                                    });

                                    const data = await response.json();
                                    if (response.ok) {
                                        Swal.fire('Deleted!', data.message, 'success')
                                            .then(() => window.location.reload());
                                    } else {
                                        throw new Error(data.error || 'Deletion failed');
                                    }
                                } catch (error) {
                                    Swal.fire('Error!', error.message, 'error');
                                }
                            }
                        });
                    });
                }
            });

            function confirmDelete(form) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data dan gambar yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
                return false;
            }
        </script>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-common.component-card title="Import Data">
                <div class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label for="file_input" id="excel-drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition-all group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400 group-hover:text-blue-500 transition-colors" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2l2 2"/>
                                </svg>
                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Import Excel</span></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Drop file or click here</p>
                            </div>
                            <input id="file_input" type="file" class="hidden" accept=".xlsx,.xls,.csv" />
                        </label>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-400 italic">*Data akan diproses secara otomatis setelah file dipilih atau di-drop</p>
                    </div>
                </div>
            </x-common.component-card>

            <x-common.component-card title="Actions">
                <div class="flex flex-wrap gap-4 items-center">
                    <a href="{{ route('product-export.export-template') }}" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">Download Template</a>
                    <a href="{{ route('product-export.export') }}" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Export All Data</a>
                    
                    <button type="button" id="btn-process-merge" class="text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-purple-600 dark:hover:bg-purple-700 focus:outline-none dark:focus:ring-purple-800">Process Merge Images</button>
                    
                    <button type="button" id="btn-merge-selected" class="hidden text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:outline-none dark:focus:ring-indigo-800 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h14a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Merge Selected (<span id="merge-selected-count">0</span>)
                    </button>

                    <button type="button" id="btn-bulk-delete" class="hidden text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-500 dark:hover:bg-red-600 focus:outline-none dark:focus:ring-red-800 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Delete Selected (<span id="selected-count">0</span>)
                    </button>
                </div>
            </x-common.component-card>
        </div>

        <x-common.component-card title="Product List">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-10">
                                <input type="checkbox" id="select-all" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            </th>
                            <th scope="col" class="px-6 py-3">Product Information</th>
                            <th scope="col" class="px-6 py-3">Order Details</th>
                            <th scope="col" class="px-6 py-3">Gambar</th>
                            <th scope="col" class="px-6 py-3">Merged Image</th>
                            <th scope="col" class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastOrderNumber = null; @endphp
                        @forelse($products as $product)
                            @php 
                                $hasValidImage = $product->image_path && 
                                                 !str_starts_with($product->image_path, '=_xlfn') && 
                                                 Storage::disk('public')->exists($product->image_path);
                                $isAlreadyMerged = (bool)$product->merged_image;
                                $canBeMerged = $hasValidImage && !$isAlreadyMerged;
                            @endphp
                            @if($lastOrderNumber !== $product->order_number)
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                    <td colspan="6" class="px-6 py-2 border-y border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-4">
                                                <span class="font-bold text-blue-600 dark:text-blue-400">#{{ $product->order_number }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 uppercase">Payment: Cash on delivery</span>
                                        </div>
                                    </td>
                                </tr>
                                @php $lastOrderNumber = $product->order_number; @endphp
                            @endif
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $canBeMerged ? '' : 'bg-gray-50/50 dark:bg-gray-800/50' }}">
                                <td class="px-4 py-4">
                                    <input type="checkbox" name="ids[]" value="{{ $product->id }}" 
                                        class="product-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 {{ $canBeMerged ? '' : 'cursor-not-allowed' }}"
                                        {{ $canBeMerged ? '' : 'disabled' }}>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $product->sku_platform }}</span>
                                        <span class="text-xs text-gray-500 mt-1">{{ $product->product_specification }}</span>
                                        <div class="flex gap-2 mt-1">
                                            <span class="text-[10px] text-gray-400">ID Produk: {{ $product->product_id ?? '-' }}</span>
                                            <span class="text-[10px] text-gray-400">ID SKU: {{ $product->sku_id ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Qty: {{ $product->quantity }}</span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-1">
                                            Resi: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $product->tracking_number ?? '-' }}</span>
                                        </div>
                                        @if($product->product_image_url)
                                            <div class="mt-2 group/preview relative inline-block">
                                                <a href="{{ $product->product_image_url }}" target="_blank" class="block">
                                                    <div class="relative w-10 h-10 overflow-hidden rounded border border-gray-200 dark:border-gray-600 shadow-xs bg-gray-50 flex items-center justify-center">
                                                        <img src="{{ $product->product_image_url }}" 
                                                             alt="Original" 
                                                             class="w-full h-full object-cover transition-transform duration-300 group-hover/preview:scale-110"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="hidden items-center justify-center h-full w-full bg-gray-50 text-[8px] text-gray-400">Err</div>
                                                    </div>
                                                </a>
                                                <!-- Tooltip/Label on hover -->
                                                <div class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-[10px] rounded opacity-0 group-hover/preview:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-20">
                                                    Original Image
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 drop-zone transition-all relative group" data-action="{{ route('product-export.upload', $product) }}">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex-shrink-0">
                                            @if($product->image_path && !str_starts_with($product->image_path, '=_xlfn'))
                                                @php
                                                    $thumbnailPath = 'thumbnails/' . basename($product->image_path);
                                                    $displayPath = Storage::disk('public')->exists($thumbnailPath) ? $thumbnailPath : $product->image_path;
                                                @endphp
                                                <img src="{{ Storage::disk('public')->url($displayPath) }}" 
                                                     alt="Product" 
                                                     class="w-12 h-12 object-cover rounded border border-gray-200 dark:border-gray-600 shadow-sm"
                                                     loading="lazy">
                                            @else
                                                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-700/50 border border-dashed border-gray-300 dark:border-gray-600 rounded flex items-center justify-center text-gray-400 group-hover:border-blue-400 group-hover:text-blue-400 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                </div>
                                            @endif
                                            
                                            <input type="file" name="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer upload-input z-10">
                                        </div>
                                        
                                        <div class="flex flex-col min-w-[100px]">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[10px] uppercase tracking-wider text-gray-400 group-hover:text-blue-500 font-semibold transition-colors">
                                                    {{ $product->image_path ? 'Change Image' : 'Drop Image' }}
                                                </span>
                                                <span class="progress-text text-[10px] font-medium text-blue-500 hidden">0%</span>
                                            </div>
                                            <div class="progress-container w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1 hidden">
                                                <div class="progress-bar bg-blue-500 h-1 rounded-full transition-all duration-300" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($product->merged_image)
                                        @php
                                            $mergedThumbnailPath = 'thumbnails/merged/' . basename($product->merged_image);
                                            $displayMergedPath = Storage::disk('public')->exists($mergedThumbnailPath) ? $mergedThumbnailPath : $product->merged_image;
                                        @endphp
                                        <a href="{{ Storage::disk('public')->url($product->merged_image) }}" target="_blank" class="block w-12 h-12">
                                            <img src="{{ Storage::disk('public')->url($displayMergedPath) }}" 
                                                 alt="Merged" 
                                                 class="w-full h-full object-cover rounded shadow-sm hover:scale-105 transition-transform"
                                                 loading="lazy">
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('product-export.destroy', $product) }}" method="POST" onsubmit="return confirmDelete(this)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">
                                    No data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </x-common.component-card>
    </div>
@endsection
