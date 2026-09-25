{{-- Komponen Dialog Progress Bar Animasi Real-time untuk Upload Data --}}
<div id="uploadProgressModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
    {{-- Backdrop blur & overlay --}}
    <div class="fixed inset-0 bg-surface-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

    {{-- Modal Card --}}
    <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-5 shadow-2xl transition-all sm:p-6">
        {{-- Header Status dengan Icon Animasi --}}
        <div class="text-center">
            <div id="uploadProgressIconContainer" class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 ring-8 ring-primary-50/50">
                {{-- Spinner Animasi SVG --}}
                <svg id="uploadProgressSpinner" class="h-7 w-7 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>

                {{-- Success Icon (tersembunyi default) --}}
                <svg id="uploadProgressSuccessIcon" class="hidden h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                {{-- Error/Warning Icon (tersembunyi default) --}}
                <svg id="uploadProgressErrorIcon" class="hidden h-7 w-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>

            <h3 id="uploadProgressTitle" class="text-lg font-bold text-surface-900">
                Memproses Upload Data...
            </h3>
            <p id="uploadProgressSubtitle" class="mt-1 text-xs text-surface-500">
                Mohon jangan menutup atau memuat ulang halaman ini.
            </p>
        </div>

        {{-- Progress Bar Box --}}
        <div class="mt-5">
            <div class="mb-1.5 flex items-center justify-between text-xs font-semibold">
                <span id="uploadProgressStatusText" class="text-surface-600">Mengirim file...</span>
                <span id="uploadProgressPercent" class="text-primary-700">0%</span>
            </div>

            {{-- Outer Bar --}}
            <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-surface-100 ring-1 ring-surface-200">
                {{-- Animated Inner Fill --}}
                <div id="uploadProgressBarFill"
                     class="h-full rounded-full bg-gradient-to-r from-primary-600 via-indigo-600 to-primary-600 transition-all duration-300 ease-out"
                     style="width: 0%; background-size: 200% 100%; animation: shimmer 2s infinite linear;">
                </div>
            </div>

            <div class="mt-1.5 flex items-center justify-between text-[11px] text-surface-400 font-mono">
                <span id="uploadProgressProcessedCount">0 baris diproses</span>
                <span id="uploadProgressTotalCount">Estimasi: -</span>
            </div>
        </div>

        {{-- Detail Counter Cards (4 Kolom Realtime) --}}
        <div class="mt-5 grid grid-cols-4 gap-2">
            {{-- Total Baris --}}
            <div class="rounded-xl border border-surface-200/80 bg-surface-50/60 p-2.5 text-center">
                <span class="block text-[10px] font-medium uppercase tracking-wider text-surface-500">Total</span>
                <span id="countTotal" class="mt-0.5 block font-mono text-base font-bold text-surface-800">0</span>
            </div>

            {{-- Sukses Baru --}}
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5 text-center">
                <span class="block text-[10px] font-medium uppercase tracking-wider text-emerald-700">Sukses</span>
                <span id="countSukses" class="mt-0.5 block font-mono text-base font-bold text-emerald-600">0</span>
            </div>

            {{-- Duplikat / Update --}}
            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-2.5 text-center">
                <span class="block text-[10px] font-medium uppercase tracking-wider text-blue-700">Duplikat</span>
                <span id="countDuplikat" class="mt-0.5 block font-mono text-base font-bold text-blue-600">0</span>
            </div>

            {{-- Gagal / Error --}}
            <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-2.5 text-center">
                <span class="block text-[10px] font-medium uppercase tracking-wider text-rose-700">Gagal</span>
                <span id="countGagal" class="mt-0.5 block font-mono text-base font-bold text-rose-600">0</span>
            </div>
        </div>

        {{-- Error Log Preview Box (Muncul hanya jika ada gagal) --}}
        <div id="uploadProgressErrorSection" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50/70 p-2.5 text-left">
            <div class="flex items-center justify-between text-xs font-semibold text-rose-800">
                <span>Ringkasan Kesalahan Baris:</span>
                <span id="uploadProgressErrorTotal" class="font-mono">0 baris</span>
            </div>
            <ul id="uploadProgressErrorList" class="mt-1.5 max-h-24 space-y-1 overflow-y-auto text-xs text-rose-700 font-mono">
                {{-- Diisi secara dinamis via JS --}}
            </ul>
        </div>

        {{-- Action Buttons saat Selesai --}}
        <div id="uploadProgressActions" class="mt-4 hidden flex-col gap-2 sm:flex-row sm:justify-end">
            <a id="uploadProgressDownloadErrorBtn" href="#" class="btn btn-secondary hidden text-xs font-medium">
                Unduh Laporan Error (.xlsx)
            </a>
            <button id="uploadProgressCloseBtn" type="button" class="btn btn-primary text-xs font-semibold">
                Selesai & Muat Ulang
            </button>
        </div>
    </div>
</div>

