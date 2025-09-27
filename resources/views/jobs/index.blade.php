<!-- resources/views/jobs/index.blade.php -->
<x-layouts.app>

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Job Posts</h1>
        <a href="{{ route('jobs.create') }}"
            class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">+ New Job</a>
    </div>

    <div class="grid gap-4">
        @forelse($jobs as $job)
            <div class="rounded border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $job->title }}</h2>
                <p class="mb-2 text-gray-700 dark:text-gray-300">{{ Str::limit($job->description, 120) }}</p>
                <a href="{{ route('jobs.show', $job->id) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">View →</a>
            </div>
        @empty
            <p class="text-center text-gray-500 dark:text-gray-400">No jobs posted yet.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
</x-layouts.app>
