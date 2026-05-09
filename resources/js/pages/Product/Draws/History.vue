<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { can } from '@/helpers/permissions';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Draw History', href: '/draws/histories' },
];

const deleteModal = ref(false);
const deletingHistory = ref<any>(null);

const publishModal = ref(false);
const publishingHistory = ref<any>(null);

// Preview modal refs
const previewModal = ref(false);
const previewImageUrl = ref<string | null>(null);
const previewFileName = ref<string>('draw-result.png');
const previewWinData = ref<any>(null); // Store win data for WhatsApp

const { wins, products, logoUrl, cupIcon, filters } = defineProps<{
    wins: any;
    products: Array<any>;
    logoUrl: string;
    cupIcon: string;
    filters: any;
}>();

const WHATSAPP_TO = '88001714963096';

const form = useForm({
    product_id: filters.product_id || '',
    start_date: filters.start_date || '',
    start_time: filters.start_time || '',
    end_date: filters.end_date || '',
    end_time: filters.end_time || ''
});

const formatDate = (date: string) => {
    const parsedDate = new Date(date);
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
    }).format(parsedDate);
};

const formatTime = (time: string) => {
    const date = new Date(time);
    return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    }).format(date);
};



const parseNumbers = (winNumber: any): string[] => {
    if (!winNumber) return [];
    if (Array.isArray(winNumber)) return winNumber.map(String);

    if (typeof winNumber === 'string') {
        try {
            const parsed = JSON.parse(winNumber);
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch {
            return [];
        }
    }

    return [];
};

const deleteHistory = (id: any) => {
    deletingHistory.value = id;
    deleteModal.value = true;
};

const confirmDelete = () => {
    router.get(route('draws.histories-delete', deletingHistory.value), {}, {
        onSuccess: () => {
            deletingHistory.value = null;
            deleteModal.value = false;
            toast.success('History deleted successfully.');
        },
        onError: () => {
            toast.error('Something went wrong when deleting History');
        }
    });
};

const handlePublish = (id: any) => {
    publishingHistory.value = id;
    publishModal.value = true;
};

const confirmPublish = () => {
    router.get(route('draws.histories-publish', publishingHistory.value), {}, {
        onSuccess: () => {
            publishingHistory.value = null;
            publishModal.value = false;
            toast.success('Draw published successfully.');
        },
        onError: () => {
            toast.error('Something went wrong when publishing Draw');
        }
    });
};

const handleSearch = () => {
    form.get(route('draws.histories'), {
        preserveScroll: true,
        replace: true,
        showProgress: false,
        preserveState: true
    });
};

// ─── Canvas Helpers ────────────────────────────────────────────────────────────

const LEFT_PANEL_W = 118;
const RIGHT_PANEL_W = 210;
const ROW_HEIGHT = 50;
const ROW_SPACING = 8;
const CARD_PADDING = 20;
const CARD_CORNER = 22;
const BADGE_W_CONST = 148;                                   // must match BADGE_W in buildResultCanvas
const BADGE_END_X  = LEFT_PANEL_W + 10 + BADGE_W_CONST;     // right edge of the badge pill (= 276)
const WHITE_END_X  = BADGE_END_X + 8;                        // kept for reference

const formatResultDate = (date: string): string => {
    const d = new Date(date);
    const day = d.getDate().toString().padStart(2, '0');
    const month = d.toLocaleString('en-US', { month: 'short' }).toUpperCase();
    const year = d.getFullYear();
    return `${day} ${month} ${year}`;
};

function drawRoundRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

function drawCardBackground(ctx: CanvasRenderingContext2D, W: number, H: number) {
    const GRAY_GAP   = 10;              // gap from top / bottom / right card edges
    const GRAY_LEFT  = LEFT_PANEL_W + 80; // 5px gap from logo panel edge (gray goes more left)
    const GRAY_TOP   = GRAY_GAP;
    const GRAY_W     = W - GRAY_LEFT - GRAY_GAP;
    const GRAY_H     = H - GRAY_GAP * 2;
    const GRAY_R     = 20;  // border-radius of the gray inner panel

    // ── Step 1: Clip to card shape ──
    ctx.save();
    drawRoundRect(ctx, 0, 0, W, H, CARD_CORNER);
    ctx.clip();

    // ── Step 2: White background fills entire card ──
    ctx.fillStyle = '#edf0f3';
    ctx.fillRect(0, 0, W, H);

    // Diagonal lines over white (subtle dark)
    ctx.strokeStyle = 'rgba(0,0,0,0.055)';
    ctx.lineWidth = 1;
    for (let i = -H * 2; i < W + H * 2; i += 16) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i + H, H);
        ctx.stroke();
    }

    // ── Step 3: Gray inner panel (inside white) ──
    ctx.save();
    drawRoundRect(ctx, GRAY_LEFT, GRAY_TOP, GRAY_W, GRAY_H, GRAY_R);

    const grayGrad = ctx.createLinearGradient(GRAY_LEFT, GRAY_TOP, W, H);
    grayGrad.addColorStop(0, '#b6c3cc');
    grayGrad.addColorStop(0.5, '#9db0bb');
    grayGrad.addColorStop(1, '#8ea2ae');
    ctx.fillStyle = grayGrad;
    ctx.fill();

    // Diagonal lines clipped to gray panel area
    ctx.clip();
    ctx.strokeStyle = 'rgba(255,255,255,0.22)';
    ctx.lineWidth = 1.2;
    for (let i = GRAY_LEFT - H * 2; i < W + H * 2; i += 16) {
        ctx.beginPath();
        ctx.moveTo(i, GRAY_TOP);
        ctx.lineTo(i + GRAY_H, GRAY_TOP + GRAY_H);
        ctx.stroke();
    }
    ctx.restore(); // end gray clip

    ctx.restore(); // end card clip

    // ── Step 4: Card outer border ──
    ctx.strokeStyle = 'rgba(0,0,0,0.14)';
    ctx.lineWidth = 2.5;
    drawRoundRect(ctx, 0, 0, W, H, CARD_CORNER);
    ctx.stroke();
}


