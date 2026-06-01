<?php

namespace App\Services;

use App\Ai\Agents\CvGenerator;
use App\Models\User;

class CvCreationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected CvGenerator $generator) {}

    /**
     * Generate a CV for the given user using AI.
     */
    public function generateCvForUser(User $user): string
    {
        $user->load([
            'jobSeekerProfile:user_id,linkedin_url,summary,portfolio_url',
            'skills:id,name',
            'educations:id,user_id,institution,degree,field_of_study,starts_at,ends_at',
            'experiences:id,user_id,company_name,title,starts_at,ends_at,description',
            'certificates:id,user_id,name,issuer,issued_at',
        ]);

        $profile = $user->jobSeekerProfile;

        $skills = $user->skills->pluck('name')->implode(', ');

        $educations = $user->educations->map(function ($edu): string {
            $start = $edu->starts_at?->format('Y-m') ?? 'Unknown';
            $end = $edu->ends_at?->format('Y-m') ?? 'Present';

            return "- {$edu->degree} in {$edu->field_of_study} from {$edu->institution} ({$start} to {$end})";
        })->implode("\n");

        $experiences = $user->experiences->map(function ($exp): string {
            $start = $exp->starts_at?->format('Y-m') ?? 'Unknown';
            $end = $exp->ends_at?->format('Y-m') ?? 'Present';

            return "- {$exp->title} at {$exp->company_name} ({$start} to {$end})\n  Description: {$exp->description}";
        })->implode("\n");

        $certificates = $user->certificates->map(function ($cert): string {
            $issued = $cert->issued_at?->format('Y-m-d') ?? 'Unknown';

            return "- {$cert->name} issued by {$cert->issuer} on {$issued}";
        })->implode("\n");

        $linkedin = $profile?->linkedin_url ?? 'N/A';
        $portfolio = $profile?->portfolio_url ?? 'N/A';
        $summary = $profile?->summary ?? 'N/A';

        $prompt = <<<TEXT
Generate a professional, polished CV based on the following user profile data:

Personal Information:
- Name: {$user->full_name}
- Email: {$user->email}
- Phone: {$user->phone}
- Location: {$user->street}, {$user->city}
- LinkedIn: {$linkedin}
- Portfolio: {$portfolio}
- Summary: {$summary}

Skills:
{$skills}

Education:
{$educations}

Work Experience:
{$experiences}

Certificates:
{$certificates}

Please generate the CV in professional Markdown format, highlighting their achievements and presenting it in a modern, clean structure.
TEXT;

        $response = $this->generator->prompt($prompt);

        return $response->text;
    }

    /**
     * Check if the user's profile is complete enough for AI CV creation.
     *
     * @return array{complete: bool, missing: array<string>}
     */
    public function checkProfileCompleteness(User $user): array
    {
        $user->loadMissing([
            'jobSeekerProfile:user_id,summary',
            'skills:id',
            'educations:id,user_id',
            'experiences:id,user_id',
        ]);

        $missing = [];

        if ($user->jobSeekerProfile === null || empty(trim($user->jobSeekerProfile->summary ?? ''))) {
            $missing[] = 'Profile Summary';
        }

        if ($user->skills->isEmpty()) {
            $missing[] = 'Skills';
        }

        if ($user->educations->isEmpty()) {
            $missing[] = 'Education';
        }

        if ($user->experiences->isEmpty()) {
            $missing[] = 'Work Experience';
        }

        return [
            'complete' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Convert markdown string to basic HTML tags suitable for PDF rendering.
     */
    public function markdownToHtml(string $markdown): string
    {
        $html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');

        // Replace headers
        $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);

        // Replace bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);

        // Replace list items
        $html = preg_replace('/^\* (.*?)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $html);

        // Replace horizontal rules
        $html = preg_replace('/^---$/m', '<hr>', $html);

        // Replace line breaks inside list items or general content
        $html = nl2br($html);

        return $html;
    }

    /**
     * Render the Markdown CV into a beautifully styled HTML template.
     */
    public function renderCvHtml(string $markdown, User $user): string
    {
        $bodyContent = $this->markdownToHtml($markdown);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CV - {$user->full_name}</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 0.5in;
        }
        h1 {
            font-size: 20pt;
            text-align: center;
            margin-top: 0;
            margin-bottom: 5px;
            color: #111111;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        h2 {
            font-size: 13pt;
            border-bottom: 1.5px solid #444444;
            padding-bottom: 3px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        h3 {
            font-size: 11pt;
            margin-top: 10px;
            margin-bottom: 3px;
            color: #333333;
        }
        p {
            margin-top: 0;
            margin-bottom: 8px;
            text-align: justify;
        }
        li {
            margin-bottom: 4px;
            text-align: justify;
        }
        hr {
            border: none;
            border-top: 1px solid #cccccc;
            margin: 15px 0;
        }
        strong {
            color: #000000;
        }
        .header-info {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 25px;
            color: #555555;
        }
    </style>
</head>
<body>
    <div class="container">
        {$bodyContent}
    </div>
</body>
</html>
HTML;
    }
}
