# 09 — Backup and NAS Automation

> Part 9 of 11. Previous: [`08-operations.md`](08-operations.md) · Next: [`10-gotchas.md`](10-gotchas.md)

**Status: ✅ automated backup is LIVE.** Built 2026-08-02, independently verified against real
files on the NAS 2026-08-03. Any document saying mfaruk.com has no automated backup is stale.

---

## 9.1 Design principle

**The server hosts no backup system at all.** The NAS reaches in and pulls. The server's only
job is to produce an archive on request and then forget about it.

That inversion is deliberate and is the direct lesson of what went wrong before (§9.6): a
backup system living on the machine it protects, writing to the disk it protects, with a web
panel exposed to the internet, is worse than no backup system — because it also lies about
being one.

```
   Synology DS923+  (n8n)                          VPSDime VPS
   ─────────────────────────                       ────────────────────
   schedule fires
        │  SSH (private key, forced-command verb)
        ├───────────────────────────────────────▶  create <tier>
        │                                          mysqldump + tar
        │  ◀── one-line JSON receipt on stdout ──  {ok,file,sha256,bytes,…}
        │      (progress goes to stderr)
        │
        ├─ Code node: hard-throw unless ok/file/sha256 present
        │
        ├── SSH ──▶ mfaruk-collect.sh (on the NAS)
        │           pulls the file itself, verifies sha256, prunes
        │
        ├── SSH ──▶ cleanup <file>   (clears server staging)
        │
        └── Telegram confirmation
```

The archive **never passes through n8n**. That keeps a ~70 MB payload out of n8n's memory, and
it sidesteps a hard platform limitation (§9.5).

---

## 9.2 The five n8n workflows

All on the NAS at `http://192.168.0.96:5678`, all Published, **all with `errorWorkflow` set** so
any failure routes to the alert handler. All created 2026-08-02, all timezone Asia/Dhaka.

| ID | Name | Schedule |
|----|------|----------|
| `IpJIdLb6fzqtouDl` | mfaruk.com backup to NAS | **weekly Sun 10:00** (keep 4) · **monthly 1st 10:30** (keep 12) |
| `7ebLQGWs2DJjZsHz` | backup staleness watchdog | daily 11:00 |
| `RiVRpnX39OGJjfL8` | n8n FAILURE alert (error handler) | on error |
| `mCvlNwHSVZ8Qh5xW` | n8n update available | weekly Mon 09:30 |
| `1zbRwOKT2o7AUzQU` | mfaruk daily digest to Telegram | daily 09:00 Dhaka |

Timezone is **Asia/Dhaka** on the workflows, overriding the container's
`GENERIC_TIMEZONE=Australia/Sydney`. Mahmud is in Dhaka; a Sydney-timed cron fired at 05:00
local, when the NAS was off.

### Backup workflow node chain

```
Weekly Sun 10am ─┐
                 ├─▶ tier config ─▶ Choose tier
Monthly 1st 10:30┘
   ─▶ SSH  create <tier>                 (on the server)
   ─▶ Code Parse receipt                 hard-throws if ok/file/sha256 missing
   ─▶ SSH  mfaruk-collect.sh <tier> <file> <sha256> <keep>   (on the NAS)
   ─▶ SSH  cleanup <file>                (clears server staging)
   ─▶ Telegram
```

SSH authentication is **private key** throughout and the commands are **forced-command verbs**
(`create`, `cleanup`), not free-form shell.

### Retention

Weekly keep **4**, monthly keep **12**. Verified against the real code: weekly tested at 5 →
pruned oldest → 4; monthly tested at 13 → pruned oldest → 12.

Two properties worth keeping if you ever touch this:

- **Prune runs only after the new archive has landed and its sha256 matches**, so a failed run
  can never shrink the pool.
- **Pruning sorts by filename, not mtime** — the timestamps are embedded as
  `YYYYMMDD-HHMMSS` in the name, and a copy or restore rewrites mtime.

---

