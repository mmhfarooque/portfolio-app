<button type="button"
    x-data="sectionSaveButton($el)"
    @click="save()"
    :disabled="saving"
    class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-lg transition-all duration-300 font-medium"
    :class="{
        'bg-green-500 text-white shadow-lg shadow-green-500/30': saved,
        'bg-gray-300 text-gray-500 cursor-wait': saving,
        'bg-blue-600 text-white shadow-lg shadow-blue-500/30 hover:bg-blue-700 animate-pulse': hasChanges && !saving && !saved,
        'bg-gray-100 text-gray-400 cursor-default': !hasChanges && !saving && !saved
    }">

    <!-- Default state (no changes) -->
    <template x-if="!saving && !saved && !hasChanges">
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Saved
        </span>
    </template>

    <!-- Has changes state -->
    <template x-if="!saving && !saved && hasChanges">
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            Save Now
        </span>
    </template>

    <!-- Saving state -->
    <template x-if="saving">
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </template>

    <!-- Saved state -->
    <template x-if="saved">
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Saved!
        </span>
    </template>
</button>

<script>
    function sectionSaveButton(el) {
        return {
            saving: false,
            saved: false,
            hasChanges: false,
            initialValues: {},

            init() {
                // Get the parent section (the card containing this button)
                const section = this.$el.closest('.bg-white');
                if (!section) return;

                // Store initial values of all inputs in this section
                const inputs = section.querySelectorAll('input, select, textarea');
                inputs.forEach((input, index) => {
                    const key = input.name || `input_${index}`;
                    if (input.type === 'checkbox') {
                        this.initialValues[key] = input.checked;
                    } else if (input.type === 'file') {
                        this.initialValues[key] = '';
                    } else {
                        this.initialValues[key] = input.value;
                    }

                    // Listen for changes
                    input.addEventListener('input', () => this.checkForChanges(section));
                    input.addEventListener('change', () => this.checkForChanges(section));
                });
            },

            checkForChanges(section) {
                const inputs = section.querySelectorAll('input, select, textarea');
                let changed = false;

                inputs.forEach((input, index) => {
                    const key = input.name || `input_${index}`;
                    let currentValue;

                    if (input.type === 'checkbox') {
                        currentValue = input.checked;
                    } else if (input.type === 'file') {
                        currentValue = input.files.length > 0 ? 'file_selected' : '';
                    } else {
                        currentValue = input.value;
                    }

                    if (currentValue !== this.initialValues[key]) {
                        changed = true;
                    }
                });

                this.hasChanges = changed;
            },

            async save() {
                if (!this.hasChanges && !this.saved) return;

                const form = this.$el.closest('form');
                if (!form) return;

                this.saving = true;
                this.saved = false;

                try {
                    const formData = new FormData(form);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.saved = true;
                        this.hasChanges = false;

                        // Update initial values to current values
                        const section = this.$el.closest('.bg-white');
                        const inputs = section.querySelectorAll('input, select, textarea');
                        inputs.forEach((input, index) => {
                            const key = input.name || `input_${index}`;
                            if (input.type === 'checkbox') {
                                this.initialValues[key] = input.checked;
                            } else if (input.type === 'file') {
                                this.initialValues[key] = '';
                                // Clear file input after successful upload
                                input.value = '';
                            } else {
                                this.initialValues[key] = input.value;
                            }
                        });

                        // Update media picker previews with uploaded file URLs
                        if (data.uploaded_files) {
                            Object.entries(data.uploaded_files).forEach(([fieldName, url]) => {
                                // Dispatch custom event for media picker to update
                                window.dispatchEvent(new CustomEvent('media-uploaded', {
                                    detail: { fieldName, url }
                                }));
                            });
                        }

                        setTimeout(() => {
                            this.saved = false;
                        }, 2000);
                    } else {
                        console.error('Save failed:', await response.text());
                        alert('Failed to save settings. Please try again.');
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('An error occurred while saving.');
                }

                this.saving = false;
            }
        }
    }
</script>
