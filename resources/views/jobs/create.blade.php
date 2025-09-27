<!-- resources/views/jobs/create.blade.php -->
<x-layouts.app>

@section('content')
<h1 class="mb-4 text-2xl font-bold text-gray-800 dark:text-gray-100">Create Job</h1>

<form method="POST" action="{{ route('jobs.store') }}" class="rounded border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    @csrf
    <div class="mb-4">
        <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Title</label>
        <input type="text" name="title" class="w-full rounded border p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
    </div>
    <div class="mb-4">
        <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Description</label>
        <textarea name="description" rows="5" class="w-full rounded border p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required></textarea>
    </div>
    <button class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Save</button>
</form>
</x-layouts.app>
