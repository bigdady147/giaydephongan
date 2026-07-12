<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::orderBy('group')->orderBy('key')->get());
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $inputSettings = $request->input('settings');
        $keys = array_keys($inputSettings);

        // Verify that all keys exist
        $existingCount = Setting::whereIn('key', $keys)->count();
        if ($existingCount !== count($keys)) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => ['settings' => ['One or more settings keys are invalid.']]
            ], 422);
        }

        foreach ($inputSettings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => Setting::pluck('value', 'key')
        ]);
    }
}
