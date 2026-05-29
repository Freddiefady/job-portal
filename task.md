# CV Validation, PDF Download, and AI Recommendation Tasks

- `[x]` Install `dompdf/dompdf` dependency.
- `[x]` Implement profile completeness validation inside `CvCreationService` & `CvCreationController`.
- `[x]` Add Markdown-to-HTML parser and styled resume layout in `CvCreationService`.
- `[x]` Add PDF generation and download stream in `CvCreationController`.
- `[x]` Register `/profile/cv/download` route in `routes/api.php`.
- `[x]` Create structured AI Agent: `App\Ai\Agents\JobRecommender`.
- `[x]` Create `JobRecommendationService` and semantic comparison prompt.
- `[x]` Create `JobRecommendationController` for fetching recommendations.
- `[x]` Register `/recommendations/jobs` route in `routes/api.php`.
- `[x]` Format code with Laravel Pint (`vendor/bin/pint`).
- `[x]` Write and run automated feature tests for CV validation, PDF downloads, and AI job recommendations.
