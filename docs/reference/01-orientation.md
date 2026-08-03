# 01 — Orientation

> Part 1 of 11. Previous: [`00-INDEX.md`](00-INDEX.md) · Next: [`02-architecture.md`](02-architecture.md)

---

## 1.1 What this is

A **photography portfolio plus technical blog**, owned and operated by Mahmud Farooque. It is a
**running product with real visitors**, not a sandbox. Changes go to production. Treat it that
way.

Two content streams share one codebase:

1. **Photography** — photo pages with first-person stories, EXIF, GPS maps, categories,
   galleries, tags, likes and OTP-verified comments. This is the long game; individual photo
   pages currently get single-digit to low-double-digit views.
2. **Technical blog** — Editor.js articles, mostly Linux and open-source topics. **This drives
   essentially all the traffic.** Around 6,600 unique humans per 30 days as of late July 2026,
   with the top article alone near 1,700 views in that window.

The strategic tension is stated plainly so you do not have to rediscover it: the blog earns the
audience, the photos are what Mahmud actually wants seen, and the bridge between them (internal
links, the blog photo outro, photo SEO backfill) is deliberate ongoing work.

---

## 1.2 Golden rules

These come from `MFARUK_WORKFLOW.md` §0 and are non-negotiable.

1. **Git is authoritative.** Every machine and the server sync *from* GitHub. Pull before you
   work. Reconcile divergence with git wins.
2. **No local runtime.** The app is not run locally — no local server, no local DB in use, no
   local `.env`, no local build. Develop = edit → commit → push → **the server builds**. Verify
   on production.
3. **Deploy chain, each link a checkpoint:** local edit → sanitize → `git commit` → `git push`
   → server `git reset --hard origin/main` + build. **Never** rsync or scp application code to
   the server.
4. **Personal only. The Jezweb boundary is absolute.** See §1.5.
5. **Backups are sacred; no self-destruct.** Never delete data, DB, or backups, and never run a
   destructive operation, without a verified backup and an explicit go-ahead.
6. **Capability questions get researched, not recalled.** Never answer is-X-possible from a
   training cutoff. Check current documentation first.

Two more that are Mahmud's standing preferences:

7. **Everything the idiomatic Laravel way.** Mailables, migrations, FormRequests, artisan
   commands — not ad-hoc tinker scripts and not hand-rolled equivalents of framework features.
   Known outstanding retrofits are listed in [`11-state-and-roadmap.md`](11-state-and-roadmap.md).
8. **Root cause before symptom.** Go deep enough to explain *why* before patching *what*.

---

## 1.3 Live state, verified 2026-08-03

Queried directly against the production database. The numbers in `CLAUDE.md` are from April and
are wrong — it claims 10 published photos and 5 categories.

| Metric | Value |
|--------|-------|
| Photos total | 27 |
| Photos published | 26 |
| Photos draft | 1 |
| Photos featured (homepage) | 9 |
| Photo masters in R2 | **27 of 27** |
| Categories | 12 |
| Galleries | 8 (all published) |
| Tags | 377 |
| Blog posts | 16 total, 13 published |
| Photo comments | 1 |
| Contact messages | 6 |
| Orders | **0** |
| Equipment records | **0** |
| Location records | **0** |
| Settings rows | 74 |
| Analytics snapshots | 8 |
| Admin users | 1 |

The zeros matter. Orders, Equipment and Locations are **fully built features with zero data** —
see §1.6.

### Categories (id | name | slug)

```
 1 | Seascapes & Beaches     | seascapes-beaches
 2 | Sunsets & Golden Hour   | sunsets-golden-hour
 3 | Landscapes              | landscapes
 4 | Rivers & Waterways      | rivers-waterways
 5 | Flora & Gardens         | flora-gardens
 6 | Linux & Open Source     | linux-open-source
 7 | Photography Tech        | photography-tech
 8 | Travel — India          | travel-india
 9 | Travel — Nepal          | travel-nepal
10 | Travel — Thailand       | travel-thailand
11 | Still Life & Details    | still-life-details
12 | People & Everyday Life  | people-everyday-life
```

Categories are shared by **both** photos and posts. Ids 6 and 7 are blog categories; the rest
are mostly photo categories. This matters: a category page can contain either kind of content.

### Galleries (all published)

```
1 Coastal Collection · 2 Kashmir Collection · 3 Thailand Collection · 4 Himalaya Collection
5 Rangamati · 6 Bangladesh Countryside · 7 Bandarban · 8 Dhaka
```

---

## 1.4 Feature flags actually set in production

Settings live in the DB (`settings` table, read via `Setting::get()`), **not** in `.env`. Verified values:

