<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.blog-posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.blog-posts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = BlogPost::uniqueSlug($data['title']);
        $data['author_id'] = (int) $request->session()->get('admin_user_id') ?: null;

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('blog-covers', 'public');
        }

        BlogPost::query()->create($data);

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', 'Blog post created.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('admin.blog-posts.edit', compact('blogPost'));
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->validatePayload($request, $blogPost->id);

        $slugInput = trim((string) $request->input('slug', ''));
        $data['slug'] = BlogPost::uniqueSlug(
            $slugInput !== '' ? $slugInput : $data['title'],
            $blogPost->id
        );

        if ($request->boolean('remove_cover') && $blogPost->cover_path) {
            Storage::disk('public')->delete($blogPost->cover_path);
            $data['cover_path'] = null;
        }

        if ($request->hasFile('cover')) {
            if ($blogPost->cover_path) {
                Storage::disk('public')->delete($blogPost->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('blog-covers', 'public');
        }

        $blogPost->update($data);

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', 'Blog post updated.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        if ($blogPost->cover_path) {
            Storage::disk('public')->delete($blogPost->cover_path);
        }

        $blogPost->delete();

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('status', 'Blog post removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($ignoreId)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        unset($data['cover']);

        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($request->input('sort_order') ?? 0);

        if ($data['is_published']) {
            $data['published_at'] = filled($data['published_at'] ?? null)
                ? $data['published_at']
                : now();
        } else {
            $data['published_at'] = filled($data['published_at'] ?? null)
                ? $data['published_at']
                : null;
        }

        return $data;
    }
}
