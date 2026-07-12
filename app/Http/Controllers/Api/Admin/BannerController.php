<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index()
    {
        return response()->json(Banner::orderBy('position')->orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:4096',
            'link' => 'nullable|string|max:500',
            'position' => ['required', Rule::in(Banner::POSITIONS)],
            'sort_order' => 'integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $path = $request->file('image')->store('banners', 'public');
        $data['image'] = Storage::disk('public')->url($path);

        $banner = Banner::create($data);

        return response()->json(['message' => 'Banner created successfully', 'banner' => $banner], 201);
    }

    public function show(Banner $banner)
    {
        return response()->json($banner);
    }

    public function update(Request $request, Banner $banner)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'sometimes|image|max:4096',
            'link' => 'nullable|string|max:500',
            'position' => ['sometimes', 'required', Rule::in(Banner::POSITIONS)],
            'sort_order' => 'integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $this->deleteImageFile($banner->image);
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = Storage::disk('public')->url($path);
        } else {
            unset($data['image']);
        }

        $banner->update($data);

        return response()->json(['message' => 'Banner updated successfully', 'banner' => $banner]);
    }

    public function destroy(Banner $banner)
    {
        $this->deleteImageFile($banner->image);
        $banner->delete();

        return response()->json(['message' => 'Banner deleted successfully']);
    }

    private function deleteImageFile(string $url): void
    {
        $path = Str::after(parse_url($url, PHP_URL_PATH) ?? '', '/storage/');
        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
