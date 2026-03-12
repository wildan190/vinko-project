// Global data rendering functions for product-export
function renderProductRow(product) {
    const isMerged = product.is_merged;
    const hasValidImage = product.has_valid_image;

    const uploadedImageHtml = hasValidImage ?
        `<img src="${product.image_url}" alt="Uploaded" class="w-12 h-12 object-cover rounded border border-gray-200 dark:border-gray-600 shadow-sm" loading="lazy">` :
        `<div class="w-12 h-12 bg-gray-50 dark:bg-gray-700/50 border border-dashed border-gray-300 dark:border-gray-600 rounded flex items-center justify-center text-gray-400 group-hover:border-blue-400 group-hover:text-blue-400 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        </div>`;

    const mergedImageHtml = isMerged ?
        `<a href="${product.merged_image_url}" target="_blank" class="block w-12 h-12 group/merged relative">
            <img src="${product.merged_thumbnail_url}" alt="Merged" class="w-full h-full object-cover rounded shadow-sm hover:scale-110 transition-transform duration-300" loading="lazy">
            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover/merged:opacity-100 transition-opacity rounded flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </div>
        </a>` :
        `<div class="w-12 h-12 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded flex items-center justify-center">
            <span class="text-[8px] text-gray-400 uppercase font-bold">Pending</span>
        </div>`;
    
    const detailsLink = isMerged ? `<a href="${product.merged_image_url}" target="_blank" class="text-xs text-blue-600 hover:underline">Details</a>` : `<span class="text-xs text-gray-300 italic">Pending</span>`;

    return `
        <td class="px-4 py-4 text-center">
            <input type="hidden" class="product-id" value="${product.id}">
            <input type="hidden" class="product-merged" value="${isMerged ? '1' : '0'}">
            <input type="hidden" class="product-valid" value="${hasValidImage ? '1' : '0'}">
        </td>
        <td class="px-6 py-4">
            <div class="flex gap-3">
                ${product.product_image_url ? `
                <div class="flex-shrink-0 relative group/preview">
                    <a href="${product.product_image_url}" target="_blank" class="block w-14 h-14 overflow-hidden rounded border border-gray-200 dark:border-gray-600 bg-gray-50">
                        <img src="${product.product_image_url}" 
                             alt="Original" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover/preview:scale-110"
                             onerror="this.src='/assets/images/placeholder.jpg'">
                    </a>
                </div>` : ''}
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-white text-xs leading-tight mb-1">${product.sku_platform} x ${product.quantity}</span>
                    <span class="text-[10px] text-gray-500 leading-tight">Option: ${product.product_specification}</span>
                    <span class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">warehouse: Indonesian warehouse</span>
                    <a href="${product.product_image_url}" target="_blank" class="text-[10px] text-blue-500 hover:underline mt-1">source <svg class="inline-block w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></a>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 drop-zone transition-all relative group" data-action="${product.upload_url}">
            <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                    ${uploadedImageHtml}
                    <input type="file" name="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer upload-input z-10">
                </div>
                <div class="flex flex-col min-w-[80px]">
                    <span class="progress-text text-[10px] font-medium text-blue-500 hidden mb-1">0%</span>
                    <div class="progress-container w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1 hidden">
                        <div class="progress-bar bg-blue-500 h-1 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <a href="#" class="text-xs text-blue-600 hover:underline font-medium mb-1">${product.order_number}</a>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-600 text-white w-fit uppercase">Book</span>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <span class="text-xs text-blue-500 font-medium">TikTok online shipping</span>
            </div>
        </td>
        <td class="px-6 py-4">
            ${mergedImageHtml}
        </td>
        <td class="px-6 py-4">
            <span class="text-xs text-gray-700 dark:text-gray-300">Paid</span>
        </td>
        <td class="px-6 py-4 text-right">
            <div class="flex flex-col items-end gap-1">
                ${detailsLink}
                <button type="button" class="delete-btn text-[10px] text-red-500 hover:text-red-700 transition-colors uppercase font-bold tracking-tighter" data-url="${product.delete_url}">Delete</button>
            </div>
        </td>
    `;
}