function drawLeftPanel(ctx: CanvasRenderingContext2D, H: number, logoImg: HTMLImageElement | null, cupImg: HTMLImageElement | null) {
    const cx = LEFT_PANEL_W / 2;

    // ── Logo ──
    const logoW = 80;
    const logoH = 80;
    const logoY = CARD_PADDING + logoH / 2 + 10;

    if (logoImg) {
        ctx.drawImage(logoImg, cx - logoW / 2, logoY - logoH / 2, logoW, logoH);
    } else {
        ctx.beginPath();
        ctx.arc(cx, logoY, 38, 0, Math.PI * 2);
        ctx.fillStyle = '#1e3a6e';
        ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('LOGO', cx, logoY);
        ctx.textBaseline = 'alphabetic';
    }

    // ── "BUY OUR PRODUCT GET COUPON" ──
    const textY = H * 0.46;
    ctx.fillStyle = '#0d1a2e';
    ctx.textAlign = 'center';
    ['BUY OUR', 'PRODUCT', 'GET COUPON'].forEach((line, i) => {
        ctx.font = `bold ${i === 2 ? 11 : 12}px Arial, sans-serif`;
        ctx.fillText(line, cx, textY + i * 17);
    });

    // ── Trophy ──
    drawTrophy(ctx, cx, H * 0.8, cupImg);
}

