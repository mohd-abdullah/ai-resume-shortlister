<!-- resources/views/jobs/show.blade.php -->
<x-layouts.app>
@section('content')
<h1 class="mb-4 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $job->title }}</h1>
<p class="mb-6 text-gray-700 dark:text-gray-300">{{ $job->description }}</p>

<!-- Upload Resume Form -->
<div class="mb-6 rounded border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-3 text-xl font-semibold text-gray-800 dark:text-gray-100">Upload Resume</h2>
    <form method="POST" action="{{ route('resumes.upload', $job->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Candidate Name</label>
            <input type="text" name="candidate_name" class="w-full rounded border p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
        </div>
        <div class="mb-3">
            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Resume File</label>
            <input type="file" accept="application/pdf" name="resume" class="w-full rounded border p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
        </div>
        <div>
            <x-button type="primary">{{ __('Upload') }}</x-button>
        </div>
    </form>
</div>

<!-- Ranking Dashboard -->
<div class="rounded border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-3 text-xl font-semibold text-gray-800 dark:text-gray-100">Shortlist Results</h2>
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-700">
                <th class="p-2 text-left text-gray-700 dark:text-gray-200">Candidate</th>
                <th class="p-2 text-left text-gray-700 dark:text-gray-200">Score</th>
                <th class="p-2 text-left text-gray-700 dark:text-gray-200">Matched Keywords</th>
                <th class="p-2 text-center text-gray-700 dark:text-gray-200">Actions</th>
            </tr>
        </thead>
        @forelse($job->resumes as $resume)
            <tbody x-data="{ open: false }" class="border-b dark:border-gray-700">
                <tr>
                    <td class="p-2 text-gray-700 dark:text-gray-300">{{ $resume->candidate_name }}</td>
                    <td class="p-2 font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $resume->score->score ?? 'Pending' }}%
                    </td>
                    <td class="p-2 text-gray-700 dark:text-gray-300">
                        @if($resume->score && $resume->score->matched_keywords)
                            @foreach($resume->score->matched_keywords as $kw)
                                <span class="rounded bg-green-100 px-2 py-1 text-sm text-green-700 dark:bg-green-900 dark:text-green-300">{{ $kw }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="p-2 text-center">
                        <div class="flex justify-center space-x-2">
                            <a href="{{ Storage::url($resume->file_path) }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">View</a>
                            <button @click="open = !open" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                <span x-show="!open">Summary</span>
                                <span x-show="open">Hide</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr x-show="open" style="display: none;">
                    <td colspan="4" class="bg-gray-50 p-4 dark:bg-gray-900">
                        <h4 class="mb-2 font-semibold text-gray-800 dark:text-gray-100">AI Summary</h4>
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                            {{ $resume->score->summary ?? 'No summary available.' }}
                        </p>
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody>
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-500">No resumes uploaded yet.</td>
                </tr>
            </tbody>
        @endforelse
    </table>
</div>
</x-layouts.app>
