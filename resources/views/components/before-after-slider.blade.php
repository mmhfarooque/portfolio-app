@props([
    'beforeImage',
    'afterImage',
    'beforeLabel' => 'Before',
    'afterLabel' => 'After',
    'height' => 'aspect-[16/10]'
])

<div x-data="beforeAfterSlider()"
     x-init="init()"
     class="relative select-none overflow-hidden rounded-lg bg-black {{ $height }}"
     @mousedown="startDrag($event)"
     @touchstart="startDrag($event)"
     @mousemove="drag($event)"
     @touchmove="drag($event)"
     @mouseup="stopDrag()"
     @touchend="stopDrag()"
     @mouseleave="stopDrag()">

    <!-- Before Image (underneath) -->
    <div class="absolute inset-0">
        <img src="{{ $beforeImage }}"
             alt="{{ $beforeLabel }}"
             class="w-full h-full object-contain"
             draggable="false">
    </div>

    <!-- After Image (clipped) -->
    <div class="absolute inset-0 overflow-hidden"
         :style="'clip-path: inset(0 0 0 ' + sliderPosition + '%)'">
        <img src="{{ $afterImage }}"
             alt="{{ $afterLabel }}"
             class="w-full h-full object-contain"
             draggable="false">
    </div>

    <!-- Slider Handle -->
    <div class="absolute top-0 bottom-0 w-1 bg-white shadow-lg cursor-ew-resize z-10"
         :style="'left: ' + sliderPosition + '%'"
         style="transform: translateX(-50%);">
        <!-- Handle Circle -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
            </svg>
        </div>
    </div>

    <!-- Labels -->
    <div class="absolute top-3 left-3 z-10">
        <span class="px-3 py-1 bg-black/70 text-white text-sm rounded-full">{{ $beforeLabel }}</span>
    </div>
    <div class="absolute top-3 right-3 z-10">
        <span class="px-3 py-1 bg-black/70 text-white text-sm rounded-full">{{ $afterLabel }}</span>
    </div>

    <!-- Instructions -->
    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10"
         x-show="!hasDragged"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <span class="px-3 py-1 bg-black/70 text-white text-xs rounded-full">Drag to compare</span>
    </div>
</div>

<script>
    function beforeAfterSlider() {
        return {
            sliderPosition: 50,
            isDragging: false,
            hasDragged: false,
            containerRect: null,

            init() {
                this.$nextTick(() => {
                    this.containerRect = this.$el.getBoundingClientRect();
                });
            },

            startDrag(event) {
                this.isDragging = true;
                this.hasDragged = true;
                this.containerRect = this.$el.getBoundingClientRect();
                this.updatePosition(event);
            },

            drag(event) {
                if (!this.isDragging) return;
                this.updatePosition(event);
            },

            stopDrag() {
                this.isDragging = false;
            },

            updatePosition(event) {
                if (!this.containerRect) return;

                let clientX;
                if (event.type.startsWith('touch')) {
                    clientX = event.touches[0].clientX;
                } else {
                    clientX = event.clientX;
                }

                const x = clientX - this.containerRect.left;
                const percentage = (x / this.containerRect.width) * 100;
                this.sliderPosition = Math.max(0, Math.min(100, percentage));
            }
        }
    }
</script>