function drawTrophy(ctx: CanvasRenderingContext2D, cx: number, cy: number, cupImg: HTMLImageElement | null) {
    if (cupImg) {
        const iconW = 74;
        const iconH = 82;
        ctx.drawImage(cupImg, cx - iconW / 2, cy - iconH / 2, iconW, iconH);
        return;
    }

    const s = 1.55;
    ctx.save();
    ctx.translate(cx, cy);

    const cupGrad = ctx.createLinearGradient(-14 * s, -20 * s, 14 * s, 14 * s);
    cupGrad.addColorStop(0, '#ffe066');
    cupGrad.addColorStop(0.45, '#f0b429');
    cupGrad.addColorStop(1, '#b8860b');

    // Cup body
    ctx.beginPath();
    ctx.moveTo(-14 * s, -20 * s);
    ctx.lineTo(-14 * s, -8 * s);
    ctx.bezierCurveTo(-15 * s, 6 * s, -8 * s, 12 * s, 0, 14 * s);
    ctx.bezierCurveTo(8 * s, 12 * s, 15 * s, 6 * s, 14 * s, -8 * s);
    ctx.lineTo(14 * s, -20 * s);
    ctx.closePath();
    ctx.fillStyle = cupGrad;
    ctx.fill();
    ctx.strokeStyle = '#9a6e00';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    // Top rim
    ctx.beginPath();
    ctx.ellipse(0, -20 * s, 14 * s, 3.5 * s, 0, 0, Math.PI * 2);
    ctx.fillStyle = '#ffe57a';
    ctx.fill();

    // Handles
    ctx.strokeStyle = '#f0b429';
    ctx.lineWidth = 4 * s;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(-14 * s, -16 * s);
    ctx.arc(-21 * s, -10 * s, 8 * s, -0.9, 1.6);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(14 * s, -16 * s);
    ctx.arc(21 * s, -10 * s, 8 * s, Math.PI + 0.9, -1.6, true);
    ctx.stroke();

    // Stem
    ctx.fillStyle = '#f0b429';
    ctx.fillRect(-3.5 * s, 14 * s, 7 * s, 8 * s);

    // Base
    ctx.beginPath();
    drawRoundRect(ctx, -16 * s, 22 * s, 32 * s, 6 * s, 3);
    ctx.fillStyle = '#f0b429';
    ctx.fill();
    ctx.strokeStyle = '#9a6e00';
    ctx.lineWidth = 1;
    ctx.stroke();

    // Shine
    ctx.beginPath();
    ctx.ellipse(-4 * s, -14 * s, 2.5 * s, 5.5 * s, -0.3, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255,255,255,0.32)';
    ctx.fill();

    ctx.restore();
}

function drawCouponBadge(
    ctx: CanvasRenderingContext2D,
    title: string,
    productNumber: string | number | null,
    x: number, y: number,
    w: number, h: number
) {
    // Navy pill background
    const grad = ctx.createLinearGradient(x, y, x, y + h);
    grad.addColorStop(0, '#243f7a');
    grad.addColorStop(1, '#162d5a');
    ctx.fillStyle = grad;
    drawRoundRect(ctx, x, y, w, h, h / 2);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.textBaseline = 'middle';
    const cy = y + h / 2;
    const numStr = productNumber != null ? String(productNumber) : '';

    // Font sizes: title normal, number BIG
    const titleFontSize = Math.min(h * 0.36, 15);
    const numFontSize   = Math.min(h * 0.72, 30);

    // Measure both parts to center them together
    ctx.font = `bold ${titleFontSize}px Arial, sans-serif`;
    const titleW = ctx.measureText(title + (numStr ? ' ' : '')).width;
    ctx.font = `bold ${numFontSize}px Arial, sans-serif`;
    const numW = numStr ? ctx.measureText(numStr).width : 0;

    const totalW = titleW + numW;
    // Centered inside badge
    const startX = x + (w - totalW) / 2;

    // Draw title text
    ctx.font = `bold ${titleFontSize}px Arial, sans-serif`;
    ctx.textAlign = 'left';
    ctx.fillText(title + (numStr ? ' ' : ''), startX, cy + (numFontSize - titleFontSize) * 0.08);

    // Draw number — larger, aligned to baseline with title
    if (numStr) {
        ctx.font = `bold ${numFontSize}px Arial, sans-serif`;
        ctx.fillText(numStr, startX + titleW, cy);
    }

    ctx.textBaseline = 'alphabetic';
}

