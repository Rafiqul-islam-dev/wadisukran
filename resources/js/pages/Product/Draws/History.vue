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

const { wins, products, logoUrl } = defineProps<{
    wins: any;
    products: Array<any>;
    logoUrl: string;
}>();

const WHATSAPP_TO = '8801911115398';

const form = useForm({
    product_id: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: ''
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

const formatTimeImage = (time: string) => {
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

const handleSearch = () => {
    form.get(route('draws.histories'), {
        preserveScroll: true,
        replace: true,
        showProgress: false,
        preserveState: true
    });
};

// ─── Canvas Helpers ────────────────────────────────────────────────────────────

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

function drawBall(ctx: CanvasRenderingContext2D, cx: number, cy: number, radius: number, text: string) {
    ctx.shadowColor = 'rgba(0,0,0,0.45)';
    ctx.shadowBlur = 10;
    ctx.shadowOffsetX = 2;
    ctx.shadowOffsetY = 3;

    const ballGrad = ctx.createRadialGradient(cx - radius * 0.3, cy - radius * 0.3, radius * 0.1, cx, cy, radius);
    ballGrad.addColorStop(0, '#fff8ec');
    ballGrad.addColorStop(0.6, '#f5e6c8');
    ballGrad.addColorStop(1, '#e8d0a0');
    ctx.fillStyle = ballGrad;
    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, Math.PI * 2);
    ctx.fill();

    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;

    ctx.fillStyle = '#1a1a1a';
    ctx.font = `bold ${radius * 0.85}px Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, cx, cy);
    ctx.textBaseline = 'alphabetic';
}

function drawProductBadge(ctx: CanvasRenderingContext2D, title: string, x: number, y: number, badgeW: number, badgeH: number) {
    const badgeGrad = ctx.createLinearGradient(x, y, x + badgeW, y);
    badgeGrad.addColorStop(0, '#1a0a3d');
    badgeGrad.addColorStop(1, '#2d1b69');
    ctx.fillStyle = badgeGrad;
    drawRoundRect(ctx, x, y, badgeW, badgeH, badgeH / 2);
    ctx.fill();

    ctx.strokeStyle = 'rgba(255,255,255,0.35)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.fillStyle = '#ffffff';
    ctx.font = `bold ${Math.min(badgeH * 0.38, 22)}px Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(title, x + badgeW / 2, y + badgeH / 2);
    ctx.textBaseline = 'alphabetic';
}

function drawHeader(ctx: CanvasRenderingContext2D, W: number, logoImg: HTMLImageElement | null, dateLabel: string) {
    const logoSize = 80;
    const logoX = 18;
    const logoY = 14;

    if (logoImg) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, logoSize / 2, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(logoImg, logoX, logoY, logoSize, logoSize);
        ctx.restore();
    } else {
        ctx.beginPath();
        ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, logoSize / 2, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(255,255,255,0.15)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.4)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('WIN', logoX + logoSize / 2, logoY + logoSize / 2);
        ctx.textBaseline = 'alphabetic';
    }

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 28px Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('DAILY PRODUCT OFFER ACTIVITIES RESULT', (W / 2) - 40, 62);

    const dateBoxW = 220;
    const dateBoxH = 36;
    const dateBoxX = W - dateBoxW - 16;
    const dateBoxY = 16;

    ctx.fillStyle = '#ffffff';
    drawRoundRect(ctx, dateBoxX, dateBoxY, dateBoxW, dateBoxH, 6);
    ctx.fill();

    ctx.fillStyle = '#1a1a1a';
    ctx.font = 'bold 15px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(dateLabel, dateBoxX + dateBoxW / 2, dateBoxY + dateBoxH / 2);
    ctx.textBaseline = 'alphabetic';
}

function drawBackground(ctx: CanvasRenderingContext2D, W: number, H: number) {
    const bgGrad = ctx.createLinearGradient(0, 0, W, H);
    bgGrad.addColorStop(0, '#2d1b69');
    bgGrad.addColorStop(0.5, '#1a0a3d');
    bgGrad.addColorStop(1, '#0f0520');
    ctx.fillStyle = bgGrad;
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = 'rgba(255,255,255,0.015)';
    for (let i = 0; i < W; i += 40) {
        ctx.fillRect(i, 0, 1, H);
    }
}

