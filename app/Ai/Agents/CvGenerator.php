<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class CvGenerator implements Agent
{
    use Promptable;

    /**
     * Get the instructions for the agent.
     */
    public function instructions(): string
    {
        return "You are an expert CV/resume writer and career coach. Your task is to generate a professional, beautifully-structured CV/resume in clean Markdown format based on the user's profile details provided. Do not include any introductory or concluding conversational text in your response, return ONLY the generated Markdown CV itself.";
    }
}
