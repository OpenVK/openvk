#!/usr/bin/env node

/**
 * OpenVK Lottie / TGS Preview Renderer
 * Renders the first frame of a Lottie vector animation into a PNG or WebP image.
 */

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');
const { execSync } = require('child_process');

// Ensure global modules can be required
if (process.env.NODE_PATH) {
  process.env.NODE_PATH.split(path.delimiter).forEach(p => {
    if (p && !module.paths.includes(p)) module.paths.unshift(p);
  });
}
['/usr/local/lib/node_modules', '/usr/lib/node_modules'].forEach(p => {
  if (fs.existsSync(p) && !module.paths.includes(p)) module.paths.unshift(p);
});

let JSDOM, Canvas;
try {
  ({ JSDOM } = require('jsdom'));
  ({ Canvas } = require('canvas'));
} catch (err) {
  console.error('Missing dependencies (jsdom or canvas):', err.message);
  process.exit(1);
}

const args = process.argv.slice(2);
if (args.length < 2) {
  console.error('Usage: render_lottie.js <input.json|input.tgs> <output.png|output.webp> [size=512] [frame]');
  process.exit(1);
}

const inputPath = args[0];
const outputPath = args[1];
const size = args[2] ? Math.max(16, Math.min(4096, parseInt(args[2], 10) || 512)) : 512;
const requestedFrame = args[3] !== undefined ? parseInt(args[3], 10) : null;

if (!fs.existsSync(inputPath)) {
  console.error('Input file not found:', inputPath);
  process.exit(2);
}

let raw = fs.readFileSync(inputPath);
if (raw.length >= 2 && raw[0] === 0x1f && raw[1] === 0x8b) {
  try {
    raw = zlib.gunzipSync(raw);
  } catch (err) {
    console.error('Failed to gunzip input file:', err.message);
    process.exit(3);
  }
}

let animData;
try {
  animData = JSON.parse(raw.toString('utf8'));
} catch (err) {
  console.error('Failed to parse Lottie JSON:', err.message);
  process.exit(4);
}

const dom = new JSDOM('', { pretendToBeVisual: true });
global.window = dom.window;
global.document = dom.window.document;
global.navigator = dom.window.navigator;

const canvas = new Canvas(size, size);
const ctx = canvas.getContext('2d');

document.createElement = (tag) => {
  return tag === 'canvas' ? new Canvas(size, size) : dom.window.document.createElement(tag);
};

let lottiePath = '/opt/openvk/Web/static/js/node_modules/lottie-web/build/player/lottie_canvas.js';
if (!fs.existsSync(lottiePath)) {
  try {
    lottiePath = require.resolve('lottie-web/build/player/lottie_canvas.js');
  } catch (_) {
    lottiePath = 'lottie-web';
  }
}

let lottie;
try {
  lottie = require(lottiePath);
} catch (err) {
  console.error('Failed to load lottie-web player:', err.message);
  process.exit(5);
}

try {
  const anim = lottie.loadAnimation({
    animationData: animData,
    renderer: 'canvas',
    loop: false,
    autoplay: false,
    rendererSettings: {
      context: ctx,
      clearCanvas: true
    }
  });

  const targetFrame = (requestedFrame !== null && !isNaN(requestedFrame))
    ? requestedFrame
    : (animData.ip || 0);

  anim.goToAndStop(targetFrame, true);

  const outDir = path.dirname(outputPath);
  if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
  }

  const outExt = path.extname(outputPath).toLowerCase();
  const pngBuffer = canvas.toBuffer('image/png');

  if (outExt === '.webp') {
    const tempPng = outputPath + '.tmp.png';
    fs.writeFileSync(tempPng, pngBuffer);
    try {
      execSync(`ffmpeg -v error -y -i "${tempPng}" "${outputPath}"`);
    } finally {
      if (fs.existsSync(tempPng)) fs.unlinkSync(tempPng);
    }
  } else {
    fs.writeFileSync(outputPath, pngBuffer);
  }

  process.exit(0);
} catch (err) {
  console.error('Render error:', err.message);
  process.exit(6);
}
