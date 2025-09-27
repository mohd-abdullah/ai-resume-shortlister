<?php

namespace App\Services;

use HosseinHezami\LaravelGemini\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class AIService
{
    public function extractSkills(string $resumeText): array
    {
        Log::info('AIService: extractSkills called.');
        try {
            $prompt = "Extract 5-10 key technical skills from this resume text. Return only the skills, one per line:\n\n{$resumeText}";

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->prompt($prompt)
                ->temperature(0.3)
                ->maxTokens(500)
                ->generate();

            $output = $response->content() ?? '';
            Log::info('AIService: Gemini API response for extractSkills: ' . $output);

            $skills = preg_split("/[\n,]+/", trim($output));
            return $skills === false ? [] : array_filter(array_map('trim', $skills));
        } catch (\Exception $e) {
            Log::error('AIService: Error in extractSkills: ' . $e->getMessage());
            return $this->fallbackExtractSkills($resumeText);
        }
    }

    public function matchResumeToJob(string $resumeText, string $jobDescription): array
    {
        Log::info('AIService: matchResumeToJob called.');
        try {
            $prompt = "You are an AI resume evaluator. Compare the following resume against the job description and give:
1. A score from 0 to 100 (higher is better fit).
2. A list of matched keywords/skills.
3. A short summary of positive and negative points

Respond ONLY in JSON format like:
{\"score\": 85,\"summary\": \"...\", \"matched\": [\"Laravel\", \"PHP\", \"MySQL\"]}

Job Description:
{$jobDescription}

Resume:
{$resumeText}";

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->system('You are an expert HR recruiter and resume evaluator.')
                ->prompt($prompt)
                ->temperature(0.2)
                ->maxTokens(1000)
                ->generate();

            $output = $response->content() ?? null;
            Log::info('AIService: Gemini API response for matchResumeToJob: ' . $output);

            if ($output === null) {
                return $this->getDefaultMatchResult();
            }

            // Clean the output to extract JSON (remove markdown code blocks if present)
            $cleanOutput = $this->extractJsonFromResponse($output);
            
            $json = json_decode($cleanOutput, true);
            if (!is_array($json)) {
                Log::warning('AIService: Invalid JSON response from Gemini: ' . $output);
                return $this->getDefaultMatchResult();
            }

            return [
                'score' => max(0, min(100, (int)($json['score'] ?? 0))), // Ensure score is between 0-100
                'summary' => $json['summary'] ?? 'No summary provided',
                'matched' => is_array($json['matched']) ? array_map('strval', array_filter($json['matched'])) : [],
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error in matchResumeToJob: ' . $e->getMessage());
            return $this->getDefaultMatchResult();
        }
    }

    /**
     * Enhanced version with structured JSON output
     */
    public function matchResumeToJobAdvanced(string $resumeText, string $jobDescription): array
    {
        Log::info('AIService: matchResumeToJobAdvanced called.');
        try {
            $prompt = "Analyze this resume against the job description and provide detailed evaluation.

Job Description:
{$jobDescription}

Resume:
{$resumeText}";

            $schema = [
                'type' => 'object',
                'properties' => [
                    'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'matched' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'missing' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'summary' => ['type' => 'string'],
                    'experience_match' => ['type' => 'string'],
                    'recommendation' => ['type' => 'string']
                ],
                'required' => ['score', 'matched', 'missing', 'summary']
            ];

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->system('You are an expert HR recruiter. Evaluate resumes thoroughly against job descriptions.')
                ->structuredSchema($schema)
                ->prompt($prompt)
                ->temperature(0.3)
                ->maxTokens(1500)
                ->generate();

            $output = $response->content() ?? null;
            Log::info('AIService: Gemini API response for matchResumeToJobAdvanced: ' . $output);

            if ($output === null) {
                return $this->getAdvancedDefaultResult();
            }

            $json = json_decode($output, true);
            if (!is_array($json)) {
                Log::warning('AIService: Invalid JSON response from Gemini: ' . $output);
                return $this->getAdvancedDefaultResult();
            }

            return [
                'score' => max(0, min(100, (int)($json['score'] ?? 0))),
                'matched' => is_array($json['matched'] ?? []) ? array_map('strval', array_filter($json['matched'])) : [],
                'missing' => is_array($json['missing'] ?? []) ? array_map('strval', array_filter($json['missing'])) : [],
                'summary' => $json['summary'] ?? 'No summary provided',
                'experience_match' => $json['experience_match'] ?? 'unknown',
                'recommendation' => $json['recommendation'] ?? 'Manual review required',
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error in matchResumeToJobAdvanced: ' . $e->getMessage());
            return $this->getAdvancedDefaultResult();
        }
    }

    /**
     * Extract job requirements with structured output
     */
    public function extractJobRequirements(string $jobDescription): array
    {
        Log::info('AIService: extractJobRequirements called.');
        try {
            $prompt = "Analyze this job description and extract all key requirements and information:\n\n{$jobDescription}";

            $schema = [
                'type' => 'object',
                'properties' => [
                    'required_skills' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'preferred_skills' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'experience_level' => ['type' => 'string'],
                    'key_responsibilities' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'soft_skills' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'education_requirements' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'salary_range' => ['type' => 'string'],
                    'work_type' => ['type' => 'string']
                ],
                'required' => ['required_skills', 'experience_level', 'key_responsibilities']
            ];

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->system('You are an expert job analyst. Extract comprehensive information from job descriptions.')
                ->structuredSchema($schema)
                ->prompt($prompt)
                ->temperature(0.2)
                ->maxTokens(1200)
                ->generate();

            $output = $response->content() ?? '';
            Log::info('AIService: Gemini API response for extractJobRequirements: ' . $output);

            $json = json_decode($output, true);
            if (!is_array($json)) {
                return $this->getJobRequirementsDefault();
            }

            return [
                'required_skills' => is_array($json['required_skills'] ?? []) ? $json['required_skills'] : [],
                'preferred_skills' => is_array($json['preferred_skills'] ?? []) ? $json['preferred_skills'] : [],
                'experience_level' => $json['experience_level'] ?? 'unknown',
                'key_responsibilities' => is_array($json['key_responsibilities'] ?? []) ? $json['key_responsibilities'] : [],
                'soft_skills' => is_array($json['soft_skills'] ?? []) ? $json['soft_skills'] : [],
                'education_requirements' => is_array($json['education_requirements'] ?? []) ? $json['education_requirements'] : [],
                'salary_range' => $json['salary_range'] ?? 'Not specified',
                'work_type' => $json['work_type'] ?? 'Not specified'
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error in extractJobRequirements: ' . $e->getMessage());
            return $this->getJobRequirementsDefault();
        }
    }

    /**
     * Analyze resume quality with structured output
     */
    public function analyzeResumeQuality(string $resumeText): array
    {
        Log::info('AIService: analyzeResumeQuality called.');
        try {
            $prompt = "Analyze this resume for overall quality and provide comprehensive feedback:\n\n{$resumeText}";

            $schema = [
                'type' => 'object',
                'properties' => [
                    'overall_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'strengths' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'weaknesses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'suggestions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'missing_sections' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'ats_friendly' => ['type' => 'boolean'],
                    'readability_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10]
                ],
                'required' => ['overall_score', 'strengths', 'suggestions', 'ats_friendly']
            ];

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->system('You are an expert resume reviewer and career consultant.')
                ->structuredSchema($schema)
                ->prompt($prompt)
                ->temperature(0.3)
                ->maxTokens(1500)
                ->generate();

            $output = $response->content() ?? '';
            Log::info('AIService: Gemini API response for analyzeResumeQuality: ' . $output);

            $json = json_decode($output, true);
            if (!is_array($json)) {
                return $this->getResumeQualityDefault();
            }

            return [
                'overall_score' => max(0, min(100, (int)($json['overall_score'] ?? 0))),
                'strengths' => is_array($json['strengths'] ?? []) ? $json['strengths'] : [],
                'weaknesses' => is_array($json['weaknesses'] ?? []) ? $json['weaknesses'] : [],
                'suggestions' => is_array($json['suggestions'] ?? []) ? $json['suggestions'] : [],
                'missing_sections' => is_array($json['missing_sections'] ?? []) ? $json['missing_sections'] : [],
                'ats_friendly' => (bool)($json['ats_friendly'] ?? false),
                'readability_score' => max(0, min(10, (int)($json['readability_score'] ?? 0))),
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error in analyzeResumeQuality: ' . $e->getMessage());
            return $this->getResumeQualityDefault();
        }
    }

    /**
     * Generate interview questions with structured output
     */
    public function generateInterviewQuestions(string $resumeText, string $jobDescription): array
    {
        Log::info('AIService: generateInterviewQuestions called.');
        try {
            $prompt = "Based on this resume and job description, generate relevant interview questions:

Job Description:
{$jobDescription}

Resume:
{$resumeText}";

            $schema = [
                'type' => 'object',
                'properties' => [
                    'technical_questions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'behavioral_questions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'situational_questions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'experience_questions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ]
                ],
                'required' => ['technical_questions', 'behavioral_questions']
            ];

            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->system('You are an expert interviewer who creates relevant, insightful questions.')
                ->structuredSchema($schema)
                ->prompt($prompt)
                ->temperature(0.4)
                ->maxTokens(1200)
                ->generate();

            $output = $response->content() ?? '';
            Log::info('AIService: Gemini API response for generateInterviewQuestions: ' . $output);

            $json = json_decode($output, true);
            if (!is_array($json)) {
                return $this->getInterviewQuestionsDefault();
            }

            return [
                'technical_questions' => is_array($json['technical_questions'] ?? []) ? $json['technical_questions'] : [],
                'behavioral_questions' => is_array($json['behavioral_questions'] ?? []) ? $json['behavioral_questions'] : [],
                'situational_questions' => is_array($json['situational_questions'] ?? []) ? $json['situational_questions'] : [],
                'experience_questions' => is_array($json['experience_questions'] ?? []) ? $json['experience_questions'] : [],
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error in generateInterviewQuestions: ' . $e->getMessage());
            return $this->getInterviewQuestionsDefault();
        }
    }

    /**
     * Batch processing with rate limiting
     */
    public function batchMatchResumes(array $resumes, string $jobDescription): array
    {
        Log::info('AIService: batchMatchResumes called with ' . count($resumes) . ' resumes.');
        
        $results = [];
        $batchSize = 3; // Conservative batch size
        $chunks = array_chunk($resumes, $batchSize, true);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $index => $resumeText) {
                try {
                    $result = $this->matchResumeToJob($resumeText, $jobDescription);
                    $results[$index] = $result;
                    
                    // Small delay between requests to avoid rate limits
                    usleep(500000); // 0.5 seconds
                } catch (\Exception $e) {
                    Log::error("AIService: Error processing resume {$index}: " . $e->getMessage());
                    $results[$index] = $this->getDefaultMatchResult();
                }
            }
            
            // Longer delay between batches
            if ($chunkIndex < count($chunks) - 1) {
                sleep(2); // 2 seconds between batches
            }
        }

        return $results;
    }

    /**
     * Test the connection
     */
    public function testConnection(): array
    {
        try {
            $response = Gemini::text()
                ->model('gemini-2.5-flash-lite')
                ->prompt('Hello! Please respond with "Connection successful" to confirm the integration is working.')
                ->temperature(0.1)
                ->maxTokens(50)
                ->generate();
            
            return [
                'status' => 'success',
                'response' => $response->content(),
                'model' => $response->model(),
                'usage' => $response->usage(),
                'package' => 'hosseinhezami/laravel-gemini'
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Connection test failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'package' => 'hosseinhezami/laravel-gemini'
            ];
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        try {
            // Run artisan command to get models
            $output = shell_exec('php artisan gemini:models 2>&1');
            
            return [
                'command_output' => $output,
                'common_models' => [
                    'gemini-2.5-flash-lite' => 'Fast, lightweight model for most tasks',
                    'gemini-2.5-flash' => 'Balanced performance model',
                    'gemini-2.5-flash-image-preview' => 'Image generation model',
                    'veo-3.0-fast-generate-001' => 'Video generation model',
                    'gemini-2.5-flash-preview-tts' => 'Text-to-speech model',
                    'gemini-embedding-001' => 'Embeddings model'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('AIService: Error getting available models: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                'common_models' => [
                    'gemini-2.5-flash-lite' => 'Fast, lightweight model for most tasks'
                ]
            ];
        }
    }

    /**
     * Extract JSON from response that might contain markdown code blocks
     */
    private function extractJsonFromResponse(string $response): string
    {
        // Remove markdown code blocks
        $cleaned = preg_replace('/```json\s*/', '', $response);
        $cleaned = preg_replace('/```\s*/', '', $cleaned);
        
        // Try to find JSON object in the response
        if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
            return $matches[0];
        }
        
        return trim($cleaned);
    }

    /**
     * Fallback method for skill extraction using simple text processing
     */
    private function fallbackExtractSkills(string $resumeText): array
    {
        Log::info('AIService: Using fallback skill extraction method.');
        
        // Enhanced skill list with more comprehensive coverage
        $commonSkills = [
            // Backend
            'PHP', 'Laravel', 'Symfony', 'CodeIgniter', 'Yii', 'CakePHP', 'Phalcon', 'Slim',
            'Python', 'Django', 'Flask', 'FastAPI', 'Tornado', 'Pyramid',
            'Java', 'Spring', 'Spring Boot', 'Hibernate', 'Maven', 'Gradle',
            'C#', '.NET', '.NET Core', 'ASP.NET', 'Entity Framework',
            'Node.js', 'Express.js', 'Nest.js', 'Koa.js',
            'Ruby', 'Ruby on Rails', 'Sinatra',
            'Go', 'Golang', 'Gin', 'Echo',
            'Rust', 'Actix', 'Rocket',
            
            // Databases
            'MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'MariaDB', 'Oracle',
            'Redis', 'Memcached', 'Elasticsearch', 'DynamoDB', 'Cassandra',
            'SQL', 'NoSQL', 'Database Design', 'Query Optimization',
            
            // Frontend
            'JavaScript', 'TypeScript', 'ES6', 'ES2015+',
            'React', 'Vue.js', 'Angular', 'Svelte', 'jQuery',
            'HTML', 'HTML5', 'CSS', 'CSS3', 'SASS', 'SCSS', 'LESS', 'Stylus',
            'Bootstrap', 'Tailwind CSS', 'Bulma', 'Material UI', 'Vuetify',
            'Webpack', 'Vite', 'Rollup', 'Parcel', 'Gulp', 'Grunt',
            
            // DevOps & Cloud
            'Docker', 'Kubernetes', 'Podman', 'Container Orchestration',
            'AWS', 'Azure', 'GCP', 'Digital Ocean', 'Heroku', 'Vercel',
            'Jenkins', 'GitHub Actions', 'GitLab CI', 'CircleCI', 'Travis CI',
            'Terraform', 'Ansible', 'Chef', 'Puppet', 'Vagrant',
            'Linux', 'Ubuntu', 'CentOS', 'Debian', 'RHEL', 'Alpine',
            'Apache', 'Nginx', 'Load Balancing', 'Reverse Proxy',
            
            // Version Control
            'Git', 'GitHub', 'GitLab', 'Bitbucket', 'SVN', 'Mercurial',
            
            // Testing
            'PHPUnit', 'Pest', 'Jest', 'Mocha', 'Cypress', 'Selenium',
            'Unit Testing', 'Integration Testing', 'TDD', 'BDD',
            
            // API Development
            'REST API', 'RESTful Services', 'GraphQL', 'SOAP', 'gRPC',
            'API Design', 'API Documentation', 'Swagger', 'OpenAPI',
            'Microservices', 'Service-Oriented Architecture', 'SOA',
            
            // Message Queues & Communication
            'RabbitMQ', 'Apache Kafka', 'Amazon SQS', 'Redis Pub/Sub',
            'WebSockets', 'Server-Sent Events', 'gRPC',
            
            // CMS & E-commerce
            'WordPress', 'Drupal', 'Joomla', 'Magento', 'Shopify', 'WooCommerce',
            'Stripe', 'PayPal', 'Payment Gateway Integration',
            
            // Mobile Development
            'React Native', 'Flutter', 'Ionic', 'Cordova', 'PhoneGap',
            'Android', 'iOS', 'Kotlin', 'Swift', 'Objective-C',
            
            // Package Managers
            'Composer', 'NPM', 'Yarn', 'Pip', 'Maven', 'Gradle', 'NuGet',
            
            // Methodologies & Practices
            'Agile', 'Scrum', 'Kanban', 'Waterfall', 'DevOps',
            'Code Review', 'Pair Programming', 'Clean Code',
            'SOLID Principles', 'Design Patterns', 'MVC', 'MVVM',
            
            // Project Management Tools
            'JIRA', 'Trello', 'Asana', 'Monday.com', 'Notion', 'Confluence',
            
            // Security
            'OAuth', 'JWT', 'HTTPS', 'SSL/TLS', 'CORS', 'CSRF',
            'Security Best Practices', 'Penetration Testing', 'OWASP'
        ];

        $foundSkills = [];
        $resumeUpper = strtoupper($resumeText);

        foreach ($commonSkills as $skill) {
            if (strpos($resumeUpper, strtoupper($skill)) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return array_slice(array_unique($foundSkills), 0, 15); // Return max 15 unique skills
    }

    /**
     * Get default match result
     */
    private function getDefaultMatchResult(): array
    {
        return ['score' => 0, 'matched' => []];
    }

    /**
     * Get advanced default result
     */
    private function getAdvancedDefaultResult(): array
    {
        return [
            'score' => 0, 
            'matched' => [], 
            'missing' => [], 
            'summary' => 'Unable to analyze resume at this time',
            'experience_match' => 'unknown',
            'recommendation' => 'Manual review required'
        ];
    }

    /**
     * Get job requirements default
     */
    private function getJobRequirementsDefault(): array
    {
        return [
            'required_skills' => [],
            'preferred_skills' => [],
            'experience_level' => 'unknown',
            'key_responsibilities' => [],
            'soft_skills' => [],
            'education_requirements' => [],
            'salary_range' => 'Not specified',
            'work_type' => 'Not specified'
        ];
    }

    /**
     * Get resume quality default
     */
    private function getResumeQualityDefault(): array
    {
        return [
            'overall_score' => 0,
            'strengths' => [],
            'weaknesses' => [],
            'suggestions' => [],
            'missing_sections' => [],
            'ats_friendly' => false,
            'readability_score' => 0
        ];
    }

    /**
     * Get interview questions default
     */
    private function getInterviewQuestionsDefault(): array
    {
        return [
            'technical_questions' => [],
            'behavioral_questions' => [],
            'situational_questions' => [],
            'experience_questions' => []
        ];
    }
}