<style>
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('uploadProgressModal');
    const spinner = document.getElementById('uploadProgressSpinner');
    const successIcon = document.getElementById('uploadProgressSuccessIcon');
    const errorIcon = document.getElementById('uploadProgressErrorIcon');
    const iconContainer = document.getElementById('uploadProgressIconContainer');

    const titleEl = document.getElementById('uploadProgressTitle');
    const subtitleEl = document.getElementById('uploadProgressSubtitle');
    const statusTextEl = document.getElementById('uploadProgressStatusText');
    const percentEl = document.getElementById('uploadProgressPercent');
    const barFillEl = document.getElementById('uploadProgressBarFill');
    const processedCountEl = document.getElementById('uploadProgressProcessedCount');
    const totalCountEl = document.getElementById('uploadProgressTotalCount');

    const countTotalEl = document.getElementById('countTotal');
    const countSuksesEl = document.getElementById('countSukses');
    const countDuplikatEl = document.getElementById('countDuplikat');
    const countGagalEl = document.getElementById('countGagal');

    const errorSection = document.getElementById('uploadProgressErrorSection');
    const errorTotalEl = document.getElementById('uploadProgressErrorTotal');
    const errorListEl = document.getElementById('uploadProgressErrorList');

    const actionsEl = document.getElementById('uploadProgressActions');
    const downloadErrorBtn = document.getElementById('uploadProgressDownloadErrorBtn');
    const closeBtn = document.getElementById('uploadProgressCloseBtn');

    let pollingTimer = null;

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // Reset UI state
        spinner.classList.remove('hidden');
        successIcon.classList.add('hidden');
        errorIcon.classList.add('hidden');
        iconContainer.className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 ring-8 ring-primary-50/50';

        titleEl.textContent = 'Mengunggah & Memproses File...';
        subtitleEl.textContent = 'Mohon tunggu, proses validasi dan penyimpanan sedang berlangsung.';
        statusTextEl.textContent = 'Mengirim berkas ke server...';
        percentEl.textContent = '0%';
        barFillEl.style.width = '5%';
        processedCountEl.textContent = '0 baris diproses';
        totalCountEl.textContent = 'Menghitung...';

        countTotalEl.textContent = '0';
        countSuksesEl.textContent = '0';
        countDuplikatEl.textContent = '0';
        countGagalEl.textContent = '0';

        errorSection.classList.add('hidden');
        errorListEl.innerHTML = '';
        actionsEl.classList.add('hidden');
        downloadErrorBtn.classList.add('hidden');
    }

    function updateProgress(data) {
        if (!data) return;

        const total = data.total_baris || 0;
        const diproses = data.baris_diproses || (data.baris_sukses + data.baris_gagal) || 0;
        const sukses = data.baris_sukses || 0;
        const duplikat = data.baris_duplikat || 0;
        const gagal = data.baris_gagal || 0;
        let percent = data.progress_persen || 0;

        if (total > 0) {
            percent = Math.min(100, Math.round((diproses / total) * 100));
        }

        percentEl.textContent = percent + '%';
        barFillEl.style.width = Math.max(5, percent) + '%';

        countTotalEl.textContent = Number(total).toLocaleString('id-ID');
        countSuksesEl.textContent = Number(sukses).toLocaleString('id-ID');
        countDuplikatEl.textContent = Number(duplikat).toLocaleString('id-ID');
        countGagalEl.textContent = Number(gagal).toLocaleString('id-ID');

        processedCountEl.textContent = Number(diproses).toLocaleString('id-ID') + ' baris diproses';
        totalCountEl.textContent = total > 0 ? 'Total: ' + Number(total).toLocaleString('id-ID') + ' baris' : 'Menghitung...';

        if (data.status === 'processing') {
            statusTextEl.textContent = 'Memvalidasi & menyimpan data (' + percent + '%)...';
        } else if (data.status === 'queued') {
            statusTextEl.textContent = 'Berkas diterima. Menunggu proses di server...';
        }

        // Tampilkan error log jika ada
        if (Array.isArray(data.error_log) && data.error_log.length > 0) {
            errorSection.classList.remove('hidden');
            errorTotalEl.textContent = data.baris_gagal + ' baris';
            errorListEl.innerHTML = '';
            data.error_log.slice(0, 15).forEach(err => {
                errorListEl.appendChild(buildErrorItem(err));
            });
        }

        // Selesai
        if (data.is_selesai || data.status === 'completed' || data.status === 'completed_with_errors' || data.status === 'failed') {
            stopPolling();
            finishUploadUI(data);
        }
    }

    /**
     * Membangun satu elemen rincian kegagalan baris: nomor baris, kolom yang
     * bermasalah, nilai yang ditolak, dan pesan validasinya.
     */
    function buildErrorItem(err) {
        const li = document.createElement('li');
        li.className = 'rounded-lg bg-white/70 px-2 py-1.5 ring-1 ring-rose-100';

        const header = document.createElement('div');
        header.className = 'flex items-center justify-between gap-2';

        const rowLabel = document.createElement('span');
        rowLabel.className = 'font-sans font-semibold text-rose-800';
        rowLabel.textContent = 'Baris ' + (err.baris || '?');
        header.appendChild(rowLabel);

        if (err.kolom) {
            const colLabel = document.createElement('span');
            colLabel.className = 'font-sans rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-700';
            colLabel.textContent = 'kolom: ' + err.kolom;
            header.appendChild(colLabel);
        }

        li.appendChild(header);

        if (err.nilai !== null && err.nilai !== undefined && err.nilai !== '') {
            const valueLabel = document.createElement('div');
            valueLabel.className = 'mt-0.5 break-all text-[11px] text-rose-600';
            valueLabel.textContent = 'nilai ditolak: "' + err.nilai + '"';
            li.appendChild(valueLabel);
        }

        const msgLabel = document.createElement('div');
        msgLabel.className = 'mt-0.5 text-[11px] leading-snug text-rose-700';
        msgLabel.textContent = err.message || 'Baris gagal divalidasi.';
        li.appendChild(msgLabel);

        return li;
    }

    function finishUploadUI(data) {
        spinner.classList.add('hidden');
        actionsEl.classList.remove('hidden');

        if (data.status === 'completed') {
            successIcon.classList.remove('hidden');
            iconContainer.className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 ring-8 ring-emerald-50/50';
            titleEl.textContent = 'Upload Selesai Sempurna!';
            subtitleEl.textContent = 'Seluruh baris data berhasil divalidasi dan disimpan ke database.';
            statusTextEl.textContent = 'Proses tuntas 100%';
            percentEl.textContent = '100%';
            barFillEl.style.width = '100%';
            barFillEl.className = 'h-full rounded-full bg-emerald-500 transition-all duration-300';
        } else if (data.status === 'completed_with_errors') {
            errorIcon.classList.remove('hidden');
            iconContainer.className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 ring-8 ring-amber-50/50';
            titleEl.textContent = 'Upload Selesai dengan Sebagian Gagal';
            subtitleEl.textContent = data.baris_sukses + ' data berhasil, namun ada ' + data.baris_gagal + ' data yang tidak valid.';
            statusTextEl.textContent = 'Selesai dengan catatan';
            percentEl.textContent = '100%';
            barFillEl.style.width = '100%';
            barFillEl.className = 'h-full rounded-full bg-amber-500 transition-all duration-300';

            // Tampilkan link unduh report error
            if (data.id) {
                downloadErrorBtn.href = '/upload/riwayat/' + data.id + '/error';
                downloadErrorBtn.classList.remove('hidden');
            }
        } else {
            errorIcon.classList.remove('hidden');
            iconContainer.className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 ring-8 ring-rose-50/50';
            titleEl.textContent = 'Upload Gagal!';
            subtitleEl.textContent = data.message || 'Terjadi kesalahan sistem saat memproses file.';
            statusTextEl.textContent = 'Gagal diproses';
            barFillEl.className = 'h-full rounded-full bg-rose-500 transition-all duration-300';
        }
    }

    function startPolling(batchId) {
        if (pollingTimer) clearInterval(pollingTimer);

        const poll = async () => {
            try {
                const response = await fetch('/upload/batch/' + batchId + '/status', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // 429 (throttle) bukan akhir proses: lewati putaran ini dan
                // biarkan polling lanjut agar progress tidak macet.
                if (!response.ok) {
                    console.warn('Polling status gagal dengan HTTP ' + response.status + ', mencoba lagi...');

                    return;
                }

                const data = await response.json();
                updateProgress(data);
            } catch (err) {
                console.error('Polling error:', err);
            }
        };

        // Langsung ambil status pertama kali tanpa menunggu interval.
        poll();
        pollingTimer = setInterval(poll, 2000);
    }

    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }

    // Tombol tutup modal: reload halaman agar tabel data terupdate
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }

    // Sambungkan ke SEMUA form upload di halaman (baik action route('upload.store') atau memiliki atribut data-upload-form)
    const uploadForms = document.querySelectorAll('form[action*="/upload"], form[data-upload-form]');

    uploadForms.forEach(form => {
        form.addEventListener('submit', async function (e) {
            const fileInput = form.querySelector('input[type="file"]');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                return; // Biarkan browser validasi required
            }

            e.preventDefault();
            openModal();

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('input[name="_token"]')?.value || ''
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    stopPolling();
                    finishUploadUI({
                        status: 'failed',
                        message: result.message || 'File tidak dapat diproses atau format tidak sesuai.'
                    });
                    return;
                }

                // Berkas diterima server dan diproses di background (queue).
                // Jangan menandai selesai di sini: status awal masih 'queued',
                // sehingga hasil akhirnya harus dipantau lewat polling status.
                if (result.batch_id) {
                    statusTextEl.textContent = 'Berkas diterima. Menunggu proses di server...';
                    startPolling(result.batch_id);

                    return;
                }

                // Fallback untuk respons non-async (tanpa batch_id): proses sudah
                // tuntas pada saat respons diterima.
                finishUploadUI({
                    status: result.status || 'completed',
                    baris_sukses: result.baris_sukses || 0,
                    baris_duplikat: result.baris_duplikat || 0,
                    baris_gagal: result.baris_gagal || 0,
                    message: result.message
                });
            } catch (err) {
                stopPolling();
                finishUploadUI({
                    status: 'failed',
                    message: err.message || 'Koneksi terputus saat mengunggah berkas.'
                });
            }
        });
    });
});
</script>
