# 10 — Gotchas

> Part 10 of 11. Previous: [`09-backup-and-automation.md`](09-backup-and-automation.md) · Next: [`11-state-and-roadmap.md`](11-state-and-roadmap.md)

**Read this before your first change.** Every item here cost real time to discover. They are
grouped by where they will bite you.

---

## 10.1 Deploy and server

| # | Gotcha |
|---|--------|
| 1 | **Never rsync or scp application code.** Git only. `git reset --hard origin/main` on the server destroys anything edited directly there |
| 2 | **`public_html` IS `public/`** — a symlink. There is no copy step. Never place Laravel source in it |
| 3 | **`public/app-path.php`** must be exactly `<?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";`. First thing to check on a post-deploy 500 |
| 4 | **`route:cache` is fine here.** The old never-route-cache warning is **obsolete**; `deploy.sh` runs it every deploy. Do not remove it on the strength of stale docs |
| 5 | **Two deploy scripts exist** — the repo's `deploy.sh` and the server's `/home/mfaruk/deploy.sh`. They differ. The server copy lacked the SSR restart until 2026-07-26 and served a stale bundle for two weeks. Keep the SSR restart if it is ever regenerated |
| 6 | **The `mfaruk` SSH alias on this laptop is the MCP log-reader key**, not a deploy key. `./deploy.sh` will not deploy from the laptop. Use `user@SERVER_IP` |
| 7 | **Migrations run automatically** with `--force` on every deploy. Anything you commit will execute |
| 8 | **`scp` lands files as `root:root`** → always `chown mfaruk:www-data && chmod 644` afterwards, especially under `storage/app/public/` |
| 9 | **Disk is 81% full** (5.4 GB free of 30 GB, 2026-08-30). Check `df -h /` before adding anything that writes to the server |

---

## 10.2 tinker

| # | Gotcha |
|---|--------|
| 10 | **Strip `<?php` and `use …;` lines**, or use fully-qualified class names |
| 11 | **Pipe scripts via stdin** (heredoc). `--execute="…"` **mangles backslashes and backticks** |
| 12 | Always `cd` to the app root first — `/home/mfaruk/web/mfaruk.com/private/portfolio-app` |

---

## 10.3 Frontend and SSR

| # | Gotcha |
|---|--------|
| 13 | **Never touch `window` / `document` / `localStorage` during `setup()` or render.** Only inside `onMounted` / `onBeforeUnmount` |
| 14 | **DOMPurify has no DOM in Node.** Use `composables/useSanitize.js`, which returns first-party content unchanged server-side and lets the browser re-sanitise on hydration |
| 15 | **JSON-LD must be a text child `{{ jsonLdString }}`, never `v-html`.** Under SSR, `v-html` serialises into an escaped `innerHTML` attribute and destroys the structured data |
| 16 | **A nonexistent route name crashes SSR** and drops the page to fallback HTML. This happened with `photo.show` — the correct name is **`photos.show`** — and cost two weeks of crawler visibility on category, gallery, and tag pages |
| 17 | **Anything only known after mount must be gated behind a `mounted` ref.** Otherwise SSR renders a statement that hydration contradicts — the gallery end-of-list message is the worked example |
| 18 | ~~Appended DOM nodes are invisible to an existing IntersectionObserver~~ — **OBSOLETE 2026-08-30.** The hand-rolled observer is gone. It was also the cause of a real SEO defect: because cards carried `data-src` and no `src`, the SSR HTML of `/photos` shipped **24 `<img>` tags with no image at all**, so a crawler that does not execute JS saw nothing to index on the site's main gallery listing. Replaced by native `loading="lazy"` on `ResponsiveImage`, which has no appended-node problem. **Do not reintroduce a JS lazy-loader that withholds `src`** |
| 19 | **Edit `.vue` files with Python** (`read_text` / `replace` / `write_text`), **never `sed`** — CSS braces and percent signs break sed expressions |
| 20 | **`manualChunks` is client-build only.** It conflicts with the SSR bundle's inlined dynamic imports. The `isSsrBuild` condition in `vite.config.js` is deliberate |
| 21 | **`npm run build` runs both builds** (client and `--ssr`). Never run just one |
| 22 | **Tailwind is v3**, despite `@tailwindcss/vite ^4.2.1` sitting in devDependencies |
| 23 | **SSR failure is silent to humans.** The site looks fine and crawlers get nothing. Check `systemctl is-active mfaruk-ssr` |

