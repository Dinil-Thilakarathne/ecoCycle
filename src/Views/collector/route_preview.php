<?php
$originLat = isset($originLat) ? trim((string) $originLat) : '';
$originLng = isset($originLng) ? trim((string) $originLng) : '';
$destinationLat = isset($destinationLat) ? trim((string) $destinationLat) : '';
$destinationLng = isset($destinationLng) ? trim((string) $destinationLng) : '';
$destinationLabel = isset($destinationLabel) ? trim((string) $destinationLabel) : 'Pickup destination';
$hasOrigin = $originLat !== '' && $originLng !== '';
$hasDestination = $destinationLat !== '' && $destinationLng !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Preview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --panel: #ffffff;
            --text: #102a43;
            --muted: #627d98;
            --line: #d9e2ec;
            --accent: #0f766e;
            --accent-soft: #ecfeff;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .route-shell {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 14px;
            box-sizing: border-box;
            gap: 12px;
        }
        .route-header {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            box-shadow: 0 8px 24px rgba(16, 42, 67, 0.06);
        }
        .route-title {
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: 700;
        }
        .route-subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }
        .route-status {
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
        }
        .route-map {
            flex: 1;
            min-height: 360px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: 0 8px 24px rgba(16, 42, 67, 0.06);
            background: #dbeafe;
        }
        .route-message {
            display: none;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fdba74;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            line-height: 1.45;
        }
    </style>
