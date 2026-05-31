<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import TelegramMockup from "@/components/TelegramMockup.vue";

const activeTab = ref("bot");
const isDark = ref(true);

watch(isDark, (val) => {
    if (val) {
        document.documentElement.classList.add("dark");
        localStorage.setItem("theme", "dark");
    } else {
        document.documentElement.classList.remove("dark");
        localStorage.setItem("theme", "light");
    }
});

onMounted(() => {
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        isDark.value = false;
        document.documentElement.classList.remove("dark");
    } else {
        isDark.value = true;
        document.documentElement.classList.add("dark");
    }
});

const toastVisible = ref(false);
const toastMsg = ref("");
const toastType = ref<"success" | "error">("success");
let toastTimer: ReturnType<typeof setTimeout>;

function showToast(msg: string, type: "success" | "error" = "success") {
    toastMsg.value = msg;
    toastType.value = type;
    toastVisible.value = true;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastVisible.value = false;
    }, 4000);
}

interface CaptionItem {
    key: string;
    content: string;
}
interface ConfigData {
    id: number;
    order: {
        string: string;
        exp_order: number;
        prefix_order: string;
        count_pending: number;
        transaksi_delay: number;
        length_random_order: number;
    };
    bot: { image: string; contact: string };
    payments: {
        wijayapay: { status: boolean; api_key: string; code_merchant: string };
    };
    captions: { orders: CaptionItem[]; others_button: CaptionItem[] };
}

const props = defineProps<{ config: ConfigData; adminName: string }>();

const form = ref<ConfigData>(JSON.parse(JSON.stringify(props.config)));
const saving = ref(false);
const errors = ref<Record<string, string[]>>({});
const showApiKey = ref(false);

const captionMeta: Record<
    string,
    { name: string; desc: string; placeholders: string[] }
> = {
    menu_start: {
        name: "Menu Start (Awal)",
        desc: "Pesan saat /start",
        placeholders: ["{greeting}", "{firstname}"],
    },
    menu_order: {
        name: "Menu Daftar Game",
        desc: "Saat menekan Mulai Pesan",
        placeholders: [],
    },
    menu_providers: {
        name: "Pilih Provider",
        desc: "Setelah memilih game",
        placeholders: ["{game}"],
    },
    menu_denoms: {
        name: "Pilih Denom",
        desc: "Setelah memilih produk",
        placeholders: ["{produk}"],
    },
    menu_confirm_order: {
        name: "Konfirmasi Beli",
        desc: "Konfirmasi pesanan",
        placeholders: ["{produk}", "{denom}", "{price}"],
    },
    invoice_order: {
        name: "Invoice Tagihan",
        desc: "Struk pembayaran",
        placeholders: [
            "{invoice_id}",
            "{status_pembayaran}",
            "{status_proses}",
            "{expired_at}",
            "{game}",
            "{provider}",
            "{denom}",
            "{price}",
            "{payment}",
            "{name_account}",
            "{number_account}",
            "{virtual_account}",
            "{nomor_pembayaran}",
            "{instruksi}",
        ],
    },
    cancel_order: {
        name: "Berhasil Batal",
        desc: "Pesanan dibatalkan",
        placeholders: ["{invoice_id}"],
    },
    confirm_cancel_order: {
        name: "Konfirmasi Batal",
        desc: "Konfirmasi pembatalan",
        placeholders: ["{invoice_id}", "{game}", "{denom}", "{price}"],
    },
    menu_history: {
        name: "Riwayat Transaksi",
        desc: "Daftar transaksi user",
        placeholders: ["{page}", "{total_pages}", "{list_transactions}"],
    },
    menu_history_empty: {
        name: "Riwayat Kosong",
        desc: "Belum ada riwayat",
        placeholders: [],
    },
    menu_account: {
        name: "Informasi Akun",
        desc: "Profil user",
        placeholders: [
            "{user_id}",
            "{name}",
            "{username}",
            "{role}",
            "{registered_at}",
        ],
    },
    menu_leaderboard_weekly: {
        name: "Leaderboard Mingguan",
        desc: "Top buyer 7 hari",
        placeholders: ["{list_rank}"],
    },
    menu_leaderboard_monthly: {
        name: "Leaderboard Bulanan",
        desc: "Top buyer 30 hari",
        placeholders: ["{list_rank}"],
    },
    menu_announcement: {
        name: "Info Pengumuman",
        desc: "Teks promosi",
        placeholders: [],
    },
    menu_resetlicense: {
        name: "Menu Reset Lisensi",
        desc: "Pilih produk lisensi",
        placeholders: [],
    },
    menu_select_resetlicense: {
        name: "Form Reset Lisensi",
        desc: "Input kunci lisensi",
        placeholders: ["{provider}"],
    },
};