| Setting | Value | Meaning |
|---------|-------|---------|
| `r2_enabled` | `1` | Photo masters go to Cloudflare R2 |
| `r2_bucket` | `photography` | R2 bucket name |
| `turnstile_enabled` | `1` | Cloudflare Turnstile guards the contact form |
| `watermark_enabled` | `1` | Global watermark on (per-photo override exists) |
| `seo_robots_allow` | `1` | `robots.txt` serves the permissive variant |
| `ai_enabled` | `1` | AI image analysis available |
| `ai_provider` | `google` | Gemini, not Anthropic or OpenAI |
| `site_theme` | `dark` | Active theme |
| `contact_email` | `farooque7@gmail.com` | Receives contact form mail |
| `stripe_enabled` | *unset* | Commerce dormant |
| `printful_enabled` | *unset* | Print fulfilment dormant |
| `newsletter_enabled` | *unset* | Newsletter dormant |

---

## 1.5 The Jezweb boundary — absolute

Mahmud works at Jezweb, a WordPress agency, under `mahmud@jezweb.net`. mfaruk.com is his
personal work. The separation is a hard rule, not a preference.

**Never** use, for anything mfaruk-related:

- The Jezweb Google Analytics, Search Console, Cloudflare, or Gmail MCP connectors — they are
  authenticated as the work account and can only see Jezweb properties anyway.
- Jezweb hosting (Rocket.net), Jezweb ERPNext, or any Jezweb credential.

**Do** use:

- The `mfaruk` MCP (personal): server log analytics, humans versus crawlers.
- Personal Cloudflare, Google Search Console, and Bing Webmaster under `farooque7@gmail.com`.
- The Synology NAS.
- GitHub as **mmhfarooque** (`farooque7@gmail.com`). The work GitHub account
  `mahmudfarooque` **cannot access this private repo** — use SSH and the personal account.

Two further behavioural boundaries:

- **Never post, reply, or send as Mahmud** on any channel without an explicit ask and a
  double-confirm.
- **No auto-delete** of data, files, DB, or backups. Verified backup plus explicit go before any
  destructive operation.

---

## 1.6 Built but dormant — do not mistake these for live features

A significant fraction of the admin surface is complete, wired, routed, and **unused**. Knowing
this prevents you from debugging a feature that has simply never been switched on, and from
assuming a feature is safe to remove.

| Area | Built | Live? | Evidence |
|------|-------|-------|----------|
| **Commerce / checkout** | Stripe payment intents, orders, licence keys, digital download, invoices | ❌ **0 orders**, `stripe_enabled` unset | `PaymentService`, `CheckoutController`, `Order` |
| **Print store** | Printful product catalogue, mockups, pricing, inquiry form | ❌ `printful_enabled` unset | `PrintService`, `PrintController` |
| **Newsletter** | Double opt-in, confirm/unsubscribe tokens, stats | ❌ `newsletter_enabled` unset | `NewsletterService` |
| **Social auto-post** | X/Twitter and platform posting engine, scheduling, engagement sync | ❌ no accounts connected | `SocialMediaService`, `SocialPost` |
| **A/B testing** | Variant assignment, cookies, conversion tracking, significance maths | ❌ no tests running | `ABTestService` |
| **Translations** | Per-model translation store, locales, completion % | ❌ unused | `TranslationService`, `Translation` |
| **Equipment / Gear pages** | Full CRUD, public `/gear` pages | ❌ **0 records** | `Equipment` |
| **Locations pages** | Full CRUD, public `/locations` pages | ❌ **0 records** | `Location` |
| **Client galleries / proofing** | Token access, password gate, selection export | ⚠️ built, no active clients | `ClientGalleryController`, `ClientProofingController` |
| **Lightroom sync** | XMP parsing, apply-to-photo | ⚠️ built, occasional | `LightroomSyncService` |

**What IS live and load-bearing:** photos, blog, categories, galleries, tags, comments and
likes, contact form (with reply), settings, activity logging, SEO/sitemap/feeds, search, the
photo pipeline with R2, analytics snapshots from the NAS, and the admin panel around all of
that.

---

## 1.7 Who does what

- **Mahmud Farooque** — owner, photographer, sole admin user. Publishes all content. An AI
  agent prepares content; **Mahmud publishes**. Never auto-publish.
- **Contact email** — `farooque7@gmail.com`.
- Site mail sends through Gmail SMTP using an app password on the server. If site mail dies
  silently, check Google app passwords under `farooque7@gmail.com` first — this exact failure
  happened once and went unnoticed for weeks.
