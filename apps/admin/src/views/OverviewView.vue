<script setup>
import { ArrowDownRight, ArrowRight, ArrowUpRight, BookOpenCheck as BookIcon, ChevronDown as ChevronDownIcon, ClipboardCheck as PracticeIcon, Coins as CoinIcon, Clock3, FilePlus2, MessageSquareText, MoreHorizontal as MoreIcon, Sparkles, UsersRound as UsersRoundIcon } from 'lucide-vue-next'
import { store } from '../demo-store'
import LatexText from '../components/LatexText.vue'

defineEmits(['new-question'])
const slug = (value) => value.toLowerCase().replaceAll(' ', '-')
const attentionItems = [
  { title: 'Soal menunggu pemeriksaan', detail: '8 soal · paling lama 2 jam', icon: MessageSquareText, tone: 'amber' },
  { title: 'Pembayaran perlu ditinjau', detail: '3 transaksi · total Rp 447.000', icon: Clock3, tone: 'blue' },
]
const activity = [
  { who: 'Nadia Anindya', action: 'menerbitkan revisi soal', object: 'Q-1281 · Model biaya perjalanan', time: '09.14', tone: 'green' },
  { who: 'Rafi Pratama', action: 'mengirim soal untuk pemeriksaan', object: 'Q-1283 · Peluang bola bernomor genap', time: '08.52', tone: 'blue' },
  { who: 'Sistem', action: 'mengonfirmasi pembayaran', object: 'INV-2611-083 · Rp 349.000', time: '08.16', tone: 'purple' },
]
</script>

