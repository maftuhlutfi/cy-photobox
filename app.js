// CY Photobox Application Controller

let mediaStream = null;
let sessionTimeLeft = 180; // 3 minutes in seconds
let sessionTimerInterval = null;
let capturedPhotos = [];
let selectedFrameId = '';
let availableFrames = [];

// DOM Elements
const screenStart = document.getElementById('screen-start');
const screenCapture = document.getElementById('screen-capture');
const screenEditor = document.getElementById('screen-editor');
const screenReady = document.getElementById('screen-ready');

const webcamElem = document.getElementById('webcam');
const sessionTimerElem = document.getElementById('session-timer');
const countdownOverlay = document.getElementById('countdown-overlay');
const countdownNumber = document.getElementById('countdown-number');
const flashOverlay = document.getElementById('flash-overlay');
const photoCounterBadge = document.getElementById('photo-counter-badge');
const thumbSlots = [
    document.getElementById('thumb-1'),
    document.getElementById('thumb-2'),
    document.getElementById('thumb-3')
];

const canvasPreview = document.getElementById('canvas-preview');
const btnRetake = document.getElementById('btn-retake');
const framesListContainer = document.getElementById('frames-list');
const finalPreviewImg = document.getElementById('final-preview-img');
const qrcodeContainer = document.getElementById('qrcode-container');

// Sound Effects
function playBeep(freq = 600, duration = 0.1) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + duration);
    } catch(e) {}
}

function playShutterSound() {
    playBeep(1200, 0.2);
}

// Navigation Helper
function showScreen(screenElem) {
    [screenStart, screenCapture, screenEditor, screenReady].forEach(s => s.classList.remove('active'));
    screenElem.classList.add('active');
}

// 1. Session Timer (3 Minutes)
function startSessionTimer() {
    sessionTimeLeft = 180; // 3 minutes
    updateSessionTimerUI();

    if (sessionTimerInterval) clearInterval(sessionTimerInterval);

    sessionTimerInterval = setInterval(() => {
        sessionTimeLeft--;
        updateSessionTimerUI();

        if (sessionTimeLeft <= 0) {
            clearInterval(sessionTimerInterval);
            sessionTimeLeft = 0;
            if (btnRetake) {
                btnRetake.classList.add('disabled');
                btnRetake.disabled = true;
                btnRetake.innerHTML = '⏱️ Waktu foto habis';
            }
        }
    }, 1000);
}

function updateSessionTimerUI() {
    const mins = Math.floor(sessionTimeLeft / 60).toString().padStart(2, '0');
    const secs = (sessionTimeLeft % 60).toString().padStart(2, '0');
    sessionTimerElem.textContent = `${mins}:${secs}`;
}

// 2. Camera Management
async function initCamera() {
    if (mediaStream) return;
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280 },
                height: { ideal: 960 },
                facingMode: 'user'
            },
            audio: false
        });
        webcamElem.srcObject = mediaStream;
    } catch (err) {
        console.warn('Camera access issue, fallback photo mode enabled:', err);
    }
}

// 3. Photo Capture Sequence (5s countdown per photo x 3 photos)
async function startSession() {
    await initCamera();
    capturedPhotos = [];
    thumbSlots.forEach(slot => slot.innerHTML = '');
    
    startSessionTimer();
    showScreen(screenCapture);

    if (btnRetake) {
        btnRetake.classList.remove('disabled');
        btnRetake.disabled = false;
        btnRetake.innerHTML = '🔄 Foto Ulang';
    }

    startCaptureSequence();
}

async function startCaptureSequence() {
    capturedPhotos = [];
    thumbSlots.forEach((slot, idx) => {
        slot.innerHTML = `Foto ${idx + 1}`;
    });

    for (let i = 1; i <= 3; i++) {
        photoCounterBadge.textContent = `Foto ${i} dari 3`;
        await runCountdown(5);
        const photoData = snapPhoto();
        capturedPhotos.push(photoData);

        thumbSlots[i - 1].innerHTML = `<img src="${photoData}" alt="Foto ${i}">`;

        await new Promise(r => setTimeout(r, 600));
    }

    setTimeout(async () => {
        await renderFramePreview();
        showScreen(screenEditor);
    }, 500);
}

function runCountdown(seconds) {
    return new Promise(resolve => {
        let count = seconds;
        countdownOverlay.style.display = 'flex';
        countdownNumber.textContent = count;
        playBeep(800, 0.1);

        const timer = setInterval(() => {
            count--;
            if (count > 0) {
                countdownNumber.textContent = count;
                playBeep(800, 0.1);
            } else {
                clearInterval(timer);
                countdownOverlay.style.display = 'none';
                resolve();
            }
        }, 1000);
    });
}

