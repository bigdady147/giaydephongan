<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pillar' => ['nullable', Rule::in(BlogPost::PILLARS)],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $query = BlogPost::published()->orderBy('published_at', 'desc');

        if ($request->filled('pillar')) {
            $query->where('pillar', $request->pillar);
        }

        return response()->json(
            $query->paginate(9, ['id', 'title', 'slug', 'excerpt', 'thumbnail', 'pillar', 'published_at'])
        );
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('pillar', $post->pillar)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'thumbnail', 'pillar', 'published_at']);

        return response()->json(array_merge($post->toArray(), ['related' => $related]));
    }
}
