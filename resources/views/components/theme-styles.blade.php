<style id="theme-variables">
{!! $css !!}
</style>

<style id="theme-utilities">
/* ==========================================
   COMPREHENSIVE THEME STYLES
   ========================================== */

/* Base body styles */
body.themed {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    font-family: var(--font-family);
    transition: background-color var(--transition), color var(--transition);
}

/* ==========================================
   BACKGROUNDS
   ========================================== */
.bg-theme-primary { background-color: var(--bg-primary); }
.bg-theme-secondary { background-color: var(--bg-secondary); }
.bg-theme-tertiary { background-color: var(--bg-tertiary); }
.bg-theme-card { background-color: var(--bg-card); }
.bg-theme-input { background-color: var(--bg-input); }
.bg-theme-accent { background-color: var(--accent); }
.bg-theme-accent-light { background-color: var(--accent-light); }
.hover\:bg-theme-hover:hover { background-color: var(--bg-hover); }
.hover\:bg-theme-accent-hover:hover { background-color: var(--accent-hover); }

/* ==========================================
   TEXT COLORS
   ========================================== */
.text-theme-primary { color: var(--text-primary); }
.text-theme-secondary { color: var(--text-secondary); }
.text-theme-muted { color: var(--text-muted); }
.text-theme-inverse { color: var(--text-inverse); }
.text-theme-accent { color: var(--accent); }
.hover\:text-theme-primary:hover { color: var(--text-primary); }
.hover\:text-theme-accent:hover { color: var(--accent-hover); }

/* ==========================================
   BORDERS
   ========================================== */
.border-theme { border-color: var(--border); }
.border-theme-light { border-color: var(--border-light); }
.border-theme-accent { border-color: var(--accent); }
.divide-theme > * + * { border-color: var(--border); }
.ring-theme { --tw-ring-color: var(--border); }
.ring-theme-accent { --tw-ring-color: var(--accent); }
.focus\:ring-theme-accent:focus { --tw-ring-color: var(--accent); }
.focus\:border-theme-accent:focus { border-color: var(--accent); }

/* ==========================================
   TYPOGRAPHY
   ========================================== */
.font-theme { font-family: var(--font-family); }
.font-theme-heading { font-family: var(--font-heading); }

h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    color: var(--text-primary);
}

a {
    color: var(--accent);
    transition: color var(--transition);
}
a:hover {
    color: var(--accent-hover);
}

/* ==========================================
   SHADOWS
   ========================================== */
.shadow-theme-sm { box-shadow: var(--shadow-sm); }
.shadow-theme { box-shadow: var(--shadow); }
.shadow-theme-lg { box-shadow: var(--shadow-lg); }

/* ==========================================
   BORDER RADIUS
   ========================================== */
.rounded-theme { border-radius: var(--border-radius); }
.rounded-theme-lg { border-radius: var(--border-radius-lg); }

/* ==========================================
   CARDS
   ========================================== */
.card-theme {
    background-color: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    transition: all var(--transition);
}
.card-theme:hover {
    box-shadow: var(--shadow-lg);
}

/* ==========================================
   BUTTONS
   ========================================== */
.btn-theme-primary {
    background-color: var(--accent);
    color: var(--text-inverse);
    border-radius: var(--border-radius);
    transition: all var(--transition);
    font-family: var(--font-family);
}
.btn-theme-primary:hover {
    background-color: var(--accent-hover);
}

.btn-theme-secondary {
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    transition: all var(--transition);
}
.btn-theme-secondary:hover {
    background-color: var(--bg-hover);
}

.btn-theme-outline {
    background-color: transparent;
    color: var(--accent);
    border: 2px solid var(--accent);
    border-radius: var(--border-radius);
    transition: all var(--transition);
}
.btn-theme-outline:hover {
    background-color: var(--accent);
    color: var(--text-inverse);
}

/* ==========================================
   FORM INPUTS
   ========================================== */
