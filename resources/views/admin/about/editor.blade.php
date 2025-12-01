<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>About Page Editor - {{ config('app.name') }}</title>
    <link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: #1a1a2e;
            color: white;
        }

        .editor-header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }

        .editor-header .actions {
            display: flex;
            gap: 10px;
        }

        .editor-header .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .editor-header .btn-back {
            background: #374151;
            color: white;
        }

        .editor-header .btn-back:hover {
            background: #4b5563;
        }

        .editor-header .btn-save {
            background: #3b82f6;
            color: white;
        }

        .editor-header .btn-save:hover {
            background: #2563eb;
        }

        .editor-header .btn-save:disabled {
            background: #6b7280;
            cursor: not-allowed;
        }

        .editor-header .btn-preview {
            background: #10b981;
            color: white;
        }

        .editor-header .btn-preview:hover {
            background: #059669;
        }

        #gjs {
            height: calc(100vh - 56px);
            width: 100%;
        }

        /* GrapesJS customizations */
        .gjs-one-bg {
            background-color: #1a1a2e;
        }

        .gjs-two-color {
            color: #ddd;
        }

        .gjs-three-bg {
            background-color: #16213e;
        }

        .gjs-four-color,
        .gjs-four-color-h:hover {
            color: #3b82f6;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        .toast-success {
            background: #10b981;
        }

        .toast-error {
            background: #ef4444;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .saving-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            color: #9ca3af;
            font-size: 13px;
        }

        .saving-indicator.active {
            display: flex;
        }

        .saving-indicator svg {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="editor-header">
        <h1>About Page Editor</h1>
        <div class="actions">
            <div class="saving-indicator" id="saving-indicator">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" stroke-dasharray="50" stroke-dashoffset="20"/>
                </svg>
                Saving...
            </div>
            <a href="{{ route('admin.settings.index') }}" class="btn btn-back">
                &larr; Back to Settings
            </a>
            <a href="{{ route('about') }}" target="_blank" class="btn btn-preview">
                Preview Page
            </a>
            <button type="button" class="btn btn-save" id="save-btn" onclick="saveContent()">
                Save Changes
            </button>
        </div>
    </div>

    <div id="gjs">
        {!! $content !!}
    </div>

    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-preset-webpage"></script>
    <script>
        const editor = grapesjs.init({
            container: '#gjs',
            fromElement: true,
            height: '100%',
            width: 'auto',
            storageManager: false,
            plugins: ['gjs-preset-webpage'],
            pluginsOpts: {
                'gjs-preset-webpage': {
                    blocksBasicOpts: {
                        blocks: ['column1', 'column2', 'column3', 'column3-7', 'text', 'link', 'image', 'video'],
                        flexGrid: true,
                    },
                    formsOpts: false,
                    navbarOpts: false,
                    countdownOpts: false,
                }
            },
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
                ],
            },
            styleManager: {
                sectors: [
                    {
                        name: 'General',
                        open: true,
                        properties: [
                            'display',
                            'float',
                            'position',
                            'top',
                            'right',
                            'left',
                            'bottom',
                        ],
                    },
                    {
                        name: 'Dimension',
                        open: false,
                        properties: [
                            'width',
                            'height',
                            'max-width',
                            'min-height',
                            'margin',
                            'padding',
                        ],
                    },
                    {
                        name: 'Typography',
                        open: false,
                        properties: [
                            'font-family',
                            'font-size',
                            'font-weight',
                            'letter-spacing',
                            'color',
                            'line-height',
                            'text-align',
                            'text-decoration',
                            'text-shadow',
                        ],
                    },
                    {
                        name: 'Decorations',
                        open: false,
                        properties: [
                            'background-color',
                            'background',
                            'border-radius',
                            'border',
                            'box-shadow',
                        ],
                    },
                ],
            },
            // Add custom blocks for photography portfolio
            blockManager: {
                appendTo: '#blocks',
            },
        });

        // Add custom blocks for photography about page
        editor.BlockManager.add('about-section', {
            label: 'About Section',
            category: 'Photography',
            content: `
                <section style="padding: 60px 20px; max-width: 800px; margin: 0 auto;">
                    <h2 style="font-size: 32px; font-weight: 600; margin-bottom: 20px; color: #1f2937;">About Me</h2>
                    <p style="font-size: 18px; line-height: 1.8; color: #4b5563;">
                        Write your story here. Share your passion for photography, your journey, and what inspires you to capture moments.
                    </p>
                </section>
            `,
        });

        editor.BlockManager.add('profile-card', {
            label: 'Profile Card',
            category: 'Photography',
            content: `
                <div style="display: flex; gap: 40px; padding: 40px; background: #f9fafb; border-radius: 12px; align-items: center; flex-wrap: wrap;">
                    <img src="https://via.placeholder.com/200x200" alt="Profile" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover;">
                    <div style="flex: 1; min-width: 250px;">
                        <h3 style="font-size: 24px; margin: 0 0 10px; color: #1f2937;">Your Name</h3>
                        <p style="color: #6b7280; margin: 0 0 15px;">Photographer & Visual Artist</p>
                        <p style="color: #4b5563; line-height: 1.6;">A brief introduction about yourself and your photography style.</p>
                    </div>
                </div>
            `,
        });

        editor.BlockManager.add('skills-grid', {
            label: 'Skills Grid',
            category: 'Photography',
            content: `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 40px 20px;">
                    <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h4 style="font-size: 18px; margin: 0 0 10px; color: #1f2937;">Landscape</h4>
                        <p style="color: #6b7280; margin: 0; font-size: 14px;">Nature & scenic photography</p>
                    </div>
                    <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h4 style="font-size: 18px; margin: 0 0 10px; color: #1f2937;">Portrait</h4>
                        <p style="color: #6b7280; margin: 0; font-size: 14px;">People & lifestyle shots</p>
                    </div>
                    <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h4 style="font-size: 18px; margin: 0 0 10px; color: #1f2937;">Events</h4>
                        <p style="color: #6b7280; margin: 0; font-size: 14px;">Weddings & celebrations</p>
                    </div>
                </div>
            `,
        });

        editor.BlockManager.add('quote-block', {
            label: 'Quote Block',
            category: 'Photography',
            content: `
                <blockquote style="padding: 40px; margin: 40px 20px; border-left: 4px solid #3b82f6; background: #f8fafc; font-size: 20px; font-style: italic; color: #374151;">
                    "Photography is the story I fail to put into words."
                    <footer style="margin-top: 15px; font-size: 14px; font-style: normal; color: #6b7280;">— Destin Sparks</footer>
                </blockquote>
            `,
        });

        editor.BlockManager.add('contact-cta', {
            label: 'Contact CTA',
            category: 'Photography',
            content: `
                <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: white; border-radius: 12px; margin: 40px 20px;">
                    <h3 style="font-size: 28px; margin: 0 0 15px;">Let's Work Together</h3>
                    <p style="font-size: 16px; margin: 0 0 25px; opacity: 0.9;">Have a project in mind? I'd love to hear from you.</p>
                    <a href="/contact" style="display: inline-block; padding: 12px 30px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: 500;">Get in Touch</a>
                </div>
            `,
        });

        // Save function
        async function saveContent() {
            const btn = document.getElementById('save-btn');
            const indicator = document.getElementById('saving-indicator');

            btn.disabled = true;
            indicator.classList.add('active');

            // Get HTML and CSS
            const html = editor.getHtml();
            const css = editor.getCss();

            // Combine HTML with inline styles
            const fullContent = `<style>${css}</style>${html}`;

            try {
                const response = await fetch('{{ route("admin.about.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: fullContent
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Content saved successfully!', 'success');
                } else {
                    showToast('Error saving content: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast('Error saving content. Please try again.', 'error');
            }

            btn.disabled = false;
            indicator.classList.remove('active');
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Keyboard shortcut for save
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveContent();
            }
        });
    </script>
</body>
</html>
