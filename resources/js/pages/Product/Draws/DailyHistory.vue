<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Head, useForm, router } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Daily History', href: '/draws/histories-daily' },
];

const { products, histories, wins, filters, logoUrl, cupIcon, categories } = defineProps<{
    products: Array<any>;
    histories: Array<any>;
    wins: any;          // paginated — only present when draw_type === 'once'
    filters: any;
    logoUrl: string;
    cupIcon: string;
    categories: Array<{ id: number; name: string; draw_type: string }>;
}>();

const previewModal = ref(false);
const previewImageUrl = ref<string | null>(null);
const previewFileName = ref<string>('daily-history.png');

// Publish & Delete modals
const publishModal = ref(false);
const deleteModal = ref(false);
const processingWin = ref<any>(null);

const form = useForm({
    start_date: filters.start_date || '',
    end_date: filters.end_date || '',
    start_time: filters.start_time || '',
    end_time: filters.end_time || '',
    draw_type: filters.draw_type || 'daily',
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
    if (!time) return '';
    const date = new Date(time);
    return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
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

const handleSearch = () => {
    form.clearErrors();
    
    let hasError = false;
    if (!form.start_date) {
        form.setError('start_date', 'The from date field is required.');
        hasError = true;
    }
    if (!form.end_date) {
        form.setError('end_date', 'The to date field is required.');
        hasError = true;
    }

    if (hasError) return;

    form.get(route('draws.histories_daily'), {
        preserveScroll: true,
        replace: true,
        showProgress: false,
        preserveState: true
    });
};

const handlePublish = (win: any) => {
    processingWin.value = win;
    publishModal.value = true;
};

const confirmPublish = () => {
    if (!processingWin.value) return;
    router.get(route('draws.histories-publish', processingWin.value.id), {}, {
        onSuccess: () => {
            publishModal.value = false;
            processingWin.value = null;
            toast.success('Draw published successfully.');
        },
        onError: () => toast.error('Failed to publish draw.')
    });
};

const handleDelete = (win: any) => {
    processingWin.value = win;
    deleteModal.value = true;
};

const confirmDelete = () => {
    if (!processingWin.value) return;
    router.get(route('draws.histories-delete', processingWin.value.id), {}, {
        onSuccess: () => {
            deleteModal.value = false;
            processingWin.value = null;
            toast.success('Draw deleted successfully.');
        },
        onError: () => toast.error('Failed to delete draw.')
    });
};

// View image for a single win row (once-mode, mirrors History.vue handleDownload)
const viewImageWin = async (win: any) => {
    const logoImg = await loadLogo(logoUrl);
    const cupImg  = await loadLogo(cupIcon);

    const rows = products.map((product) => ({
        title:         product.title,
        productNumber: product.product_number ?? null,
        numbers:       product.id === win.product_id ? parseNumbers(win.win_number) : [],
    }));

    const resultDate = formatResultDate(win.to_time ?? win.draw_time);
    const canvas = buildResultCanvas(rows, logoImg, cupImg, resultDate);

    const slug = (win.product?.title + ' ' + (win.product?.product_number ?? '')).trim().replace(/\s+/g, '-');
    previewImageUrl.value = canvas.toDataURL('image/png');
    previewFileName.value = `once-result-${slug}-${win.id}.png`;
    previewModal.value = true;
};

// ─── Canvas Helpers (Copied from History.vue) ──────────────────────────────────
const LEFT_PANEL_W = 118;
const RIGHT_PANEL_W = 210;
const ROW_HEIGHT = 50;
const ROW_SPACING = 8;
const CARD_PADDING = 20;
const CARD_CORNER = 22;

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
    const GRAY_GAP   = 10;
    const GRAY_LEFT  = LEFT_PANEL_W + 80;
    const GRAY_TOP   = GRAY_GAP;
    const GRAY_W     = W - GRAY_LEFT - GRAY_GAP;
    const GRAY_H     = H - GRAY_GAP * 2;
    const GRAY_R     = 20;

    ctx.save();
    drawRoundRect(ctx, 0, 0, W, H, CARD_CORNER);
    ctx.clip();

    ctx.fillStyle = '#edf0f3';
    ctx.fillRect(0, 0, W, H);

    ctx.strokeStyle = 'rgba(0,0,0,0.055)';
    ctx.lineWidth = 1;
    for (let i = -H * 2; i < W + H * 2; i += 16) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i + H, H);
        ctx.stroke();
    }

    ctx.save();
    drawRoundRect(ctx, GRAY_LEFT, GRAY_TOP, GRAY_W, GRAY_H, GRAY_R);

    const grayGrad = ctx.createLinearGradient(GRAY_LEFT, GRAY_TOP, W, H);
    grayGrad.addColorStop(0, '#b6c3cc');
    grayGrad.addColorStop(0.5, '#9db0bb');
    grayGrad.addColorStop(1, '#8ea2ae');
    ctx.fillStyle = grayGrad;
    ctx.fill();

    ctx.clip();
    ctx.strokeStyle = 'rgba(255,255,255,0.22)';
    ctx.lineWidth = 1.2;
    for (let i = GRAY_LEFT - H * 2; i < W + H * 2; i += 16) {
        ctx.beginPath();
        ctx.moveTo(i, GRAY_TOP);
        ctx.lineTo(i + GRAY_H, GRAY_TOP + GRAY_H);
        ctx.stroke();
    }
    ctx.restore();
    ctx.restore();

    ctx.strokeStyle = 'rgba(0,0,0,0.14)';
    ctx.lineWidth = 2.5;
    drawRoundRect(ctx, 0, 0, W, H, CARD_CORNER);
    ctx.stroke();
}