function drawWinBall(ctx: CanvasRenderingContext2D, cx: number, cy: number, r: number, text: string) {
    ctx.shadowColor = 'rgba(0,0,0,0.3)';
    ctx.shadowBlur = 8;
    ctx.shadowOffsetX = 1;
    ctx.shadowOffsetY = 3;

    const grad = ctx.createRadialGradient(cx - r * 0.28, cy - r * 0.28, r * 0.05, cx, cy, r);
    grad.addColorStop(0, '#f9f9f9');
    grad.addColorStop(0.65, '#e2e2e2');
    grad.addColorStop(1, '#c5c5c5');
    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fill();

    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;

    ctx.fillStyle = '#1a1a1a';
    ctx.font = `bold ${r * 0.88}px Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, cx, cy);
    ctx.textBaseline = 'alphabetic';
}

function drawInfoPanel(ctx: CanvasRenderingContext2D, W: number, H: number, resultDate: string) {
    const px  = W - RIGHT_PANEL_W + 6;
    const pw  = RIGHT_PANEL_W - 30;
    const pcx = px + pw / 2;
    const BOX_GAP = 8;

    // ── Pre-calculate all box heights ──
    const resH  = 75;
    const giftH = 65;
    const timeH = 50;

    // ── Position at top with 200px margin ──
    const totalH = resH + BOX_GAP + giftH + BOX_GAP + timeH;
    const startY = 100;

    const resY  = startY;
    const giftY = resY  + resH  + BOX_GAP;
    const timeY = giftY + giftH + BOX_GAP;

    // ── RESULT box ──
    const resGrad = ctx.createLinearGradient(px, resY, px, resY + resH);
    resGrad.addColorStop(0, '#243f7a');
    resGrad.addColorStop(1, '#162d5a');
    ctx.fillStyle = resGrad;
    drawRoundRect(ctx, px, resY, pw, resH, 8);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.font = `bold ${Math.min(resH * 0.3, 20)}px Arial, sans-serif`;
    ctx.fillText('RESULT', pcx, resY + resH * 0.36);
    ctx.font = `bold ${Math.min(resH * 0.24, 14)}px Arial, sans-serif`;
    ctx.fillText(resultDate, pcx, resY + resH * 0.7);
    ctx.textBaseline = 'alphabetic';

    // ── CURRENT GIFT box ──
    ctx.fillStyle = '#cc2a2a';
    drawRoundRect(ctx, px, giftY, pw, giftH, 8);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.font = `bold ${Math.min(giftH * 0.3, 13)}px Arial, sans-serif`;
    ctx.fillText('CURRENT GIFT', pcx, giftY + giftH * 0.33);
    ctx.font = `bold ${Math.min(giftH * 0.27, 12)}px Arial, sans-serif`;
    ctx.fillText('AED 500,000', pcx, giftY + giftH * 0.68);
    ctx.textBaseline = 'alphabetic';

    // ── DRAW TIME box ──
    ctx.fillStyle = '#f4f4f4';
    drawRoundRect(ctx, px, timeY, pw, timeH, 8);
    ctx.fill();
    ctx.strokeStyle = '#cccccc';
    ctx.lineWidth = 1;
    ctx.stroke();

    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#0d1a2e';
    ctx.font = `bold ${Math.min(timeH * 0.28, 13)}px Arial, sans-serif`;
    ctx.fillText('DRAW TIME', pcx, timeY + timeH * 0.28);
    ctx.font = `bold ${Math.min(timeH * 0.26, 12)}px Arial, sans-serif`;
    ctx.fillText('12:00 AM', pcx, timeY + timeH * 0.57);
    ctx.fillStyle = '#1565c0';
    ctx.font = `bold ${Math.min(timeH * 0.24, 11)}px Arial, sans-serif`;
    ctx.fillText('Everyday', pcx, timeY + timeH * 0.84);
    ctx.textBaseline = 'alphabetic';
}


function loadLogo(url: string): Promise<HTMLImageElement | null> {
    return new Promise((resolve) => {
        if (!url) return resolve(null);
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

function buildResultCanvas(
    rows: Array<{ title: string; productNumber: string | number | null; numbers: string[] }>,
    logoImg: HTMLImageElement | null,
    cupImg: HTMLImageElement | null,
    resultDate: string
): HTMLCanvasElement {
    const W = 900;
    const rowCount = Math.max(rows.length, 1);
    const contentH = rowCount * ROW_HEIGHT + (rowCount - 1) * ROW_SPACING;
    const H = Math.max(CARD_PADDING * 2 + contentH, 300);

    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d')!;

    drawCardBackground(ctx, W, H);
    drawLeftPanel(ctx, H, logoImg, cupImg);
    drawInfoPanel(ctx, W, H, resultDate);

    // Center rows
    const BADGE_W = 100;
    const BADGE_X = LEFT_PANEL_W + 10;
    const BALL_R = 27;
    const BALL_PITCH = BALL_R * 2 + 8;

    const startY = Math.round((H - contentH) / 2);

    rows.forEach((row, i) => {
        const rowY = startY + i * (ROW_HEIGHT + ROW_SPACING);
        const BADGE_H = ROW_HEIGHT - 10;
        drawCouponBadge(ctx, row.title, row.productNumber, BADGE_X, rowY + 5, BADGE_W, BADGE_H);

        const ballStartX = BADGE_X + BADGE_W + BALL_R + 10;
        const ballCY = rowY + ROW_HEIGHT / 2;
        row.numbers.forEach((num, ni) => {
            drawWinBall(ctx, ballStartX + ni * BALL_PITCH, ballCY, BALL_R, num);
        });
    });

    return canvas;
}

const openWhatsAppChat = (message: string) => {
    const encodedMessage = encodeURIComponent(message);
    const url = `https://wa.me/${WHATSAPP_TO}?text=${encodedMessage}`;
    window.open(url, '_blank');
};