<template>
  <div class="view-wrap">
    <div class="welcome-row"><div><div class="eyebrow"><Sparkles :size="13" /> SELASA, 10 NOVEMBER 2026</div><h1>Selamat pagi, Nadia<span class="heading-period">.</span></h1><p class="page-subtitle">Ini perkembangan ruang belajar TKA Matematika hari ini.</p></div><button class="button button-primary" @click="$emit('new-question')"><FilePlus2 :size="17" /> Tulis soal</button></div>

    <div class="metrics-grid">
      <article class="metric-card"><div class="metric-head"><span>Murid dengan akses aktif</span><span class="metric-icon mint"><UsersRoundIcon /></span></div><div class="metric-value">2.418</div><div class="metric-foot"><span class="trend-up"><ArrowUpRight :size="14" /> 12,8%</span><span>dari bulan lalu</span><div class="sparkline spark-green"><i style="height:34%"></i><i style="height:45%"></i><i style="height:40%"></i><i style="height:58%"></i><i style="height:54%"></i><i style="height:73%"></i><i style="height:66%"></i><i style="height:88%"></i></div></div></article>
      <article class="metric-card"><div class="metric-head"><span>Soal siap digunakan</span><span class="metric-icon lilac"><BookIcon /></span></div><div class="metric-value">386 <small>/ 520</small></div><div class="metric-foot"><span class="metric-note"><span class="mini-dot dot-blue"></span> 8 menunggu review</span><div class="sparkline spark-blue"><i style="height:29%"></i><i style="height:43%"></i><i style="height:47%"></i><i style="height:51%"></i><i style="height:70%"></i><i style="height:62%"></i><i style="height:83%"></i><i style="height:91%"></i></div></div></article>
      <article class="metric-card"><div class="metric-head"><span>Percobaan bulan ini</span><span class="metric-icon peach"><PracticeIcon /></span></div><div class="metric-value">8.642</div><div class="metric-foot"><span class="trend-up"><ArrowUpRight :size="14" /> 8,2%</span><span>dari bulan lalu</span><div class="sparkline spark-orange"><i style="height:42%"></i><i style="height:50%"></i><i style="height:38%"></i><i style="height:66%"></i><i style="height:56%"></i><i style="height:73%"></i><i style="height:63%"></i><i style="height:86%"></i></div></div></article>
      <article class="metric-card"><div class="metric-head"><span>Penerimaan terkonfirmasi</span><span class="metric-icon green"><CoinIcon /></span></div><div class="metric-value currency">Rp 38,4<span> jt</span></div><div class="metric-foot"><span class="trend-down"><ArrowDownRight :size="14" /> 2,1%</span><span>dari bulan lalu</span><div class="sparkline spark-green"><i style="height:78%"></i><i style="height:65%"></i><i style="height:70%"></i><i style="height:58%"></i><i style="height:68%"></i><i style="height:50%"></i><i style="height:56%"></i><i style="height:47%"></i></div></div></article>
    </div>

    <div class="dashboard-columns">
      <section class="panel attention-panel"><div class="panel-heading"><div><span class="section-kicker">PERLU PERHATIAN</span><h2>Beberapa hal menunggu Anda</h2></div><span class="count-pill">11</span></div><button v-for="item in attentionItems" :key="item.title" class="attention-item"><span :class="['attention-icon', item.tone]"><component :is="item.icon" :size="18" /></span><span class="attention-copy"><b>{{ item.title }}</b><small>{{ item.detail }}</small></span><ArrowRight :size="17" class="attention-arrow" /></button><div class="attention-footer"><span class="status-line"><i class="status-dot"></i> Semua sistem berjalan normal</span><button class="text-button">Lihat antrean <ArrowRight :size="14" /></button></div></section>
      <section class="panel practice-panel"><div class="panel-heading"><div><span class="section-kicker">AKTIVITAS BELAJAR</span><h2>Ringkasan percobaan</h2></div><button class="select-button">7 hari terakhir <ChevronDownIcon /></button></div><div class="chart-summary"><strong>2.184</strong><span>percobaan selesai</span><span class="trend-up"><ArrowUpRight :size="14" /> 14,6%</span></div><div class="bar-chart"><div class="y-axis"><span>600</span><span>400</span><span>200</span><span>0</span></div><div class="chart-body"><div class="chart-gridline"></div><div class="chart-gridline"></div><div class="chart-gridline"></div><div class="bars"><div v-for="(bar, index) in [36, 54, 42, 68, 60, 82, 70, 94, 73, 89, 66, 100, 74, 90]" :key="index" class="bar-group"><i :style="{ height: `${bar * .72}%` }"></i><i class="bar-secondary" :style="{ height: `${bar}%` }"></i></div></div><div class="x-axis"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div></div></div><div class="chart-legend"><span><i class="legend-dot"></i> Selesai</span><span><i class="legend-dot secondary"></i> Dimulai</span></div></section>
    </div>

    <div class="dashboard-columns lower-columns">
      <section class="panel activity-panel"><div class="panel-heading"><div><span class="section-kicker">LOG AKTIVITAS</span><h2>Yang baru terjadi</h2></div><button class="icon-button"><MoreIcon /></button></div><div class="activity-list"><div v-for="item in activity" :key="item.time" class="activity-row"><span :class="['activity-avatar', item.tone]">{{ item.who === 'Sistem' ? 'S' : item.who.split(' ').map((n) => n[0]).join('') }}</span><p><b>{{ item.who }}</b> {{ item.action }}<br /><span>{{ item.object }}</span></p><time>{{ item.time }}</time></div></div></section>
      <section class="panel recent-panel"><div class="panel-heading"><div><span class="section-kicker">TERAKHIR DIPERBARUI</span><h2>Soal terbaru</h2></div><button class="text-button" @click="$emit('new-question')">Buat soal <ArrowRight :size="14" /></button></div><div class="recent-question" v-for="q in store.questions.slice(0, 2)" :key="q.id"><div class="question-id">{{ q.id }} <span>· {{ q.topic }}</span></div><p><LatexText :text="q.title" /></p><div class="recent-meta"><span :class="['status-tag', `status-${slug(q.status)}`]">{{ q.status }}</span><span>{{ q.format }}</span><span>{{ q.updated }}</span></div></div></section>
    </div>
    <div class="demo-note"><Sparkles :size="14" /><span>Demo interaktif · Data contoh tersimpan sementara di browser.</span><a href="./prototypes/question-authoring.html" target="_blank">Buka prototipe editor soal <ArrowRight :size="13" /></a></div>
  </div>
</template>