function snapPhoto() {
    flashOverlay.classList.remove('snap');
    void flashOverlay.offsetWidth;
    flashOverlay.classList.add('snap');
    playShutterSound();

    const canvas = document.createElement('canvas');
    const w = webcamElem.videoWidth || 1280;
    const h = webcamElem.videoHeight || 960;
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');

    ctx.translate(w, 0);
    ctx.scale(-1, 1);

    if (webcamElem && webcamElem.readyState >= 2) {
        ctx.drawImage(webcamElem, 0, 0, w, h);
    } else {
        // Fallback test snapshot if webcam video not active
        ctx.fillStyle = '#6366f1';
        ctx.fillRect(0, 0, w, h);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 48px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('CY PHOTOBOX SNAP', w / 2, h / 2);
    }

    return canvas.toDataURL('image/jpeg', 0.95);
}

// Parse SVG structure to get background fill, 6 photo slot coordinates, and optional foreground overlay
function getRectBox(r) {
    let x = parseFloat(r.getAttribute('x') || '0');
    let y = parseFloat(r.getAttribute('y') || '0');
    let w = parseFloat(r.getAttribute('width') || '0');
    let h = parseFloat(r.getAttribute('height') || '0');

    const transform = r.getAttribute('transform');
    if (transform) {
        const translateMatch = transform.match(/translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/);
        if (translateMatch) {
            x += parseFloat(translateMatch[1]);
            y += parseFloat(translateMatch[2]);
        }
    }
    return { x, y, w, h };
}

function parseSvgFrame(svgContent) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(svgContent || '', "image/svg+xml");
    const svg = doc.querySelector('svg');

    let bgColor = '#B20707';
    const slots = [];
    let hasForegroundOverlay = false;

    if (svg) {
        const allRects = Array.from(doc.querySelectorAll('rect'));

        // Identify background color if background rect exists, but DO NOT remove background rect or any other element
        allRects.forEach(r => {
            const w = parseFloat(r.getAttribute('width') || '0');
            const h = parseFloat(r.getAttribute('height') || '0');
            const fill = (r.getAttribute('fill') || '').toLowerCase();

            if (w >= 1100 && h >= 1700) {
                if (fill && fill !== 'none' && !fill.startsWith('url(')) {
                    bgColor = fill;
                }
            }
        });

        // Identify ONLY photo slot rects (width 500 and height 400, or names/IDs like 27, 28, 29, photo, slot)
        const isPhotoSlot = (r) => {
            const w = Math.round(parseFloat(r.getAttribute('width') || '0'));
            const h = Math.round(parseFloat(r.getAttribute('height') || '0'));
            const fill = (r.getAttribute('fill') || '').toLowerCase();

            // Direct check for exact photo slot dimensions (width=500, height=400) and non-pattern fill
            const isExactDimensions = (w === 500 && h === 400 && !fill.startsWith('url(#'));
            if (isExactDimensions) return true;

            // Explicit names/IDs check (Rectangle 27, 28, 29, photo, slot)
            const attrs = [
                r.getAttribute('id'),
                r.getAttribute('name'),
                r.getAttribute('data-name'),
                r.getAttribute('class'),
                r.getAttribute('label'),
                r.getAttribute('inkscape:label'),
                r.getAttribute('aria-label'),
                r.parentElement ? r.parentElement.getAttribute('id') : null,
                r.parentElement ? r.parentElement.getAttribute('name') : null
            ].filter(Boolean);

            const pattern = /rectangle[_\s-]*(27|28|29)\b|rect[_\s-]*(27|28|29)\b|\b(27|28|29)\b|photo|slot/i;
            return attrs.some(attr => pattern.test(attr));
        };

        const slotRects = allRects.filter(isPhotoSlot);

        // Process identified photo slot rects
        if (slotRects.length > 0) {
            const rawSlots = slotRects.map(r => {
                const box = getRectBox(r);
                r.remove(); // Remove ONLY photo slot rects from overlay
                return box;
            });

            // Separate and sort into left column (x < 600) and right column (x >= 600) from top to bottom (by y)
            const leftSlots = rawSlots.filter(s => s.x < 600).sort((a, b) => a.y - b.y);
            const rightSlots = rawSlots.filter(s => s.x >= 600).sort((a, b) => a.y - b.y);

            if (leftSlots.length > 0 || rightSlots.length > 0) {
                slots.push(...leftSlots, ...rightSlots);
            } else {
                rawSlots.sort((a, b) => a.y - b.y);
                slots.push(...rawSlots);
            }
        }

        if (doc.querySelectorAll('path, text, image, circle, line, g, polygon, rect').length > 0) {
            hasForegroundOverlay = true;
        }
    }

    // Default fallback coordinates if fewer than 6 slots detected
    if (slots.length < 6) {
        slots.length = 0;
        slots.push(
            { x: 50,  y: 104, w: 500, h: 400 },
            { x: 50,  y: 548, w: 500, h: 400 },
            { x: 50,  y: 992, w: 500, h: 400 },
            { x: 650, y: 104, w: 500, h: 400 },
            { x: 650, y: 548, w: 500, h: 400 },
            { x: 650, y: 992, w: 500, h: 400 }
        );
    }

    const serializer = new XMLSerializer();
    const foregroundSvgStr = serializer.serializeToString(doc);

    return { bgColor, slots, foregroundSvgStr, hasForegroundOverlay };
}