</head>
<body>
    <div class="route-shell">
        <div class="route-header">
            <h1 class="route-title">Route Preview</h1>
            <p class="route-subtitle"><?php echo htmlspecialchars($destinationLabel, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="route-status" id="routeStatus">Loading map...</div>
        </div>
        <div id="routeMessage" class="route-message"></div>
        <div id="routeMap" class="route-map"></div>
    </div>

    <script>
        (function () {
            const hasOrigin = <?php echo $hasOrigin ? 'true' : 'false'; ?>;
            const hasDestination = <?php echo $hasDestination ? 'true' : 'false'; ?>;
            const destinationLabel = <?php echo json_encode($destinationLabel, JSON_UNESCAPED_UNICODE); ?>;
            const origin = hasOrigin ? {
                lat: parseFloat(<?php echo json_encode($originLat, JSON_UNESCAPED_UNICODE); ?>),
                lng: parseFloat(<?php echo json_encode($originLng, JSON_UNESCAPED_UNICODE); ?>)
            } : null;
            const destination = hasDestination ? {
                lat: parseFloat(<?php echo json_encode($destinationLat, JSON_UNESCAPED_UNICODE); ?>),
                lng: parseFloat(<?php echo json_encode($destinationLng, JSON_UNESCAPED_UNICODE); ?>),
                label: destinationLabel
            } : null;

            const statusEl = document.getElementById('routeStatus');
            const messageEl = document.getElementById('routeMessage');
            const mapEl = document.getElementById('routeMap');

            function showMessage(message) {
                messageEl.textContent = message;
                messageEl.style.display = 'block';
            }

            function setStatus(text) {
                statusEl.textContent = text;
            }

            function buildExternalDirectionsUrl(currentOrigin, currentDestination) {
                const params = new URLSearchParams();
                if (currentOrigin && Number.isFinite(currentOrigin.lat) && Number.isFinite(currentOrigin.lng)) {
                    params.set('origin', `${currentOrigin.lat},${currentOrigin.lng}`);
                }
                if (currentDestination && Number.isFinite(currentDestination.lat) && Number.isFinite(currentDestination.lng)) {
                    params.set('destination', `${currentDestination.lat},${currentDestination.lng}`);
                } else if (destinationLabel) {
                    params.set('destination', destinationLabel);
                }
                params.set('travelmode', 'driving');
                return `https://www.google.com/maps/dir/?api=1&${params.toString()}`;
            }

            function buildGeocodeCandidates(label) {
                const normalized = String(label || '').replace(/\s+/g, ' ').trim();
                if (!normalized) {
                    return [];
                }

                const candidates = [normalized];
                const parts = normalized.split(',').map((part) => part.trim()).filter(Boolean);

                if (parts.length > 1) {
                    const tail = parts.slice(1).join(', ').trim();
                    if (tail && !candidates.includes(tail)) {
                        candidates.push(tail);
                    }
                }

                const cleaned = normalized
                    .replace(/\b(no\.?|number|#|flat|apt|unit)\s*\d+[a-zA-Z-]?\b/gi, ' ')
                    .replace(/\b\d{4,6}\b/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();

                if (cleaned && !candidates.includes(cleaned)) {
                    candidates.push(cleaned);
                }

                const cityKeywords = /(city|town|district|province|municipality|metropolitan)\b/i;
                const cityPart = parts.find((part) => cityKeywords.test(part));
                if (cityPart && !candidates.includes(cityPart)) {
                    candidates.push(cityPart);
                }

                if (parts.length > 0) {
                    const cityTail = parts[parts.length - 1];
                    if (cityTail && cityTail.length >= 3 && !candidates.includes(cityTail)) {
                        candidates.push(cityTail);
                    }
                }

                return candidates;
            }

            async function geocodeApproximate(label) {
                const candidates = buildGeocodeCandidates(label);

                for (const candidate of candidates) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(candidate)}`);
                        if (!res.ok) continue;
                        const rows = await res.json();
                        if (Array.isArray(rows) && rows.length > 0) {
                            return {
                                lat: parseFloat(rows[0].lat),
                                lng: parseFloat(rows[0].lon),
                                label: `${candidate} (approx.)`,
                                approximate: true
                            };
                        }
                    } catch (_) {
                        // continue trying other candidates
                    }
                }

                return null;
            }

            async function getOrigin() {
                if (origin && Number.isFinite(origin.lat) && Number.isFinite(origin.lng)) {
                    return origin;
                }

                if (!navigator.geolocation) {
                    throw new Error('Geolocation is not supported in this browser.');
                }

                return new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => resolve({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        }),
                        () => reject(new Error('Current location is unavailable.')),
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
                    );
                });
            }

            async function loadRoute() {
                if (!window.L) {
                    showMessage('Map library failed to load.');
                    setStatus('Map unavailable');
                    return;
                }

                let map = L.map(mapEl, { zoomControl: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                let target = destination;
                if (!target && destinationLabel) {
                    setStatus('Finding closest match...');
                    target = await geocodeApproximate(destinationLabel);
                }

                if (!target) {
                    showMessage('Unable to resolve a destination point. Showing the general area only.');
                    setStatus('Destination not exact');
                    map.setView([6.9271, 79.8612], 12);
                    return;
                }

                let currentOrigin = null;
                try {
                    currentOrigin = await getOrigin();
                } catch (error) {
                    showMessage('Current location is unavailable, so the map is centered on the destination.');
                    setStatus('Destination only');
                }

                const points = [];
                const targetMarker = L.marker([target.lat, target.lng]).addTo(map).bindPopup(target.label || destinationLabel || 'Pickup destination');
                points.push([target.lat, target.lng]);

                if (currentOrigin) {
                    const originMarker = L.marker([currentOrigin.lat, currentOrigin.lng]).addTo(map).bindPopup('Your location');
                    points.unshift([currentOrigin.lat, currentOrigin.lng]);

                    try {
                        const routeUrl = `https://router.project-osrm.org/route/v1/driving/${currentOrigin.lng},${currentOrigin.lat};${target.lng},${target.lat}?overview=full&geometries=geojson`;
                        const routeRes = await fetch(routeUrl);
                        if (!routeRes.ok) throw new Error('Route service unavailable');
                        const routeJson = await routeRes.json();
                        const route = routeJson?.routes?.[0];
                        if (route?.geometry?.coordinates?.length) {
                            const polyline = L.polyline(route.geometry.coordinates.map((p) => [p[1], p[0]]), {
                                color: '#0f766e',
                                weight: 5,
                                opacity: 0.9
                            }).addTo(map);
                            map.fitBounds(polyline.getBounds(), { padding: [20, 20] });
                            setStatus(`Approx ${((route.distance || 0) / 1000).toFixed(1)} km • ${Math.ceil((route.duration || 0) / 60)} min drive`);
                            return;
                        }
                    } catch (error) {
                        showMessage('Exact route could not be loaded. Showing the destination area instead.');
                    }
                }

                const bounds = L.latLngBounds(points);
                map.fitBounds(bounds, { padding: [20, 20] });
                setStatus('Approximate destination');
            }

            loadRoute().catch((error) => {
                showMessage(error.message || 'Failed to load route preview.');
                setStatus('Map unavailable');
            });
        })();
    </script>
</body>
</html>