## 9.3 What is on the NAS

`/volume1/homes/mimocloud/Backup/mfaruk.com/`

```
weekly/     4 × ~70 MB .tar.gz, each with a matching .sha256 sidecar
monthly/    mfaruk-monthly-20260802-093304.tar.gz  + sidecar
legacy-server-backups-20260802/   the retired system's archives, preserved
mfaruk-collect.sh          pulls, verifies, prunes
mfaruk-backup-age.sh       reports counts/ages/free space as JSON
mfaruk-send-to-server.sh   push helper
```

Each archive holds **~194 entries** including a gzipped DB dump (~814 KB). So the newest usable
database dump is now days old, not the 12 July one that older docs still name.

### How to verify a backup is real — do this, not a glance at green ticks

1. Mount the NAS `home` SMB share.
2. List `weekly/` and `monthly/` — confirm counts and sizes.
3. Read the `.sha256` sidecar for the newest archive and compare it to the sha256 in the n8n
   execution receipt. They must be byte-identical.
4. Run `gzip -t` on the archive.

All four checks passed on 2026-08-03: sidecar `7fa62282…d72568` matched the receipt exactly, and
`gzip -t` returned clean.

---

## 9.3b Restore — built, and the test PASSED

**`/usr/local/bin/mfaruk-restore.sh`** on the VPS, **root-only (mode 700)**. Verified present
2026-08-03. Three modes:

| Mode | Behaviour |
|------|-----------|
| `inspect <file>` | Read-only — sha256, manifest, contents |
| `test <file>` | Restores into a **timestamped scratch DB** and `/tmp` dir, verifies, drops the scratch DB. **Production untouched** |
| `production <file> "OVERWRITE PRODUCTION"` | Gated: exact confirmation phrase required, **and** it takes a safety dump of the current DB first, aborting if that dump is implausibly small |

### 🔒 The privilege separation — the most important design decision here

**Restore is NOT reachable by the n8n backup key.** That key is `command=`-pinned to the
producer verbs only. **Automation may CREATE backups; it must never be able to overwrite the
live site.** Restore is a deliberate human action over normal root SSH.

Supporting this, a dedicated system user **`mfbackup`** (uid 996) owns
`/home/mfbackup/staging` and nothing else, reached by an SFTP-only fetch key. The NAS sender
`~mimocloud/Backup/mfaruk.com/mfaruk-send-to-server.sh <tier> <file>` uploads a **stored**
archive back into that staging directory.

⚠️ **Test the STORED copy, not a fresh one.** A freshly created archive only proves the producer
works. The stored copy is what you would actually restore from.

### Test result — oldest stored weekly, 2026-08-02

- sha256 matched the sidecar
- dump ends with the **mysqldump completion marker** (the truncation check)
- imported with **zero errors**
- **all 37 tables present**, list identical to production
- real row counts matched live exactly (users 1, photos 24, posts 16, categories 11,
  galleries 8, tags 361, settings 74)
- content readable by slug; files extracted; a sampled JPEG was valid

**These are proven backups.**

### Two verification traps worth memorising

1. ⚠️ **`information_schema.table_rows` is an InnoDB ESTIMATE — never verify with it.** The
   manifest reported `users: 0` when the real count was 1. Verifying against the manifest would
   have produced a **false failure**. Always compare a real `COUNT(*)`.
2. ⚠️ **Assert the dump ends with the mysqldump completion marker.** A truncated dump imports
   partially and **silently** — the classic way a restore quietly loses data.

⚠️ The **production** restore path is written but has never been exercised, which is correct —
exercising it would mean overwriting the live database. Note also that it does **not**
auto-restore files; it prints the extract command for a human to run.

Local copies of the tooling: `~/mfaruk-backups/{mfaruk-restore.sh,mfaruk-send-to-server.sh}`.

---

## 9.4 The staleness watchdog

`7ebLQGWs2DJjZsHz`, daily 11:00. Runs `mfaruk-backup-age.sh` over SSH and evaluates:

