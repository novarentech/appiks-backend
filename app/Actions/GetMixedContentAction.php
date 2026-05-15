<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Video;
use App\Models\Quote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetMixedContentAction
{
    public function handle(int $schoolId): Collection
    {
        $videos = Video::select('video_id as ids', 'title', DB::raw("'video' as type"), 'created_at')
            ->where('school_id', $schoolId);

        $articles = Article::select('slug as ids', 'title', DB::raw("'article' as type"), 'created_at')
            ->where('school_id', $schoolId);

        $quotes = Quote::select('id as ids', 'text as title', DB::raw("'quote' as type"), 'created_at')
            ->where('school_id', $schoolId);

        $contents = DB::query()
            ->fromSub($videos->union($articles)->union($quotes), 'contents')
            ->orderBy('created_at', 'desc')
            ->get();

        // Inject tags untuk video
        $videoIds  = $contents->where('type', 'video')->pluck('ids');
        $videoTags = Video::with('tags')->whereIn('video_id', $videoIds)->get()->keyBy('video_id');

        $contents->transform(function ($item) use ($videoTags) {
            $item->tags = ($item->type === 'video' && isset($videoTags[$item->ids]))
                ? $videoTags[$item->ids]->tags
                : collect();

            return $item;
        });

        return $contents;
    }
}
