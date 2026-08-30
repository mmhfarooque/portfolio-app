# 07 — Frontend

> Part 7 of 11. Previous: [`06-photo-pipeline.md`](06-photo-pipeline.md) · Next: [`08-operations.md`](08-operations.md)

Vue 3 with `<script setup>`, Inertia v2, Tailwind v3, Vite 7, server-side rendered. 90 page
components, 18 shared components, 3 layouts, 1 composable.

---

## 7.1 Layout of `resources/js`

```
resources/js/
├── app.js                    client entry
├── ssr.js                    SSR entry — eager page glob, Ziggy from shared props
├── Layouts/
│   ├── PublicLayout.vue          all public pages
│   ├── AuthenticatedLayout.vue   admin
│   └── GuestLayout.vue           auth screens
├── Pages/                    90 components, mirrors the route tree
│   ├── Public/  Gallery(6) Blog(2) ClientGallery(4) Checkout(2)
│   │            Gear(2) Locations(2) Newsletter(2) Home …
│   ├── Admin/   Photos(5) Galleries(4) Social(4) EmailTemplates(4) ABTests(4)
│   │            Categories(3) Equipment(3) Locations(3) Posts(3) Seo(3)
│   │            Translations(3) Comments(2) Contacts(2) LightroomSync(2)
│   │            Logs(2) Orders(2) About(2) …
│   └── Auth/    6 Breeze screens
├── Components/
│   ├── SeoHead.vue               ⭐ all meta, OG, Twitter, JSON-LD
│   ├── Pagination.vue            used by 18 pages
│   ├── Photo/  LikeButton · CommentSection · CommentThread
│   ├── Blog/   PhotoOutro.vue
│   ├── Admin/  SendEmailDialog.vue
│   └── FlashMessages · Toast · ConfirmDialog · ConfirmModal · NavDropdown
│       InputError · InputLabel · TextInput · PrimaryButton · SecondaryButton · DangerButton
└── composables/
    └── useSanitize.js            DOMPurify with an SSR guard
```

Alias: `@` → `/resources/js` (`vite.config.js`).

---

## 7.2 Shared props — available on every page

Injected by `HandleInertiaRequests::share()`, so never re-fetch these:

| Prop | Contents |
|------|----------|
| `auth.user` | `{id, name, email}` or `null` |
| `flash` | `success`, `error`, `warning`, `info` |
| `appName` | From `Setting::get('photographer_name')` |
| `theme` | `{name, colors, styles, isDark}` from `ThemeService` |
| `ziggy` | Full route table + current URL — **this is what makes `route()` work under SSR** |

---

## 7.3 `SeoHead.vue` — every meta tag comes from here

Used on public pages. Props:

```
title, description, image, imageAlt, url,
type          'website' | 'article' | 'photo'   (default 'website')
publishedTime, modifiedTime, author,
photo         Object   — for ImageObject JSON-LD
article       Object   — for Article JSON-LD
breadcrumbs   Array    — BreadcrumbList JSON-LD
```

It emits Open Graph, Twitter Cards (`summary_large_image`), canonical URL, and JSON-LD
structured data automatically. Typical use:

```vue
<SeoHead
    :title="photo.seo_title"
    :description="photo.meta_description"
    :image="photo.display_path"
    type="photo"
    :photo="photo"
    :breadcrumbs="[...]"
/>
```

⚠️ **JSON-LD must be rendered as a text child — `{{ jsonLdString }}` — never `v-html`.** Under
SSR, `v-html` serialises into an escaped `innerHTML` attribute and the structured data is
destroyed. This was fixed once; do not reintroduce it.

Content limits are enforced upstream, not here: `seo_title` ≤ **70** characters,
`meta_description` ≤ **160**. See `docs/CONTENT_GUIDELINES.md`.

---

## 7.4 The gallery load-more (Inertia v2)

`resources/js/Pages/Public/Gallery/Index.vue` + `GalleryController@index`. Implemented
2026-08-02. Worth understanding because it combines three subtle things.

**Server side**

```php
$photos = $query->latest('captured_at')->paginate(24)->withQueryString()->through(fn($photo) => [...]);
return Inertia::render('Public/Gallery/Index', [
    'photos' => Inertia::scroll($photos),
]);
```

`Inertia::scroll()` marks the paginator's data array for **appending** on partial reloads and
normalises the page metadata. The prop shape is unchanged, so `photos.data` keeps working and
nothing downstream had to move.

**Client side**

```vue
<InfiniteScroll data="photos" manual>
  <template #next="{ loading, fetch, hasMore }">
    <button v-if="hasMore" …>Load more</button>
    <p v-else-if="mounted">That is every photo.</p>
  </template>
</InfiniteScroll>
```

`manual` means nothing loads until the button is clicked — it is a **load-more button, not
infinite scroll**.

**The three traps, all already handled**

