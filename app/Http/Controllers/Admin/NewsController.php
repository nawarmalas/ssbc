<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsContentBlock;
use App\Models\NewsPost;
use App\Models\NewsPostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    protected const STAGING_DIR = 'news/_staging';

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
        } elseif ($request->filled('featured_image_staged')) {
            $featured = $this->claimStagedImage($request->input('featured_image_staged'), 'news/'.$slug);
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

        if ($request->hasFile('featured_image') || $request->filled('featured_image_staged')) {
            $newFeatured = $request->hasFile('featured_image')
                ? $request->file('featured_image')->store('news/'.$news->slug, 'public')
                : $this->claimStagedImage($request->input('featured_image_staged'), 'news/'.$news->slug);

            if ($newFeatured) {
                if ($news->featured_image) {
                    Storage::disk('public')->delete($news->featured_image);
                }
                $news->featured_image = $newFeatured;
            }
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

        // Cap the total gallery at 10 images (remaining + newly uploaded).
        $incoming = count((array) $request->input('gallery_staged', []))
            + count((array) $request->file('gallery_images', []));
        if ($news->images()->count() + $incoming > 10) {
            throw ValidationException::withMessages([
                'gallery_staged' => __('A post can have at most 10 gallery images in total.'),
            ]);
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
            'featured_image'        => ['nullable', 'image', 'max:16384'],
            'featured_image_staged' => ['nullable', 'string', 'max:255'],
            'gallery_images'        => ['nullable', 'array', 'max:10'],
            'gallery_images.*'      => ['image', 'max:16384'],
            'gallery_staged'        => ['nullable', 'array', 'max:10'],
            'gallery_staged.*'      => ['string', 'max:255'],
            'delete_image_ids'      => ['nullable', 'array'],
            'delete_image_ids.*'    => ['integer'],
            'category'              => ['nullable', 'string', 'max:255'],
            'status'                => ['required', 'in:draft,published'],
            'published_at'          => ['nullable', 'date'],
            'blocks_en'             => ['nullable', 'array'],
            'blocks_en.*.staged_image' => ['nullable', 'string', 'max:255'],
            'blocks_ar'             => ['nullable', 'array'],
            'blocks_ar.*.staged_image' => ['nullable', 'string', 'max:255'],
            'block_image_en'        => ['nullable', 'array'],
            'block_image_en.*'      => ['nullable', 'image', 'max:16384'],
            'block_image_ar'        => ['nullable', 'array'],
            'block_image_ar.*'      => ['nullable', 'image', 'max:16384'],
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

                if ($type === 'image') {
                    $newPath = null;
                    if ($newFile) {
                        $newPath = $newFile->store("news/{$post->id}/blocks", 'public');
                    } elseif (! empty($blockData['staged_image'])) {
                        $newPath = $this->claimStagedImage($blockData['staged_image'], "news/{$post->id}/blocks");
                    }

                    if ($newPath) {
                        if ($blockId) {
                            $old = NewsContentBlock::find($blockId);
                            if ($old && $old->image_path) {
                                Storage::disk('public')->delete($old->image_path);
                            }
                        }
                        $attrs['image_path'] = $newPath;
                    }
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
        $order = $startOrder;

        foreach ((array) $request->file('gallery_images', []) as $file) {
            $path = $file->store('news/'.$post->slug.'/gallery', 'public');
            NewsPostImage::create([
                'news_post_id' => $post->id,
                'path'         => $path,
                'sort_order'   => $order++,
            ]);
        }

        // Images already uploaded one-by-one via the async endpoint; the form
        // only carries their staged paths, so claiming them here is instant.
        foreach ((array) $request->input('gallery_staged', []) as $staged) {
            $path = $this->claimStagedImage($staged, 'news/'.$post->slug.'/gallery');
            if ($path) {
                NewsPostImage::create([
                    'news_post_id' => $post->id,
                    'path'         => $path,
                    'sort_order'   => $order++,
                ]);
            }
        }
    }

    /**
     * Accepts a single gallery/featured/block image, stores it in a staging
     * area and returns its path. The news Save request then references these
     * paths instead of carrying binary data, keeping every HTTP request to at
     * most one image.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:16384'],
        ]);

        $disk = Storage::disk('public');

        // Opportunistically clear staged files that were never attached to a post.
        foreach ($disk->files(self::STAGING_DIR) as $stale) {
            if ($disk->lastModified($stale) < now()->subDay()->getTimestamp()) {
                $disk->delete($stale);
            }
        }

        $ext  = strtolower($request->file('image')->getClientOriginalExtension() ?: 'jpg');
        $id   = (string) Str::uuid();
        $path = $request->file('image')->storeAs(self::STAGING_DIR, $id.'.'.$ext, 'public');

        if ($path === false) {
            return response()->json(['message' => __('The image could not be stored. Please try again.')], 500);
        }

        return response()->json([
            'id'   => $id,
            'path' => $path,
            'url'  => $disk->url($path),
        ], 201);
    }

    /**
     * Move a previously staged upload into its final directory. Returns the
     * new path, or null when the value is not a valid staged path (protects
     * against path traversal / arbitrary file moves).
     */
    protected function claimStagedImage(?string $staged, string $destDir): ?string
    {
        if (! $staged || ! preg_match('#^news/_staging/[0-9a-fA-F-]{36}\.(jpe?g|png|webp)$#', $staged)) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($staged)) {
            return null;
        }

        $newPath = rtrim($destDir, '/').'/'.basename($staged);
        $disk->move($staged, $newPath);

        return $newPath;
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
