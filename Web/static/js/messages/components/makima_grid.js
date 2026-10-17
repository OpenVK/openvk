import { html } from './render.js';

function formatDuration(sec) {
    if (!sec || isNaN(sec)) return '';
    if (typeof window !== 'undefined' && typeof window.fmtTime === 'function') {
        return window.fmtTime(sec);
    }
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

/**
 * Extracts width, height and aspect ratio from a photo or video attachment.
 */
export function getMediaDimensions(att) {
    if (!att) return { width: 4, height: 3, ratio: 4 / 3 };

    if (att.type === 'photo') {
        const p = att.photo || {};
        let w = Number(p.width || p.orig_photo?.width);
        let h = Number(p.height || p.orig_photo?.height);

        if ((!w || !h || isNaN(w) || isNaN(h)) && Array.isArray(p.sizes) && p.sizes.length > 0) {
            for (let i = p.sizes.length - 1; i >= 0; i--) {
                const s = p.sizes[i];
                if (s && s.width && s.height && !isNaN(s.width) && !isNaN(s.height)) {
                    w = Number(s.width);
                    h = Number(s.height);
                    break;
                }
            }
        }

        if (!w || !h || isNaN(w) || isNaN(h) || w <= 0 || h <= 0) {
            w = 600;
            h = 450;
        }

        return { width: w, height: h, ratio: w / h };
    } else if (att.type === 'video') {
        const v = att.video?.video || att.video || {};
        let w = Number(v.width);
        let h = Number(v.height);

        if (!w || !h || isNaN(w) || isNaN(h) || w <= 0 || h <= 0) {
            w = 640;
            h = 360;
        }

        return { width: w, height: h, ratio: w / h };
    }

    return { width: 4, height: 3, ratio: 4 / 3 };
}

/**
 * Computes Makima mosaic layout for message attachments.
 * Takes into account dialog window dimensions (FastChats vs Standard Chat vs Mobile).
 * 
 * @param {Array} visualItems - Array of { type: 'photo'|'video', photo/video, ... }
 * @param {Object} options - { isFastchat, depth, maxWidth, maxHeight }
 * @returns {Object} Layout object with gridWidth, gridHeight, and layoutRows or columns
 */
export function computeMakimaGrid(visualItems, options = {}) {
    const count = visualItems.length;
    if (count === 0) return null;

    const isFastchat = Boolean(options.isFastchat ?? window.im?.state?.isFastchat);
    const depth = options.depth || 0;

    let maxW = options.maxWidth;
    if (!maxW) {
        maxW = isFastchat ? 205 : 380;
        if (depth > 0) {
            maxW = Math.max(180, maxW - depth * 20);
        }
    }

    let maxH = options.maxHeight;
    if (!maxH) {
        maxH = isFastchat ? 230 : 350;
        if (depth > 0) {
            maxH = Math.max(180, maxH - depth * 15);
        }
    }

    const items = visualItems.map(item => ({
        att: item,
        dims: getMediaDimensions(item)
    }));

    // ── 1 Item ──────────────────────────────────────────────
    if (count === 1) {
        const { width, height, ratio } = items[0].dims;
        const clampedRatio = Math.max(0.45, Math.min(2.4, ratio));
        let w, h;

        if (clampedRatio >= 1) {
            w = Math.min(width, maxW);
            h = Math.round(w / clampedRatio);
            if (h > maxH) {
                h = maxH;
                w = Math.round(h * clampedRatio);
            }
        } else {
            h = Math.min(height, maxH);
            w = Math.round(h * clampedRatio);
            if (w > maxW) {
                w = maxW;
                h = Math.round(w / clampedRatio);
            }
        }

        w = Math.max(isFastchat ? 60 : 90, Math.min(maxW, w));
        h = Math.max(isFastchat ? 60 : 90, Math.min(maxH, h));

        return {
            type: 'single',
            gridWidth: w,
            gridHeight: h,
            ratio: clampedRatio,
            tile: { item: items[0].att, width: w, height: h, ratio: clampedRatio }
        };
    }

    // ── 2 Items ─────────────────────────────────────────────
    if (count === 2) {
        const r0 = items[0].dims.ratio;
        const r1 = items[1].dims.ratio;

        // If both are very wide panoramas, stack them vertically
        if (r0 >= 1.6 && r1 >= 1.6) {
            const h0 = Math.round(Math.min(maxH * 0.48, maxW / r0));
            const h1 = Math.round(Math.min(maxH * 0.48, maxW / r1));
            return {
                type: 'standard',
                gridWidth: maxW,
                gridHeight: h0 + h1 + 2,
                rows: [
                    { height: h0, ratio: maxW / h0, tiles: [{ item: items[0].att, flex: 1, height: h0 }] },
                    { height: h1, ratio: maxW / h1, tiles: [{ item: items[1].att, flex: 1, height: h1 }] }
                ]
            };
        }

        // Default: side by side
        const avgR = (r0 + r1) / 2;
        let rowH = Math.round((maxW / 2) / avgR);
        rowH = Math.max(isFastchat ? 70 : 110, Math.min(maxH * 0.75, rowH));

        return {
            type: 'standard',
            gridWidth: maxW,
            gridHeight: rowH,
            rows: [
                {
                    height: rowH,
                    ratio: maxW / rowH,
                    tiles: [
                        { item: items[0].att, flex: 1, height: rowH },
                        { item: items[1].att, flex: 1, height: rowH }
                    ]
                }
            ]
        };
    }

    // ── 3 Items ─────────────────────────────────────────────
    if (count === 3) {
        const r0 = items[0].dims.ratio;
        const r1 = items[1].dims.ratio;
        const r2 = items[2].dims.ratio;

        // If all 3 are wide photos: 1 on top (100%), 2 below (50% each)
        if (r0 >= 1.2 && r1 >= 1.2 && r2 >= 1.2) {
            const hTop = Math.round(Math.min(maxH * 0.52, maxW / r0));
            const hBottom = Math.round(Math.min(maxH - hTop - 2, (maxW / 2) / ((r1 + r2) / 2)));
            const safeBottom = Math.max(isFastchat ? 60 : 90, hBottom);

            return {
                type: 'standard',
                gridWidth: maxW,
                gridHeight: hTop + safeBottom + 2,
                rows: [
                    { height: hTop, ratio: maxW / hTop, tiles: [{ item: items[0].att, flex: 1, height: hTop }] },
                    {
                        height: safeBottom,
                        ratio: maxW / safeBottom,
                        tiles: [
                            { item: items[1].att, flex: 1, height: safeBottom },
                            { item: items[2].att, flex: 1, height: safeBottom }
                        ]
                    }
                ]
            };
        }

        // Classic Makima 3-photo: 1 dominant on left, 2 stacked on right
        const totalH = Math.round(Math.min(maxH, Math.max(isFastchat ? 120 : 180, maxW * 0.65)));
        const hSub = Math.round((totalH - 2) / 2);

        return {
            type: 'split_left',
            gridWidth: maxW,
            gridHeight: totalH,
            leftTile: { item: items[0].att, flex: 1.35, height: totalH },
            rightTiles: [
                { item: items[1].att, flex: 1, height: hSub },
                { item: items[2].att, flex: 1, height: hSub }
            ]
        };
    }

    // ── 4 Items ─────────────────────────────────────────────
    if (count === 4) {
        // 2x2 grid
        const rowH = Math.round(Math.min(maxH * 0.48, Math.max(isFastchat ? 65 : 95, (maxW / 2) * 0.72)));
        return {
            type: 'standard',
            gridWidth: maxW,
            gridHeight: rowH * 2 + 2,
            rows: [
                {
                    height: rowH,
                    ratio: maxW / rowH,
                    tiles: [
                        { item: items[0].att, flex: 1, height: rowH },
                        { item: items[1].att, flex: 1, height: rowH }
                    ]
                },
                {
                    height: rowH,
                    ratio: maxW / rowH,
                    tiles: [
                        { item: items[2].att, flex: 1, height: rowH },
                        { item: items[3].att, flex: 1, height: rowH }
                    ]
                }
            ]
        };
    }

    // ── 5 to 10 Items ────────────────────────────────────────
    let rowCounts = [];
    if (count === 5) rowCounts = [2, 3];
    else if (count === 6) rowCounts = [3, 3];
    else if (count === 7) rowCounts = [3, 2, 2];
    else if (count === 8) rowCounts = [2, 3, 3];
    else if (count === 9) rowCounts = [3, 3, 3];
    else rowCounts = [3, 3, 4]; // 10

    const numRows = rowCounts.length;
    const targetRowH = Math.round(Math.min(maxH * 0.45, Math.max(isFastchat ? 55 : 85, (maxH - (numRows - 1) * 2) / numRows)));

    let itemIdx = 0;
    const rows = [];
    let totalH = 0;

    for (let r = 0; r < numRows; r++) {
        const cInRow = rowCounts[r];
        const rowTiles = [];
        for (let c = 0; c < cInRow && itemIdx < count; c++) {
            rowTiles.push({
                item: items[itemIdx++].att,
                flex: 1,
                height: targetRowH
            });
        }
        rows.push({ height: targetRowH, ratio: maxW / targetRowH, tiles: rowTiles });
        totalH += targetRowH;
    }
    totalH += (numRows - 1) * 2;

    return {
        type: 'standard',
        gridWidth: maxW,
        gridHeight: totalH,
        rows: rows
    };
}

/**
 * Single media tile with gray placeholder background and loaded transition.
 */
export const MediaTile = ({ item, msg, width, height, flex, ratio, style = '' }) => {
    if (!item) return null;

    const isPhoto = item.type === 'photo';
    const isVideo = item.type === 'video';

    let mediaSrc = '';
    let linkHref = '#';
    let altText = '';

    if (isPhoto) {
        const p = item.photo || {};
        mediaSrc = p.photo_604 || p.photo_807 || p.photo_320 || p.photo_130 || p.photo_75 || '';
        if (!mediaSrc && Array.isArray(p.sizes) && p.sizes.length > 0) {
            const preferred = p.sizes.find(s => s.type === 'x') || p.sizes.find(s => s.type === 'm') || p.sizes[p.sizes.length - 1];
            mediaSrc = preferred?.url || '';
        }
        if (!mediaSrc && p.url) {
            mediaSrc = p.url;
        }
        if (!mediaSrc && p.link) {
            mediaSrc = p.link;
        }
        linkHref = p.link || `/photo${p.owner_id}_${p.id}`;
        altText = p.text || 'photo';
    } else if (isVideo) {
        const v = item.video?.video || item.video || {};
        mediaSrc = v.image?.[0]?.url || v.thumbnail || v.photo_320 || v.photo_130 || '/assets/packages/static/openvk/video/rendering.apng';
        linkHref = `/video${v.owner_id}_${v.id}`;
        altText = v.title || v.name || 'video';
    }

    const videoDuration = isVideo ? (item.video?.video?.duration || item.video?.video?.length || item.video?.duration || item.video?.length || 0) : 0;
    const durationFormatted = videoDuration ? formatDuration(videoDuration) : '';

    const handleClick = (e) => {
        if (window.im?.messenger?.showAttachment && msg) {
            window.im.messenger.showAttachment(e, msg, item);
        } else if (isPhoto && typeof OpenMiniature === 'function') {
            const p = item.photo || {};
            OpenMiniature(e, mediaSrc, p.owner_id + '_' + p.id, p.id, 'photo');
        }
    };

    const tileStyle = `
        ${flex != null ? `flex: ${flex};` : ''}
        ${height ? `height: ${height}px;` : ''}
        ${width ? `width: ${width}px;` : ''}
        ${ratio ? `aspect-ratio: ${ratio};` : ''}
        ${style}
    `.trim();

    return html`
        <div class="msg-media-tile ${isVideo ? 'msg-media-tile-video' : ''}" style=${tileStyle}>
            <a class="msg-media-tile-link ${isVideo ? 'compact_video' : ''}" href=${linkHref} onClick=${handleClick}>
                ${isVideo ? html`
                    <div class="play-button"><div class="play-button-ico"></div></div>
                    ${durationFormatted ? html`<div class="video-length">${durationFormatted}</div>` : ''}
                ` : null}
                <img
                    class="media_makima"
                    src=${mediaSrc}
                    alt=${altText}
                    loading="lazy"
                    onLoad=${(e) => { e.target.classList.add('loaded'); }}
                    ref=${(el) => { if (el && el.complete) el.classList.add('loaded'); }}
                />
            </a>
        </div>
    `;
};

/**
 * MessageMakimaGrid component renders a responsive Makima mosaic with gray skeleton segments.
 */
export const MessageMakimaGrid = ({ visualItems, msg, depth = 0 }) => {
    if (!visualItems || visualItems.length === 0) return null;

    const layout = computeMakimaGrid(visualItems, { depth });
    if (!layout) return null;

    // Single item layout
    if (layout.type === 'single') {
        return html`
            <div class="msg-makima-grid msg-makima-single" style="width: ${layout.gridWidth}px; max-width: 100%;">
                <${MediaTile}
                    item=${layout.tile.item}
                    msg=${msg}
                    width=${layout.tile.width}
                    height=${layout.tile.height}
                    ratio=${layout.tile.ratio}
                    style="max-width: 100%;"
                />
            </div>
        `;
    }

    // Split left layout (1 dominant left, 2 stacked right)
    if (layout.type === 'split_left') {
        return html`
            <div class="msg-makima-grid" style="width: ${layout.gridWidth}px; height: ${layout.gridHeight}px; max-width: 100%; aspect-ratio: ${layout.gridWidth} / ${layout.gridHeight};">
                <div class="msg-makima-split-row" style="display: flex; gap: 2px; height: 100%; width: 100%;">
                    <${MediaTile} item=${layout.leftTile.item} msg=${msg} flex=${layout.leftTile.flex} height=${null} style="height: 100%;" />
                    <div class="msg-makima-col-stacked" style="flex: 1; display: flex; flex-direction: column; gap: 2px; height: 100%;">
                        ${layout.rightTiles.map(t => html`
                            <${MediaTile} item=${t.item} msg=${msg} flex=${t.flex} height=${null} style="flex: 1; height: 100%;" />
                        `)}
                    </div>
                </div>
            </div>
        `;
    }

    // Standard row-based layout
    return html`
        <div class="msg-makima-grid" style="width: ${layout.gridWidth}px; max-width: 100%;">
            ${layout.rows.map(row => html`
                <div class="msg-makima-row" style="display: flex; gap: 2px; width: 100%; height: ${row.height}px; max-height: ${row.height}px; margin-bottom: 2px; ${row.ratio ? `aspect-ratio: ${row.ratio};` : ''}">
                    ${row.tiles.map(t => html`
                        <${MediaTile} item=${t.item} msg=${msg} flex=${t.flex} height=${null} style="height: 100%;" />
                    `)}
                </div>
            `)}
        </div>
    `;
};
