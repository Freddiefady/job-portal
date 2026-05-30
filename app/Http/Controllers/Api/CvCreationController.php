<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\CvCreationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CvCreationController extends Controller
{
    /**
     * Generate a CV for the authenticated job seeker.
     */
    public function generate(Request $request, CvCreationService $service): JsonResponse
    {
        $user = $request->user();

        $completeness = $service->checkProfileCompleteness($user);

        if (! $completeness['complete']) {
            return response()->json([
                'message' => 'Please complete your profile details before generating an AI CV.',
                'errors' => [
                    'profile' => [
                        'Your profile is missing: '.implode(', ', $completeness['missing']).'.',
                    ],
                ],
            ], 422);
        }

        $cvText = $service->generateCvForUser($user);

        $html = $service->renderCvHtml($cvText, $user);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();
        $filename = 'ai-cv-'.time().'.pdf';
        $storedPath = 'profile/cvs/'.$user->id.'/'.$filename;

        \Illuminate\Support\Facades\Storage::disk('public')->put($storedPath, $pdfOutput);

        $profile = $user->jobSeekerProfile()->firstOrCreate(['user_id' => $user->id]);
        $previousCvPath = $profile->cv_path;
        $profile->cv_path = $storedPath;
        $profile->save();

        if (is_string($previousCvPath) && $previousCvPath !== '') {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($previousCvPath);
            \Illuminate\Support\Facades\Storage::disk('local')->delete($previousCvPath);
        }

        return ApiResponse::data([
            'cv' => $cvText,
            'cv_url' => $profile->cvPublicUrl(),
        ]);
    }

    /**
     * Download the CV as a beautifully formatted PDF.
     */
    public function download(Request $request, CvCreationService $service): mixed
    {
        $user = $request->user();

        $completeness = $service->checkProfileCompleteness($user);

        if (! $completeness['complete']) {
            return response()->json([
                'message' => 'Please complete your profile details before downloading your AI CV.',
                'errors' => [
                    'profile' => [
                        'Your profile is missing: '.implode(', ', $completeness['missing']).'.',
                    ],
                ],
            ], 422);
        }

        $cvText = $service->generateCvForUser($user);
        $html = $service->renderCvHtml($cvText, $user);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="cv.pdf"',
        ]);
    }
}
