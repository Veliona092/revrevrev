<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClassAiSettingsRequest;
use App\Http\Requests\UpdateGlobalAiSettingsRequest;
use App\Models\ClassModel;
use App\Services\AiSettingsResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function global(AiSettingsResolver $settingsResolver): View
    {
        if (request()->user()?->role !== 'superadmin') {
            abort(403);
        }

        return view('pages.admin.ai-settings-global', [
            'settings' => $settingsResolver->getGlobalSnapshot(),
        ]);
    }

    public function classes(AiSettingsResolver $settingsResolver): View
    {
        if (! in_array(request()->user()?->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $classes = ClassModel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'school_year', 'ai_settings']);

        $classSettings = $classes->mapWithKeys(function (ClassModel $class) use ($settingsResolver): array {
            return [$class->id => $settingsResolver->getClassSettings($class)];
        })->toArray();

        return view('pages.admin.ai-settings-classes', [
            'classes' => $classes,
            'classSettings' => $classSettings,
        ]);
    }

    public function updateGlobal(UpdateGlobalAiSettingsRequest $request, AiSettingsResolver $settingsResolver): RedirectResponse|JsonResponse
    {
        if ($request->user()?->role !== 'superadmin') {
            abort(403);
        }

        $settingsResolver->updateGlobalSettings($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Global AI settings updated successfully.',
                'settings' => $settingsResolver->getGlobalSnapshot(),
            ]);
        }

        return redirect()->back()->with('status', 'Global AI settings updated successfully.');
    }

    public function updateClass(UpdateClassAiSettingsRequest $request, ClassModel $class, AiSettingsResolver $settingsResolver): RedirectResponse|JsonResponse
    {
        if (! in_array($request->user()?->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $validated = $request->validated();

        $requestedDifficulty = trim((string) $request->input('quiz_defaults.difficulty', ''));
        if ($requestedDifficulty !== '' && in_array($requestedDifficulty, ['Easy', 'Normal', 'Hard'], true)) {
            data_set($validated, 'quiz_defaults.difficulty', $requestedDifficulty);
        }

        $settingsResolver->updateClassSettings($class, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Class AI settings updated successfully.',
                'settings' => $settingsResolver->getClassSettings($class),
            ]);
        }

        return redirect()->back()->with('status', 'Class AI settings updated successfully.');
    }
}