// ─── Download Single Win ──────────────────────────────────────────────────────

const handleDownload = async (win: any) => {
    const logoImg = await loadLogo(logoUrl);

    const rows = products.map((product) => {
        const isMatch = product.id === win.product_id;
        return {
            title: product.title,
            productNumber: product.product_number ?? null,
            numbers: isMatch ? parseNumbers(win.win_number) : [],
        };
    });

    const cupImg = await loadLogo(cupIcon || '/public/assets/cup-icon.png');
    const resultDate = formatResultDate(win.draw_time);
    const canvas = buildResultCanvas(rows, logoImg, cupImg, resultDate);

    const fileName = `draw-result-${(win.product?.title+" "+win.product?.product_number ?? 'product').replace(/\s+/g, '-')}-${win.id}.png`;
    
    // Store for preview
    previewImageUrl.value = canvas.toDataURL('image/png');
    previewFileName.value = fileName;
    previewWinData.value = win; // Store win data for WhatsApp
    previewModal.value = true;
};

// ─── Download All Wins ────────────────────────────────────────────────────────

const handleDownloadAll = async () => {
    const allWins = wins?.data ?? [];
    if (!allWins.length) {
        toast.error('No records to download.');
        return;
    }

    const logoImg = await loadLogo(logoUrl);

    const rows = allWins.map((win: any) => ({
        title: win.product?.title ?? 'Unknown',
        productNumber: win.product?.product_number ?? null,
        numbers: parseNumbers(win.win_number),
    }));

    const cupImg = await loadLogo(cupIcon || '/public/assets/cup-icon.png');
    const latestWin = allWins[0];
    const resultDate = filters.start_date ? formatResultDate(filters.start_date) : formatResultDate(latestWin.draw_time);
    const canvas = buildResultCanvas(rows, logoImg, cupImg, resultDate);

    const fileName = `draw-results-all-${Date.now()}.png`;
    
    // Store for preview
    previewImageUrl.value = canvas.toDataURL('image/png');
    previewFileName.value = fileName;
    previewModal.value = true;

    toast.success(`Preview ready with ${allWins.length} results.`);
};

const confirmDownload = () => {
    if (!previewImageUrl.value) return;
    
    const link = document.createElement('a');
    link.download = previewFileName.value;
    link.href = previewImageUrl.value;
    link.click();
    
    // WhatsApp message when clicking download
    if (previewWinData.value) {
        const win = previewWinData.value;
        const numbers = parseNumbers(win.win_number).join(', ') || 'N/A';
        const message = `Draw result ready.\nProduct: ${win.product?.title+" "+win.product?.product_number ?? 'N/A'}\nDate: ${win.formatted_date}\nDraw Time: ${win.formatted_time}\nWin Number: ${numbers}`;
        openWhatsAppChat(message);
    }
    
    previewModal.value = false;
    previewWinData.value = null;
    toast.success('Image downloaded successfully.');
};
</script>

