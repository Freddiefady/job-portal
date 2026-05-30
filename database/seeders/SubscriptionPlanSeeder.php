<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::query()->updateOrCreate(
            ['id' => 1],
            [
                'title' => 'AI CV Creator',
                'description' => 'Generate highly professional resumes with AI and download beautiful PDF copies.',
                'benefits' => [
                    'AI Resume Generation',
                    'Premium Executive PDF Layouts',
                    'Instant Redownloads',
                ],
                'price' => 9.99,
            ]
        );

        SubscriptionPlan::query()->updateOrCreate(
            ['id' => 2],
            [
                'title' => 'Smart Career Matcher',
                'description' => 'Evaluate your qualifications and match semantically against all active job postings.',
                'benefits' => [
                    'Unlimited AI Recommendations',
                    'Direct Skills Match Scores',
                    'Opportunity Analyzer Dashboard',
                ],
                'price' => 14.99,
            ]
        );

        SubscriptionPlan::query()->updateOrCreate(
            ['id' => 3],
            [
                'title' => 'Ultimate AI Career Suite',
                'description' => 'Get both AI Resume Generation and smart matching job recommendations for full optimization.',
                'benefits' => [
                    'AI Resume Generation',
                    'Premium Executive PDF Layouts',
                    'Unlimited AI Recommendations',
                    'Direct Skills Match Scores',
                ],
                'price' => 19.99,
            ]
        );
    }
}
