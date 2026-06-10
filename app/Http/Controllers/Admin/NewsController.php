<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsContentBlock;
use App\Models\NewsPost;
use App\Models\NewsPostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index()
    {
        $posts = NewsPost::query()
            ->select(['id', 'slug', 'title_en', 'title_ar', 'status', 'published_at', 'created_at'])
            ->when(auth()->user()?->canPublishNews() === false, fn ($query) => $query->where('status', 'draft'))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.news.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.news.create', [
            'post' => new NewsPost(['status' => 'draft']),
        ]);
    }

    public function store(Request $request)
    {
        $_perfStart = microtime(true);
        $data = $this->validatePost($request);

        $slug = NewsPost::generateUniqueSlug($data['title_en']);

        $featured = null;
        if ($request->hasFile('featured_image')) {
            $featured = $request->file('featured_image')->store('news/'.$slug, 'public');
        }

        if ($request->user()?->canPublishNews() === false) {
            $data['status'] = 'draft';
            $data['published_at'] = null;
        }

        $post = NewsPost::create([
            'slug' => $slug,
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'excerpt_en' => $data['excerpt_en'] ?? null,
            'excerpt_ar' => $data['excerpt_ar'] ?? null,
            'content_en' => $data['content_en'] ?? null,
            'content_ar' => $data['content_ar'] ?? null,
            'featured_image' => $featured,
            'category' => $data['category'] ?? null,
            'status' => $data['status'],
            'published_at' => $this->publishedAtFromRequest($request, $data['status']),
            'created_by_user_id' => $request->user()?->id,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        $this->storeGalleryImages($request, $post, 0);
        $this->saveContentBlocks($request, $post);

        \Illuminate\Support\Facades\Log::info('NewsController::store completed in ' . round((microtime(true) - $_perfStart) * 1000) . 'ms');

        return redirect()->route('admin.news.index')->with('status', __('admin.news_created'));
    }

    public function edit(NewsPost $news)
    {
        $this->authorizeSubadminDraftAccess($news);

        $news->load('images', 'contentBlocks');

        return view('admin.news.edit', ['post' => $news]);
    }

    public function update(Request $request, NewsPost $news)
    {
        $this->authorizeSubadminDraftAccess($news);

        $data = $this->validatePost($request);

        if ($request->user()?->canPublishNews() === false) {
            $data['status'] = 'draft';
            $data['published_at'] = null;
        }

        if ($data['title_en'] !== $news->title_en) {
            $news->slug = NewsPost::generateUniqueSlug($data['title_en'], $news->id);
        }

        if ($request->hasFile('featured_image')) {
            if ($news->featured_image) {
                Storage::disk('public')->delete($news->featured_image);
            }
            $news->featured_image = $request->file('featured_image')->store('news/'.$news->slug, 'public');
        }

        // Delete gallery images the editor checked for removal
        $deleteIds = array_filter(array_map('intval', $request->input('delete_image_ids', [])));
        if ($deleteIds) {
            $toDelete = NewsPostImage::whereIn('id', $deleteIds)->where('news_post_id', $news->id)->get();
            foreach ($toDelete as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }
        }

        // Append any newly uploaded gallery images
        $nextOrder = $news->images()->max('sort_order') + 1;
        $this->storeGalleryImages($request, $news, (int) $nextOrder);

        $news->fill([
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'excerpt_en' => $data['excerpt_en'] ?? null,
            'excerpt_ar' => $data['excerpt_ar'] ?? null,
            'content_en' => $data['content_en'] ?? null,
            'content_ar' => $data['content_ar'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => $data['status'],
            'updated_by_user_id' => $request->user()?->id,
        ]);

        $news->published_at = $this->publishedAtFromRequest($request, $data['status']);

        $news->save();
        $this->saveContentBlocks($request, $news, true);

        return redirect()->route('admin.news.index')->with('status', __('admin.news_updated'));
    }

    public function destroy(NewsPost $news)
    {
        if (auth()->user()?->canPublishNews() === false && $news->status !== 'draft') {
            abort(403);
        }

        if ($news->featured_image) {
            Storage::disk('public')->delete($news->featured_image);
        }

        foreach ($news->images as $img) {
            Storage::disk('public')->delete($img->path);
        }

        $news->delete();

        return redirect()->route('admin.news.index')->with('status', __('admin.news_deleted'));
    }

    protected function validatePost(Request $request): array
    {
        return $request->validate([
            'title_en'              => ['required', 'string', 'max:255'],
            'title_ar'              => ['required', 'string', 'max:255'],
            'excerpt_en'            => ['nullable', 'string', 'max:1000'],
            'excerpt_ar'            => ['nullable', 'string', 'max:1000'],
            'content_en'            => ['nullable', 'string'],
            'content_ar'            => ['nullable', 'string'],
            'featured_image'        => ['nullable', 'image', 'max:8192'],
            'gallery_images'        => ['nullable', 'array', 'max:10'],
            'gallery_images.*'      => ['image', 'max:8192'],
            'delete_image_ids'      => ['nullable', 'array'],
            'delete_image_ids.*'    => ['integer'],
            'category'              => ['nullable', 'string', 'max:255'],
            'status'                => ['required', 'in:draft,published'],
            'published_at'          => ['nullable', 'date'],
            'blocks_en'             => ['nullable', 'array'],
            'blocks_ar'             => ['nullable', 'array'],
            'block_image_en'        => ['nullable', 'array'],
            'block_image_en.*'      => ['nullable', 'image', 'max:8192'],
            'block_image_ar'        => ['nullable', 'array'],
            'block_image_ar.*'      => ['nullable', 'image', 'max:8192'],
        ]);
    }

    protected function saveContentBlocks(Request $request, NewsPost $post, bool $isUpdate = false): void
    {
        foreach (['en', 'ar'] as $locale) {
            $submittedBlocks = $request->input("blocks_{$locale}", []);
            $uploadedImages  = $request->file("block_image_{$locale}", []);
            $submittedIds    = [];

            foreach ($submittedBlocks as $slot => $blockData) {
                $type      = $blockData['type'] ?? 'text';
                $blockId   = ! empty($blockData['block_id']) ? (int) $blockData['block_id'] : null;
                $sortOrder = (int) ($blockData['sort_order'] ?? $slot);

                $attrs = [
                    'news_post_id' => $post->id,
                    'locale'       => $locale,
                    'type'         => $type,
                    'sort_order'   => $sortOrder,
                    'caption_en'   => $blockData['caption_en'] ?? null,
                    'caption_ar'   => $blockData['caption_ar'] ?? null,
                ];

                if ($type === 'text') {
                    $attrs['content'] = $blockData['content'] ?? null;
                }

                $newFile = $uploadedImages[$slot] ?? null;

                if ($type === 'image' && $newFile) {
                    if ($blockId) {
                        $old = NewsContentBlock::find($blockId);
                        if ($old && $old->image_path) {
                            Storage::disk('public')->delete($old->image_path);
                        }
                    }
                    $attrs['image_path'] = $newFile->store("news/{$post->id}/blocks", 'public');
                }

                if ($blockId) {
                    $block = NewsContentBlock::where('id', $blockId)
                        ->where('news_post_id', $post->id)
                        ->where('locale', $locale)
                        ->first();
                    if ($block) {
                        $block->update($attrs);
                        $submittedIds[] = $blockId;
                        continue;
                    }
                }

                $newBlock       = NewsContentBlock::create($attrs);
                $submittedIds[] = $newBlock->id;
            }

            if ($isUpdate) {
                $toDelete = NewsContentBlock::where('news_post_id', $post->id)
                    ->where('locale', $locale)
                    ->when(! empty($submittedIds), fn ($q) => $q->whereNotIn('id', $submittedIds))
                    ->get();

                foreach ($toDelete as $block) {
                    if ($block->image_path) {
                        Storage::disk('public')->delete($block->image_path);
                    }
                    $block->delete();
                }
            }
        }
    }

    protected function storeGalleryImages(Request $request, NewsPost $post, int $startOrder): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        $order = $startOrder;
        foreach ($request->file('gallery_images') as $file) {
            $path = $file->store('news/'.$post->slug.'/gallery', 'public');
            NewsPostImage::create([
                'news_post_id' => $post->id,
                'path'         => $path,
                'sort_order'   => $order++,
            ]);
        }
    }

    protected function publishedAtFromRequest(Request $request, string $status): ?Carbon
    {
        if ($status !== 'published') {
            return null;
        }

        if (! $request->filled('published_at')) {
            return now();
        }

        return Carbon::parse($request->input('published_at'), $this->adminTimezone())
            ->timezone(config('app.timezone', 'UTC'));
    }

    protected function adminTimezone(): string
    {
        return config('app.admin_timezone', config('app.timezone', 'UTC'));
    }

    protected function authorizeSubadminDraftAccess(NewsPost $news): void
    {
        if (auth()->user()?->canPublishNews() === false && $news->status !== 'draft') {
            abort(403);
        }
    }
}