function getMeta(key: string) {
    return (
        captionMeta[key] ?? { name: key, desc: "Template", placeholders: [] }
    );
}

const invoicePreview = computed(() => {
    const p = form.value.order.prefix_order || "";
    const l = form.value.order.length_random_order || 8;
    const s = form.value.order.string || "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let r = "";
    for (let i = 0; i < l; i++)
        r += s.charAt(Math.floor(Math.random() * s.length));
    return p + r;
});

function insertTag(
    textareaId: string,
    tag: string,
    captionArr: CaptionItem[],
    index: number,
) {
    const el = document.getElementById(
        textareaId,
    ) as HTMLTextAreaElement | null;
    if (!el) return;
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const val = el.value;
    el.value = val.substring(0, start) + tag + val.substring(end);
    captionArr[index].content = el.value;
    el.focus();
    el.setSelectionRange(start + tag.length, start + tag.length);
}

async function saveConfig() {
    saving.value = true;
    errors.value = {};
    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/admin/config", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Inertia": "false",
            },
            body: JSON.stringify(form.value),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            showToast(data.message, "success");
        } else if (res.status === 422) {
            errors.value = data.errors || {};
            showToast(data.message, "error");
        } else {
            showToast(data.message || "Terjadi kesalahan.", "error");
        }
    } catch {
        showToast("Tidak dapat menghubungi server.", "error");
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <!-- Toast Notification -->
    <Transition name="toast">
        <div
            v-if="toastVisible"
            class="fixed top-6 right-6 z-[100] max-w-sm w-full"
        >
            <div
                :class="[
                    'flex items-center gap-3 rounded-xl border px-5 py-4 shadow-2xl backdrop-blur-md transition-all',
                    toastType === 'success'
                        ? 'border-emerald-500/30 bg-emerald-950/90 text-emerald-100'
                        : 'border-red-500/30 bg-red-950/90 text-red-100',
                ]"
            >
                <span v-if="toastType === 'success'" class="text-lg">✅</span>
                <span v-else class="text-lg">❌</span>
                <div class="flex-1">
                    <p class="text-sm font-semibold">
                        {{ toastType === "success" ? "Berhasil!" : "Gagal!" }}
                    </p>
                    <p class="text-xs opacity-80">{{ toastMsg }}</p>
                </div>
                <button
                    @click="toastVisible = false"
                    class="opacity-50 hover:opacity-100 text-xs"
                >
                    ✕
                </button>
            </div>
        </div>
    </Transition>

    <div class="flex min-h-screen bg-background text-foreground transition-colors duration-300">
        <!-- Sidebar -->
        <aside
            class="hidden lg:flex w-64 flex-col border-r border-border bg-card p-5 fixed h-screen z-30"
        >
            <div class="flex items-center gap-3 mb-6">
                <Avatar class="h-9 w-9 border-2 border-primary/20">
                    <AvatarImage :src="form.bot.image" />
                    <AvatarFallback>BC</AvatarFallback>
                </Avatar>
                <div>
                    <h2 class="text-sm font-bold tracking-tight">Bot Admin</h2>
                    <p class="text-[10px] text-muted-foreground">
                        Panel Konfigurasi
                    </p>
                </div>
            </div>

            <nav class="flex-1 space-y-1">
                <button
                    @click="activeTab = 'bot'"
                    :class="[
                        'w-full flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold transition-all',
                        activeTab === 'bot'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 0-10 10v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7a10 10 0 0 0-10-10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    Bot & Gateway
                </button>
                <button
                    @click="activeTab = 'order'"
                    :class="[
                        'w-full flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold transition-all',
                        activeTab === 'order'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Sistem Transaksi
                </button>
                <button
                    @click="activeTab = 'tpl-order'"
                    :class="[
                        'w-full flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold transition-all',
                        activeTab === 'tpl-order'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Template Order
                </button>
                <button
                    @click="activeTab = 'tpl-other'"
                    :class="[
                        'w-full flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold transition-all',
                        activeTab === 'tpl-other'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    Template Lainnya
                </button>
            </nav>

            <div class="mt-auto space-y-2">
                <!-- Theme Switcher -->
                <div 
                    @click="isDark = !isDark"
                    class="flex items-center justify-between rounded-lg border border-border bg-accent/30 px-3 py-2 text-xs font-medium cursor-pointer select-none"
                >
                    <span class="text-muted-foreground flex items-center gap-2">
                        <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                        {{ isDark ? 'Mode Gelap' : 'Mode Terang' }}
                    </span>
                    <Switch :checked="isDark" class="scale-90 pointer-events-none" />
                </div>

                <Separator class="opacity-50" />
                
                <div class="flex items-center gap-3 py-1">
                    <Avatar class="h-7 w-7">
                        <AvatarFallback class="bg-primary/10 text-primary text-[10px] font-bold">
                            {{ adminName.substring(0, 2).toUpperCase() }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate">{{ adminName }}</p>
                        <p class="text-[9px] text-muted-foreground">Owner</p>
                    </div>
                </div>
                
                <a
                    href="/admin/config/logout"
                    class="flex items-center justify-center gap-1.5 rounded-lg border border-destructive/20 bg-destructive/5 hover:bg-destructive/10 px-3 py-2 text-xs font-semibold text-destructive transition-colors w-full"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 lg:ml-64 min-h-screen pb-24">
            <div
                class="sticky top-0 z-10 border-b border-border bg-background/80 backdrop-blur-md px-6 py-4"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">
                            Konfigurasi Bot
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            Kelola setelan bot, gateway, dan template pesan.
                        </p>
                    </div>
                    <Badge variant="outline" class="gap-1.5 text-[10px] px-2 py-0.5 border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-500/5">
                        <span class="relative flex h-1.5 w-1.5"
                            ><span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                            ></span
                            ><span
                                class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"
                            ></span
                        ></span>
                        Online
                    </Badge>
                </div>
            </div>

            <div class="p-6 max-w-5xl">
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList
                        class="mb-6 grid w-full grid-cols-4 lg:w-auto lg:inline-flex border border-border/50 bg-muted/30"
                    >
                        <TabsTrigger value="bot" class="text-xs">🤖 Bot & Gateway</TabsTrigger>
                        <TabsTrigger value="order" class="text-xs">⚙️ Transaksi</TabsTrigger>
                        <TabsTrigger value="tpl-order" class="text-xs">💬 Order</TabsTrigger>
                        <TabsTrigger value="tpl-other" class="text-xs">💬 Lainnya</TabsTrigger>
                    </TabsList>

                    <!-- TAB: Bot & Gateway -->
                    <TabsContent value="bot" class="space-y-4 outline-none">
                        <Card class="border border-border/50 shadow-sm">
                            <CardHeader class="p-4 sm:p-5 pb-3">
                                <CardTitle class="text-sm font-bold">Profil & Identitas Bot</CardTitle>
                                <CardDescription class="text-xs text-muted-foreground"
                                    >Gambar utama bot dan link kontak support.</CardDescription
                                >
                            </CardHeader>
                            <CardContent class="p-4 sm:p-5 pt-0 space-y-3">
                                <div class="flex items-center gap-4">
                                    <img
                                        :src="form.bot.image"
                                        class="h-12 w-12 rounded-lg border border-border/50 object-cover bg-muted"
                                        @error="
                                            (
                                                $event.target as HTMLImageElement
                                            ).src = 'https://placehold.co/48'
                                        "
                                    />
                                    <div class="flex-1 space-y-1">
                                        <Label for="bot-image" class="text-xs font-semibold text-muted-foreground"
                                            >URL Logo Bot</Label
                                        >
                                        <Input
                                            id="bot-image"
                                            v-model="form.bot.image"
                                            placeholder="https://..."
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <Label for="bot-contact" class="text-xs font-semibold text-muted-foreground"
                                        >Link Kontak Support</Label
                                    >
                                    <Input
                                        id="bot-contact"
                                        v-model="form.bot.contact"
                                        placeholder="https://t.me/username"
                                        class="h-9 text-xs"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border border-border/50 shadow-sm">
                            <CardHeader class="p-4 sm:p-5 pb-3">
                                <CardTitle class="text-sm font-bold">Integrasi WijayaPay</CardTitle>
                                <CardDescription class="text-xs text-muted-foreground"
                                    >Setelan payment gateway otomatis.</CardDescription
                                >
                            </CardHeader>
                            <CardContent class="p-4 sm:p-5 pt-0 space-y-3">
                                <div
                                    class="flex items-center justify-between rounded-lg border border-border/40 p-3 bg-muted/20"
                                >
                                    <div>
                                        <p class="text-xs font-bold">
                                            Status Gateway
                                        </p>
                                        <p
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            Aktifkan/nonaktifkan WijayaPay.
                                        </p>
                                    </div>
                                    <Switch
                                        :checked="
                                            form.payments.wijayapay.status
                                        "
                                        @update:checked="
                                            form.payments.wijayapay.status =
                                                $event
                                        "
                                        class="scale-90"
                                    />
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <Label for="wp-merchant" class="text-xs font-semibold text-muted-foreground"
                                            >Code Merchant</Label
                                        >
                                        <Input
                                            id="wp-merchant"
                                            v-model="
                                                form.payments.wijayapay
                                                    .code_merchant
                                            "
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label for="wp-key" class="text-xs font-semibold text-muted-foreground">API Key</Label>
                                        <div class="relative">
                                            <Input
                                                id="wp-key"
                                                :type="
                                                    showApiKey
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                v-model="
                                                    form.payments.wijayapay
                                                        .api_key
                                                "
                                                class="pr-10 h-9 text-xs"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    showApiKey = !showApiKey
                                                "
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                            >
                                                <svg
                                                    v-if="!showApiKey"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path
                                                        d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                                                    />
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3"
                                                    />
                                                </svg>
                                                <svg
                                                    v-else
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path
                                                        d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"
                                                    />
                                                    <path
                                                        d="M14.084 14.158a3 3 0 0 1-4.242-4.242"
                                                    />
                                                    <path
                                                        d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"
                                                    />
                                                    <path d="m2 2 20 20" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- TAB: Transaksi -->
                    <TabsContent value="order" class="space-y-4 outline-none">
                        <Card class="border border-border/50 shadow-sm">
                            <CardHeader class="p-4 sm:p-5 pb-3">
                                <CardTitle class="text-sm font-bold">Sistem Order & Keamanan</CardTitle>
                                <CardDescription class="text-xs text-muted-foreground"
                                    >Generator invoice, limit pending, dan anti-spam.</CardDescription
                                >
                            </CardHeader>
                            <CardContent class="p-4 sm:p-5 pt-0 space-y-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <Label for="ord-prefix" class="text-xs font-semibold text-muted-foreground"
                                            >Prefix Invoice</Label
                                        >
                                        <Input
                                            id="ord-prefix"
                                            v-model="form.order.prefix_order"
                                            placeholder="KM-"
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label for="ord-len" class="text-xs font-semibold text-muted-foreground"
                                            >Panjang Kode Acak</Label
                                        >
                                        <Input
                                            id="ord-len"
                                            type="number"
                                            v-model.number="
                                                form.order.length_random_order
                                            "
                                            :min="4"
                                            :max="30"
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <Label for="ord-str" class="text-xs font-semibold text-muted-foreground"
                                        >Karakter Pengacak</Label
                                    >
                                    <Input
                                        id="ord-str"
                                        v-model="form.order.string"
                                        class="font-mono text-xs h-9"
                                    />
                                    <p class="text-[10px] text-muted-foreground flex items-center gap-1.5 mt-1">
                                        <span>Preview Invoice acak:</span>
                                        <code class="font-mono font-bold bg-muted px-1.5 py-0.5 rounded text-primary">{{
                                            invoicePreview
                                        }}</code>
                                    </p>
                                </div>
                                <Separator class="my-2 opacity-50" />
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="space-y-1">
                                        <Label for="ord-exp" class="text-xs font-semibold text-muted-foreground"
                                            >Expire (Menit)</Label
                                        >
                                        <Input
                                            id="ord-exp"
                                            type="number"
                                            v-model.number="
                                                form.order.exp_order
                                            "
                                            :min="1"
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label for="ord-pending" class="text-xs font-semibold text-muted-foreground"
                                            >Maks Pending</Label
                                        >
                                        <Input
                                            id="ord-pending"
                                            type="number"
                                            v-model.number="
                                                form.order.count_pending
                                            "
                                            :min="1"
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label for="ord-delay" class="text-xs font-semibold text-muted-foreground"
                                            >Anti-Spam (Detik)</Label
                                        >
                                        <Input
                                            id="ord-delay"
                                            type="number"
                                            v-model.number="
                                                form.order.transaksi_delay
                                            "
                                            :min="0"
                                            class="h-9 text-xs"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- TAB: Template Order -->
                    <TabsContent value="tpl-order" class="space-y-4 outline-none">
                        <Card
                            v-for="(cap, idx) in form.captions.orders"
                            :key="cap.key"
                            class="border border-border/50 shadow-sm"
                        >
                            <CardHeader class="p-4 sm:p-5 pb-3">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                    {{ getMeta(cap.key).name }}
                                </CardTitle>
                                <CardDescription class="text-xs text-muted-foreground">{{
                                    getMeta(cap.key).desc
                                }}</CardDescription>
                            </CardHeader>
                            <CardContent class="p-4 sm:p-5 pt-0 space-y-3">
                                <div
                                    v-if="getMeta(cap.key).placeholders.length"
                                    class="flex flex-wrap gap-1 bg-muted/30 p-2 rounded-lg border border-border/30"
                                >
                                    <Badge
                                        v-for="tag in getMeta(cap.key)
                                            .placeholders"
                                        :key="tag"
                                        variant="secondary"
                                        class="cursor-pointer font-mono text-[9px] py-0.5 px-1.5 bg-background border border-border/50 hover:bg-primary hover:text-primary-foreground transition-all duration-200"
                                        @click="
                                            insertTag(
                                                'cap-order-' + idx,
                                                tag,
                                                form.captions.orders,
                                                idx,
                                            )
                                        "
                                    >
                                        {{ tag }}
                                    </Badge>
                                </div>
                                <div class="grid gap-3 lg:grid-cols-2">
                                    <Textarea
                                        :id="'cap-order-' + idx"
                                        v-model="cap.content"
                                        class="font-mono text-xs min-h-[110px] leading-relaxed p-3 focus-visible:ring-1"
                                    />
                                    <TelegramMockup
                                        :content="cap.content"
                                        :image="
                                            [
                                                'menu_start',
                                                'cancel_order',
                                            ].includes(cap.key)
                                                ? form.bot.image
                                                : undefined
                                        "
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- TAB: Template Lainnya -->
                    <TabsContent value="tpl-other" class="space-y-4 outline-none">
                        <Card
                            v-for="(cap, idx) in form.captions.others_button"
                            :key="cap.key"
                            class="border border-border/50 shadow-sm"
                        >
                            <CardHeader class="p-4 sm:p-5 pb-3">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                    {{ getMeta(cap.key).name }}
                                </CardTitle>
                                <CardDescription class="text-xs text-muted-foreground">{{
                                    getMeta(cap.key).desc
                                }}</CardDescription>
                            </CardHeader>
                            <CardContent class="p-4 sm:p-5 pt-0 space-y-3">
                                <div
                                    v-if="getMeta(cap.key).placeholders.length"
                                    class="flex flex-wrap gap-1 bg-muted/30 p-2 rounded-lg border border-border/30"
                                >
                                    <Badge
                                        v-for="tag in getMeta(cap.key)
                                            .placeholders"
                                        :key="tag"
                                        variant="secondary"
                                        class="cursor-pointer font-mono text-[9px] py-0.5 px-1.5 bg-background border border-border/50 hover:bg-primary hover:text-primary-foreground transition-all duration-200"
                                        @click="
                                            insertTag(
                                                'cap-other-' + idx,
                                                tag,
                                                form.captions.others_button,
                                                idx,
                                            )
                                        "
                                    >
                                        {{ tag }}
                                    </Badge>
                                </div>
                                <div class="grid gap-3 lg:grid-cols-2">
                                    <Textarea
                                        :id="'cap-other-' + idx"
                                        v-model="cap.content"
                                        class="font-mono text-xs min-h-[110px] leading-relaxed p-3 focus-visible:ring-1"
                                    />
                                    <TelegramMockup :content="cap.content" />
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Save Bar -->
            <div
                class="fixed bottom-0 left-0 lg:left-64 right-0 border-t border-border bg-background/85 backdrop-blur-md px-6 py-3.5 z-20 transition-all duration-300"
            >
                <div class="flex justify-end max-w-5xl">
                    <Button
                        @click="saveConfig"
                        :disabled="saving"
                        size="default"
                        class="min-w-[160px] h-9 text-xs font-semibold shadow-sm"
                    >
                        <svg
                            v-if="saving"
                            class="mr-2 h-3.5 w-3.5 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                        {{ saving ? "Menyimpan..." : "💾 Simpan Konfigurasi" }}
                    </Button>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.toast-enter-active {
    transition: all 0.3s ease-out;
}
.toast-leave-active {
    transition: all 0.3s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
