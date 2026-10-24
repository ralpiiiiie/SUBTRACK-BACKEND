<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\AuthUserResource;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'user'         => new AuthUserResource($user),
                'preferences'  => $this->settingsService->resolvedPreferences($user),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->safe()->only(['name', 'email']));

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data'    => new AuthUserResource($user),
        ]);
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $preferences = $this->settingsService->updatePreferences(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'data'    => $preferences,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->validated('current_password'), $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors'  => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();
        $data = $this->settingsService->exportData($user);
        $filename = 'subtrack-export-' . now()->format('Y-m-d_His') . '.json';

        return response()->streamDownload(function () use ($data): void {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $this->settingsService->deleteAccount($request->user());

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}