function drawProductRow(
    ctx: CanvasRenderingContext2D,
    productTitle: string,
    numbers: string[],
    fromTime: string | null,
    toTime: string | null,
    W: number,
    rowY: number,
    rowH: number
) {
    const rowX = 30;
    const rowW = W - 60;
    const rowRadius = 40;

    ctx.shadowColor = 'rgba(0,0,0,0.4)';
    ctx.shadowBlur = 14;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 6;

    const rowGrad = ctx.createLinearGradient(rowX, rowY, rowX, rowY + rowH);
    rowGrad.addColorStop(0, '#7e22ce');
    rowGrad.addColorStop(1, '#6b21a8');
    ctx.fillStyle = rowGrad;
    drawRoundRect(ctx, rowX, rowY, rowW, rowH, rowRadius);
    ctx.fill();

    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;

    const centerY = rowY + rowH / 2;

    const badgeX = rowX + 20;
    const badgeW = 160;
    const badgeH = 60;
    drawProductBadge(ctx, productTitle, badgeX, centerY - badgeH / 2, badgeW, badgeH);

    if (numbers.length > 0) {
        const ballRadius = 36;
        const ballSpacing = 84;
        const ballStartX = badgeX + badgeW + 40 + ballRadius;

        numbers.forEach((num, i) => {
            drawBall(ctx, ballStartX + i * ballSpacing, centerY, ballRadius, num);
        });
    }

    if (numbers.length > 0 && fromTime && toTime) {
        ctx.fillStyle = 'rgba(255,255,255,0.75)';
        ctx.font = 'bold 13px Arial';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillText(`${formatTimeImage(toTime)}`, rowX + rowW - 20, centerY);
        ctx.textBaseline = 'alphabetic';
    }
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

function createCanvas(rowCount: number): {
    canvas: HTMLCanvasElement;
    ctx: CanvasRenderingContext2D;
    W: number;
    H: number;
    HEADER_H: number;
    ROW_H: number;
    ROW_GAP: number;
} {
    const W = 1200;
    const HEADER_H = 120;
    const ROW_H = 90;
    const ROW_GAP = 10;
    const FOOTER_H = 30;
    const H = HEADER_H + rowCount * (ROW_H + ROW_GAP) + FOOTER_H;

    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;

    return {
        canvas,
        ctx: canvas.getContext('2d')!,
        W,
        H,
        HEADER_H,
        ROW_H,
        ROW_GAP
    };
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
            productTitle: product.title,
            numbers: isMatch ? parseNumbers(win.win_number) : [],
            fromTime: isMatch ? (win.from_time ?? null) : null,
            toTime: isMatch ? (win.to_time ?? null) : null,
        };
    });

    const { canvas, ctx, W, H, HEADER_H, ROW_H, ROW_GAP } = createCanvas(rows.length);

    drawBackground(ctx, W, H);

    const dateLabel = `${formatDate(win.draw_time)}  ${win.to_time ? formatTimeImage(win.to_time) : ''}`;
    drawHeader(ctx, W, logoImg, dateLabel);

    let currentY = HEADER_H;
    rows.forEach((row) => {
        drawProductRow(ctx, row.productTitle, row.numbers, row.fromTime, row.toTime, W, currentY, ROW_H);
        currentY += ROW_H + ROW_GAP;
    });

    const fileName = `draw-result-${(win.product?.title ?? 'product').replace(/\s+/g, '-')}-${win.id}.png`;

    // image download
    const link = document.createElement('a');
    link.download = fileName;
    link.href = canvas.toDataURL('image/png');
    link.click();

    // whatsapp text open
    const numbers = parseNumbers(win.win_number).join(', ') || 'N/A';
    const message = `Draw result ready.
Product: ${win.product?.title ?? 'N/A'}
Date: ${formatDate(win.draw_time)}
Time: ${ win.to_time ? `${formatTime(win.to_time)}` : 'N/A'}
Win Number: ${numbers}`;

    setTimeout(() => {
        openWhatsAppChat(message);
    }, 800);

    toast.success('Image downloaded. WhatsApp chat opened.');
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
        productTitle: win.product?.title ?? 'Unknown',
        numbers: parseNumbers(win.win_number),
        fromTime: win.from_time ?? null,
        toTime: win.to_time ?? null,
    }));

    const { canvas, ctx, W, H, HEADER_H, ROW_H, ROW_GAP } = createCanvas(rows.length);

    drawBackground(ctx, W, H);

    const latestWin = allWins[0];
    const dateLabel = `${formatDate(latestWin.draw_time)}  ${latestWin.to_time ? formatTimeImage(latestWin.to_time) : ''}`;
    drawHeader(ctx, W, logoImg, dateLabel);

    let currentY = HEADER_H;
    rows.forEach((row: any) => {
        drawProductRow(ctx, row.productTitle, row.numbers, row.fromTime, row.toTime, W, currentY, ROW_H);
        currentY += ROW_H + ROW_GAP;
    });

    const fileName = `draw-results-all-${Date.now()}.png`;

    const link = document.createElement('a');
    link.download = fileName;
    link.href = canvas.toDataURL('image/png');
    link.click();

    toast.success(`Downloaded ${allWins.length} results and opened WhatsApp.`);
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
                                        {{ product.title }}
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

                    <!-- Download All Button -->
                    <div class="flex justify-end mb-4">
                        <Button
                            @click="handleDownloadAll"
                            variant="default"
                            size="lg"
                            class="cursor-pointer px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                            </svg>
                            Download All
                        </Button>
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
                                <tr v-for="(win, index) in wins?.data" :key="index">
                                    <td class="px-3 text-sm md:text-md md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r">
                                        {{ (wins?.current_page - 1) * wins?.per_page + index + 1 }}
                                    </td>

                                    <td class="px-3 text-sm md:text-md md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r text-center">
                                        {{ win?.product?.title }}
                                    </td>

                                    <td class="px-6 py-4 border-r text-center">
                                        <p class="text-lg">{{ formatDate(win.draw_time) }}</p>
                                        <p class="font-bold text-md" v-if="win.from_time && win.to_time">
                                            {{ formatTime(win.from_time) }} - {{ formatTime(win.to_time) }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4 border-r">
                                        <div class="flex gap-2 justify-center">
                                            <div
                                                v-for="(number, idx) in parseNumbers(win.win_number)"
                                                :key="idx"
                                                class="w-10 h-10 rounded-lg flex flex-col items-center justify-center text-center font-bold border-orange-700 text-black opacity-100 bg-orange-100 border-2"
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
                                                size="lg"
                                                class="cursor-pointer px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-xl hover:from-blue-600 hover:to-cyan-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                                </svg>
                                                Download
                                            </Button>

                                            <Button
                                                v-if="can('draw history delete')"
                                                @click="deleteHistory(win.id)"
                                                size="sm"
                                                class="bg-red-500 hover:bg-red-700 cursor-pointer"
                                            >
                                                Delete
                                            </Button>
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
    </AppLayout>
</template>