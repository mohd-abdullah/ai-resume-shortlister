<?php

// database/seeders/DemoDataSeeder.php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Resume;
use App\Models\Score;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Demo User (HR)
        $user = User::firstOrCreate(
            ['email' => 'hr@demo.com'],
            [
                'name' => 'Demo HR',
                'password' => Hash::make('password')
            ]
        );

        // Create Demo Job
        $job = Job::create([
            'title' => 'Laravel Developer',
            'description' => 'We are looking for a Laravel Developer with strong knowledge of PHP, MySQL, REST APIs, and Vue.js.',
            'created_by' => $user->id,
        ]);

        // Create Sample Resumes
        $resumes = [
            [
                'candidate_name' => 'Alice Johnson',
                'file_path' => 'resumes/alice.pdf',
                'extracted_text' => 'Alice has 5 years of experience in PHP and Laravel. She also worked with Vue.js and MySQL.',
            ],
            [
                'candidate_name' => 'Bob Smith',
                'file_path' => 'resumes/bob.pdf',
                'extracted_text' => 'Bob has 3 years of experience in Java and Spring Boot. Basic PHP knowledge but no Laravel experience.',
            ],
            [
                'candidate_name' => 'Charlie Kumar',
                'file_path' => 'resumes/charlie.pdf',
                'extracted_text' => 'Charlie is a full-stack developer skilled in Laravel, React.js, and database design. 6 years experience.',
            ],
        ];

        foreach ($resumes as $r) {
            $resume = Resume::create([
                'job_id' => $job->id,
                'candidate_name' => $r['candidate_name'],
                'file_path' => $r['file_path'],
                'extracted_text' => $r['extracted_text'],
            ]);

            // Simulated Scores (instead of real AI for demo speed)
            $score = 0;
            $keywords = [];

            if (str_contains(strtolower($r['extracted_text']), 'laravel')) {
                $score += 40;
                $keywords[] = 'Laravel';
            }
            if (str_contains(strtolower($r['extracted_text']), 'php')) {
                $score += 30;
                $keywords[] = 'PHP';
            }
            if (str_contains(strtolower($r['extracted_text']), 'mysql')) {
                $score += 20;
                $keywords[] = 'MySQL';
            }
            if (str_contains(strtolower($r['extracted_text']), 'vue')) {
                $score += 10;
                $keywords[] = 'Vue.js';
            }

            Score::create([
                'resume_id' => $resume->id,
                'score' => $score,
                'matched_keywords' => $keywords,
            ]);
        }
    }
}