// 4. Frame Compositing Engine - Photos rendered at the exact DOM layer position of photo rectangles
async function renderFramePreview() {
    canvasPreview.width = 1200;
    canvasPreview.height = 1800;
    const ctx = canvasPreview.getContext('2d');

    ctx.clearRect(0, 0, 1200, 1800);

    const currentFrameObj = availableFrames.find(f => f.id === selectedFrameId) || availableFrames[0];
    if (!currentFrameObj) return;

    // Parse SVG DOM to insert photo layers at their exact layer hierarchy
    const parser = new DOMParser();
    const doc = parser.parseFromString(currentFrameObj.content || '', "image/svg+xml");
    const svg = doc.querySelector('svg');

    if (!svg) {
        ctx.fillStyle = '#111111';
        ctx.fillRect(0, 0, 1200, 1800);
        return;
    }

    const allRects = Array.from(doc.querySelectorAll('rect'));

    // Filter photo slot rects (width 500 and height 400, or names/IDs like 27, 28, 29, photo, slot)
    const isPhotoSlot = (r) => {
        const w = Math.round(parseFloat(r.getAttribute('width') || '0'));
        const h = Math.round(parseFloat(r.getAttribute('height') || '0'));
        const fill = (r.getAttribute('fill') || '').toLowerCase();

        const isExactDimensions = (w === 500 && h === 400 && !fill.startsWith('url(#'));
        if (isExactDimensions) return true;

        const attrs = [
            r.getAttribute('id'),
            r.getAttribute('name'),
            r.getAttribute('data-name'),
            r.getAttribute('class'),
            r.getAttribute('label'),
            r.getAttribute('inkscape:label'),
            r.getAttribute('aria-label'),
            r.parentElement ? r.parentElement.getAttribute('id') : null,
            r.parentElement ? r.parentElement.getAttribute('name') : null
        ].filter(Boolean);

        const pattern = /rectangle[_\s-]*(27|28|29)\b|rect[_\s-]*(27|28|29)\b|\b(27|28|29)\b|photo|slot/i;
        return attrs.some(attr => pattern.test(attr));
    };

    const slotRects = allRects.filter(isPhotoSlot);

    if (slotRects.length > 0) {
        const rawSlots = slotRects.map(r => ({
            box: getRectBox(r),
            elem: r
        }));

        // Sort into left column (x < 600) and right column (x >= 600) from top to bottom (by y)
        const leftSlots = rawSlots.filter(s => s.box.x < 600).sort((a, b) => a.box.y - b.box.y);
        const rightSlots = rawSlots.filter(s => s.box.x >= 600).sort((a, b) => a.box.y - b.box.y);
        const sortedSlots = (leftSlots.length > 0 || rightSlots.length > 0) 
            ? [...leftSlots, ...rightSlots] 
            : rawSlots.sort((a, b) => a.box.y - b.box.y);

        // Replace each photo slot rect IN PLACE in the DOM tree with the photo <image> or placeholder
        sortedSlots.forEach((slotObj, idx) => {
            const photoIdx = idx % 3; // 0, 1, 2 for left; 0, 1, 2 for right
            const photoData = capturedPhotos[photoIdx];
            const b = slotObj.box;
            const rElem = slotObj.elem;

            if (photoData) {
                // Create SVG <image> element at exact layer position in the SVG DOM
                const imgNode = doc.createElementNS("http://www.w3.org/2000/svg", "image");
                imgNode.setAttribute('x', b.x);
                imgNode.setAttribute('y', b.y);
                imgNode.setAttribute('width', b.w);
                imgNode.setAttribute('height', b.h);
                imgNode.setAttribute('preserveAspectRatio', 'xMidYMid slice');
                imgNode.setAttributeNS('http://www.w3.org/1999/xlink', 'href', photoData);
                imgNode.setAttribute('href', photoData);

                if (rElem.parentNode) {
                    rElem.parentNode.replaceChild(imgNode, rElem);
                }
            } else {
                // Placeholder black box at exact layer position if photos not taken yet
                rElem.setAttribute('fill', '#000000');
            }
        });
    }

    // Render the complete SVG DOM (with embedded photos in correct layer hierarchy) to Canvas
    const serializer = new XMLSerializer();
    const finalSvgStr = serializer.serializeToString(doc);

    const frameImg = new Image();
    const svgBlob = new Blob([finalSvgStr], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    await new Promise(resolve => {
        frameImg.onload = () => {
            ctx.drawImage(frameImg, 0, 0, 1200, 1800);
            URL.revokeObjectURL(url);
            resolve();
        };
        frameImg.onerror = (err) => {
            console.error("Error rendering composite SVG frame:", err);
            URL.revokeObjectURL(url);
            resolve();
        };
        frameImg.src = url;
    });
}

// 5. Dynamic Frame Loading strictly from 'frame/' folder
async function loadAvailableFrames() {
    try {
        const res = await fetch('api.php?action=list_frames');
        const data = await res.json();
        if (data.success && Array.isArray(data.frames) && data.frames.length > 0) {
            availableFrames = data.frames;
            if (!selectedFrameId || !availableFrames.find(f => f.id === selectedFrameId)) {
                selectedFrameId = availableFrames[0].id;
            }
        } else {
            availableFrames = [];
        }
    } catch (err) {
        console.error('Error fetching frames from folder:', err);
    }
    renderFramesListUI();
}

function renderFramesListUI() {
    if (!availableFrames || availableFrames.length === 0) {
        framesListContainer.innerHTML = `<div style="grid-column:1/-1; color:#9ca3af; font-size:13px; text-align:center;">Tidak ada file frame SVG di folder frame/</div>`;
        return;
    }

    framesListContainer.innerHTML = availableFrames.map(f => {
        const isActive = f.id === selectedFrameId;
        const displayName = f.name || f.id.replace(/\.svg$/i, '');
        const frameSrc = f.url || ('data:image/svg+xml;charset=utf-8,' + encodeURIComponent(f.content));

        return `
            <div class="frame-item ${isActive ? 'active' : ''}" onclick="selectFrame('${f.id}')">
                <div style="height: 100px; border-radius: 10px; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.15); margin-bottom: 8px; display:flex; align-items:center; justify-content:center; overflow:hidden; padding: 4px;">
                    <img src="${frameSrc}" alt="${displayName}" style="max-height: 100%; max-width: 100%; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));" />
                </div>
                <div class="frame-item-name" style="font-weight: 700;">${displayName}</div>
            </div>
        `;
    }).join('');
}

async function selectFrame(frameId) {
    selectedFrameId = frameId;
    renderFramesListUI();
    await renderFramePreview();
}

function retakePhotos() {
    if (sessionTimeLeft <= 0) {
        alert('Waktu 3 menit sesi foto telah habis! Anda tidak dapat mengulang foto.');
        return;
    }
    showScreen(screenCapture);
    startCaptureSequence();
}

// 6. Confirm & Upload Session -> Ready to Print & QR Code
async function confirmAndFinish() {
    const framedB64 = canvasPreview.toDataURL('image/png');

    showScreen(screenReady);
    finalPreviewImg.src = framedB64;
    qrcodeContainer.innerHTML = '<p style="color:#666; font-size:12px;">Membuat QR Code...</p>';

    try {
        const res = await fetch('api.php?action=upload_session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                framed_image: framedB64,
                photo1: capturedPhotos[0] || '',
                photo2: capturedPhotos[1] || '',
                photo3: capturedPhotos[2] || '',
                frame_id: selectedFrameId
            })
        });

        const data = await res.json();
        if (data.success) {
            generateQRCode(data.download_url);
        } else {
            alert('Gagal menyimpan foto: ' + (data.message || 'Error server'));
        }
    } catch (err) {
        console.error('Upload session error:', err);
        const fallbackUrl = window.location.origin + window.location.pathname.replace('index.php', '') + 'download.php?id=local';
        generateQRCode(fallbackUrl);
    }
}

function generateQRCode(url) {
    qrcodeContainer.innerHTML = '';
    if (typeof QRCode !== 'undefined') {
        new QRCode(qrcodeContainer, {
            text: url,
            width: 180,
            height: 180,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    } else {
        const qrImg = document.createElement('img');
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url)}`;
        qrImg.alt = 'QR Code Download';
        qrcodeContainer.appendChild(qrImg);
    }
}

function resetToStart() {
    if (sessionTimerInterval) clearInterval(sessionTimerInterval);
    sessionTimeLeft = 180;
    updateSessionTimerUI();

    if (countdownOverlay) countdownOverlay.style.display = 'none';

    capturedPhotos = [];
    thumbSlots.forEach((slot, idx) => {
        if (slot) slot.innerHTML = `Foto ${idx + 1}`;
    });

    if (btnRetake) {
        btnRetake.classList.remove('disabled');
        btnRetake.disabled = false;
        btnRetake.innerHTML = '🔄 Foto Ulang';
    }

    showScreen(screenStart);
}

// Initial Setup
document.addEventListener('DOMContentLoaded', () => {
    loadAvailableFrames();
});