function drawLeftPanel(ctx: CanvasRenderingContext2D, H: number, logoImg: HTMLImageElement | null, cupImg: HTMLImageElement | null) {
    const cx = LEFT_PANEL_W / 2;
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

    const textY = H * 0.46;
    ctx.fillStyle = '#0d1a2e';
    ctx.textAlign = 'center';
    ['BUY OUR', 'PRODUCT', 'GET COUPON'].forEach((line, i) => {
        ctx.font = `bold ${i === 2 ? 11 : 12}px Arial, sans-serif`;
        ctx.fillText(line, cx, textY + i * 17);
    });

    if (cupImg) {
        const iconW = 74;
        const iconH = 82;
        ctx.drawImage(cupImg, cx - iconW / 2, H * 0.8 - iconH / 2, iconW, iconH);
    }
}

function drawCouponBadge(ctx: CanvasRenderingContext2D, title: string, productNumber: string | number | null, x: number, y: number, w: number, h: number) {
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
    const titleFontSize = Math.min(h * 0.36, 15);
    const numFontSize   = Math.min(h * 0.72, 30);

    ctx.font = `bold ${titleFontSize}px Arial, sans-serif`;
    const titleW = ctx.measureText(title + (numStr ? ' ' : '')).width;
    ctx.font = `bold ${numFontSize}px Arial, sans-serif`;
    const numW = numStr ? ctx.measureText(numStr).width : 0;
    const totalW = titleW + numW;
    const startX = x + (w - totalW) / 2;

    ctx.font = `bold ${titleFontSize}px Arial, sans-serif`;
    ctx.textAlign = 'left';
    ctx.fillText(title + (numStr ? ' ' : ''), startX, cy + (numFontSize - titleFontSize) * 0.08);

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

    const resH  = 75;
    const giftH = 65;
    const timeH = 50;
    const startY = 100;

    const resY  = startY;
    const giftY = resY  + resH  + BOX_GAP;
    const timeY = giftY + giftH + BOX_GAP;

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

    ctx.fillStyle = '#cc2a2a';
    drawRoundRect(ctx, px, giftY, pw, giftH, 8);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.font = `bold ${Math.min(giftH * 0.3, 13)}px Arial, sans-serif`;
    ctx.fillText('CURRENT GIFT', pcx, giftY + giftH * 0.33);
    ctx.font = `bold ${Math.min(giftH * 0.27, 12)}px Arial, sans-serif`;
    ctx.fillText('AED 500,000', pcx, giftY + giftH * 0.68);

    ctx.fillStyle = '#f4f4f4';
    drawRoundRect(ctx, px, timeY, pw, timeH, 8);
    ctx.fill();
    ctx.strokeStyle = '#cccccc';
    ctx.lineWidth = 1;
    ctx.stroke();

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

function buildResultCanvas(rows: Array<{ title: string; productNumber: string | number | null; numbers: string[] }>, logoImg: HTMLImageElement | null, cupImg: HTMLImageElement | null, resultDate: string): HTMLCanvasElement {
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

    const BADGE_W = 120;
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

        if (row.numbers.length === 0) {
            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            ctx.font = 'italic 15px Arial, sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText('', ballStartX - BALL_R + 5, ballCY);
            ctx.textBaseline = 'alphabetic';
        } else {
            row.numbers.forEach((num, ni) => {
                drawWinBall(ctx, ballStartX + ni * BALL_PITCH, ballCY, BALL_R, num);
            });
        }
    });

    return canvas;
}