async function fetchAndRenderProducts(url = ProductExportConfig.routes.data) {
    const tableBody = document.getElementById('product-table-body');
    const paginationContainer = document.getElementById('pagination-links');
    
    if (!tableBody) return;

    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch data');
        
        const result = await response.json();
        
        // Clear and render table
        tableBody.innerHTML = '';
        if (result.data && result.data.length > 0) {
            let lastOrderNumber = null;
            result.data.forEach(product => {
                if (lastOrderNumber !== product.order_number) {
                    const groupRow = document.createElement('tr');
                    groupRow.className = 'bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700';
                    groupRow.innerHTML = `
                        <td colspan="8" class="px-4 py-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" class="group-checkbox w-3.5 h-3.5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400 cursor-pointer">#${product.order_number}</span>
                                </div>
                                <div class="flex items-center gap-6 text-[10px] text-gray-500 font-medium">
                                    <span>Payment: Cash on delivery</span>
                                    <span>Buyer's designation: Economical</span>
                                    <span class="text-gray-400">TikTok: TQ01-ID</span>
                                </div>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(groupRow);
                    lastOrderNumber = product.order_number;
                }
                
                const productRow = document.createElement('tr');
                productRow.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors';
                productRow.dataset.productId = product.id;
                productRow.innerHTML = renderProductRow(product);
                tableBody.appendChild(productRow);
            });
        } else {
            tableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-10 text-center text-gray-400 italic">No data available.</td></tr>`;
        }
        
        // Render pagination
        renderPagination(result);
        
        // Reset bulk actions state
        const selectAll = document.getElementById('select-all');
        if (selectAll) selectAll.checked = false;
        
        // The function updateBulkActions is defined later in DOMContentLoaded
        // so we manually hide the bar and reset counters if we can't call it
        const bar = document.getElementById('bulk-actions-bar');
        if (bar) bar.classList.add('hidden');
        
        const selectedCount = document.getElementById('selected-count');
        if (selectedCount) selectedCount.innerText = '0';

        const mergeCount = document.getElementById('merge-selected-count');
        if (mergeCount) mergeCount.innerText = '0';

        const downloadCount = document.getElementById('download-selected-count');
        if (downloadCount) downloadCount.innerText = '0';
        
    } catch (error) {
        tableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-10 text-center text-red-400 italic">Error loading data.</td></tr>`;
    }
}

function renderPagination(result) {
    const paginationContainer = document.getElementById('pagination-links');
    if (!paginationContainer) return;
    
    paginationContainer.innerHTML = '';
    if (!result.links || result.links.length <= 3) return;

    const nav = document.createElement('nav');
    nav.className = 'flex items-center gap-2';
    
    result.links.forEach(link => {
        if (link.url || link.label.includes('...')) {
            const element = link.url ? document.createElement('a') : document.createElement('span');
            element.innerHTML = link.label.replace('&laquo;', '').replace('&raquo;', '');
            
            if (link.url) {
                element.href = link.url;
                element.dataset.url = link.url;
                element.className = `pagination-link px-3 py-2 text-xs font-medium rounded-md transition-colors ${link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-300'}`;
            } else {
                element.className = 'px-3 py-2 text-gray-500';
            }
            
            nav.appendChild(element);
        }
    });
    
    paginationContainer.appendChild(nav);
}

document.addEventListener('DOMContentLoaded', function() {
    let activeXHR = null;

    window.addEventListener('beforeunload', function (e) {
        if (activeXHR) {
            activeXHR.abort(); // Cancel active upload on reload
        }
    });

    // AJAX Upload helper function
    function uploadFile(file, action, progressBar, progressContainer) {
        if (!progressBar || !progressContainer) {
            console.warn('Progress elements not found for upload');
        }

        const formData = new FormData();
        formData.append('image', file);
        formData.append('_token', ProductExportConfig.csrfToken);
        
        if (progressContainer) progressContainer.classList.remove('hidden');
        const progressText = progressContainer ? (progressContainer.previousElementSibling ? progressContainer.previousElementSibling.querySelector('.progress-text') : null) : null;
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
                    if (progressContainer) progressContainer.classList.add('hidden');
                    if (progressText) progressText.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
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
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.product) {
                    Swal.fire({
                        title: 'Uploaded!',
                        text: 'Image has been uploaded.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });

                    // Find the row and update it
                    const rowToUpdate = document.querySelector(`tr[data-product-id="${response.product.id}"]`);
                    if (rowToUpdate) {
                        const newRow = document.createElement('tr');
                        newRow.dataset.productId = response.product.id;
                        newRow.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors';
                        newRow.innerHTML = renderProductRow(response.product);
                        rowToUpdate.parentNode.replaceChild(newRow, rowToUpdate);
                    }
                } else if (response.success) {
                    // Just a simple success without product object
                    Swal.fire({
                        title: 'Uploaded!',
                        text: 'Image has been uploaded.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    // Refresh data
                    const currentPageLink = document.querySelector('#pagination-links .pagination-link.bg-blue-600');
                    const refetchUrl = currentPageLink ? currentPageLink.dataset.url : ProductExportConfig.routes.data;
                    fetchAndRenderProducts(refetchUrl);
                } else {
                    Swal.fire('Error!', 'Upload succeeded but no product data was returned.', 'error');
                }
            } else {
                let errorMsg = 'Upload failed.';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    errorMsg = errorResponse.message || 'Upload failed: ' + xhr.statusText;
                } catch (e) { /* Ignore parsing error */ }
                Swal.fire('Error!', errorMsg, 'error');
            }

            if (progressContainer) {
                progressContainer.classList.add('hidden');
                const progressText = progressContainer.previousElementSibling ? progressContainer.previousElementSibling.querySelector('.progress-text') : null;
                if (progressText) progressText.classList.add('hidden');
            }
            if (progressBar) progressBar.style.width = '0%';
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
    document.addEventListener('dragover', function(e) {
        const zone = e.target.closest('.drop-zone');
        if (zone) {
            e.preventDefault();
            zone.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
        }
    });

    document.addEventListener('dragleave', function(e) {
        const zone = e.target.closest('.drop-zone');
        if (zone) {
            e.preventDefault();
            zone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
        }
    });

    document.addEventListener('drop', function(e) {
        const zone = e.target.closest('.drop-zone');
        if (zone) {
            e.preventDefault();
            zone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-500');
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const action = zone.dataset.action;
                const progressBar = zone.querySelector('.progress-bar');
                const progressContainer = zone.querySelector('.progress-container');
                uploadFile(file, action, progressBar, progressContainer);
            } else {
                Swal.fire('Invalid File', 'Please drop an image file.', 'error');
            }
        }
    });

    // Single Image Upload (Input Change)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('upload-input')) {
            const file = e.target.files[0];
            if (file) {
                const dropZone = e.target.closest('.drop-zone');
                const progressBar = dropZone.querySelector('.progress-bar');
                const progressContainer = dropZone.querySelector('.progress-container');
                uploadFile(file, dropZone.dataset.action, progressBar, progressContainer);
            }
        }
    });

    // Consolidated Merge Logic
    async function handleMerge(ids = null) {
        const isBulk = !ids;
        const title = isBulk ? 'Merging Images...' : 'Merging Selected Images...';
        const payload = ids ? { ids } : {};

        try {
            const response = await fetch(ProductExportConfig.routes.process, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': ProductExportConfig.csrfToken,
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
                        // Determine current page for refetching
                        const currentPageLink = document.querySelector('#pagination-links .pagination-link.bg-blue-600');
                        const refetchUrl = currentPageLink ? currentPageLink.dataset.url : ProductExportConfig.routes.data;

                        if (batch.failedJobs > 0) {
                            Swal.fire('Warning!', 'Some jobs failed to process. Check Horizon.', 'warning')
                                .then(() => fetchAndRenderProducts(refetchUrl));
                        } else {
                            Swal.fire('Success!', 'Images merged successfully.', 'success')
                                .then(() => fetchAndRenderProducts(refetchUrl));
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
        formData.append('_token', ProductExportConfig.csrfToken);

        // Show initial loading
        Swal.fire({
            title: 'Uploading Excel...',
            text: 'Preparing data for import...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const response = await fetch(ProductExportConfig.routes.import, {
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
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const bulkDeleteBtn = document.getElementById('btn-bulk-delete');
    const mergeSelectedBtn = document.getElementById('btn-merge-selected');
    const downloadSelectedBtn = document.getElementById('btn-download-selected');
    const selectedCountLabel = document.getElementById('selected-count');
    const mergeSelectedCountSpan = document.getElementById('merge-selected-count');
    const downloadSelectedCountSpan = document.getElementById('download-selected-count');

    function updateBulkActions() {
        const checkedGroups = document.querySelectorAll('.group-checkbox:checked');
        let totalSelected = 0;
        let mergeableIds = [];
        let mergedIds = [];
        let allIds = [];

        checkedGroups.forEach(groupCb => {
            const tr = groupCb.closest('tr');
            let nextTr = tr.nextElementSibling;
            
            // Find all product rows belonging to this group
            while (nextTr && !nextTr.querySelector('.group-checkbox')) {
                const productIdInput = nextTr.querySelector('.product-id');
                if (productIdInput) {
                    const id = productIdInput.value;
                    const isValid = nextTr.querySelector('.product-valid').value === '1';
                    const isMerged = nextTr.querySelector('.product-merged').value === '1';

                    allIds.push(id);
                    if (isValid) mergeableIds.push(id);
                    if (isMerged) mergedIds.push(id);
                    totalSelected++;
                }
                nextTr = nextTr.nextElementSibling;
            }
        });

        if (selectedCountLabel) selectedCountLabel.innerText = totalSelected;
        if (mergeSelectedCountSpan) mergeSelectedCountSpan.innerText = mergeableIds.length;
        if (downloadSelectedCountSpan) downloadSelectedCountSpan.innerText = mergedIds.length;

        // Store selected IDs for actions
        window.currentSelectedIds = allIds;
        window.currentMergeableIds = mergeableIds;
        window.currentMergedIds = mergedIds;

        if (bulkActionsBar) {
            if (totalSelected > 0) {
                bulkActionsBar.classList.remove('hidden');
            } else {
                bulkActionsBar.classList.add('hidden');
            }
        }

        if (totalSelected > 0) {
            if (bulkDeleteBtn) bulkDeleteBtn.classList.remove('hidden');
        } else {
            if (bulkDeleteBtn) bulkDeleteBtn.classList.add('hidden');
        }

        if (mergeableIds.length > 0) {
            if (mergeSelectedBtn) mergeSelectedBtn.classList.remove('hidden');
        } else {
            if (mergeSelectedBtn) mergeSelectedBtn.classList.add('hidden');
        }

        if (mergedIds.length > 0) {
            if (downloadSelectedBtn) downloadSelectedBtn.classList.remove('hidden');
        } else {
            if (downloadSelectedBtn) downloadSelectedBtn.classList.add('hidden');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const groupCheckboxes = document.querySelectorAll('.group-checkbox');
            groupCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkActions();
        });
    }

    // Group checkbox logic
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('group-checkbox')) {
            updateBulkActions();
        }
    });

    if (mergeSelectedBtn) {
        mergeSelectedBtn.addEventListener('click', function() {
            if (window.currentMergeableIds && window.currentMergeableIds.length > 0) {
                handleMerge(window.currentMergeableIds);
            }
        });
    }

    if (downloadSelectedBtn) {
        downloadSelectedBtn.addEventListener('click', function() {
            if (window.currentMergedIds && window.currentMergedIds.length > 0) {
                const url = ProductExportConfig.routes.downloadMerged + '?ids=' + window.currentMergedIds.join(',');
                window.location.href = url;
            }
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            if (!window.currentSelectedIds || window.currentSelectedIds.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete all products in the selected orders (${window.currentSelectedIds.length} items)!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete all!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(ProductExportConfig.routes.bulkDelete, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': ProductExportConfig.csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ ids: window.currentSelectedIds })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            Swal.fire('Deleted!', data.message, 'success')
                                .then(() => {
                                    const currentPageLink = document.querySelector('#pagination-links .pagination-link.bg-blue-600');
                                    const refetchUrl = currentPageLink ? currentPageLink.dataset.url : ProductExportConfig.routes.data;
                                    fetchAndRenderProducts(refetchUrl);
                                });
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

    // Initial data load
    const initialUrl = ProductExportConfig.routes.data + window.location.search;
    fetchAndRenderProducts(initialUrl);

    // Handle pagination clicks
    document.addEventListener('click', function(e) {
        const paginationLink = e.target.closest('#pagination-links a');
        if (paginationLink) {
            e.preventDefault();
            const url = paginationLink.href;
            if (url) {
                fetchAndRenderProducts(url);
                window.history.pushState({path: url}, '', url);
            }
        }
    });

    // Handle single item delete via event delegation
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const url = deleteBtn.dataset.url;
            if (!url) return;

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': ProductExportConfig.csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            Swal.fire('Deleted!', data.message, 'success');
                            const currentPageLink = document.querySelector('#pagination-links .pagination-link.bg-blue-600');
                            const refetchUrl = currentPageLink ? currentPageLink.dataset.url : ProductExportConfig.routes.data;
                            fetchAndRenderProducts(refetchUrl);
                        } else {
                            throw new Error(data.error || 'Deletion failed');
                        }
                    } catch (error) {
                        Swal.fire('Error!', error.message, 'error');
                    }
                }
            });
        }
    });
});
