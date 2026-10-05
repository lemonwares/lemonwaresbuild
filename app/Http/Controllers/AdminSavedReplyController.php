<?php

namespace App\Http\Controllers;

use App\Models\SavedReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSavedReplyController extends Controller
{
    public function index(): View
    {
        return view('admin.saved-replies.index', [
            'replies' => SavedReply::query()->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SavedReply::query()->create($this->validated($request));

        return redirect()->route('admin.saved-replies.index')->with('status', 'Saved reply added.');
    }

    public function update(Request $request, SavedReply $savedReply): RedirectResponse
    {
        $savedReply->update($this->validated($request));

        return redirect()->route('admin.saved-replies.index')->with('status', 'Saved reply updated.');
    }

    public function destroy(SavedReply $savedReply): RedirectResponse
    {
        $savedReply->delete();

        return redirect()->route('admin.saved-replies.index')->with('status', 'Saved reply deleted.');
    }

    /**
     * @return array{title:string,body:string}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
    }
}
