@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto min-h-screen bg-gray-50/30 dark:bg-gray-900/10">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <span class="p-2.5 bg-blue-600 rounded-2xl shadow-lg shadow-blue-500/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </span>
                Product Export Management
            </h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Import, manage, and merge product images efficiently.
            </p>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Actions Bar (Import, Export, Merge) --}}
        @include('pages.product-export.partials.actions')

        {{-- Product Table --}}
        @include('pages.product-export.partials.table')
    </div>
</div>

{{-- Scripts --}}
@include('pages.product-export.partials.scripts')

@endsection
