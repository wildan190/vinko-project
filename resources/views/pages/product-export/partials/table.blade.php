<div class="relative overflow-hidden bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                    </th>
                    <th scope="col" class="px-6 py-3 min-w-[280px]">Product Information</th>
                    <th scope="col" class="px-6 py-3">Unggah Gambar</th>
                    <th scope="col" class="px-6 py-3">Order number</th>
                    <th scope="col" class="px-6 py-3">Logistics methods</th>
                    <th scope="col" class="px-6 py-3">Merged Image</th>
                    <th scope="col" class="px-6 py-3">state</th>
                    <th scope="col" class="px-6 py-3 text-right">operate</th>
                </tr>
            </thead>
            <tbody id="product-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($products as $product)
                    <!-- Rows will be populated by AJAX, but we keep initial SSR for SEO/initial load if needed -->
                    {{-- SSR row content matches renderProductRow JS --}}
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-400 italic">No data available. Please import Excel first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="pagination-links" class="mt-6 flex justify-center">
    {{ $products->links() }}
</div>
