<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('pages.blog', compact('posts'));
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(
            $blogPost->is_published
                && $blogPost->published_at
                && $blogPost->published_at->lte(now()),
            404
        );

        $blogPost->load('author:id,name');

        return view('pages.blog-show', [
            'post' => $blogPost,
        ]);
    }
}
