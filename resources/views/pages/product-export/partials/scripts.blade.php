<script>
    const ProductExportConfig = {
        csrfToken: '{{ csrf_token() }}',
        routes: {
            data: '{{ route('product-export.data') }}',
            import: '{{ route('product-export.import') }}',
            process: '{{ route('product-export.process') }}',
            bulkDelete: '{{ route('product-export.bulk-delete') }}',
            downloadMerged: '{{ route('product-export.download-merged') }}'
        }
    };
</script>
<script src="{{ asset('js/product-export.js') }}"></script>