```js
const WEEKLY_MAX_DAYS  = 8;
const MONTHLY_MAX_DAYS = 32;
// count === 0                → NO backups exist at all
// age === null               → could not determine age (suspicious, never "fine")
// age > MAX                  → stale
// nas_free_mb < 5000         → low space
if (problems.length === 0) return [];   // healthy → stay SILENT
```

⚠️ **Age is derived from the FILENAME stamp, not mtime** — a copy or restore rewrites mtime and
would report a five-month-old backup as fresh. Same reasoning as the prune ordering.

It exists to close a blind spot the error handler **cannot** see: the failure alert only fires
when an execution runs and errors. If n8n is down or the NAS is asleep at the scheduled moment,
**no execution exists**, nothing fires, and n8n never back-fills missed schedules. A
`crash.journal` dated 2026-08-02 12:25 in the n8n data directory proves this container does
crash unprompted.

⚠️ **Do not re-investigate execution 74.** It fired a STALE alert reading
`weekly is 0 days old (limit -1)`. That threshold was deliberately dropped to `-1` to force the
alert path during testing and was reverted to 8/32 nine seconds later. Likewise the dashboard's
26.7% failure rate is the same-day build-out window, not a live fault.

---

## 9.5 n8n platform constraints — learned the hard way

- **`executeCommand` nodes cannot be activated.** The NAS n8n accepts
  `n8n-nodes-base.executeCommand` on POST and PUT and stores it fine, then fails
  `POST /workflows/:id/activate` with *Unrecognized node type*. **Storage-level validation is
  not proof a node will run — always test activation.** Practical effect: n8n on this NAS cannot
  run local shell commands, which is why the collector is reached over SSH to `127.0.0.1`.
- **The public API v1 cannot trigger a manual run** (405). Read and update only.
- **Never use `$json.execution.url` in alerts.** It inherits `WEBHOOK_URL`, which is
  `http://localhost:5678/` on this container (set for the Google OAuth callback), so every link
  is dead on any device except the NAS. Build links by hand:
  `http://192.168.0.96:5678/workflow/{{ $json.workflow?.id }}/executions/{{ $json.execution?.id }}`.
- **The Error Trigger payload has no usable `startedAt`** — use
  `{{ $now.setZone('Asia/Dhaka').toFormat('yyyy-LL-dd HH:mm') }}`.
- **The Telegram node's output does not contain the sent message body.** Verify rendered text on
  the phone; verify the expressions by confirming the handler execution succeeded (a throwing
  expression fails the handler and loses the alert silently).
- **Deleting a workflow cascade-deletes its executions** — never delete a test probe before
  inspecting it.
- API key label `mfaruk`, recoverable from the laptop fallback DB `~/.n8n/database.sqlite`,
  table `user_api_keys`.

---

## 9.6 What was removed, and why it matters

On 2026-08-02, **two** backup systems were retired. Neither had produced a backup since
**8 March 2026**. Commit `24aa81c`, −3,997 lines.

**System 1 — standalone panel and CLI**

- `public/backup-panel/` was **publicly reachable, HTTP 200** — a 60 KB PHP file executing
  **outside Laravel** via its own `.htaccess` bypassing the front controller.
- It was **tracked in git**, so deleting it server-side alone would have been undone by the next
  `deploy.sh` (`git reset --hard`).
- The `mfaruk` crontab **still fired** `scheduled-backup.sh` weekly and monthly. The script
  exited at line 1 every time because `.schedule-enabled` / `.schedule-config` had been deleted
  from the panel on **15 Mar 2026 11:46:17**. **Five silent no-ops a month for nearly five
  months — no error, no alert.** That is exactly why the gap went unnoticed.
- The wrapper hardcoded the pre-May password in **plaintext on disk**, so a re-enabled run would
  have failed authentication anyway.
- Backups were written to the **same 30 GB disk they were meant to protect**. Never offsite.
- The panel password was committed in **plaintext in `ACCESS.md`**.

