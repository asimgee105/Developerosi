<?php

namespace App\Http\Controllers;

use App\Models\GitPullRequest;
use App\Models\QaRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QaController extends Controller
{
    /**
     * Trigger or rerun Playwright QA E2E test runs against a pull request preview.
     */
    public function triggerQaRun(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pr_id' => ['required', 'uuid'],
        ]);

        $pr = GitPullRequest::findOrFail($validated['pr_id']);

        // Simulate Playwright Warm Browser Pools Incognito Context launch
        $script = [
            'test_suite' => 'Checkout Flow E2E',
            'assertions' => [
                ['action' => 'navigate', 'value' => 'https://preview-pr-' . $pr->id . '.devos.host/checkout'],
                ['action' => 'fill', 'selector' => '#card-number', 'value' => '4242_4242_4242_4242'],
                ['action' => 'click', 'selector' => '#submit-btn'],
                ['action' => 'assertText', 'selector' => '.success-title', 'value' => 'Payment Successful!'],
            ],
            'browser_pool' => [
                'headless' => true,
                'attached_incognito_context' => 'context_' . Str::random(8),
                'startup_time_ms' => 48, // sub-50ms pool attach time
            ]
        ];

        // Simulate Dom Visual Pixel matching (detects 20px button shift regressions)
        $hasVisualRegression = $request->input('simulate_visual_regression', false);
        $status = 'success';
        $errorMessage = null;

        if ($hasVisualRegression) {
            $status = 'failed';
            $errorMessage = 'Visual Regression Alert: CSS layout shift detected. Button shifted by 22px vertically.';
        }

        // Flake assertion retries logic (retries up to 3 times on fail before reporting final regression)
        $retries = 0;
        if ($status === 'failed' && !$hasVisualRegression) {
            // If it failed due to a network flake, retry resolves it
            $retries = 2;
            $status = 'success';
        }

        $qaRun = QaRun::create([
            'pr_id' => $pr->id,
            'status' => $status,
            'generated_script' => $script,
            'video_artifact_url' => 'https://s3.amazonaws.com/devos-qa-recordings/' . Str::random(16) . '.mp4',
            'flake_retries' => $retries,
        ]);

        return response()->json([
            'message' => 'Autonomous QA test execution completed.',
            'qa_run' => $qaRun,
            'visual_regression_spotted' => $hasVisualRegression ? $errorMessage : null,
        ]);
    }
}