---

## 10.4 Dependencies and versions

| # | Gotcha |
|---|--------|
| 24 | **Verify features against the INSTALLED package, not the docs.** `Inertia::scroll()` and `InfiniteScroll` exist in inertia-laravel 2.0.22 / @inertiajs/vue3 2.3.18, but the public documentation describes a later version — version numbers alone would have misled |
| 25 | **`n8n` stores a node fine and then refuses to activate it.** Storage-level validation is not proof a node will run |

---

## 10.5 Data and configuration

| # | Gotcha |
|---|--------|
| 26 | **Most configuration is in the `settings` DB table, not `.env`.** R2 credentials, Turnstile keys, watermark, quality, AI provider, theme, SEO flags. Editing `.env` alone will appear to do nothing |
| 27 | **A setting saved into the wrong group silently reverts.** This caused a real bug where the quality slider kept returning to 82% |
| 28 | **`Setting::get()` is cached.** Call `Setting::clearCache()` after writing outside the normal path |
| 29 | **`photos.status` has four values** — `draft`, `published`, `processing`, `failed` — not two |
| 30 | **`likes_count` / `comments_count` are denormalised** and can drift. `syncCommentsCount()` exists for that reason |
| 31 | **Categories and Tags are shared between Photos and Posts.** A category page can hold either |
| 32 | **`seo_robots_allow` is a single DB row that can delist the entire site.** `robots.txt` is a closure in `routes/web.php` and serves `Disallow: /` when it is not `'1'` |
| 33 | **`SearchService` filters camera and lens with MySQL JSON functions** on `exif_data`. Not portable to SQLite |
| 34 | **The local `database/database.sqlite` is stale (April 2026) and unused.** There is no local runtime. Never treat it as truth |
| 35 | **Analytics snapshot row id 1 is test data** pushed during the build |

---

## 10.6 Photo pipeline

| # | Gotcha |
|---|--------|
| 36 | **`watermark_disabled` on a photo beats the global setting, always.** Per-photo disabled means never watermarked |
| 37 | **Bulk optimisation deliberately overrides per-photo custom settings.** Not a regression |
| 38 | **AVIF quality ≠ WebP quality.** The service maps between them (`mapWebpToAvifQuality`). Do not pass a WebP number to an AVIF encoder |
| 39 | **`findSourceFile()` falls back to derivatives.** If the R2 master is unreachable it will silently regenerate from an already-compressed display or watermarked file, causing generation loss with no error |
| 40 | **RAW `.RAF` files are never uploaded to R2** — laptop and NAS only. R2 holds the processed JPEG master |
| 41 | **Two watermarks exist** — the one baked into the master by Lightroom/darktable (permanent) and the app watermark (a DB flag, reapplied on regen). Do not conflate them |
| 42 | **The queue worker is load-bearing.** One supervisor process. If it stops, uploads sit in `processing` with no visible error |
| 43 | **`captured_at` is the public gallery's default sort.** A photo with no capture date sorts oddly |
| 44 | **Not every photo has GPS.** At least one has hand-entered coordinates. Guard with `hasLocation()` |
| 44b | **Image width allowlists must use the PER-PHOTO ladder**, `$photo->image->for($variant)->widths()`, never `ImageVariant::widths()`. `PhotoImage` caps the enum ladder at the master width, so a 1280 master offers 1280 and a portrait offers 853 — neither is in the raw enum. Validating against the enum 404'd all 26 image-sitemap URLs and every photo page's JPEG LCP element |
| 44c | **`image_max_resolution` is 1280 in production, not the 1920 the code default and these docs long implied.** It was set on 2026-01-04 and never documented. It is the only reason indexed images are 1280 wide (853 for portraits) — not storage, not the pipeline. Raising it costs nothing in R2 and needs no re-upload |

---

## 10.7 Routing