.input-theme {
    background-color: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    font-family: var(--font-family);
    transition: all var(--transition);
}
.input-theme:focus {
    border-color: var(--accent);
    outline: none;
    box-shadow: 0 0 0 3px var(--accent-light);
}
.input-theme::placeholder {
    color: var(--text-muted);
}

/* ==========================================
   NAVIGATION
   ========================================== */
.nav-theme {
    background-color: var(--bg-card);
    border-color: var(--border);
}
.nav-theme-link {
    color: var(--text-secondary);
    font-family: var(--font-family);
    transition: color var(--transition);
}
.nav-theme-link:hover {
    color: var(--text-primary);
}
.nav-theme-link.active {
    color: var(--accent);
}

/* ==========================================
   FOOTER
   ========================================== */
.footer-theme {
    background-color: var(--bg-secondary);
    border-color: var(--border);
    color: var(--text-secondary);
}

/* ==========================================
   PHOTO GALLERY SPECIFIC
   ========================================== */
.photo-card-theme {
    background-color: var(--bg-card);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all var(--transition);
}
.photo-card-theme:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.photo-overlay-theme {
    background: var(--overlay);
}

/* ==========================================
   SCROLLBAR (for dark themes)
   ========================================== */
@if($isDark)
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
::-webkit-scrollbar-track {
    background: var(--bg-secondary);
}
::-webkit-scrollbar-thumb {
    background: var(--bg-tertiary);
    border-radius: 5px;
}
::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted);
}
@endif

/* ==========================================
   SELECTION
   ========================================== */
::selection {
    background-color: var(--accent);
    color: var(--text-inverse);
}

/* ==========================================
   GRADIENTS
   ========================================== */
.bg-gradient-theme {
    background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
}
.bg-gradient-theme-accent {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
}

/* ==========================================
   HERO SECTION
   ========================================== */
.hero-theme {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}
.hero-theme h1 {
    font-family: var(--font-heading);
}

/* ==========================================
   STATUS COLORS
   ========================================== */
.text-theme-success { color: var(--success); }
.text-theme-warning { color: var(--warning); }
.text-theme-error { color: var(--error); }
.bg-theme-success { background-color: var(--success); }
.bg-theme-warning { background-color: var(--warning); }
.bg-theme-error { background-color: var(--error); }

/* ==========================================
   TRANSITIONS
   ========================================== */
.transition-theme {
    transition: all var(--transition);
}

/* ==========================================
   PHOTOGRAPHY-SPECIFIC ENHANCEMENTS
   ========================================== */

/* Photo frame effect for images */
.photo-frame {
    position: relative;
    padding: 0.5rem;
    background: var(--bg-card);
    box-shadow: var(--shadow-lg);
}

/* Cinematic letterbox bars (for Darkroom theme) */
.theme-dark .photo-cinematic::before,
.theme-dark .photo-cinematic::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 8%;
    background: var(--bg-primary);
    opacity: 0.6;
    pointer-events: none;
}
.theme-dark .photo-cinematic::before { top: 0; }
.theme-dark .photo-cinematic::after { bottom: 0; }

/* Film strip perforations (for Vintage theme) */
[data-theme="retro"] .photo-card-theme {
    position: relative;
}
[data-theme="retro"] .photo-card-theme::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: repeating-linear-gradient(
        90deg,
        var(--border) 0px,
        var(--border) 8px,
        transparent 8px,
        transparent 16px
    );
    opacity: 0.5;
}

/* Elegant photo mat (for Gallery theme) */
[data-theme="light"] .photo-card-theme {
    border: 1px solid var(--border);
    padding: 1rem;
    background: linear-gradient(
        to bottom right,
        var(--bg-card) 0%,
        rgba(231, 229, 228, 0.3) 100%
    );
}

/* Photo hover effects per theme */
.theme-dark .photo-card-theme:hover img {
    filter: brightness(1.05) contrast(1.02);
}
[data-theme="light"] .photo-card-theme:hover img {
    filter: brightness(1.02) saturate(1.05);
}
[data-theme="retro"] .photo-card-theme:hover img {
    filter: sepia(0.1) brightness(1.03);
}

