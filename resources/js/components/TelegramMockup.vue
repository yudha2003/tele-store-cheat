<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ content: string; image?: string }>()

const sampleData: Record<string, string> = {
  '{greeting}': 'Selamat Pagi',
  '{firstname}': 'Yudha',
  '{game}': 'Mobile Legends',
  '{produk}': 'MLBB Weekly Diamond Pass',
  '{denom}': 'Weekly Pass x1',
  '{price}': 'Rp 27.500',
  '{invoice_id}': 'KM-6279BF78X9A2',
  '{status_pembayaran}': 'Pending',
  '{status_proses}': 'Pending',
  '{expired_at}': '31 May 2026, 17:35 GMT+7',
  '{payment}': 'WijayaPay Qris',
  '{name_account}': 'DG STORE ID',
  '{number_account}': '089537612711',
  '{virtual_account}': '8267199182772',
  '{nomor_pembayaran}': '928817299',
  '{instruksi}': '1. Buka aplikasi E-wallet\n2. Scan QRIS\n3. Bayar sesuai nominal.',
  '{page}': '1',
  '{total_pages}': '3',
  '{list_transactions}': '<b>1.</b> <code>KM-ML1928</code> — MLBB (Rp 27.500) ✅\n<b>2.</b> <code>KM-FF9827</code> — Free Fire (Rp 20.000) ❌',
  '{user_id}': '521998277',
  '{name}': 'Yudha Pratama',
  '{username}': '@yudhapra',
  '{role}': 'User',
  '{registered_at}': '28 May 2026',
  '{list_rank}': '🥇 <b>Yudha Pratama</b> — Rp 250.000\n🥈 <b>Akira Code</b> — Rp 120.000',
  '{provider}': 'Mobile Legends Resetter',
}

const rendered = computed(() => {
  if (!props.content) return '<span class="opacity-50 italic text-slate-400 dark:text-slate-500">(Pesan kosong)</span>'
  let t = props.content
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  // re-enable telegram tags with classes matching light/dark modes
  t = t
    .replace(/&lt;b&gt;/g, '<b class="font-bold text-slate-900 dark:text-white">').replace(/&lt;\/b&gt;/g, '</b>')
    .replace(/&lt;strong&gt;/g, '<strong class="font-bold text-slate-900 dark:text-white">').replace(/&lt;\/strong&gt;/g, '</strong>')
    .replace(/&lt;i&gt;/g, '<i>').replace(/&lt;\/i&gt;/g, '</i>')
    .replace(/&lt;em&gt;/g, '<em>').replace(/&lt;\/em&gt;/g, '</em>')
    .replace(/&lt;code&gt;/g, '<code class="bg-slate-200 dark:bg-black/30 px-1 rounded text-xs font-mono text-pink-600 dark:text-red-300">').replace(/&lt;\/code&gt;/g, '</code>')
    .replace(/&lt;pre&gt;/g, '<pre class="bg-slate-200 dark:bg-black/30 p-2 rounded my-1 text-xs font-mono overflow-x-auto text-slate-800 dark:text-red-300">').replace(/&lt;\/pre&gt;/g, '</pre>')
  // replace placeholders with sample values
  for (const [k, v] of Object.entries(sampleData)) {
    t = t.replaceAll(k, v)
  }
  t = t.replace(/\n/g, '<br>')
  return t
})
</script>

<template>
  <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-[#e7ebf0] dark:bg-[#0e1621] p-3.5 overflow-auto max-h-[350px] shadow-sm">
    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5">
      <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
      Telegram Preview
    </p>
    <div class="max-w-[95%] sm:max-w-[85%]">
      <img v-if="image" :src="image" class="w-full max-h-[110px] object-cover rounded-t-xl border border-slate-300/30 dark:border-white/5" @error="($event.target as HTMLImageElement).style.display='none'" />
      <div class="bg-white dark:bg-[#182533] text-[13px] leading-relaxed text-slate-800 dark:text-gray-100 p-3.5 whitespace-pre-wrap break-words shadow-sm" :class="image ? 'rounded-b-xl border-t-0' : 'rounded-xl'" v-html="rendered" />
    </div>
  </div>
</template>