**System 2 — Backblaze B2 admin integration**

`/admin/backup`, `BackupController`, the `backup:photos` command, and a Vue page. `B2_*` was
never set in `.env` and `last_backup_at` was never populated. **It never ran once.** Removed as
dead code.

**Removal was non-destructive.** Before anything was deleted, all archives were copied to the
laptop and the NAS and **MD5-verified identical to the server, 7 of 7**. Server files were moved
to `/home/mfaruk/_retired/backup-system-20260802/`, including the pre-change crontab, with a
README documenting inventory and rollback. Repo files were removed with `git rm`, so full
contents remain in history.

Two footnotes: `pre-phase2-20260308.sql.gz` is a **20-byte truncated gzip** and never was
usable; and `pre-series-removal-20260712-071454.sql.gz` was the newest usable dump at the time —
it has since been superseded by the live pipeline.

**The lesson, stated plainly:** a backup system that reports nothing is indistinguishable from
one that works. Every replacement component here — the JSON receipt with a hard throw, the
checksum verification before prune, the staleness watchdog, the Telegram confirmation — exists
because silence was the actual failure mode.

---

## 9.7 The offsite leg — still missing

Current coverage:

| Layer | Status |
|-------|--------|
| Server → NAS, weekly + monthly, checksum-verified | ✅ live |
| NAS RAID redundancy | ✅ (guards a dead drive only) |
| Photo masters independently in Cloudflare R2 | ✅ |
| Restore tooling + verified restore test | ✅ passed 2026-08-02 (§9.3b) |
| **DB dump pushed offsite to R2** | ❌ **designed, not built** |

⚠️ **RAID is not backup.** It guards against a dead drive, not deletion, corruption,
ransomware, or loss of the NAS itself. The intended third leg is pushing the small DB dump to R2
periodically, which is free. Images are already independently safe in R2, so the DB is the
exposure.

---

## 9.8 Other NAS automation

**Analytics push → mfaruk.com.** The 9am digest workflow has a node that POSTs to
`https://mfaruk.com/api/nas/analytics` with a bearer token, carrying Google Search Console and
Google Analytics figures. Stored in `analytics_snapshots`, rendered on the admin dashboard. This
**replaced** the site's own Search Console OAuth, which has been dead since April.

⚠️ The Get GSC report node has **pinned data** — editor and manual runs push tiny pinned
numbers; only the scheduled 09:00 runs push real figures.

⚠️ The Search Console property is registered as a **URL prefix** (`https://mfaruk.com/`), not a
domain property, so the API path must be `…/sites/https%3A%2F%2Fmfaruk.com%2F/…`. Using
`sc-domain%3A…` returns 403.

**Digest.** Daily 09:00 Dhaka Telegram digest: server log reads versus crawls (via
`mfaruk-mcp`), 7-day Google Analytics users, and top Search Console queries.

---

## 9.9 Open risks

1. **No offsite DB copy** (§9.7). This is now the top gap, since restore itself is proven.
2. **The NAS is powered off overnight** and n8n never back-fills. A Sunday 10:00 Dhaka fire with
   the NAS asleep silently produces nothing. The watchdog catches it a day later; a DSM Control
   Panel → Hardware & Power → **Power Schedule** startup would prevent it outright.
3. **The n8n container image is `n8nio/n8n:latest`, unpinned.** Any restart — crash, reboot,
   power schedule — can silently jump versions, including a major, underneath the workflows that
   orchestrate these backups. Pinning and updating are the same action: set
   `image: n8nio/n8n:2.32.7` via Container Manager → project **Stop** → **YAML Configurations**
   tab → **Build**. Editing `docker-compose.yml` on disk does nothing; Container Manager keeps
   an internal copy. After any upgrade, **re-run the backup workflow manually** to confirm the
   SSH and Code nodes still work, and **bump the hardcoded `BASELINE`** in
   `~/mfaruk-backups/build-n8n-update-notifier.py` or the update alert repeats forever.
