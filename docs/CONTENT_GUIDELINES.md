# Content & SEO Guidelines for mfaruk.com

Reference document for maintaining consistency when adding or updating content.

---

## SEO Character Limits (STRICT - MUST FOLLOW)

**Only these two fields have hard limits:**

| Field | Max Characters | Notes |
|-------|----------------|-------|
| **SEO Title** | **70** | Displayed in search results, browser tabs |
| **Meta Description** | **160** | Displayed in search results snippet |

**WARNING**: If SEO Title > 70 or Meta Description > 160, the photo may not display correctly on the homepage!

**No limits on:**
- Title (display title - can be long)
- Slug (URL - keep reasonable)
- Description (short summary)
- Story (narrative - can be multiple paragraphs)

### SEO Title Best Practices
- Include primary keyword near the beginning
- Use pipe `|` or hyphen `-` as separator
- Format: `Primary Keyword | Secondary Keyword` or `Location | Site Name`
- Example: `Nong Nooch Tropical Garden Pattaya | Thailand Photography` (57 chars)

### Meta Description Best Practices
- Write a compelling summary that encourages clicks
- Include primary keyword naturally
- End with complete sentence (no truncation)
- Example: `Discover Nong Nooch Tropical Garden in Pattaya, Thailand. This 500-acre botanical paradise features stunning French gardens, rare orchids, and cycads.` (150 chars)

---

## Photo Requirements

### Required Fields Before Publishing
- [ ] Title (display title)
- [ ] Slug (URL-friendly)
- [ ] Description (short summary)
- [ ] SEO Title (within 70 chars)
- [ ] Meta Description (within 160 chars)
- [ ] Location Name (human-readable location)
- [ ] GPS Coordinates (latitude/longitude if available)
- [ ] Story (detailed narrative, HTML formatted)
- [ ] Tags (10-15 relevant keywords)
- [ ] Category assignment
- [ ] Status set to "published"

### GPS Coordinates
- Extract from EXIF data when available
- If not in EXIF, research and add manually
- Format: Decimal degrees (e.g., 34.0837, 74.8425)
- Verify coordinates point to correct location using Google Maps

### Tags/Keywords
- Add 10-15 relevant tags per photo
- Include: location, subject, style, mood, season
- Use lowercase, spaces allowed
- Example tags for botanical garden: botanical garden, thailand, pattaya, tropical, garden, nature, flowers, travel, southeast asia, landscape

---

## Photo Status Values

| Status | Visibility |
|--------|------------|
| draft | Not visible anywhere |
| published | Visible on site, galleries, home page (if featured) |

**Important**: Featured photos must have `status: published` to appear on home page.

---

## Content Writing Style

### Voice & Tone
Write as **Mahmud the photographer** sharing his work, NOT as a travel brochure.

**AVOID these AI-sounding words:**
- "stunning", "breathtaking", "extraordinary", "spectacular"
- "world-renowned", "internationally acclaimed"
- "captured with", "the exceptional lens"
- "Instagram-worthy"

**USE personal, authentic voice:**
- "I came here on a whim..."
- "What struck me first was..."
- "The morning light was perfect..."
- "I spent most of my time in..."

### Titles
- Descriptive, can be any length
- Include location when relevant
- Capitalize major words

### Descriptions
- 1-3 sentences
- What the photo shows
- Where it was taken
- NO word limits

### Stories
- HTML formatted (`<p>` tags)
- 2-4 paragraphs
- Personal narrative - your experience taking the photo
- What drew you to the location/subject
- The moment, the light, the feeling
- NO word limits

---

## Database Reference

### Photos Table Key Fields
```
id, title, seo_title, slug, description, meta_description, story,
latitude, longitude, location_name, status, is_featured
```

### Relationships
- `photos` -> `tags` (many-to-many via `photo_tag`)
- `photos` -> `categories` (belongs to)
- `photos` -> `galleries` (belongs to)

---

## Production Server

- **SSH**: `root@63.142.240.72`
- **App Path**: `/home/mfaruk/web/mfaruk.com/private/portfolio-app`
- **Public Path**: `/home/mfaruk/web/mfaruk.com/public_html`
- **Artisan**: Run from `/private/portfolio-app` directory

### Quick Commands
```bash
# SSH to server
ssh root@63.142.240.72

# Run artisan commands
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan tinker

# Deploy from local
./deploy.sh
```

---

## Checklist Before Adding SEO Content

1. [ ] Count characters for SEO Title (must be ≤70)
2. [ ] Count characters for Meta Description (must be ≤160)
3. [ ] Verify GPS coordinates are accurate
4. [ ] Add 10-15 relevant tags
5. [ ] Set status to "published" if ready to go live
6. [ ] Mark as featured if should appear on home page

---

*Last updated: December 2024*
