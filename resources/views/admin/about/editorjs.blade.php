<x-app-layout>
    @php
        $target = $target ?? 'about';
        $isProfile = $target === 'profile';
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $isProfile ? __('Profile Bio Editor') : __('About Page Editor') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isProfile ? 'Edit your personal bio displayed on the home page' : 'Simple block-based editor for your about page content' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $isProfile ? route('home') : route('about') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                </a>
                <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Settings
                </a>
                <button type="button" id="save-btn" onclick="saveContent()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Editor Container -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div id="editorjs" class="min-h-[500px]">
                    <!-- Loading indicator -->
                    <div id="editor-loading" class="flex items-center justify-center h-[500px]">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-500">Loading editor...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-100">
                <h4 class="font-medium text-blue-900 mb-2">How to use the editor:</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>Click on a block to edit it</li>
                    <li>Press <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Enter</kbd> to create new paragraph</li>
                    <li>Press <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Tab</kbd> or click the <strong>+</strong> button to add blocks</li>
                    <li>Select text to format (bold, italic, link)</li>
                    <li>Use <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Ctrl+S</kbd> to save</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Load scripts sequentially to ensure proper order
        const scripts = [
            'https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.28.2/dist/editorjs.umd.min.js',
            'https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.1/dist/header.umd.min.js',
            'https://cdn.jsdelivr.net/npm/@editorjs/list@1.9.0/dist/list.umd.min.js',
            'https://cdn.jsdelivr.net/npm/@editorjs/quote@2.6.0/dist/quote.umd.min.js',
            'https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1.4.0/dist/delimiter.umd.min.js'
        ];

        let loadedCount = 0;

        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        async function loadAllScripts() {
            for (const src of scripts) {
                await loadScript(src);
            }
            initEditor();
        }

        function initEditor() {
            // Parse existing content or use default
            let initialData = @json($editorData ?? null);
            const isProfile = {{ $isProfile ? 'true' : 'false' }};

            if (!initialData || !initialData.blocks || initialData.blocks.length === 0) {
                if (isProfile) {
                    // Default content for profile bio
                    initialData = {
                        time: Date.now(),
                        blocks: [
                            {
                                type: "paragraph",
                                data: { text: "I'm a passionate web developer and landscape photographer with over [X] years of experience in building modern web applications and capturing the beauty of natural landscapes." }
                            },
                            {
                                type: "paragraph",
                                data: { text: "My journey began with a love for technology and photography, two fields that allow me to express creativity in different ways. As a developer, I specialize in building scalable web applications using Laravel, PHP, and modern JavaScript frameworks." }
                            },
                            {
                                type: "paragraph",
                                data: { text: "When I'm not coding, you'll find me exploring nature with my camera, chasing golden hour light, and capturing the serene beauty of landscapes. Each photograph tells a story of patience, timing, and connection with nature." }
                            }
                        ],
                        version: "2.28.2"
                    };
                } else {
                    // Default content for about page
                    initialData = {
                        time: Date.now(),
                        blocks: [
                            {
                                type: "header",
                                data: { text: "About Me", level: 1 }
                            },
                            {
                                type: "paragraph",
                                data: { text: "Welcome to my photography portfolio. I'm passionate about capturing the beauty of nature and landscapes." }
                            },
                            {
                                type: "header",
                                data: { text: "My Story", level: 2 }
                            },
                            {
                                type: "paragraph",
                                data: { text: "Photography has been my passion for many years. It all started when I picked up my first camera and discovered the magic of freezing moments in time. Since then, I've dedicated myself to honing my craft and developing a unique visual style." }
                            },
                            {
                                type: "quote",
                                data: {
                                    text: "Photography is the story I fail to put into words.",
                                    caption: "Destin Sparks"
                                }
                            },
                            {
                                type: "header",
                                data: { text: "Let's Connect", level: 2 }
                            },
                            {
                                type: "paragraph",
                                data: { text: "Have a project in mind? I'd love to hear from you. Feel free to reach out through the contact page." }
                            }
                        ],
                        version: "2.28.2"
                    };
                }
            }

            // Initialize Editor.js
            try {
                window.editor = new EditorJS({
                    holder: 'editorjs',
                    placeholder: 'Click here to start writing...',
                    data: initialData,
                    autofocus: true,
                    minHeight: 300,
                    tools: {
                        header: {
                            class: Header,
                            inlineToolbar: ['link'],
                            config: {
                                placeholder: 'Enter a header',
                                levels: [1, 2, 3],
                                defaultLevel: 2
                            }
                        },
                        list: {
                            class: List,
                            inlineToolbar: true,
                            config: {
                                defaultStyle: 'unordered'
                            }
                        },
                        quote: {
                            class: Quote,
                            inlineToolbar: true,
                            config: {
                                quotePlaceholder: 'Enter a quote',
                                captionPlaceholder: 'Quote author'
                            }
                        },
                        delimiter: Delimiter
                    },
                    onReady: function() {
                        console.log('Editor.js is ready!');
                        const loading = document.getElementById('editor-loading');
                        if (loading) loading.remove();
                        document.getElementById('editorjs').classList.add('editor-ready');
                    },
                    onChange: function(api, event) {
                        console.log('Content changed');
                    }
                });
            } catch (error) {
                console.error('Editor.js initialization error:', error);
                document.getElementById('editorjs').innerHTML = '<div class="p-4 text-red-600">Error loading editor. Please refresh the page.</div>';
            }
        }

        // Start loading when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadAllScripts);
        } else {
            loadAllScripts();
        }

        async function saveContent() {
            const btn = document.getElementById('save-btn');
            const target = '{{ $target ?? "about" }}';
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;

            try {
                const outputData = await window.editor.save();
                console.log('Saving data:', outputData);

                const response = await fetch('{{ route("admin.about.save-editorjs") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: outputData,
                        target: target
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Content saved successfully!', 'success');
                } else {
                    showToast('Error: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast('Error saving content: ' + error.message, 'error');
            }

            btn.disabled = false;
            btn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Save Changes
            `;
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Keyboard shortcut for save
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveContent();
            }
        });
    </script>

    <style>
        /* Editor.js styling */
        #editorjs {
            cursor: text;
        }
        #editorjs.editor-ready {
            min-height: 400px;
        }
        .codex-editor {
            padding: 20px;
        }
        .codex-editor__redactor {
            padding-bottom: 100px !important;
        }
        .ce-block__content {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }
        .ce-toolbar__content {
            max-width: 100%;
        }
        .ce-toolbar__plus {
            left: 10px;
        }
        .ce-toolbar__actions {
            right: 10px;
        }
        .ce-header {
            font-weight: 600;
            padding: 0.6em 0;
        }
        h1.ce-header {
            font-size: 2rem;
        }
        h2.ce-header {
            font-size: 1.5rem;
        }
        h3.ce-header {
            font-size: 1.25rem;
        }
        .ce-paragraph {
            line-height: 1.7;
            font-size: 1.05rem;
        }
        .cdx-quote {
            border-left: 4px solid #3b82f6;
            padding-left: 20px;
            font-style: italic;
        }
        .cdx-quote__text {
            font-size: 1.1rem;
            min-height: auto;
        }
        .cdx-quote__caption {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .ce-delimiter {
            line-height: 1.6em;
        }
        .ce-delimiter:before {
            content: '***';
            letter-spacing: 0.3em;
            color: #cbd5e1;
        }
    </style>
</x-app-layout>
