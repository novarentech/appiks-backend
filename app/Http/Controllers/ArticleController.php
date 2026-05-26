<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Tag;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

#[ExcludeAllRoutesFromDocs]
class ArticleController extends Controller
{
    use ApiResponder;

    /**
     * Get all articles
     *
     * Mendapatkan semua data artikel di sekolah tersebut
     */
    #[Group('Article')]
    public function index()
    {
        $articles = Article::with('tags')->where('school_id', Auth::user()->school_id)->get();

        return $this->success(ArticleResource::collection($articles));
    }

    /**
     * Get all article by tag
     *
     * Mendapatkan semua artikel dengan tag tertentu di sekolah tersebut. Menggunakan id dari Tag
     */
    #[Group('Article')]
    public function getByTag(Tag $tag)
    {
        $articles = $tag->articles()->with('tags')->where('school_id', Auth::user()->school_id)->get();

        return $this->success(ArticleResource::collection($articles));
    }

    /**
     * Create new article
     *
     * Membuat sebuah artikel baru
     *
     * @bodyParam tags array<int> optional Daftar ID tag. Contoh: [1, 2, 3]
     */
    #[Group('Article')]
    public function store(CreateArticleRequest $request)
    {
        $data = $request->safe()->except(['tags']);
        $tags = $request->validated()['tags'] ?? [];

        $path = $request->file('thumbnail')->store('thumbnails', 'public');
        $data['thumbnail'] = config('app.url') . Storage::url($path);

        $article = Article::create($data);
        $article->tags()->sync($tags);

        $res = Article::with(['school', 'tags'])->find($article->id);

        return $this->success(new ArticleResource($res));
    }

    /**
     * Update article
     *
     * Mengupdate tag yang dimiliki oleh artikel tersebut. Hanya bisa dilakukan oleh Admin TU di sekolah tersebut
     */
    #[Group('Article')]
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $data = $request->safe()->except(['tags']);
        $tags = $request->validated()['tags'] ?? [];

        // handle thumbnail jika ada file baru
        if ($request->hasFile('thumbnail')) {
            if ($article->thumbnail && str_contains($article->thumbnail, '/storage/')) {
                $oldPath = str_replace(config('app.url') . '/storage/', '', $article->thumbnail);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = config('app.url') . Storage::url($path);
        }

        $article->update($data);
        $article->tags()->sync($tags);

        $res = Article::with(['school', 'tags'])->find($article->id);

        return $this->success(new ArticleResource($res));
    }

    /**
     * Delete article
     *
     * Menghapus konten artikel di sekolah tersebut berdasarkan slug. Hanya bisa dilakukan oleh Admin TU di sekolah tersebut.
     */
    #[Group('Article')]
    public function destroy(string $article)
    {
        $article = Article::whereSlug($article)->first();
        Gate::authorize('delete', $article);
        $data = $article->toArray();
        $article->delete();

        return $this->delete($data);
    }

    /**
     * Get article detail
     *
     * Mendapatkan artikel detail berdasarkan slug
     */
    #[Group('Article')]
    public function getArticle(string $article)
    {
        $res = Article::with('tags')->where('slug', $article)->first();
        Gate::authorize('view', $res);

        return $this->success(new ArticleResource($res));
    }
}
