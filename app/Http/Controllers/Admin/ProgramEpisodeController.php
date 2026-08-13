<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramEpisodeRequest;
use App\Http\Requests\Admin\UpdateProgramEpisodeRequest;
use App\Models\ContentItem;
use App\Models\ProgramEpisode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramEpisodeController extends Controller
{
    use ManagesContentItems;

    public function index(Request $request, ContentItem $program): View
    {
        $this->authorize('permission', 'content.view');
        $this->authorizeProgram($program);

        $episodes = ContentItem::query()
            ->ofType('program_episode')
            ->whereHas('programEpisode', fn (Builder $q) => $q->where('program_id', $program->id))
            ->with('programEpisode', 'media')
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return view('admin.programs.episodes.index', [
            'program' => $program,
            'episodes' => $episodes,
        ]);
    }

    public function create(ContentItem $program): View
    {
        $this->authorize('permission', 'content.create');
        $this->authorizeProgram($program);

        return view('admin.programs.episodes.form', [
            'program' => $program,
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
        ]);
    }

    public function store(StoreProgramEpisodeRequest $request, ContentItem $program): RedirectResponse
    {
        $this->authorizeProgram($program);
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request, $program) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'program_episode',
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildSeoMeta($data, ['video_url' => $data['video_url']]),
            ]);

            ProgramEpisode::create([
                'content_item_id' => $item->id,
                'program_id' => $program->id,
                'episode_number' => $this->nextEpisodeNumber($program->id),
            ]);

            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'program-episodes', $request->user()->id);

            return $item;
        });

        $this->recordActivity('program_episodes.created', $item);

        return redirect()->route('admin.programs.episodes.index', $program)->with('status', 'تمت إضافة الحلقة.');
    }

    public function edit(ContentItem $program, ContentItem $episode): View
    {
        $this->authorize('permission', 'content.update');
        $this->authorizeEpisode($program, $episode);
        $episode->load('media');

        return view('admin.programs.episodes.form', [
            'program' => $program,
            'item' => $episode,
        ]);
    }

    public function update(UpdateProgramEpisodeRequest $request, ContentItem $program, ContentItem $episode): RedirectResponse
    {
        $this->authorizeEpisode($program, $episode);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $episode) {
            $episode->update([
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title'], $episode),
                'excerpt' => $data['excerpt'] ?? null,
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $episode->published_at),
                'meta' => $this->buildSeoMeta($data, ['video_url' => $data['video_url']]),
            ]);

            $this->replaceMediaCollection($episode, $request->file('cover_image'), 'cover', 'program-episodes', $request->user()->id);
        });

        $this->recordActivity('program_episodes.updated', $episode);

        return redirect()->route('admin.programs.episodes.index', $program)->with('status', 'تم تحديث الحلقة.');
    }

    public function destroy(ContentItem $program, ContentItem $episode): RedirectResponse
    {
        $this->authorize('permission', 'content.delete');
        $this->authorizeEpisode($program, $episode);
        $episode->delete();
        $this->recordActivity('program_episodes.deleted', $episode);

        return redirect()->route('admin.programs.episodes.index', $program)->with('status', 'تم نقل الحلقة إلى المحذوفات.');
    }

    public function restore(ContentItem $program, int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $this->authorizeProgram($program);

        $episode = ContentItem::onlyTrashed()->where('type', 'program_episode')->findOrFail($id);
        abort_unless($episode->programEpisode?->program_id === $program->id, 404);

        $episode->restore();
        $this->recordActivity('program_episodes.restored', $episode);

        return redirect()->route('admin.programs.episodes.index', ['program' => $program, 'trashed' => 1])->with('status', 'تمت استعادة الحلقة.');
    }

    public function publish(ContentItem $program, ContentItem $episode): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $this->authorizeEpisode($program, $episode);
        $episode->update(['status' => 'published', 'published_at' => $episode->published_at ?? now()]);
        $this->recordActivity('program_episodes.published', $episode);

        return back()->with('status', 'تم نشر الحلقة.');
    }

    public function unpublish(ContentItem $program, ContentItem $episode): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $this->authorizeEpisode($program, $episode);
        $episode->update(['status' => 'draft']);
        $this->recordActivity('program_episodes.unpublished', $episode);

        return back()->with('status', 'تم إلغاء نشر الحلقة.');
    }

    private function authorizeProgram(ContentItem $program): void
    {
        abort_unless($program->type === 'program', 404);
    }

    private function authorizeEpisode(ContentItem $program, ContentItem $episode): void
    {
        $this->authorizeProgram($program);
        abort_unless($episode->type === 'program_episode' && $episode->programEpisode?->program_id === $program->id, 404);
    }

    private function nextEpisodeNumber(int $programId): int
    {
        return (ProgramEpisode::where('program_id', $programId)->max('episode_number') ?? 0) + 1;
    }
}