/* Darkroom theme - Amber glow effect on focus */
.theme-dark *:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    box-shadow: 0 0 20px rgba(212, 165, 116, 0.3);
}

/* Gallery theme - Refined underline links */
[data-theme="light"] a:not(.btn-theme-primary):not(.btn-theme-secondary):not(.btn-theme-outline) {
    text-decoration: none;
    background-image: linear-gradient(var(--accent), var(--accent));
    background-size: 0% 1px;
    background-position: 0 100%;
    background-repeat: no-repeat;
    transition: background-size var(--transition);
}
[data-theme="light"] a:not(.btn-theme-primary):not(.btn-theme-secondary):not(.btn-theme-outline):hover {
    background-size: 100% 1px;
}

/* Vintage theme - Subtle paper texture effect */
[data-theme="retro"] body {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
}

/* Custom scrollbar styling per theme */
.theme-dark::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #3d3835 0%, #292524 100%);
}
[data-theme="light"]::-webkit-scrollbar {
    width: 8px;
}
[data-theme="light"]::-webkit-scrollbar-track {
    background: var(--bg-secondary);
}
[data-theme="light"]::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 4px;
}
[data-theme="retro"]::-webkit-scrollbar-thumb {
    background: var(--accent);
    border-radius: 0;
}

/* Navigation hover line effect */
.nav-theme-link {
    position: relative;
}
.nav-theme-link::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    width: 0;
    height: 2px;
    background: var(--accent);
    transition: all var(--transition);
    transform: translateX(-50%);
}
.nav-theme-link:hover::after,
.nav-theme-link.active::after {
    width: 100%;
}

/* Darkroom: Soft glow on nav hover */
.theme-dark .nav-theme-link:hover {
    text-shadow: 0 0 20px rgba(212, 165, 116, 0.5);
}

/* Button enhancements per theme */
.theme-dark .btn-theme-primary {
    background: linear-gradient(135deg, var(--accent) 0%, #c4956a 100%);
    box-shadow: 0 4px 15px rgba(212, 165, 116, 0.3);
}
.theme-dark .btn-theme-primary:hover {
    box-shadow: 0 6px 20px rgba(212, 165, 116, 0.4);
    transform: translateY(-1px);
}

[data-theme="light"] .btn-theme-primary {
    font-weight: 500;
    letter-spacing: 0.025em;
    text-transform: uppercase;
    font-size: 0.875rem;
}

[data-theme="retro"] .btn-theme-primary {
    box-shadow: 3px 3px 0 0 rgba(109, 31, 51, 0.4);
    border-radius: 0;
}
[data-theme="retro"] .btn-theme-primary:hover {
    box-shadow: 2px 2px 0 0 rgba(109, 31, 51, 0.4);
    transform: translate(1px, 1px);
}

/* Card enhancements */
.theme-dark .card-theme {
    background: linear-gradient(145deg, #141414 0%, #0c0c0c 100%);
    border: 1px solid rgba(61, 56, 53, 0.5);
}

[data-theme="retro"] .card-theme {
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

/* Image vignette effect option */
.photo-vignette {
    position: relative;
}
.photo-vignette::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.3);
}

/* Heading letter-spacing per theme */
.theme-dark h1, .theme-dark h2, .theme-dark h3 {
    letter-spacing: -0.025em;
}
[data-theme="light"] h1, [data-theme="light"] h2, [data-theme="light"] h3 {
    letter-spacing: 0.01em;
    font-weight: 400;
}
[data-theme="retro"] h1, [data-theme="retro"] h2, [data-theme="retro"] h3 {
    letter-spacing: 0.02em;
    font-style: italic;
}
</style>

<script>
    // Add theme classes to body
    document.body.classList.add('themed');
    @if($isDark)
    document.body.classList.add('theme-dark');
    document.body.classList.remove('theme-light');
    @else
    document.body.classList.add('theme-light');
    document.body.classList.remove('theme-dark');
    @endif
    document.body.dataset.theme = '{{ $themeName }}';
</script>