⚠️ **`<main>` in `PublicLayout.vue` is capped at `max-w-7xl` (1280px) and centred, since
2026-08-30.** It previously had no max-width, so anything that did not wrap itself — the photo
hero in particular — stretched to the full viewport and rendered at ~1661px from a 1280px
master, upscaled by the browser. Every other wrapper on the site is already `max-w-7xl` and the
pipeline caps derivatives at 1280, so capping `<main>` made the layout and the images agree
without touching a single file. Deliberate side effect: full-bleed coloured bands (the hero's
black field, the Home featured section, the blog related strip, `PhotoOutro`) now stop at 1280
too. **Any `sizes` attribute must therefore end in a fixed pixel width past 1280**, never a bare
viewport fraction, or wide screens compute a slot larger than the element and over-fetch.

1. ~~**Lazy-loaded images on appended cards.**~~ **REMOVED 2026-08-30.** The grid used to
   lazy-load via an `IntersectionObserver` over `.photo-card`, with cards carrying `data-src`
   rather than `src`, and an `observeCards()` + `watch(() => props.photos.data.length, …)` pair
   to re-observe appended nodes. That whole subsystem is gone. It solved the append problem but
   created a worse one: **the server-rendered `/photos` shipped 24 `<img>` tags with no `src` at
   all**, so any crawler that does not execute JavaScript saw an imageless gallery — measured on
   the live site, 0 real `src` against 24 `data-src`. Native `loading="lazy"` on
   `ResponsiveImage` has no appended-node problem and keeps a real `src` and `srcset` in the SSR
   HTML. Do not reintroduce a JS lazy-loader that withholds `src`.
2. **The SSR flash.** `hasMore` is only known once `InfiniteScroll` mounts, so server-side it is
   false and the end-of-list message rendered on a gallery that *does* have a page 2, then
   flipped to the button on hydration — a visible flash of a false statement. Fixed with a
   `mounted` ref, so SSR renders nothing in that slot.
3. **Version drift.** `Inertia::scroll()` exists in inertia-laravel **v2.0.22** and
   `InfiniteScroll` ships in **@inertiajs/vue3 2.3.18**. The public docs describe a later
   version. **Verify against the installed packages, not the docs.**

**Known accepted trade-off:** prop merging applies only to partial reloads, so loading page 2
puts `?page=2` in the URL and a hard refresh then shows page 2 alone. Acceptable at two pages;
`preserveUrl` would suppress it.

`Pagination.vue` is untouched and still used by 18 other pages — category, gallery, and tag
pages all still use conventional pagination.

---

## 7.5 SSR constraints — the rules for every component

Inertia SSR runs your components in **Node, with no DOM**. Violate these and you break either
the server render or hydration:

1. **Never touch `window`, `document`, or `localStorage` during `setup()` or render.** Only
   inside `onMounted` / `onBeforeUnmount`.
2. **DOMPurify has no DOM in Node.** `composables/useSanitize.js` returns first-party content
   unchanged server-side; the browser re-sanitises on hydration. Use the composable, not
   DOMPurify directly.
3. **JSON-LD as a text child, never `v-html`** (§7.3).
4. **Anything that is only known after mount must be gated behind a `mounted` ref** — the
   gallery end-of-list message is the worked example (§7.4).
5. `app.blade.php`'s static `<title>` is wrapped in
   `@unless(config('inertia.ssr.enabled'))` to avoid a duplicate title. Leave that alone.
6. **Route names must exist.** A nonexistent name throws during SSR and takes the whole page
   down to fallback HTML — this happened with `photo.show` versus **`photos.show`** and cost two
   weeks of crawler visibility on category, gallery, and tag pages.

If the SSR daemon is down, Inertia silently falls back to client rendering. The site looks fine
to a human and is invisible to a crawler. That failure mode is quiet — check
`systemctl is-active mfaruk-ssr`.

---

## 7.6 Editor.js

Blog post `content` and the About page are **Editor.js JSON**, not HTML or Markdown. Plugins
installed: header, paragraph, list, quote, delimiter, image.

The About page has **two** editors, reachable at `admin/about/editor` and
`admin/about/editorjs`, saving via `about/save` and `about/save-editorjs` respectively. Check
which one is actually in use before editing either.

The media picker for embedding photos is `GET admin/media/photos`.

---

## 7.7 The blog photo outro

`Components/Blog/PhotoOutro.vue`, rendered on every article. Shows 3 random featured photos plus
a browse-all link, with copy that varies by article type:

- development articles → *When I am not coding*
- photography-category articles → *More frames from my camera*

Detection is by category name or slug containing photo or camera. Props come from
`BlogController@show` as `outroPhotos` and `isPhotographyPost`.

This exists because the blog is the traffic and the photos are the goal — it is the deliberate
bridge between them.

---

## 7.8 Build

```
npm run build     →  vite build  &&  vite build --ssr
```

**Both builds, always.** Client output → `public/build/`. SSR output → `bootstrap/ssr/ssr.js`.
Both are gitignored and **built on the server**, never committed.

`manualChunks` splits `vendor-vue` (vue + @inertiajs/vue3) and `vendor-ziggy` for the client
build only — it conflicts with the SSR bundle's inlined dynamic imports, hence the `isSsrBuild`
condition in `vite.config.js`.

---

## 7.9 Editing `.vue` files programmatically

⚠️ **Use Python (`read_text` / `replace` / `write_text`), never `sed`.** CSS braces and percent
signs in Vue single-file components break `sed` expressions. This is a standing rule from
`MFARUK_WORKFLOW.md` §13 and it has bitten before.
