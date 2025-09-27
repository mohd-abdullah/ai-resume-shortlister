<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Resume;
use App\Models\Score;
use App\Services\AIService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    protected AIService $ai;

    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Store uploaded resume for a job
     */
    public function store(Request $request, Job $job): RedirectResponse
    {
        $request->validate([
            'resume' => 'required|mimes:pdf|max:2048',
            'candidate_name' => 'required|string|max:255',
        ]);

        // Store file
        $file = $request->file('resume');
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('resumes', $filename, 'public');

        if ($path === false) {
            return redirect()->back()->with('error', 'Failed to store the resume file.');
        }

        // Extract text from resume
        $resumeText = '';
        $mimeType = $file->getMimeType();

        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') { // .docx
            $resumeText = $this->extractTextFromDocx($path);
        } elseif ($mimeType === 'application/pdf') { // .pdf
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile(storage_path('app/public/' . $path));
            $resumeText = $pdf->getText();
        } else {
            $resumeText = 'Text extraction for this file type is not yet implemented.';
        }

        // Save Resume record
        $resume = Resume::create([
            'job_id' => $job->id,
            'candidate_name' => $request->candidate_name,
            'file_path' => $path,
            'extracted_text' => $resumeText,
        ]);

        try {
            $result = $this->ai->matchResumeToJobAdvanced($resumeText, $job->description);
        } catch (\Exception $e) {
            // Fallback scoring in case Gemini API fails
            $result = $this->keywordBasedScoring($resumeText, $job->description);
        }

        // Save score
        Score::updateOrCreate(
            ['resume_id' => $resume->id],
            [
                'score' => $result['score'],
                'summary' => $result['summary'],
                'matched_keywords' => $result['matched'],
            ]
        );

        return redirect()->route('jobs.show', $job->id)
            ->with('success', 'Resume uploaded and scored successfully!');
    }

    /**
     * Show resume details
     */
    public function show(Job $job, Resume $resume): View
    {
        $resume->load('score');

        return view('resumes.show', compact('job', 'resume'));
    }

    /**
     * Fallback keyword scoring
     * @return array{score: int, matched: array<string>, summary: string}
     */
    protected function keywordBasedScoring(string $resumeText, string $jobDescription): array
    {
        $keywords = [];
        $score = 0;

        $jobKeywords = ['laravel', 'php', 'mysql', 'vue', 'react', 'api', 'javascript'];

        foreach ($jobKeywords as $kw) {
            if (stripos($resumeText, $kw) !== false) {
                $keywords[] = ucfirst($kw);
                $score += 10;
            }
        }

        return [
            'score' => min($score, 100),
            'matched' => $keywords,
            'summary' => 'AI summary could not be generated. Scoring is based on keyword matching.',
        ];
    }

    private function extractTextFromDocx(string $filePath): string
    {
        $striped_content = '';
        $content = '';

        $zip = new \ZipArchive;

        if ($zip->open(storage_path('app/public/'.$filePath)) === true) {
            $data = $zip->getFromName('word/document.xml');
            if ($data !== false) {
                // Strip XML tags
                $content = strip_tags($data);
                $striped_content = preg_replace("/
|
|
/", ' ', $content);
            }
            $zip->close();
        }

        return (string) $striped_content;
    }
}
