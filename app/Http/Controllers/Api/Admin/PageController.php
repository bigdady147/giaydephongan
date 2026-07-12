<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        return response()->json(Page::orderBy('title')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['title'], 'pages');

        $page = Page::create($data);

        return response()->json(['message' => 'Page created successfully', 'page' => $page], 201);
    }

    public function show(Page $page)
    {
        return response()->json($page);
    }

    public function update(Request $request, Page $page)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page->id)],
            'content' => 'sometimes|required|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['title']) && !isset($data['slug'])) {
            // Only auto generate slug if title is changed and slug is not explicitly sent
            $data['slug'] = UniqueSlug::make($data['title'], 'pages');
        }

        $page->update($data);

        return response()->json(['message' => 'Page updated successfully', 'page' => $page]);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Page deleted successfully']);
    }
}
