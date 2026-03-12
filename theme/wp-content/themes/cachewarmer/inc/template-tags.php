<?php
/**
 * CacheWarmer Template Tags
 * Reusable component helper functions.
 */

/**
 * Render an inline SVG icon.
 */
function cachewarmer_icon($name, $class = '', $size = 24) {
    $icons = [
        'globe' => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10A15.3 15.3 0 0 1 12 2z"/>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'twitter' => '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'server' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'x-mark' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'close' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'github' => '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.4 5.4 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65S8.93 17.38 9 18v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>',
        'docker' => '<path d="M22 12.5c-.5-1.5-2-2.5-2-2.5s.5-.5 0-1.5c-.3-.6-1-1-1-1s0-1.5-1-2h-2V4h-2V3h-2v2H9V3H7v2.5H5c-1 .5-1 2-1 2s-.7.4-1 1c-.5 1 0 1.5 0 1.5S1 11 1 12.5c0 3 3.5 4.5 7.5 4.5h7C19.5 17 22 15.5 22 12.5zM7 13H5v-2h2v2zm3 0H8v-2h2v2zm3 0h-2v-2h2v2zm3 0h-2v-2h2v2z"/>',
        'terminal' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'key' => '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'book' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'refresh' => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'alert-triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'flame' => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
        'sitemap' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'queue' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'wordpress' => '<path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zM3.5 12c0-.97.17-1.9.47-2.77L7.83 20.1A8.51 8.51 0 0 1 3.5 12zm8.5 8.5c-.83 0-1.63-.12-2.39-.34l2.54-7.37 2.6 7.12c.02.04.03.08.05.12-.88.3-1.82.47-2.8.47zm1.1-12.47c.51-.03.97-.08.97-.08.46-.05.4-.73-.05-.7 0 0-1.37.11-2.26.11-.83 0-2.23-.11-2.23-.11-.46-.03-.51.68-.05.7 0 0 .44.06.89.08l1.33 3.63L9.9 18l-3.54-10.5c.51-.03.97-.08.97-.08.46-.06.4-.73-.05-.7 0 0-1.37.1-2.26.1-.16 0-.35 0-.54 0A8.48 8.48 0 0 1 12 3.5c2.13 0 4.07.78 5.56 2.07-.04 0-.07-.01-.1-.01-.83 0-1.42.73-1.42 1.51 0 .7.4 1.29.83 1.99.32.56.7 1.28.7 2.32 0 .72-.28 1.55-.64 2.71l-.84 2.82-3.05-9.07zm3.21 12.28l2.57-7.44c.48-1.2.64-2.16.64-3.01 0-.31-.02-.6-.06-.87A8.49 8.49 0 0 1 20.5 12a8.51 8.51 0 0 1-4.19 7.31z"/>',
        'drupal' => '<path d="M15.78 5.11C14.46 3.79 12.63 2 12 2c-.63 0-2.46 1.79-3.78 3.11C6.02 7.31 3 10.33 3 14.5 3 19.19 6.81 22 12 22s9-2.81 9-7.5c0-4.17-3.02-7.19-5.22-9.39zM12 20c-3.86 0-7-2.24-7-5.5 0-3.31 2.64-5.94 4.56-7.86.85-.85 1.73-1.73 2.44-2.64.71.91 1.59 1.79 2.44 2.64C16.36 8.56 19 11.19 19 14.5c0 3.26-3.14 5.5-7 5.5zm-2.5-4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm4-2a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>',
        'package' => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'rocket' => '<path d="M12 2L9.5 7 9.3 13h5.4l-.2-6z" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="1.3"/><path d="M9.3 11L7.5 13.5l1.8-.4"/><path d="M14.7 11l1.8 2.5-1.8-.4"/><rect x="5" y="15" width="14" height="2.5" rx="0.5"/><rect x="5" y="19" width="14" height="2.5" rx="0.5"/><circle cx="7.5" cy="16.2" r="0.3" fill="currentColor"/><circle cx="9.5" cy="16.2" r="0.3" fill="currentColor"/><circle cx="7.5" cy="20.2" r="0.3" fill="currentColor"/><circle cx="9.5" cy="20.2" r="0.3" fill="currentColor"/><path d="M11 13.2c0 0 .4 1.5 1 2 .6-.5 1-2 1-2" fill="currentColor" stroke="none"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>',
        'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'shopping-cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
        'pinterest' => '<path d="M12 2C6.477 2 2 6.477 2 12c0 4.237 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.035-2.868.182-.78 1.172-4.97 1.172-4.97s-.299-.6-.299-1.486c0-1.39.806-2.428 1.81-2.428.852 0 1.264.64 1.264 1.408 0 .858-.546 2.14-.828 3.33-.236.995.5 1.807 1.48 1.807 1.778 0 3.144-1.874 3.144-4.58 0-2.393-1.72-4.068-4.177-4.068-2.845 0-4.515 2.134-4.515 4.34 0 .859.331 1.781.745 2.282a.3.3 0 0 1 .069.288l-.278 1.133c-.044.183-.145.222-.335.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.965-.527-2.291-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.523 0 10-4.477 10-10S17.523 2 12 2z"/>',
        'cloudflare' => '<path d="M16.5 15.5l.6-2.1c.1-.4.1-.7-.1-.9-.2-.2-.5-.4-.8-.4l-8.7-.1c-.1 0-.1 0-.2-.1 0 0 0-.1.1-.2l.1-.1h8.8c1.1-.1 2.2-.9 2.6-2l.5-1.4c0-.1 0-.1 0-.2C18.6 5.4 16.5 3.5 14 3.5c-2.1 0-3.9 1.3-4.7 3.1-.5-.4-1.1-.6-1.8-.6-1.5 0-2.7 1.2-2.7 2.7v.2C3.2 9.3 2 10.7 2 12.4c0 1.8 1.4 3.2 3.2 3.2h11.1c.1 0 .2-.1.2-.1zM19.3 9c-.1 0-.3 0-.4 0l-.1.1-.2.6c-.3 1-.9 1.4-2 1.4h-1l-.4 1.4c-.1.4 0 .8.3 1 .1.1.4.2.6.2h.8c1.5.1 2.8-1 3.1-2.4.1-.4-.1-.8-.4-1-.2-.2-.4-.2-.6-.3h.3z"/>',
        'imperva' => '<path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5zm0 2.18L19 8.3v4.7c0 4.42-3.06 8.55-7 9.82-3.94-1.27-7-5.4-7-9.82V8.3L12 4.18zm0 3.32l-4 2.22v3.78c0 2.65 1.7 5.1 4 5.88 2.3-.78 4-3.23 4-5.88V9.72l-4-2.22z"/>',
        'akamai' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-1-13l-4 8h3v5l4-8h-3V7z"/>',
        'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
        'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="6" x2="9.01" y2="6"/><line x1="15" y1="6" x2="15.01" y2="6"/><line x1="9" y1="10" x2="9.01" y2="10"/><line x1="15" y1="10" x2="15.01" y2="10"/><line x1="9" y1="14" x2="9.01" y2="14"/><line x1="15" y1="14" x2="15.01" y2="14"/><line x1="9" y1="18" x2="15" y2="18"/>',
        'file-check' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'infinity' => '<path d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.74-8z"/>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
    ];

    if (!isset($icons[$name])) {
        return;
    }

    // WordPress and Drupal icons use fill instead of stroke
    $fill_icons = ['wordpress', 'drupal', 'pinterest', 'cloudflare', 'imperva', 'akamai'];
    $is_fill = in_array($name, $fill_icons, true);

    $class_attr = $class ? ' class="icon ' . esc_attr($class) . '"' : ' class="icon"';

    // SVG paths are hardcoded above - safe to output with targeted wp_kses
    $svg_kses = [
        'path'     => ['d' => [], 'fill' => [], 'stroke' => [], 'stroke-linejoin' => [], 'stroke-none' => []],
        'polygon'  => ['points' => []],
        'polyline' => ['points' => []],
        'circle'   => ['cx' => [], 'cy' => [], 'r' => [], 'fill' => []],
        'rect'     => ['x' => [], 'y' => [], 'width' => [], 'height' => [], 'rx' => [], 'ry' => []],
        'ellipse'  => ['cx' => [], 'cy' => [], 'rx' => [], 'ry' => []],
        'line'     => ['x1' => [], 'y1' => [], 'x2' => [], 'y2' => []],
    ];

    $s = (int) $size;
    if ($is_fill) {
        echo '<svg' . $class_attr . ' width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true" focusable="false">' . wp_kses($icons[$name], $svg_kses) . '</svg>';
    } else {
        echo '<svg' . $class_attr . ' width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . wp_kses($icons[$name], $svg_kses) . '</svg>';
    }
}

