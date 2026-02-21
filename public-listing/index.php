<?php
/**
 * Property Listing Page
 * 
 * Standalone PHP page for displaying property listings from EspoCRM.
 * Designed for PHP 8.1+ on a LAMP stack.
 * 
 * Usage: index.php?id=PROPERTY_ID
 * 
 * Configuration: Update the constants below or use environment variables.
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$ESPOCRM_URL = getenv('ESPOCRM_URL') ?: '';
$ESPOCRM_API_KEY = getenv('ESPOCRM_API_KEY') ?: '';

if (empty($ESPOCRM_URL) || empty($ESPOCRM_API_KEY)) {
    http_response_code(500);
    die('<!DOCTYPE html><html><head><title>Configuration Error</title></head><body style="font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;"><div style="text-align:center;max-width:400px;"><h1>Configuration Required</h1><p>EspoCRM URL and API Key must be configured. Set ESPOCRM_URL and ESPOCRM_API_KEY as environment variables or create a config.php file.</p></div></body></html>');
}

function fetchFromEspo(string $path, string $baseUrl, string $apiKey): array|false {
    $url = rtrim($baseUrl, '/') . '/api/v1/' . ltrim($path, '/');
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $response === false) {
        return false;
    }
    
    return json_decode($response, true) ?: false;
}

// Handle image proxy requests
if (isset($_GET['attachment'])) {
    $attachmentId = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['attachment']);
    $url = rtrim($ESPOCRM_URL, '/') . '/api/v1/Attachment/file/' . $attachmentId;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $ESPOCRM_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg';
    curl_close($ch);
    
    if ($httpCode === 200 && $response !== false) {
        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=86400');
        echo $response;
    } else {
        http_response_code(404);
        echo 'Image not found';
    }
    exit;
}

// Get property ID from URL
$propertyId = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id']) : null;

$property = null;
$error = null;

if ($propertyId) {
    $data = fetchFromEspo('CUnits/' . $propertyId, $ESPOCRM_URL, $ESPOCRM_API_KEY);
    if ($data === false) {
        $error = 'The property you are looking for could not be found.';
    } else {
        $property = $data;
    }
} else {
    $error = 'No property ID provided. Please add ?id=YOUR_PROPERTY_ID to the URL.';
}

$pageTitle = $property ? htmlspecialchars($property['name'] ?? 'Property Listing') : 'Property Not Found';
$photos = $property['propertyPhotosIds'] ?? [];
$photoNames = $property['propertyPhotosNames'] ?? [];

function getAddress(array $property): string {
    return implode(', ', array_filter([
        $property['addressStreet'] ?? '',
        $property['addressCity'] ?? '',
        $property['addressState'] ?? '',
        $property['addressPostalCode'] ?? '',
    ]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Placement Findr Property Listing</title>
    <meta name="description" content="<?= $property ? htmlspecialchars(getAddress($property)) : 'Property listing' ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($photos)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>&attachment=<?= htmlspecialchars($photos[0]) ?>">
    <?php endif; ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg: #ffffff;
            --bg-card: #f8f8f8;
            --fg: #1a1a1a;
            --fg-secondary: #666666;
            --fg-tertiary: #999999;
            --border: #e8e8e8;
            --primary: #4A6492;
            --primary-light: rgba(74, 100, 146, 0.1);
            --radius: 0.375rem;
            --shadow: 0 1px 3px rgba(0,0,0,0.06);
            --font: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #141414;
                --bg-card: #1a1a1a;
                --fg: #f2f2f2;
                --fg-secondary: #b3b3b3;
                --fg-tertiary: #808080;
                --border: #2d2d2d;
                --primary: #7A94C2;
                --primary-light: rgba(74, 100, 146, 0.15);
                --shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
        }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--fg);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header */
        .header {
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: blur(8px);
        }
        .header .container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .header svg { color: var(--primary); }
        .header-title { font-weight: 600; font-size: 1.125rem; }

        /* Property Title */
        .property-header { margin-bottom: 1.5rem; }
        .property-name {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .property-type-badge {
            display: inline-block;
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 0.125rem 0.625rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 500;
            vertical-align: middle;
            margin-left: 0.5rem;
        }
        .address-line {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--fg-secondary);
            font-size: 0.875rem;
            margin-top: 0.375rem;
        }
        .address-line svg { flex-shrink: 0; }

        /* Image Gallery */
        .gallery { margin-bottom: 2rem; }
        .main-image-wrap {
            position: relative;
            aspect-ratio: 16/9;
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            background: var(--bg-card);
        }
        .main-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s;
        }
        .main-image-wrap:hover img { transform: scale(1.02); }
        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.85);
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 1.25rem;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        @media (prefers-color-scheme: dark) {
            .gallery-nav { background: rgba(0,0,0,0.7); color: #eee; }
        }
        .main-image-wrap:hover .gallery-nav { opacity: 1; }
        .gallery-nav.prev { left: 0.75rem; }
        .gallery-nav.next { right: 0.75rem; }
        .image-counter {
            position: absolute;
            bottom: 0.75rem;
            right: 0.75rem;
            background: rgba(0,0,0,0.6);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .main-image-wrap:hover .image-counter { opacity: 1; }

        .thumbnails {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 0.75rem 0 0.25rem;
            scrollbar-width: thin;
        }
        .thumbnail {
            flex-shrink: 0;
            width: 80px;
            height: 56px;
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            opacity: 0.6;
            transition: all 0.2s;
        }
        .thumbnail:hover { opacity: 1; }
        .thumbnail.active {
            border-color: var(--primary);
            opacity: 1;
            box-shadow: 0 0 0 2px var(--bg), 0 0 0 4px var(--primary);
        }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 640px) {
            .details-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
        }
        .detail-card-inner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .detail-label { font-size: 0.8rem; color: var(--fg-secondary); }
        .detail-value { font-weight: 600; }

        /* Sections */
        .section { margin-bottom: 2rem; }
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
        }
        .info-card-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        /* Footer */
        .footer {
            border-top: 1px solid var(--border);
            padding: 1.5rem 0;
            margin-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--fg-tertiary);
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0,0,0,0.92);
            align-items: center;
            justify-content: center;
        }
        .lightbox.open { display: flex; }
        .lightbox img {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: var(--radius);
        }
        .lightbox-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 40px;
            height: 40px;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.1); }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,0.1); }
        .lightbox-nav.prev { left: 1rem; }
        .lightbox-nav.next { right: 1rem; }
        .lightbox-counter {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.6);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
        }

        /* Error / Empty States */
        .error-state {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 400px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
        }
        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .error-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .error-message { font-size: 0.875rem; color: var(--fg-secondary); }

        /* No photos state */
        .no-photos {
            aspect-ratio: 16/9;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.5rem;
            color: var(--fg-tertiary);
        }

        @media (max-width: 640px) {
            .property-name { font-size: 1.375rem; }
            .container { padding: 0 0.75rem; }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php if ($error): ?>
    <div class="header">
        <div class="container">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span class="header-title">Placement Findr Property Listing</span>
        </div>
    </div>
    <div class="error-state">
        <div class="error-card">
            <div class="error-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="error-title"><?= $propertyId ? 'Property Not Found' : 'No Property Selected' ?></div>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
<?php else: ?>

    <div class="header">
        <div class="container">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span class="header-title">Placement Findr Property Listing</span>
        </div>
    </div>

    <main style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="container">

            <!-- Property Header -->
            <div class="property-header">
                <div>
                    <span class="property-name"><?= htmlspecialchars($property['name'] ?? '') ?></span>
                    <?php if (!empty($property['propertyType'])): ?>
                        <span class="property-type-badge"><?= htmlspecialchars($property['propertyType']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="address-line">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span><?= htmlspecialchars(getAddress($property)) ?></span>
                </div>
            </div>

            <!-- Image Gallery -->
            <div class="gallery">
                <?php if (count($photos) > 0): ?>
                    <div class="main-image-wrap" id="mainImageWrap" onclick="openLightbox()">
                        <img id="mainImage" src="?attachment=<?= htmlspecialchars($photos[0]) ?>" alt="<?= htmlspecialchars($photoNames[$photos[0]] ?? $property['name'] ?? 'Property photo') ?>">
                        <?php if (count($photos) > 1): ?>
                            <button class="gallery-nav prev" onclick="event.stopPropagation(); changeImage(-1)">&#8249;</button>
                            <button class="gallery-nav next" onclick="event.stopPropagation(); changeImage(1)">&#8250;</button>
                        <?php endif; ?>
                        <div class="image-counter" id="imageCounter">1 / <?= count($photos) ?></div>
                    </div>

                    <?php if (count($photos) > 1): ?>
                        <div class="thumbnails" id="thumbnails">
                            <?php foreach ($photos as $i => $photoId): ?>
                                <div class="thumbnail <?= $i === 0 ? 'active' : '' ?>" onclick="setImage(<?= $i ?>)" data-index="<?= $i ?>">
                                    <img src="?attachment=<?= htmlspecialchars($photoId) ?>" alt="<?= htmlspecialchars($photoNames[$photoId] ?? 'Photo ' . ($i + 1)) ?>" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.4"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>No photos available</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details Grid -->
            <div class="details-grid">
                <div class="detail-card">
                    <div class="detail-card-inner">
                        <div class="detail-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>
                        </div>
                        <div>
                            <div class="detail-label">Bedrooms</div>
                            <div class="detail-value"><?= htmlspecialchars($property['bedrooms'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="detail-card-inner">
                        <div class="detail-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1z"/><path d="M6 12V5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v7"/></svg>
                        </div>
                        <div>
                            <div class="detail-label">Bathrooms</div>
                            <div class="detail-value"><?= htmlspecialchars($property['bathrooms'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="detail-card-inner">
                        <div class="detail-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"/><path d="M2 11v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H6v-2a2 2 0 0 0-4 0z"/><path d="M4 18v2"/><path d="M20 18v2"/></svg>
                        </div>
                        <div>
                            <div class="detail-label">Receptions</div>
                            <div class="detail-value"><?= htmlspecialchars($property['receptionRooms'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="detail-card-inner">
                        <div class="detail-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C1.4 11.3 1 12.1 1 13v3c0 .6.4 1 1 1h1"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                        </div>
                        <div>
                            <div class="detail-label">Parking</div>
                            <div class="detail-value"><?= htmlspecialchars($property['parking'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($property['description'])): ?>
            <div class="section">
                <h2 class="section-title">About this property</h2>
                <div style="color: var(--fg-secondary); font-size: 0.9375rem;">
                    <?= nl2br(htmlspecialchars($property['description'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Location -->
            <div class="section">
                <h2 class="section-title">Location</h2>
                <div class="info-card">
                    <div class="info-card-row">
                        <div class="detail-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <?php if (!empty($property['addressStreet'])): ?>
                                <div style="font-weight: 500;"><?= htmlspecialchars($property['addressStreet']) ?></div>
                            <?php endif; ?>
                            <div style="font-size: 0.875rem; color: var(--fg-secondary);">
                                <?= htmlspecialchars(implode(', ', array_filter([$property['addressCity'] ?? '', $property['addressState'] ?? '']))) ?>
                            </div>
                            <?php if (!empty($property['addressPostalCode'])): ?>
                                <div style="font-size: 0.875rem; color: var(--fg-secondary);"><?= htmlspecialchars($property['addressPostalCode']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </main>

    <div class="footer">
        <div class="container">
            Property listing provided by <a href="https://propertypipeline.co.uk/" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline;">PropertyPipeline</a>
        </div>
    </div>

    <!-- Lightbox -->
    <?php if (count($photos) > 0): ?>
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <?php if (count($photos) > 1): ?>
            <button class="lightbox-nav prev" onclick="event.stopPropagation(); changeImage(-1)">&#8249;</button>
            <button class="lightbox-nav next" onclick="event.stopPropagation(); changeImage(1)">&#8250;</button>
        <?php endif; ?>
        <img id="lightboxImage" src="" alt="Property photo">
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>
    <?php endif; ?>

    <script>
        const photos = <?= json_encode($photos) ?>;
        const photoNames = <?= json_encode($photoNames) ?>;
        let currentIndex = 0;

        function setImage(index) {
            currentIndex = index;
            const mainImg = document.getElementById('mainImage');
            mainImg.src = '?attachment=' + photos[index];
            mainImg.alt = photoNames[photos[index]] || 'Property photo';
            
            document.getElementById('imageCounter').textContent = (index + 1) + ' / ' + photos.length;

            document.querySelectorAll('.thumbnail').forEach(function(thumb, i) {
                thumb.classList.toggle('active', i === index);
            });

            if (document.getElementById('lightbox').classList.contains('open')) {
                const lbImg = document.getElementById('lightboxImage');
                lbImg.src = '?attachment=' + photos[index];
                lbImg.alt = photoNames[photos[index]] || 'Property photo';
                document.getElementById('lightboxCounter').textContent = (index + 1) + ' / ' + photos.length;
            }
        }

        function changeImage(direction) {
            let newIndex = currentIndex + direction;
            if (newIndex < 0) newIndex = photos.length - 1;
            if (newIndex >= photos.length) newIndex = 0;
            setImage(newIndex);
        }

        function openLightbox() {
            const lightbox = document.getElementById('lightbox');
            const lbImg = document.getElementById('lightboxImage');
            lbImg.src = '?attachment=' + photos[currentIndex];
            lbImg.alt = photoNames[photos[currentIndex]] || 'Property photo';
            document.getElementById('lightboxCounter').textContent = (currentIndex + 1) + ' / ' + photos.length;
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('lightbox')?.addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') changeImage(-1);
            if (e.key === 'ArrowRight') changeImage(1);
        });
    </script>

<?php endif; ?>
</body>
</html>
