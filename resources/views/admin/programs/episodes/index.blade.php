@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-6xl px-6 py-10">
    <a href="{{ route('admin.programs.edit', $program) }}" class="text-sm font-semibold text-emerald-700">← {{ $program->title }}</a>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">حلقات {{ $program->title }}</h1>
        @can('permission', 'content.create')
            <a href="{{ route('admin.programs.episodes.create', $program) }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">حلقة جديدة</a>
        @endcan
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="GET" class="mt-6 flex items-center gap-3">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed')) onchange="this.form.submit()"> عرض المحذوفات</label>
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-4"></th>
                    <th class="p-4">#</th>
                    <th class="p-4">العنوان</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4">الترتيب</th>
                    <th class="p-4">تاريخ النشر</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($episodes as $episode)
                    <tr class="border-t border-slate-200">
                        <td class="p-4">
                            @if($cover = $episode->coverImage())
                                <img src="{{ $cover->url() }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-lg bg-slate-100 text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-500">{{ $episode->programEpisode?->episode_number }}</td>
                        <td class="p-4 font-medium">{{ $episode->title }}</td>
                        <td class="p-4">
                            @if($episode->trashed())
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">محذوف</span>
                            @elseif($episode->status === 'published')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">منشور</span>
                            @elseif($episode->status === 'unpublished')
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">غير منشور</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">مسودة</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $episode->sort_order }}</td>
                        <td class="p-4 text-slate-500">{{ $episode->published_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="p-4 whitespace-nowrap">
                            @if($episode->trashed())
                                @can('permission', 'content.update')
                                    <form class="inline" method="POST" action="{{ route('admin.programs.episodes.restore', [$program, $episode->id]) }}">@csrf<button class="text-emerald-700">استعادة</button></form>
                                @endcan
                            @else
                                @can('permission', 'content.update')
                                    <a class="text-emerald-700" href="{{ route('admin.programs.episodes.edit', [$program, $episode]) }}">تعديل</a>
                                    @if($episode->status === 'published')
                                        <form class="inline" method="POST" action="{{ route('admin.programs.episodes.unpublish', [$program, $episode]) }}">@csrf<button class="mr-3 text-amber-700">إلغاء النشر</button></form>
                                    @else
                                        <form class="inline" method="POST" action="{{ route('admin.programs.episodes.publish', [$program, $episode]) }}">@csrf<button class="mr-3 text-emerald-700">نشر</button></form>
                                    @endif
                                @endcan
                                @can('permission', 'content.delete')
                                    <form class="inline" method="POST" action="{{ route('admin.programs.episodes.destroy', [$program, $episode]) }}">@csrf @method('DELETE')<button class="mr-3 text-red-700" onclick="return confirm('حذف هذه الحلقة؟')">حذف</button></form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-500">لا توجد حلقات بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $episodes->links() }}</div>
</main>
@endsection