/**
 * Render breadcrumb navigation with schema.org markup.
 * Matches the PDFViewer breadcrumb pattern (Bootstrap-style classes, CSS separators).
 */
function cachewarmer_breadcrumb($page_title, $page_url = '') {
    $home_url = esc_url(home_url('/'));
    echo '<nav aria-label="breadcrumb">';
    echo '<div class="container">';
    echo '<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">';
    echo '<li class="breadcrumb-item" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">';
    echo '<a href="https://dross.net/media/" itemprop="item" title="Dross:Media - Web Development &amp; Digital Services" target="_blank" rel="noopener"><span itemprop="name">Dross:Media</span></a>';
    echo '<meta itemprop="position" content="1" />';
    echo '</li>';
    echo '<li class="breadcrumb-item" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">';
    echo '<a href="' . $home_url . '" itemprop="item" title="CacheWarmer - Cache Warming for WordPress, Drupal &amp; Node.js"><span itemprop="name">CacheWarmer</span></a>';
    echo '<meta itemprop="position" content="2" />';
    echo '</li>';
    echo '<li class="breadcrumb-item active" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">';
    echo '<span aria-current="page" itemprop="name" title="' . esc_attr($page_title) . ' - CacheWarmer">' . esc_html($page_title) . '</span>';
    echo '<meta itemprop="position" content="3" />';
    echo '</li>';
    echo '</ol>';
    echo '</div>';
    echo '</nav>';
}

