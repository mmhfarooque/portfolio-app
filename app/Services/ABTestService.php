<?php

namespace App\Services;

use App\Models\ABTest;
use App\Models\ABTestParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ABTestService
{
    protected string $cookieName = 'ab_visitor_id';
    protected int $cookieDuration = 43200; // 30 days

    /**
     * Get or create visitor ID.
     */
    public function getVisitorId(Request $request): string
    {
        $visitorId = $request->cookie($this->cookieName);

        if (!$visitorId) {
            $visitorId = Str::uuid()->toString();
        }

        return $visitorId;
    }

    /**
     * Create visitor cookie response.
     */
    public function setVisitorCookie(string $visitorId): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie($this->cookieName, $visitorId, $this->cookieDuration);
    }

    /**
     * Get assigned variant for a test.
     */
    public function getVariant(ABTest $test, string $visitorId): ?string
    {
        // Check if already assigned
        $participant = ABTestParticipant::where('ab_test_id', $test->id)
            ->where('visitor_id', $visitorId)
            ->first();

        if ($participant) {
            return $participant->variant;
        }

        // Only assign new variants to running tests
        if (!$test->isRunning()) {
            return null;
        }

        // Assign based on weights
        $variant = $this->selectVariant($test->variants);

        // Create participant
        ABTestParticipant::create([
            'ab_test_id' => $test->id,
            'visitor_id' => $visitorId,
            'variant' => $variant,
        ]);

        return $variant;
    }

    /**
     * Select variant based on weights.
     */
    protected function selectVariant(array $variants): string
    {
        $totalWeight = collect($variants)->sum('weight');
        $random = mt_rand(1, $totalWeight * 100) / 100;

        $cumulative = 0;
        foreach ($variants as $variant) {
            $cumulative += $variant['weight'];
            if ($random <= $cumulative) {
                return $variant['name'];
            }
        }

        return $variants[0]['name'];
    }

    /**
     * Get active theme test variant.
     */
    public function getActiveTheme(Request $request): ?array
    {
        $test = ABTest::running()->forType('theme')->first();

        if (!$test) {
            return null;
        }

        $visitorId = $this->getVisitorId($request);
        $variantName = $this->getVariant($test, $visitorId);

        if (!$variantName) {
            return null;
        }

        $variant = $test->getVariantByName($variantName);

        return [
            'test_id' => $test->id,
            'test_slug' => $test->slug,
            'variant' => $variantName,
            'value' => $variant['value'] ?? null,
        ];
    }

    /**
     * Record a conversion.
     */
    public function recordConversion(string $testSlug, string $visitorId): bool
    {
        $test = ABTest::where('slug', $testSlug)->first();

        if (!$test) {
            return false;
        }

        $participant = ABTestParticipant::where('ab_test_id', $test->id)
            ->where('visitor_id', $visitorId)
            ->first();

        if (!$participant) {
            return false;
        }

        $participant->markConverted();

        // Check if test should auto-complete
        $this->checkAutoComplete($test);

        return true;
    }

    /**
     * Check if test should auto-complete.
     */
    protected function checkAutoComplete(ABTest $test): void
    {
        if (!$test->isRunning() || !$test->hasSufficientSample()) {
            return;
        }

        $result = $this->calculateStatisticalSignificance($test);

        if ($result['significant']) {
            $test->complete($result['winner']);
        }
    }

    /**
     * Calculate statistical significance.
     */
    public function calculateStatisticalSignificance(ABTest $test): array
    {
        $rates = $test->getConversionRates();

        if (count($rates) < 2) {
            return ['significant' => false, 'winner' => null, 'p_value' => null];
        }

        // Get the two main variants (A/B)
        $variants = array_keys($rates);
        $a = $rates[$variants[0]];
        $b = $rates[$variants[1]];

        // Skip if not enough data
        if ($a['total'] < 30 || $b['total'] < 30) {
            return ['significant' => false, 'winner' => null, 'p_value' => null];
        }

        // Calculate Z-score for two proportions
        $p1 = $a['converted'] / $a['total'];
        $p2 = $b['converted'] / $b['total'];
        $n1 = $a['total'];
        $n2 = $b['total'];

        $pooledP = ($a['converted'] + $b['converted']) / ($n1 + $n2);
        $se = sqrt($pooledP * (1 - $pooledP) * (1/$n1 + 1/$n2));

        if ($se == 0) {
            return ['significant' => false, 'winner' => null, 'p_value' => null];
        }

        $z = abs($p1 - $p2) / $se;

        // Z-score to P-value (approximate)
        $pValue = exp(-0.5 * $z * $z) / sqrt(2 * M_PI);

        // Determine significance based on confidence level
        $alphaLevel = (100 - $test->confidence_level) / 100;
        $significant = $pValue < $alphaLevel;

        $winner = null;
        if ($significant) {
            $winner = $a['rate'] > $b['rate'] ? $variants[0] : $variants[1];
        }

        return [
            'significant' => $significant,
            'winner' => $winner,
            'p_value' => round($pValue, 4),
            'z_score' => round($z, 4),
        ];
    }

    /**
     * Get test results summary.
     */
    public function getTestResults(ABTest $test): array
    {
        $rates = $test->getConversionRates();
        $significance = $this->calculateStatisticalSignificance($test);

        return [
            'test' => $test,
            'total_participants' => $test->getTotalParticipants(),
            'conversion_rates' => $rates,
            'statistical_significance' => $significance,
            'progress' => min(100, round(($test->getTotalParticipants() / $test->sample_size) * 100)),
        ];
    }

    /**
     * Create a new A/B test.
     */
    public function createTest(array $data): ABTest
    {
        return ABTest::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'theme',
            'variants' => $data['variants'],
            'goal' => $data['goal'] ?? 'conversion',
            'sample_size' => $data['sample_size'] ?? 1000,
            'confidence_level' => $data['confidence_level'] ?? 95,
        ]);
    }
}
