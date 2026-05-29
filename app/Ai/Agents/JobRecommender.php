<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class JobRecommender implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions for the agent.
     */
    public function instructions(): string
    {
        return 'You are an AI career advisor. Your task is to recommend the best job postings for a candidate based on their profile. Analyze their skills, experience, and education, then choose up to 5 matching jobs from the provided list. Return a JSON object containing the recommended job IDs, match percentages, and matching reasons explaining the fit.';
    }

    /**
     * Define the JSON schema for structured output.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'recommendations' => $schema->array()->items(
                $schema->object([
                    'job_posting_id' => $schema->integer()->required(),
                    'matching_reason' => $schema->string()->required(),
                    'match_percentage' => $schema->integer()->min(0)->max(100)->required(),
                ])
            )->required(),
        ];
    }
}