<template>
    <Head title="Draw History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">

                    <!-- Filters -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Product</label>
                                <select
                                    v-model="form.product_id"
                                    class="w-full border-2 border-gray-200 px-2 py-1.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                >
                                    <option value="">Select Product</option>
                                    <option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.title }} {{ product.product_number }}
                                    </option>
                                </select>
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Start Date</label>
                                <Input v-model="form.start_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Start Time</label>
                                <Input v-model="form.start_time" type="time" class="w-full" />
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">End Date</label>
                                <Input v-model="form.end_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">End Time</label>
                                <Input v-model="form.end_time" type="time" class="w-full" />
                            </div>

                            <div class="relative flex items-center flex-col justify-center gap-2">
                                <Button
                                    @click="handleSearch"
                                    variant="destructive"
                                    size="lg"
                                    class="cursor-pointer px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                >
                                    Search
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="border rounded-lg overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">SL</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-center">Product</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">Date</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">Win Number</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y">
                                <tr v-for="(win, index) in wins.data" :key="win.id" class="group">
                                    <td class="px-6 py-4 font-medium text-gray-900 border-r">
                                        {{ (wins.current_page - 1) * wins.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900 border-r text-center">
                                        {{ win.product?.title }} {{ win.product?.product_number }}
                                    </td>
                                    <td class="px-6 py-4 border-r text-center">
                                        <p class="text-lg">{{ win.formatted_date }}</p>
                                        <p class="font-bold text-md" v-if="win.formatted_time">{{ win.formatted_time }}</p>
                                    </td>
                                    <td class="px-6 py-4 border-r">
                                        <div class="flex gap-2 justify-center flex-nowrap">
                                            <div
                                                v-for="(number, idx) in parseNumbers(win.win_number)"
                                                :key="idx"
                                                class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-center font-bold border-2 border-gray-800 text-gray-900 bg-white shadow-sm"
                                            >
                                                {{ number }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 justify-center">
                                            
                                            <Button
                                                @click="handleDownload(win)"
                                                variant="default"
                                                size="sm"
                                                class="px-2 py-1 bg-blue-500 text-white hover:bg-blue-600"
                                            >
                                                View
                                            </Button>
                                            <div class="flex gap-2" v-if="!win.publish">
                                                <Button
                                                    @click="handlePublish(win.id)"
                                                    variant="default"
                                                    size="sm"
                                                    class="px-2 py-1 bg-green-500 text-white hover:bg-green-600"
                                                >
                                                    Publish
                                                </Button>
                                                <Button
                                                    v-if="can('draw history delete')"
                                                    @click="deleteHistory(win.id)"
                                                    variant="destructive"
                                                    size="sm"
                                                    class="px-2 py-1"
                                                >
                                                    Delete
                                                </Button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This History will be deleted permanently.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Preview Dialog -->
        <Dialog v-model:open="previewModal" class="max-w-6xl">
            <DialogContent class="max-w-6xl max-h-[95vh] overflow-auto w-[95vw]">
                <DialogHeader>
                    <DialogTitle>Preview Draw Result</DialogTitle>
                    <DialogDescription>
                        Review the image before downloading.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="flex justify-center py-4">
                    <img 
                        v-if="previewImageUrl" 
                        :src="previewImageUrl" 
                        alt="Draw Result Preview" 
                        class="max-w-full h-auto rounded-lg shadow-lg border"
                    />
                </div>
                
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="previewModal = false">Cancel</Button>
                    <Button 
                        variant="default" 
                        @click="confirmDownload"
                        class="bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                        </svg>
                        Download Now
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Publish Confirmation Dialog -->
        <Dialog v-model:open="publishModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Confirm Publication</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to publish this draw? This will notify winners and update their tickets. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="publishModal = false">Cancel</Button>
                    <Button variant="default" @click="confirmPublish" class="bg-green-600 hover:bg-green-700">Confirm Publish</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>