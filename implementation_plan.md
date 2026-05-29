# Implementation Plan - CV Validation, PDF Download & AI Job Recommendation

This updated plan adds profile completeness checks, PDF generation capabilities, and a structured AI-driven job recommendation system.

---

## User Review Required

> [!IMPORTANT]
> - **Composer Dependency**: To support clean PDF generation, we propose installing the standard `dompdf/dompdf` library (`composer require dompdf/dompdf`). This will convert generated Markdown CVs into beautifully formatted, printable PDF documents.
> - **Completeness Constraints**: We will enforce that a job seeker must have a non-empty summary, and at least one skill, one education record, and one experience record to generate an AI CV.

---

## Proposed Changes

### 1. Profile Completeness Checks

We will introduce a completeness validator inside the service to safeguard the quality of the AI generated CV.

#### [MODIFY] [CvCreationService.php](file:///c:/Users/Freddie/Documents/job-portal/app/Services/CvCreationService.php)
- Implement `checkProfileCompleteness(User $user): array` returning:
  - `complete` (boolean)
  - `missing` (array of missing section labels)

#### [MODIFY] [CvCreationController.php](file:///c:/Users/Freddie/Documents/job-portal/app/Http/Controllers/Api/CvCreationController.php)
- Update `generate()` to check completeness and return a `422 Unprocessable Entity` JSON response if sections are missing.

---

### 2. PDF Download System

We will install `dompdf/dompdf` to render HTML templates to PDF format.

#### [MODIFY] [composer.json](file:///c:/Users/Freddie/Documents/job-portal/composer.json)
- Add `"dompdf/dompdf": "^3.0"` to requirements (installed via composer).

#### [MODIFY] [CvCreationService.php](file:///c:/Users/Freddie/Documents/job-portal/app/Services/CvCreationService.php)
- Implement a minimalist Markdown-to-HTML parser using `preg_replace` to convert headers, lists, bold text, and line-breaks.
- Render CV to a styled HTML template with a clean resume layout (serif fonts, borders, professional margins).

#### [MODIFY] [CvCreationController.php](file:///c:/Users/Freddie/Documents/job-portal/app/Http/Controllers/Api/CvCreationController.php)
- Implement `download(Request $request, CvCreationService $service): Response` to stream the CV as a PDF file attachment.

#### [MODIFY] [api.php](file:///c:/Users/Freddie/Documents/job-portal/routes/api.php)
- Register `Route::get('/profile/cv/download', [CvCreationController::class, 'download'])` under the `job_seeker` route group.

---

### 3. AI Job Recommendations & Semantic Matching Logic

We will construct a structured AI agent that semantically matches the job seeker's profile with active job postings using a multi-dimensional comparison:

- **Semantic Features Used in Comparison**:
  1. **Skills Alignment**: Direct mapping between the user's `skills` and the job's `skills`/`requirements`.
  2. **Experience & Seniority Fit**: Analyzing job seeker `experiences` (titles, company names, durations, and descriptions) against the job's `description` and complexity level.
  3. **Education & Certifications Match**: Correlating the candidate's `educations` and specialized `certificates` with the job's `qualification` requirements.
  4. **Profile Summary Alignment**: Semantic overlap of the job seeker's self-written `summary` with the job `category` and scope of work.

Using these features, the AI model (Gemini) will perform a holistic semantic evaluation to select the top 5 most relevant postings. It will return a structured JSON response specifying the matching reasoning and calculating a calculated `match_percentage` (0-100%).

#### [NEW] [JobRecommender.php](file:///c:/Users/Freddie/Documents/job-portal/app/Ai/Agents/JobRecommender.php)
- Create an Agent implementing `Laravel\Ai\Contracts\Agent` and `Laravel\Ai\Contracts\HasStructuredOutput`.
- Define system instructions asking the agent to act as a career advisor.
- Set up the JSON schema to output an array of recommendations containing `job_posting_id` (integer), `matching_reason` (string), and `match_percentage` (integer between 0 and 100).

#### [NEW] [JobRecommendationService.php](file:///c:/Users/Freddie/Documents/job-portal/app/Services/JobRecommendationService.php)
- Load the job seeker's full profile details (summary, skills, educations, experiences, certificates).
- Load all active job postings in the database.
- Prompt the `JobRecommender` agent with the candidate profile and job options.
- Map the recommended IDs back to `JobPosting` Eloquent models and attach the matching reasons/percentages.

#### [NEW] [JobRecommendationController.php](file:///c:/Users/Freddie/Documents/job-portal/app/Http/Controllers/Api/JobRecommendationController.php)
- Implement `index(Request $request, JobRecommendationService $service): JsonResponse` to fetch the recommendations.

#### [MODIFY] [api.php](file:///c:/Users/Freddie/Documents/job-portal/routes/api.php)
- Register `Route::get('/recommendations/jobs', [JobRecommendationController::class, 'index'])` under the `job_seeker` route group.

---

## Verification Plan

### Automated Tests
We will extend and build feature tests to verify:
- **CV Profile validation**: Asserts a 422 response is returned when the profile is incomplete.
- **PDF Download endpoint**: Mock-fakes the generator and asserts that a PDF attachment is streamed back.
- **AI Job Recommendations endpoint**: Mock-fakes `JobRecommender` using fakes, verifying it returns recommended job posts with percentages and descriptions.

#### Execution Command
```powershell
C:\Users\Freddie\.config\herd\bin\php.bat artisan test --compact tests/Feature/CvCreationTest.php
```