| # | Gotcha |
|---|--------|
| 45 | **Route order matters and is load-bearing.** `photos/create`, `photos/bulk-edit`, `photos/processing-status` are declared **before** `photos/{photo}`. Same pattern for `email-templates/compose` and `/logs`, and `social/accounts`. Reordering breaks them |
| 46 | **Route-model binding is by slug** for public routes, not id |
| 47 | **`stripe/webhook` is the only CSRF-exempt route** |
| 48 | **Adding a third-party script or XHR host requires editing the CSP** in `SecurityHeaders.php`, or the browser blocks it silently |
| 48b | **`routes/images.php` must stay OUTSIDE the web group.** It is registered from `bootstrap/app.php` via `withRouting(then: …)` with `SubstituteBindings` and nothing else. Inside the web group, image responses carry `Set-Cookie` (XSRF + session) and `Vary: X-Inertia`, and Cloudflare answers **`cf-cache-status: BYPASS` on every image** — every byte then comes off the origin through a full session boot, which is worse than the static `/storage` paths it replaced |
| 48c | **Excluding middleware by class name is a trap in L13.** The web group registers `Illuminate\Foundation\Http\Middleware\PreventRequestForgery`; `ValidateCsrfToken` is only a **deprecated alias**, so `withoutMiddleware([ValidateCsrfToken::class])` removes nothing and the real middleware then asks a session-less request for its session — *Session store not set on request*, 500 on every image. Give a route its own file rather than name-matching middleware |

---

## 10.8 Mail

| # | Gotcha |
|---|--------|
| 49 | **Site mail has failed completely and silently before.** The Gmail app password was revoked; contact notifications and comment OTPs died for weeks with no error. **Check Google app passwords under `farooque7@gmail.com` first** |
| 50 | **`LoggingService` is a static API.** A previous bug called a nonexistent instance method `logActivity()`, producing latent 500s. Use `activity()` and `error()` |

---

## 10.9 Backups and NAS

| # | Gotcha |
|---|--------|
| 51 | **Green execution ticks are not proof of a backup.** Verify the sidecar sha256 against the n8n receipt and run `gzip -t` |
| 52 | **Do not re-investigate n8n execution 74's STALE alert reading `limit -1`** — that threshold was set deliberately to force the alert path during testing and was reverted nine seconds later |
| 53 | **RAID is not backup.** The offsite DB leg to R2 is designed but **not built** |
| 53b | **Test the STORED archive, not a fresh one.** A fresh archive only proves the producer works |
| 53c | **The n8n backup key cannot restore** — it is `command=`-pinned to producer verbs. Automation creates backups; it must never be able to overwrite the live site |
| 54 | **Never verify a restore with `information_schema.table_rows`** — it is an InnoDB estimate and reported `users: 0` when the real count was 1, which would have been a false failure. Use real `COUNT(*)`. Also assert the dump ends with the mysqldump completion marker — a truncated dump imports partially and silently |
| 55 | **The NAS is off overnight and n8n never back-fills** missed schedules |
| 56 | **The n8n image is unpinned (`:latest`)** — a restart can silently jump versions under the backup workflows |
| 57 | **Deleting an n8n workflow cascade-deletes its executions**, destroying the evidence |
| 58 | **Never use `$json.execution.url` in n8n alerts** — it renders a `localhost` link that is dead everywhere except the NAS |

---

## 10.10 Boundaries

| # | Gotcha |
|---|--------|
| 59 | **Never use the Jezweb MCPs for mfaruk work.** They are authenticated as `mahmud@jezweb.net` and cannot see mfaruk properties anyway |
| 60 | **GitHub must be the personal account `mmhfarooque`.** The work account `mahmudfarooque` **cannot access this private repo**. Use SSH — HTTPS breaks because of the wrong active `gh` account |
| 61 | **Never post, reply, or send as Mahmud** without an explicit ask and a double-confirm |
| 62 | **Never auto-publish content.** An agent prepares; **Mahmud publishes** |
| 63 | **No auto-delete of data, files, DB, or backups.** Verified backup plus explicit go before any destructive operation |

---

## 10.11 Documentation

| # | Gotcha |
|---|--------|
| 64 | **`CLAUDE.md` is partly stale** — it claims DigitalOcean hosting, 10 published photos, 5 categories, no automated backup, and describes an rsync deploy in one section while forbidding rsync in another. Keep it only for the `/content` step list |
| 65 | **This reference set wins** over any other doc in the repo. It is the only one verified against live systems |
