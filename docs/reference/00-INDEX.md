# mfaruk.com — Reference Set (part 0 of 11: INDEX)

> **You are an AI agent (or human) with no prior context on this project. Start here.**
> This set is written so that reading it end to end gives you a complete working grip on
> mfaruk.com: what it is, how it is built, what every part does, how it runs, and where the
> bodies are buried. Every claim in this set was verified against the actual code, the live
> production server, or the live NAS on **2026-08-03**, and re-verified where
> marked on **2026-08-30** — not copied from older docs.

---

## Read order

| # | File | Read it when |
|---|------|--------------|
| 0 | `00-INDEX.md` (this file) | Always first |
| 1 | [`01-orientation.md`](01-orientation.md) | Always second — golden rules, live state, boundaries |
| 2 | [`02-architecture.md`](02-architecture.md) | Before touching any code |
| 3 | [`03-data-model.md`](03-data-model.md) | Working with data, models, DB |
| 4 | [`04-http-surface.md`](04-http-surface.md) | Adding/changing a route, page, or endpoint |
| 5 | [`05-services-jobs-commands.md`](05-services-jobs-commands.md) | Business logic lives here, not in controllers |
| 6 | [`06-photo-pipeline.md`](06-photo-pipeline.md) | Anything touching images, R2, watermarks |
| 7 | [`07-frontend.md`](07-frontend.md) | Vue, Inertia, SSR, SEO head |
| 8 | [`08-operations.md`](08-operations.md) | Deploying, debugging prod, server work |
| 9 | [`09-backup-and-automation.md`](09-backup-and-automation.md) | Backups, NAS, n8n, analytics push |
| 10 | [`10-gotchas.md`](10-gotchas.md) | **Read before your first change.** Hard-won, do not relearn |
| 11 | [`11-state-and-roadmap.md`](11-state-and-roadmap.md) | What is live, what is dormant, what is next |
| 12 | [`12-server-wide-backup-design.md`](12-server-wide-backup-design.md) | ⛔ **SUPERSEDED 2026-09-06 — do not build from it.** Its restic → R2 architecture is wrong; the correct design extends the n8n NAS-pull pipeline in 09. Measured facts and the restore-gap list remain useful |

**Minimum viable context** if you are in a hurry: parts 1, 2, 10. That is orientation,
architecture, and the landmine list. Everything else is lookup.

---

## How this set relates to the other docs in this repo

| Doc | Status | Use for |
|-----|--------|---------|
| `docs/reference/*` (this set) | ✅ **Authoritative, verified 2026-08-03** | Everything technical |
| `MFARUK_WORKFLOW.md` | ✅ Authoritative for **policy** — golden rules, boundaries, secrets, contingency | Operating philosophy and non-technical policy |
| `CLAUDE.md` | ⚠️ **Partly stale** — keep ONLY for the `/content` photo-SEO step list | The `/content` content workflow |
| `docs/CONTENT_GUIDELINES.md` | ✅ Current | SEO character limits |
| `DEPLOYMENT.md`, `DEVELOPMENT.md`, `PROGRESS_SUMMARY.md`, `RESUME.md`, `TASKS.md`, `CLAUDE_CONTEXT.md` | ⚠️ **Historical** | Archaeology only. Superseded by this set. Do not trust their numbers |
| `ACCESS.md` | ✅ Current (password removed 2026-08-02) | Access notes |

**Rule of precedence:** when this set and any other doc disagree, **this set wins** — it is
the only one verified against live systems. If you find this set wrong, fix it in the same
session and say so.

---

## The one-paragraph version

mfaruk.com is Mahmud Farooque's **personal** photography portfolio and technical blog. Laravel
12 + Vue 3 + Inertia (with SSR) + Tailwind, on a single HestiaCP VPS, fronted by Cloudflare,
with photo masters in Cloudflare R2 and backups orchestrated from a Synology NAS via n8n. It is
a **running product**, not a project under development. There is **no local runtime** — you
edit, commit, push, and the server builds. The blog drives essentially all the traffic; the
photo pages are the long-term SEO play. A large amount of the admin surface (commerce, print,
social, A/B testing, translations) is **built but dormant**.

---

## Fast facts (verified 2026-08-03)

| Thing | Value |
|-------|-------|
| Live URL | https://mfaruk.com |
| Repo | `git@github.com:mmhfarooque/portfolio-app` (**private**, branch `main`) |
| Local path on every machine | `~/portfolio-app` |
| Server | VPSDime VPS, `user@SERVER_IP`, HestiaCP |
| Framework | Laravel Framework **13.29.0** on PHP **8.4.10** (upgraded 2026-08-30) |
| Database | MySQL, `mfaruk_portfolio` |
| Photos | 27 total (26 published, 1 draft), **27/27 masters in R2** |
| Blog posts | 16 total, 13 published |
| Categories / Galleries / Tags | 12 / 8 / 377 |
| Admin users | 1 |
| Disk | 30 GB, **81% used, 5.4 GB free** (2026-08-30) |
| Backups | ✅ Live — NAS n8n, weekly×4 + monthly×12 |

---

## Boundary — read this before doing anything

mfaruk.com is **100% personal**. It must **never** touch Jezweb infrastructure or accounts.
Never use the Jezweb MCPs (Google Analytics, Search Console, Cloudflare, Gmail) which are
authenticated as `mahmud@jezweb.net`. Own infra only: the `mfaruk` MCP, personal Cloudflare /
GSC / Bing under `farooque7@gmail.com`, and the NAS. Full statement in
[`01-orientation.md`](01-orientation.md) and `MFARUK_WORKFLOW.md` §0/§15.