const viewImage = async (history: any) => {
    const logoImg = await loadLogo(logoUrl);
    const cupImg = await loadLogo(cupIcon);
    const canvasRows: any[] = [];

    products.forEach(product => {
        const result = history.results[product.id];

        if (Array.isArray(result) && result.length > 0) {
            result.forEach((res: any) => {
                canvasRows.push({
                    title: product.title,
                    productNumber: product.product_number,
                    numbers: parseNumbers(res.numbers),
                });
            });
        } else if (result && !Array.isArray(result)) {
            canvasRows.push({
                title: product.title,
                productNumber: product.product_number,
                numbers: parseNumbers(result.numbers),
            });
        }
    });

    const resultDate = formatResultDate(history.date);
    const canvas = buildResultCanvas(canvasRows, logoImg, cupImg, resultDate);
    
    previewImageUrl.value = canvas.toDataURL('image/png');
    previewFileName.value = `daily-history-${history.date}.png`;
    previewModal.value = true;
};

const confirmDownload = () => {
    if (!previewImageUrl.value) return;
    const link = document.createElement('a');
    link.download = previewFileName.value;
    link.href = previewImageUrl.value;
    link.click();
    previewModal.value = false;
    toast.success('Image downloaded successfully.');
};
</script>

<template>
    <Head title="Daily History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">

                    <!-- Filters -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4 items-end">
                            <!-- Category Filter -->
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">Category</label>
                                <select
                                    v-model="form.draw_type"
                                    class="w-full h-12 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option v-for="cat in categories" :key="cat.draw_type" :value="cat.draw_type">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">From Date</label>
                                <Input v-model="form.start_date" type="date" class="w-full h-12" :class="{'border-red-500': form.errors.start_date}" />
                                <div v-if="form.errors.start_date" class="text-red-500 text-xs mt-1 text-center">{{ form.errors.start_date }}</div>
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">From Time</label>
                                <Input v-model="form.start_time" type="time" class="w-full h-12" />
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">To Date</label>
                                <Input v-model="form.end_date" type="date" class="w-full h-12" :class="{'border-red-500': form.errors.end_date}" />
                                <div v-if="form.errors.end_date" class="text-red-500 text-xs mt-1 text-center">{{ form.errors.end_date }}</div>
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">To Time</label>
                                <Input v-model="form.end_time" type="time" class="w-full h-12" />
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    @click="handleSearch"
                                    class="h-12 px-8 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md transition-all flex items-center gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- ══ DAILY TABLE (draw_type === 'daily') ══ -->
                    <div v-if="filters.draw_type !== 'once'" class="border rounded-lg overflow-x-auto shadow-sm">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[60px]">SL</th>
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[130px]">Date</th>
                                    <th 
                                        v-for="product in products" 
                                        :key="product.id"
                                        class="px-4 py-4 text-center text-sm font-bold text-gray-800 border min-w-[220px]"
                                    >
                                        {{ product.title }} {{ product.product_number }}
                                    </th>
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[100px]">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="histories.length === 0">
                                    <td :colspan="products.length + 3" class="px-6 py-10 text-center text-gray-500 italic">
                                        Please select date range to view daily history.
                                    </td>
                                </tr>
                                <tr v-for="(history, index) in histories" :key="index" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-6 text-center text-sm font-bold text-gray-900 border">
                                        {{ index + 1 }}
                                    </td>

                                    <td class="px-4 py-6 text-center text-sm font-medium text-gray-700 border whitespace-nowrap">
                                        {{ formatDate(history.date) }}
                                    </td>

                                    <td 
                                        v-for="product in products" 
                                        :key="product.id"
                                        class="px-4 py-6 border text-center"
                                    >
                                        <div class="flex flex-col items-center gap-2">
                                            <!-- Once product: results is an array of wins -->
                                            <template v-if="Array.isArray(history.results[product.id]) && history.results[product.id].length > 0">
                                                <div v-for="(res, ridx) in history.results[product.id]" :key="ridx" class="flex flex-col items-center pb-2 last:pb-0 border-b last:border-b-0 border-gray-100 w-full">
                                                    <div class="flex gap-2 justify-center flex-nowrap mb-1">
                                                        <div
                                                            v-for="(number, idx) in parseNumbers(res.numbers)"
                                                            :key="idx"
                                                            class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-center font-bold border-2 border-gray-800 text-gray-900 bg-white shadow-sm"
                                                        >
                                                            {{ number }}
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col items-center gap-1 group">
                                                        <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded shadow-sm">
                                                            {{ formatTime(res.time) }}
                                                        </span>
                                                        <div v-if="!res.publish" class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <button @click="handlePublish(res)" class="text-[10px] px-1.5 py-0.5 bg-green-500 text-white rounded hover:bg-green-600 font-bold transition-colors">Publish</button>
                                                            <button @click="handleDelete(res)" class="text-[10px] px-1.5 py-0.5 bg-red-500 text-white rounded hover:bg-red-600 font-bold transition-colors">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <!-- Daily product: results is a single object -->
                                            <template v-else-if="history.results[product.id] && !Array.isArray(history.results[product.id])">
                                                <div class="flex gap-2 justify-center flex-nowrap mb-1">
                                                    <div
                                                        v-for="(number, idx) in parseNumbers(history.results[product.id].numbers)"
                                                        :key="idx"
                                                        class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-center font-bold border-2 border-gray-800 text-gray-900 bg-white shadow-sm"
                                                    >
                                                        {{ number }}
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-center gap-1 group">
                                                    <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded shadow-sm">
                                                        {{ formatTime(history.results[product.id].time) }}
                                                    </span>
                                                    <div v-if="!history.results[product.id].publish" class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <button @click="handlePublish(history.results[product.id])" class="text-[10px] px-1.5 py-0.5 bg-green-500 text-white rounded hover:bg-green-600 font-bold transition-colors">Publish</button>
                                                        <button @click="handleDelete(history.results[product.id])" class="text-[10px] px-1.5 py-0.5 bg-red-500 text-white rounded hover:bg-red-600 font-bold transition-colors">Delete</button>
                                                    </div>
                                                </div>
                                            </template>
                                            <span v-else class="text-gray-300 text-xs">No result</span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-6 border">
                                        <div class="flex gap-2 justify-center">
                                            <Button
                                                size="sm"
                                                @click="viewImage(history)"
                                                class="bg-indigo-500 hover:bg-indigo-600 text-white p-2 h-9 w-9 rounded-md shadow"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ══ ONCE TABLE (draw_type === 'once') — mirrors History.vue ══ -->
                    <div v-else class="border rounded-lg overflow-y-auto shadow-sm">
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
                                <tr v-if="!wins?.data?.length">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                        Please select date range to view results.
                                    </td>
                                </tr>
                                <tr v-for="(win, index) in wins?.data" :key="win.id" class="group">
                                    <td class="px-3 text-sm md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r">
                                        {{ (wins.current_page - 1) * wins.per_page + index + 1 }}
                                    </td>
                                    <td class="px-3 text-sm md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r text-center">
                                        {{ win?.product?.title }} {{ win?.product?.product_number }}
                                    </td>
                                    <td class="px-6 py-4 border-r text-center">
                                        <p class="text-lg">{{ formatDate(win.to_time) }}</p>
                                        <p class="font-bold text-md" v-if="win.to_time">{{ formatTime(win.to_time) }}</p>
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
                                                @click="viewImageWin(win)"
                                                variant="default"
                                                size="sm"
                                                class="px-2 py-1 bg-blue-500 text-white hover:bg-blue-600"
                                            >
                                                View
                                            </Button>
                                            
                                            <template v-if="!win.publish">
                                                <div class="flex gap-2 transition-opacity">
                                                    <Button
                                                        @click="handlePublish(win)"
                                                        variant="default"
                                                        size="sm"
                                                        class="px-2 py-1 bg-green-500 text-white hover:bg-green-600"
                                                    >
                                                        Publish
                                                    </Button>
                                                    
                                                    <Button
                                                        @click="handleDelete(win)"
                                                        variant="destructive"
                                                        size="sm"
                                                        class="px-2 py-1"
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- Preview Dialog -->
        <Dialog v-model:open="previewModal">
            <DialogContent class="max-w-6xl max-h-[95vh] overflow-auto w-[95vw]">
                <DialogHeader>
                    <DialogTitle>Preview Daily History</DialogTitle>
                    <DialogDescription>
                        Review the image before downloading.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="flex justify-center py-4">
                    <img 
                        v-if="previewImageUrl" 
                        :src="previewImageUrl" 
                        alt="Daily History Preview" 
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
                        Download Image
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

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Confirm Deletion</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this win record? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Confirm Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
table {
    border-spacing: 0;
}
th, td {
    border: 1px solid #e5e7eb;
}
</style>
