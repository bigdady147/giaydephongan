<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pillar')) {
            $query->where('pillar', $request->pillar);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['title'], 'blog_posts');

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('blog', 'public');
            $data['thumbnail'] = Storage::disk('public')->url($path);
        } else {
            unset($data['thumbnail']);
        }

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = BlogPost::create($data);

        return response()->json(['message' => 'Blog post created successfully', 'blog_post' => $post], 201);
    }

    public function show(BlogPost $blog)
    {
        return response()->json($blog);
    }

    public function update(Request $request, BlogPost $blog)
    {
        $validator = Validator::make($request->all(), $this->rules($blog->id));

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnailFile($blog->thumbnail);
            $path = $request->file('thumbnail')->store('blog', 'public');
            $data['thumbnail'] = Storage::disk('public')->url($path);
        } else {
            unset($data['thumbnail']);
        }

        if (($data['status'] ?? $blog->status) === 'published' && empty($data['published_at'] ?? $blog->published_at)) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return response()->json(['message' => 'Blog post updated successfully', 'blog_post' => $blog]);
    }

    public function destroy(BlogPost $blog)
    {
        $this->deleteThumbnailFile($blog->thumbnail);
        $blog->delete();

        return response()->json(['message' => 'Blog post deleted successfully']);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($ignoreId)],
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|max:4096',
            'pillar' => ['nullable', Rule::in(BlogPost::PILLARS)],
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ];
    }

    private function deleteThumbnailFile(?string $url): void
    {
        if (!$url) {
            return;
        }

        $path = Str::after(parse_url($url, PHP_URL_PATH) ?? '', '/storage/');
        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
