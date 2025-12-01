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