/**
 * Render a feature card.
 */
function cachewarmer_card($title, $description, $icon = '', $link = '') {
    $tag = $link ? 'a' : 'div';
    $link_attrs = $link
        ? ' href="' . esc_url($link) . '" title="' . esc_attr($title) . ' - Learn How CacheWarmer Automates This" aria-label="Learn more about ' . esc_attr($title) . '"'
        : '';
    echo '<' . $tag . ' class="card card-feature"' . $link_attrs . '>';
    if ($icon) {
        echo '<div class="card-icon">';
        cachewarmer_icon($icon);
        echo '</div>';
    }
    echo '<h3 class="card-title">' . esc_html($title) . '</h3>';
    echo '<p class="card-description">' . esc_html($description) . '</p>';
    if ($link) {
        echo '<span class="card-link">Learn more ';
        cachewarmer_icon('arrow-right', '', 16);
        echo '</span>';
    }
    echo '</' . $tag . '>';
}

/**
 * Render a step card.
 */
function cachewarmer_step($number, $title, $description) {
    echo '<div class="step">';
    echo '<div class="step-number">' . (int)$number . '</div>';
    echo '<div class="step-content">';
    echo '<h3 class="step-title">' . esc_html($title) . '</h3>';
    echo '<p class="step-description">' . esc_html($description) . '</p>';
    echo '</div>';
    echo '</div>';
}

/**
 * Render an FAQ accordion item.
 */
function cachewarmer_faq($question, $answer, $slug = '') {
    $id_attr = $slug ? ' id="' . esc_attr($slug) . '"' : '';
    $title_attr = ' title="' . esc_attr($question) . '"';
    $aria_attr = ' aria-label="' . esc_attr($question) . '"';
    echo '<details class="faq-item"' . $id_attr . '>';
    echo '<summary class="faq-question"' . $title_attr . $aria_attr . '>';
    echo '<span>' . esc_html($question) . '</span>';
    cachewarmer_icon('chevron-down', 'faq-chevron', 20);
    echo '</summary>';
    echo '<div class="faq-answer">' . wp_kses_post($answer) . '</div>';
    echo '</details>';
}

/**
 * Render a code block.
 */
function cachewarmer_code_block($code, $language = '') {
    echo '<div class="code-block">';
    if ($language) {
        echo '<div class="code-block-header">';
        echo '<span class="code-block-lang">' . esc_html($language) . '</span>';
        echo '<button class="code-copy-btn" aria-label="Copy code">';
        cachewarmer_icon('copy', '', 16);
        echo '</button>';
        echo '</div>';
    }
    echo '<pre><code>' . esc_html($code) . '</code></pre>';
    echo '</div>';
}

/**
 * Render a callout box.
 */
function cachewarmer_callout($text, $type = 'info') {
    $icon = $type === 'warning' ? 'alert-triangle' : 'info';
    echo '<div class="callout callout-' . esc_attr($type) . '">';
    echo '<div class="callout-icon">';
    cachewarmer_icon($icon, '', 20);
    echo '</div>';
    echo '<div class="callout-content">' . wp_kses_post($text) . '</div>';
    echo '</div>';
}

/**
 * Render API endpoint documentation.
 */
function cachewarmer_api_endpoint($method, $path, $description, $request_body = '', $response = '') {
    $method_class = strtolower($method);
    echo '<div class="api-endpoint">';
    echo '<div class="api-endpoint-header">';
    echo '<span class="api-method api-method-' . esc_attr($method_class) . '">' . esc_html($method) . '</span>';
    echo '<code class="api-path">' . esc_html($path) . '</code>';
    echo '</div>';
    echo '<p class="api-description">' . esc_html($description) . '</p>';
    if ($request_body) {
        echo '<div class="api-section"><strong>Request Body</strong>';
        cachewarmer_code_block($request_body, 'JSON');
        echo '</div>';
    }
    if ($response) {
        echo '<div class="api-section"><strong>Response</strong>';
        cachewarmer_code_block($response, 'JSON');
        echo '</div>';
    }
    echo '</div>';
}
