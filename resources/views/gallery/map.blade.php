<x-layouts.public>
    <x-slot name="title">Photo Map - {{ config('app.name', 'Photography Portfolio') }}</x-slot>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        #world-map {
            height: calc(100vh - 64px);
            width: 100%;
        }
        .leaflet-popup-content {
            margin: 0;
        }
        .photo-popup {
            width: 200px;
        }
        .photo-popup img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px 4px 0 0;
        }
        .photo-popup-content {
            padding: 10px;
        }
        .photo-popup-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .photo-popup-location {
            font-size: 12px;
            color: #6b7280;
        }
        .photo-popup-link {
            display: inline-block;
            margin-top: 8px;
            font-size: 12px;
            color: #3b82f6;
            text-decoration: none;
        }
        .photo-popup-link:hover {
            text-decoration: underline;
        }
        .marker-cluster-small {
            background-color: rgba(110, 204, 57, 0.6);
        }
        .marker-cluster-small div {
            background-color: rgba(110, 204, 57, 0.8);
        }
        .marker-cluster-medium {
            background-color: rgba(240, 194, 12, 0.6);
        }
        .marker-cluster-medium div {
            background-color: rgba(240, 194, 12, 0.8);
        }
        .marker-cluster-large {
            background-color: rgba(241, 128, 23, 0.6);
        }
        .marker-cluster-large div {
            background-color: rgba(241, 128, 23, 0.8);
        }
    </style>

    <div class="relative">
        <!-- Map Container -->
        <div id="world-map"></div>

        <!-- Back Button Overlay -->
        <div class="absolute top-4 left-4 z-[1000]">
            <a href="{{ route('photos.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-800 rounded-lg shadow-lg hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Gallery
            </a>
        </div>

        <!-- Photo Count Overlay -->
        <div class="absolute top-4 right-4 z-[1000]">
            <div class="bg-white px-4 py-2 rounded-lg shadow-lg">
                <span class="text-gray-800 font-medium">{{ $photos->count() }} photos with location</span>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Leaflet MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map centered on world view
            const map = L.map('world-map').setView([20, 0], 2);

            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Photos data
            const photos = @json($photos);

            // Create marker cluster group
            const markers = L.markerClusterGroup({
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 50
            });

            // Add markers for each photo
            const bounds = [];
            photos.forEach(photo => {
                const lat = parseFloat(photo.latitude);
                const lng = parseFloat(photo.longitude);

                if (lat && lng) {
                    bounds.push([lat, lng]);

                    const popupContent = `
                        <div class="photo-popup">
                            <img src="/storage/${photo.thumbnail_path}" alt="${photo.title}" loading="lazy">
                            <div class="photo-popup-content">
                                <div class="photo-popup-title">${photo.title}</div>
                                ${photo.location_name ? `<div class="photo-popup-location">${photo.location_name}</div>` : ''}
                                <a href="/photo/${photo.slug}" class="photo-popup-link">View Photo →</a>
                            </div>
                        </div>
                    `;

                    const marker = L.marker([lat, lng])
                        .bindPopup(popupContent, {
                            minWidth: 200,
                            maxWidth: 200,
                            className: 'photo-marker-popup'
                        });

                    markers.addLayer(marker);
                }
            });

            map.addLayer(markers);

            // Fit map to show all markers
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        });
    </script>
</x-layouts.public>
