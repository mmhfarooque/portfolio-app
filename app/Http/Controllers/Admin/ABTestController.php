<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ABTest;
use App\Services\ABTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ABTestController extends Controller
{
    protected ABTestService $abTestService;

    public function __construct(ABTestService $abTestService)
    {
        $this->abTestService = $abTestService;
    }

    /**
     * Display A/B tests dashboard.
     */
    public function index()
    {
        $tests = ABTest::latest()->paginate(20);

        $stats = [
            'total' => ABTest::count(),
            'running' => ABTest::running()->count(),
            'completed' => ABTest::where('status', 'completed')->count(),
        ];

        return view('admin.abtests.index', compact('tests', 'stats'));
    }

    /**
     * Show form to create a new test.
     */
    public function create()
    {
        return view('admin.abtests.create');
    }

    /**
     * Store a new A/B test.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:theme,layout,content',
            'goal' => 'required|in:conversion,engagement,bounce',
            'sample_size' => 'required|integer|min:100|max:100000',
            'confidence_level' => 'required|numeric|min:80|max:99',
            'variants' => 'required|array|min:2',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.value' => 'required|string',
            'variants.*.weight' => 'required|numeric|min:1|max:100',
        ]);

        $test = $this->abTestService->createTest($request->all());

        return redirect()->route('admin.abtests.show', $test)
            ->with('success', 'A/B test created successfully.');
    }

    /**
     * Show test details and results.
     */
    public function show(ABTest $abtest)
    {
        $results = $this->abTestService->getTestResults($abtest);

        return view('admin.abtests.show', compact('abtest', 'results'));
    }

    /**
     * Edit test settings.
     */
    public function edit(ABTest $abtest)
    {
        if ($abtest->isRunning()) {
            return back()->with('error', 'Cannot edit a running test.');
        }

        return view('admin.abtests.edit', compact('abtest'));
    }

    /**
     * Update test settings.
     */
    public function update(Request $request, ABTest $abtest)
    {
        if ($abtest->isRunning()) {
            return back()->with('error', 'Cannot update a running test.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sample_size' => 'required|integer|min:100|max:100000',
            'confidence_level' => 'required|numeric|min:80|max:99',
            'variants' => 'required|array|min:2',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.value' => 'required|string',
            'variants.*.weight' => 'required|numeric|min:1|max:100',
        ]);

        $abtest->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'sample_size' => $request->sample_size,
            'confidence_level' => $request->confidence_level,
            'variants' => $request->variants,
        ]);

        return redirect()->route('admin.abtests.show', $abtest)
            ->with('success', 'A/B test updated.');
    }

    /**
     * Start a test.
     */
    public function start(ABTest $abtest)
    {
        if ($abtest->status !== 'draft' && $abtest->status !== 'paused') {
            return back()->with('error', 'Test cannot be started.');
        }

        // Pause any other running theme tests
        if ($abtest->type === 'theme') {
            ABTest::running()->forType('theme')->update(['status' => 'paused']);
        }

        $abtest->start();

        return back()->with('success', 'A/B test started!');
    }

    /**
     * Pause a test.
     */
    public function pause(ABTest $abtest)
    {
        if (!$abtest->isRunning()) {
            return back()->with('error', 'Test is not running.');
        }

        $abtest->pause();

        return back()->with('success', 'A/B test paused.');
    }

    /**
     * Complete a test and declare winner.
     */
    public function complete(Request $request, ABTest $abtest)
    {
        $request->validate([
            'winner' => 'nullable|string',
        ]);

        $abtest->complete($request->winner);

        return back()->with('success', 'A/B test completed.');
    }

    /**
     * Delete a test.
     */
    public function destroy(ABTest $abtest)
    {
        if ($abtest->isRunning()) {
            return back()->with('error', 'Cannot delete a running test. Pause it first.');
        }

        $abtest->delete();

        return redirect()->route('admin.abtests.index')
            ->with('success', 'A/B test deleted.');
    }
}
