@extends('layouts.app')

@section('title', 'Pengujian Algoritma | CryptoLab')

@section('topbar_title', 'Pengujian Algoritma')

@section('topbar_subtitle', 'Uji tiap algoritma lewat praktik langsung. Hybrid Trio di bawah menampilkan jejak komputasi per karakter.')

@push('styles')
<style>
/* ============================================================
   LAB RESULT CARDS & COPY BUTTON (High-Contrast, Modern Terminal)
   ============================================================ */
.lab-result-item {
    background: rgba(13, 21, 37, 0.75);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 10px;
    transition: border-color 0.2s ease, background 0.2s ease;
}

[data-bs-theme="light"] .lab-result-item {
    background: #f8fafc;
    border-color: rgba(15, 23, 42, 0.12);
}

.lab-result-item:hover {
    border-color: var(--border-strong);
}

.lab-result-label {
    font-size: 0.76rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
}

.lab-result-value {
    font-family: var(--bs-font-monospace);
    font-size: 0.88rem;
    color: var(--text-main);
    word-break: break-all;
    user-select: all;
    line-height: 1.5;
}

.copy-btn {
    font-size: 0.75rem;
    line-height: 1.4;
    border-radius: 6px;
    transition: all 0.15s ease-in-out;
    background: transparent;
    border-color: var(--border);
    color: var(--text-muted);
}

.copy-btn:hover {
    background: var(--accent-soft);
    border-color: var(--accent);
    color: var(--accent) !important;
}

.copy-btn.copied {
    border-color: #198754 !important;
    color: #198754 !important;
    background: rgba(25, 135, 84, 0.14) !important;
}

.pipeline-step {
    background: rgba(13, 21, 37, 0.65);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px;
    transition: border-color 0.2s ease;
}

[data-bs-theme="light"] .pipeline-step {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.12);
}
</style>
@endpush

@section('content')

<div class="py-1">

    <div class="row g-4">

        {{-- ================= PENGUJIAN GENERIK ================= --}}
        <div class="col-lg-5">

            <div class="panel-card h-100">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-clipboard-check text-neon"></i>
                    <h6 class="fw-bold mb-0">Uji Algoritma</h6>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="algorithmSelect">Algoritma</label>
                    <select id="algorithmSelect" class="form-select">

                        @foreach ($katalog as $modul => $algorithms)

                            <optgroup label="{{ $modul }}">

                                @foreach ($algorithms as $slug => $config)

                                    @php $available = in_array($slug, $tersedia); @endphp

                                    <option value="{{ $slug }}"
                                            data-kind="{{ $config['kind'] }}"
                                            data-note="{{ $config['note'] }}"
                                            data-label="{{ $config['label'] }}"
                                            {{ !$available ? 'disabled' : '' }}
                                            {{ ($dipilih === $slug || (!$dipilih && $loop->parent->first && $loop->first)) ? 'selected' : '' }}>
                                        {{ $config['label'] }}{{ !$available ? ' (belum tersedia)' : '' }}
                                    </option>

                                @endforeach

                            </optgroup>

                        @endforeach

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="messageInput">
                        <span id="messageLabelText">{{ __('Pesan') }}</span> <span class="text-muted-custom small" id="messageHintText">(plaintext / ciphertext hex untuk dekripsi)</span>
                    </label>
                    <textarea id="messageInput" rows="3" class="form-control"
                              placeholder="Tulis pesan untuk diuji…">KRIPTO</textarea>
                </div>

                <div class="mb-3" id="keyGroup">
                    <label class="form-label" for="keyInput">
                        <span id="keyLabelText">Kunci</span>
                    </label>
                    <input type="text" id="keyInput" class="form-control" value="KUNCI"
                           placeholder="Kunci / passphrase">
                </div>

                <div class="mb-2" id="actionGroup">
                    <label class="form-label d-block">Arah uji</label>
                    <div class="btn-group w-100" role="group" id="actionButtons">

                        <button type="button" class="btn btn-outline-primary" data-action="enkripsi" title="Pesan terang menjadi ciphertext">
                            <i class="bi bi-lock"></i> Enkripsi
                        </button>

                        <button type="button" class="btn btn-outline-primary" data-action="dekripsi" title="Ciphertext kembali menjadi pesan terang">
                            <i class="bi bi-unlock"></i> Dekripsi
                        </button>

                        <button type="button" class="btn btn-primary" data-action="roundtrip" title="Enkripsi lalu langsung dekripsi kembali">
                            <i class="bi bi-arrow-repeat"></i> Bolak-balik
                        </button>

                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted-custom">
                        <i class="bi bi-lightning-charge"></i> Hasil diperbarui otomatis saat Anda mengetik.
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="runButton">
                        <i class="bi bi-arrow-clockwise"></i> Jalankan
                    </button>
                </div>

                <div class="alert alert-info mt-3 mb-0 small" id="algorithmNote" role="note"></div>

                <div class="alert alert-danger mt-3 mb-0 small d-none" id="runError" role="alert"></div>

            </div>

        </div>

        {{-- ================= HASIL ================= --}}
        <div class="col-lg-7">

            <div class="panel-card h-100 d-flex flex-column">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-terminal text-neon"></i>
                        <h6 class="fw-bold mb-0">Hasil Pengujian</h6>
                    </div>
                    <span class="badge text-bg-secondary" id="resultAlgorithm">Belum dipilih</span>
                </div>

                <div id="resultPanel"
                     class="flex-fill d-flex flex-column justify-content-center align-items-center text-center py-5">

                    <i class="bi bi-beaker display-4 text-muted-custom mb-3"></i>

                    <p class="fw-semibold mb-2">Belum ada hasil</p>

                    <p class="small text-muted-custom mb-0">
                        Pilih algoritma dan arah uji — hasilnya muncul seketika.
                    </p>

                </div>

            </div>

        </div>

    </div>

    {{-- ================= HYBRID TRIO: TRACE & ETM ================= --}}
    <div class="row g-4 mt-0" id="trioSection">

        <div class="col-lg-5">

            <div class="panel-card">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-beaker text-neon"></i>
                    <h6 class="fw-bold mb-0">Hybrid Trio: Encrypt-then-MAC</h6>
                </div>

                <form id="trioForm" method="POST" action="{{ route('lab.proses') }}">

                    @csrf
                    <input type="hidden" name="aksi" value="enkripsi">

                    <div class="mb-3">
                        <label class="form-label" for="plaintext">Pesan terang</label>
                        <textarea name="plaintext" id="plaintext" rows="2" required
                                  class="form-control" placeholder="Contoh: KRIPTO">KRIPTO</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="passphrase">Frasa sandi induk</label>
                        <input type="text" name="passphrase" id="passphrase" required minlength="4"
                               class="form-control" value="KUNCI"
                               placeholder="Minimal 4 karakter">
                        <div class="form-text">Satu frasa sandi diturunkan menjadi 4 subkunci via HKDF.</div>
                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-primary flex-fill" id="trioSubmit">
                            <i class="bi bi-shield-lock"></i> Enkripsi + Jejak
                        </button>

                        <button type="button" class="btn btn-outline-secondary" id="trioContoh">
                            Isi contoh
                        </button>

                    </div>

                </form>

                <div class="alert alert-danger mt-3 mb-0 small d-none" id="trioError" role="alert"></div>

            </div>

        </div>

        <div class="col-lg-7">

            @if (isset($error))
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-octagon"></i>
                    <strong>Dekripsi digagalkan:</strong> {{ $error }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-octagon"></i>
                    <strong>Validasi gagal:</strong> {{ $errors->first() }}
                </div>
            @endif

            <div id="trioBundle" class="d-none">
                <div class="panel-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-neon"><i class="bi bi-shield-check me-1"></i> Paket Encrypt-then-MAC (ETM)</h6>
                        <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i> Tag HMAC Valid</span>
                    </div>

                    <div class="lab-result-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="lab-result-label">Ciphertext (Hex)</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-2" data-target="trioCiphertext" title="Salin ciphertext">
                                <i class="bi bi-clipboard me-1"></i> Salin
                            </button>
                        </div>
                        <div class="lab-result-value" id="trioCiphertext"></div>
                    </div>

                    <div class="row g-2 mb-0">
                        <div class="col-sm-4">
                            <div class="lab-result-item h-100 mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="lab-result-label">Salt (16 B)</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-1" data-target="trioSalt" title="Salin salt">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <div class="lab-result-value small" id="trioSalt"></div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="lab-result-item h-100 mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="lab-result-label">Nonce (12 B)</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-1" data-target="trioNonce" title="Salin nonce">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <div class="lab-result-value small" id="trioNonce"></div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="lab-result-item h-100 mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="lab-result-label text-neon">Tag HMAC</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-1" data-target="trioTag" title="Salin tag HMAC">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <div class="lab-result-value small text-neon" id="trioTag"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="trioTrace" class="d-none">
                <div class="panel-card mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-columns-reverse text-primary me-1"></i> Jejak Komputasi Per Karakter (ASCII 7-bit)</h6>
                        <small class="text-muted-custom">P ⊕ Z<sub>LFSR</sub> ⊕ Z<sub>RC4</sub> ⊕ Z<sub>ChaCha</sub></small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark-custom align-middle text-center small mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Char</th>
                                    <th>Plain</th>
                                    <th class="text-info">Z_LFSR</th>
                                    <th class="text-warning">Z_RC4</th>
                                    <th class="text-primary">Z_ChaCha</th>
                                    <th>Tahap 1</th>
                                    <th>Tahap 2</th>
                                    <th class="text-neon">Cipher</th>
                                </tr>
                            </thead>
                            <tbody id="trioTraceBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="trioKosong" class="panel-card d-flex flex-column justify-content-center align-items-center text-center py-5">

                <i class="bi bi-beaker display-4 text-muted-custom mb-3"></i>

                <p class="fw-semibold mb-2">Jejak Hybrid Trio</p>

                <p class="small text-muted-custom mb-0">
                    Tekan <em>Enkripsi + Jejak</em> untuk melihat langkah demi langkah
                    dengan plaintext contoh <code>KRIPTO</code> dan passphrase <code>KUNCI</code>.
                </p>

            </div>

        </div>

    </div>

</div>

@push('scripts')
<script>
(function () {
    const select = document.getElementById('algorithmSelect');
    const message = document.getElementById('messageInput');
    const key = document.getElementById('keyInput');
    const keyGroup = document.getElementById('keyGroup');
    const actionGroup = document.getElementById('actionGroup');
    const note = document.getElementById('algorithmNote');
    const errorBox = document.getElementById('runError');
    const panel = document.getElementById('resultPanel');
    const badge = document.getElementById('resultAlgorithm');
    const runButton = document.getElementById('runButton');

    // Hybrid Trio DOM
    const trioForm = document.getElementById('trioForm');
    const trioSubmit = document.getElementById('trioSubmit');
    const trioContoh = document.getElementById('trioContoh');
    const trioError = document.getElementById('trioError');
    const trioBundle = document.getElementById('trioBundle');
    const trioTrace = document.getElementById('trioTrace');
    const trioKosong = document.getElementById('trioKosong');

    let currentAction = 'roundtrip';
    let seq = 0;            // pembatal hasil usang saat mengetik cepat
    let paketTerakhir = null; // { algorithm, hex } untuk tombol "uji dekripsi"
    let timer = null;
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function currentOption() {
        return select.options[select.selectedIndex];
    }

    function jenisDipilih() {
        return currentOption().dataset.kind;
    }

    // ============ adaptasi form dinamis & kontekstual ============
    function perbaruiFormKontekstual() {
        const kind = jenisDipilih();
        const algo = select.value;
        const msgLabel = document.getElementById('messageLabelText');
        const msgHint = document.getElementById('messageHintText');
        const keyLabel = document.getElementById('keyLabelText');

        // Reset default
        if (msgLabel) msgLabel.textContent = 'Pesan';
        if (msgHint) msgHint.textContent = '(plaintext / ciphertext hex untuk dekripsi)';
        if (keyLabel) keyLabel.textContent = 'Kunci';

        if (kind === 'math') {
            if (algo === 'modulo') {
                if (msgLabel) msgLabel.textContent = 'Bilangan Bulat (a)';
                if (msgHint) msgHint.textContent = '(bilangan yang dibagi)';
                if (keyLabel) keyLabel.textContent = 'Modulus (n)';
                message.placeholder = 'Contoh: 17';
                key.placeholder = 'Contoh: 5';
                if (message.value === 'KRIPTO' || !/^-?\d+$/.test(message.value.trim())) message.value = '17';
                if (key.value === 'KUNCI' || !/^-?\d+$/.test(key.value.trim())) key.value = '5';
            } else if (algo === 'gcd' || algo === 'extended-euclidean') {
                if (msgLabel) msgLabel.textContent = 'Bilangan Pertama (a)';
                if (msgHint) msgHint.textContent = '(bilangan bulat)';
                if (keyLabel) keyLabel.textContent = 'Bilangan Kedua (b)';
                message.placeholder = 'Contoh: 48';
                key.placeholder = 'Contoh: 18';
                if (message.value === 'KRIPTO' || !/^-?\d+$/.test(message.value.trim())) message.value = '48';
                if (key.value === 'KUNCI' || !/^-?\d+$/.test(key.value.trim())) key.value = '18';
            } else if (algo === 'bilangan-prima' || algo === 'phi-euler') {
                if (msgLabel) msgLabel.textContent = 'Bilangan Bulat (n)';
                if (msgHint) msgHint.textContent = '(uji sifat bilangan n)';
                message.placeholder = algo === 'phi-euler' ? 'Contoh: 36' : 'Contoh: 29';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = algo === 'phi-euler' ? '36' : '29';
            } else if (algo === 'faktorisasi' || algo === 'faktorisasi-rsa') {
                if (msgLabel) msgLabel.textContent = 'Bilangan Bulat Komposit (n)';
                if (msgHint) msgHint.textContent = '(rentang 4 sampai 1.000.000.000)';
                message.placeholder = 'Contoh: 3233';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '3233';
            } else if (algo === 'fermat') {
                if (msgLabel) msgLabel.textContent = 'Basis (a)';
                if (msgHint) msgHint.textContent = '(basis pangkat)';
                if (keyLabel) keyLabel.textContent = 'Modulus Prima (p)';
                message.placeholder = 'Contoh: 3';
                key.placeholder = 'Contoh: 7';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '3';
                if (key.value === 'KUNCI' || !/^\d+$/.test(key.value.trim())) key.value = '7';
            } else if (algo === 'euler') {
                if (msgLabel) msgLabel.textContent = 'Basis (a)';
                if (msgHint) msgHint.textContent = '(basis koprima dengan n)';
                if (keyLabel) keyLabel.textContent = 'Modulus (n)';
                message.placeholder = 'Contoh: 3';
                key.placeholder = 'Contoh: 10';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '3';
                if (key.value === 'KUNCI' || !/^\d+$/.test(key.value.trim())) key.value = '10';
            } else if (algo === 'finite-field') {
                if (msgLabel) msgLabel.textContent = 'Polinomial Byte 1 (Heksa)';
                if (msgHint) msgHint.textContent = '(elemen GF(2^8) format hex 00-FF)';
                if (keyLabel) keyLabel.textContent = 'Polinomial Byte 2 (Heksa)';
                message.placeholder = 'Contoh: 57';
                key.placeholder = 'Contoh: 83';
                if (message.value === 'KRIPTO' || !/^[0-9a-fA-F]{1,2}$/.test(message.value.trim())) message.value = '57';
                if (key.value === 'KUNCI' || !/^[0-9a-fA-F]{1,2}$/.test(key.value.trim())) key.value = '83';
            } else if (algo === 'discrete-logarithm') {
                if (msgLabel) msgLabel.textContent = 'Nilai Target h';
                if (msgHint) msgHint.textContent = '(mencari x pada g^x ≡ h mod p)';
                if (keyLabel) keyLabel.textContent = 'Basis & Modulus (g,p)';
                message.placeholder = 'Contoh: 100';
                key.placeholder = 'Contoh: 2,2579';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '100';
                if (key.value === 'KUNCI' || key.value.indexOf(',') === -1) key.value = '2,2579';
            } else if (algo === 'elliptic-curve') {
                if (msgLabel) msgLabel.textContent = 'Pengali Skalar k';
                if (msgHint) msgHint.textContent = '(menghitung titik k·P)';
                message.placeholder = 'Contoh: 3';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '3';
            } else if (algo === 'rabin') {
                if (msgLabel) msgLabel.textContent = 'Pesan Kuadrat m';
                if (msgHint) msgHint.textContent = '(bilangan bulat 0 <= m < n)';
                message.placeholder = 'Contoh: 42';
                if (message.value === 'KRIPTO' || !/^\d+$/.test(message.value.trim())) message.value = '42';
            }
        } else if (kind === 'hash') {
            if (msgLabel) msgLabel.textContent = 'Data / Pesan';
            if (msgHint) msgHint.textContent = '(teks apapun untuk dihitung digest hash)';
            message.placeholder = 'Tulis pesan untuk dihitung ringkasannya…';
            if (/^\d+$/.test(message.value.trim())) message.value = 'KRIPTO';
        } else {
            message.placeholder = 'Tulis pesan untuk diuji…';
            key.placeholder = 'Kunci / passphrase';
            if (/^\d+$/.test(message.value.trim()) && ['17', '48', '29', '3233', '36', '3', '57', '100', '42'].includes(message.value.trim())) {
                message.value = 'KRIPTO';
            }
            if (/^\d+$/.test(key.value.trim()) && ['5', '18', '7', '10', '83'].includes(key.value.trim())) {
                key.value = 'KUNCI';
            }
        }
    }

    function render() {
        const kind = jenisDipilih();
        const algo = select.value;

        note.textContent = currentOption().dataset.note;
        badge.textContent = currentOption().dataset.label;

        // Hash & beberapa kalkulasi matematika tidak butuh kunci
        const tanpaKunci = (kind === 'hash' || algo === 'bilangan-prima' || algo === 'faktorisasi' || algo === 'phi-euler' || algo === 'elliptic-curve' || algo === 'faktorisasi-rsa' || algo === 'rabin' || algo === 'diffie-hellman' || algo === 'ntru' || algo === 'elgamal');
        keyGroup.style.display = tanpaKunci ? 'none' : '';

        // Arah uji hanya relevan untuk cipher simetris/stream dua arah
        const tanpaArah = (kind === 'hash' || kind === 'mac' || kind === 'math' || kind === 'special');
        actionGroup.style.display = tanpaArah ? 'none' : '';
        errorBox.classList.add('d-none');

        document.getElementById('trioSection').style.display =
            algo === 'hybrid-trio' ? '' : 'none';

        perbaruiFormKontekstual();
    }

    function tandaiAksi(action) {
        currentAction = action;
        document.querySelectorAll('#actionButtons button').forEach(b => {
            b.classList.toggle('btn-primary', b.dataset.action === action);
            b.classList.toggle('btn-outline-primary', b.dataset.action !== action);
        });
    }

    // ============ pemanggilan AJAX ============

    function headerPermintaan() {
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfToken;
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        };
    }

    async function kirim(payload) {
        return fetch('{{ route('lab.run') }}', {
            method: 'POST',
            headers: headerPermintaan(),
            body: JSON.stringify(payload),
        });
    }

    // Sesi kedaluwarsa (419): ambil token CSRF terbaru dari meta tag atau halaman
    async function ambilTokenBaru() {
        try {
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (metaToken && metaToken !== csrfToken) {
                return metaToken;
            }

            const halaman = await fetch(location.href, { headers: { 'Accept': 'text/html' } });
            const cocok = await halaman.text();
            const m = cocok.match(/name="csrf-token"\s+content="([^"]+)"/) || cocok.match(/csrfToken\s*=\s*'([^']+)'/);

            return m ? m[1] : null;
        } catch (e) {
            return null;
        }
    }

    async function panggil(payload) {
        let response = await kirim(payload);

        if (response.status === 419) {
            const tokenBaru = await ambilTokenBaru();

            if (tokenBaru) {
                csrfToken = tokenBaru;
                response = await kirim(payload);

                if (response.ok) {
                    return response.json();
                }
            }

            throw new Error('Sesi kedaluwarsa. Muat ulang halaman (Ctrl+R) lalu coba lagi.');
        }

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.ok !== true) {
            throw new Error(data?.error || data?.message || ('Server menolak permintaan (status ' + response.status + ').'));
        }

        return data;
    }

    function payloadUntuk(action, isManual = false, overrideAlgo = null) {
        return {
            algorithm: overrideAlgo || select.value,
            message: message.value,
            key: keyGroup.style.display === 'none' ? '' : key.value,
            action,
            log: isManual,
        };
    }

    function aksiServer(kind) {
        if (kind === 'hash') return 'hash';
        if (kind === 'mac' || kind === 'math' || kind === 'special') return 'enkripsi';
        return currentAction === 'roundtrip' ? 'enkripsi' : currentAction;
    }

    async function jalankan(isManual = false) {
        const kind = jenisDipilih();
        const algoDipilih = select.value;
        const nomor = ++seq;

        errorBox.classList.add('d-none');

        // Dekripsi dengan pesan yang masih plaintext: cukup petunjuk
        if ((kind === 'symmetric' || kind === 'stream') && currentAction === 'dekripsi' && !terlihatCiphertext(message.value)) {
            renderHintDekripsi();
            return;
        }

        // Tampilkan feedback visual pada tombol jalankan
        const ikonAsli = runButton.innerHTML;
        runButton.disabled = true;
        runButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Uji...';

        try {
            if (kind !== 'hash' && kind !== 'mac' && kind !== 'math' && kind !== 'special' && currentAction === 'roundtrip') {
                const hasilEnkrip = await panggil(payloadUntuk('enkripsi', isManual, algoDipilih));
                if (nomor !== seq) return;

                const hasilDekrip = await panggil({
                    algorithm: algoDipilih,
                    message: hasilEnkrip.paketDekripsi,
                    key: keyGroup.style.display === 'none' ? '' : key.value,
                    action: 'dekripsi',
                    log: isManual,
                });
                if (nomor !== seq) return;

                renderRoundtrip(hasilEnkrip, hasilDekrip, message.value);
            } else {
                const data = await panggil(payloadUntuk(aksiServer(kind), isManual, algoDipilih));
                if (nomor !== seq) return;

                renderSatu(data);
            }
        } catch (e) {
            if (nomor === seq) galat(e.message || 'Gagal terhubung ke server.');
        } finally {
            runButton.disabled = false;
            runButton.innerHTML = ikonAsli;
        }
    }

    function galat(text) {
        errorBox.textContent = text;
        errorBox.classList.remove('d-none');
        // Bersihkan panel
        panel.className = 'flex-fill d-flex flex-column justify-content-center align-items-center text-center py-5';
        panel.innerHTML =
            '<i class="bi bi-exclamation-triangle display-4 text-danger mb-3"></i>' +
            '<p class="fw-semibold mb-1">Pengujian gagal</p>' +
            '<p class="small text-muted-custom mb-0">' + escapeHtml(text) + '</p>';
    }

    // ============ clipboard & copy helper ============

    async function salinKeClipboard(teks, btn) {
        if (!teks) return;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(teks);
            } else {
                const ta = document.createElement('textarea');
                ta.value = teks;
                ta.style.position = 'fixed';
                ta.style.left = '-999999px';
                ta.style.top = '-999999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }

            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.classList.add('copied');
                btn.innerHTML = '<i class="bi bi-check-lg text-success me-1"></i><span class="text-success fw-bold">Tersalin!</span>';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = originalHtml;
                }, 1500);
            }
        } catch (err) {
            console.error('Gagal menyalin:', err);
        }
    }

    // Global click listener untuk tombol copy
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.copy-btn');
        if (!btn) return;

        let teks = btn.getAttribute('data-copy');
        if (teks === null) {
            const targetId = btn.getAttribute('data-target');
            if (targetId) {
                const targetEl = document.getElementById(targetId);
                if (targetEl) teks = targetEl.textContent || targetEl.innerText;
            }
        }

        if (teks) {
            salinKeClipboard(teks.trim(), btn);
        }
    });

    // ============ rendering hasil ============

    function escapeHtml(text) {
        return String(text)
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    }

    function escapeAttribute(text) {
        return String(text)
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function barisHasil(results) {
        if (!results || results.length === 0) return '';
        return results.map(r => {
            const rawVal = String(r.value || '');
            const escVal = escapeHtml(rawVal);
            const escLabel = escapeHtml(r.label || '');
            const escAttr = escapeAttribute(rawVal);

            return `
            <div class="lab-result-item">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                    <span class="lab-result-label">${escLabel}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-2"
                            data-copy="${escAttr}"
                            title="Salin ${escLabel}">
                        <i class="bi bi-clipboard me-1"></i>Salin
                    </button>
                </div>
                <div class="lab-result-value">${escVal}</div>
            </div>`;
        }).join('');
    }

    function kotakPipeline(judul, isi, kelas = '', copyValue = null) {
        const rawCopy = copyValue !== null ? copyValue : isi;
        const escCopy = escapeAttribute(String(rawCopy));
        return `
        <div class="pipeline-step mb-2 ${kelas}" style="min-width:0;">
            <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                <span class="small fw-semibold text-muted-custom">${escapeHtml(judul)}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary copy-btn py-0 px-2"
                        data-copy="${escCopy}"
                        title="Salin ${escapeHtml(judul)}">
                    <i class="bi bi-clipboard me-1"></i>Salin
                </button>
            </div>
            <div class="font-monospace small lab-result-value" style="word-break: break-all;">${escapeHtml(isi)}</div>
        </div>`;
    }

    function panahAlur() {
        return '<div class="text-center text-muted-custom my-1 lh-1" aria-hidden="true">' +
            '<i class="bi bi-arrow-down"></i></div>';
    }

    function renderRoundtrip(enkrip, dekrip, asli) {
        const hex = enkrip.paketDekripsi || '';
        const pulih = dekrip.results[0] ? dekrip.results[0].value : '';
        const cocok = pulih === asli;

        const rows = barisHasil(enkrip.results);
        const tombolDekripsi =
            `<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="bawaDekripsi">` +
            `<i class="bi bi-unlock"></i> Uji dekripsi ciphertext ini saja</button>`;

        panel.className = 'flex-fill';
        panel.innerHTML =
            kotakPipeline('Plaintext asli', asli, 'border-secondary') +
            panahAlur() +
            kotakPipeline('Ciphertext (hex)', '0x' + hex, 'border-primary', hex) +
            panahAlur() +
            kotakPipeline('Dekripsi kembali', pulih, cocok ? 'border-success text-success-emphasis' : 'border-danger text-danger-emphasis') +
            `<p class="mb-3 small">${cocok
                ? '<i class="bi bi-check-circle text-success me-1"></i><strong>Bolak-balik pulih sempurna:</strong> enkripsi lalu dekripsi menghasilkan pesan asli.'
                : '<i class="bi bi-x-circle text-danger me-1"></i><strong>Peringatan:</strong> Hasil dekripsi tidak sama dengan pesan asli.'}</p>` +
            `<div class="mt-2 mb-2">${rows}</div>` +
            `<p class="small text-muted-custom mt-3 mb-0">${escapeHtml(enkrip.note)}</p>` +
            tombolDekripsi;

        paketTerakhir = { algorithm: select.value, hex };
        document.getElementById('bawaDekripsi')?.addEventListener('click', bawaKeDekripsi);
    }

    function renderSatu(data) {
        let kepala = '';

        if (data.operation === 'dekripsi') {
            const pulih = data.results[0] ? data.results[0].value : '';
            kepala =
                kotakPipeline('Ciphertext masuk', '0x' + message.value.trim(), 'border-primary', message.value.trim()) +
                panahAlur() +
                kotakPipeline('Pesan terang pulih', pulih, 'border-success') +
                '<div class="mb-3"></div>';
        } else {
            const paket = data.paketDekripsi || null;
            paketTerakhir = paket ? { algorithm: select.value, hex: paket } : null;
            kepala = paket
                ? `<button type="button" class="btn btn-sm btn-outline-primary mb-3" id="bawaDekripsi">` +
                  `<i class="bi bi-unlock"></i> Uji dekripsi ciphertext ini saja</button>`
                : '';
        }

        panel.className = 'flex-fill';
        panel.innerHTML =
            `<p class="mb-2 small">Operasi: <span class="badge text-bg-primary">${escapeHtml(data.operation)}</span></p>` +
            kepala +
            `<div class="mb-2">${barisHasil(data.results)}</div>` +
            `<p class="small text-muted-custom mt-3 mb-0">${escapeHtml(data.note)}</p>`;

        const tombol = document.getElementById('bawaDekripsi');
        if (tombol) tombol.addEventListener('click', bawaKeDekripsi);
    }

    function bawaKeDekripsi() {
        if (!paketTerakhir || paketTerakhir.algorithm !== select.value) return;

        message.value = paketTerakhir.hex;
        tandaiAksi('dekripsi');
        jalankan(true);
    }

    // ============ realtime: jalankan otomatis ============

    function jadwalkan(segera = false) {
        clearTimeout(timer);
        timer = setTimeout(() => jalankan(segera), segera ? 0 : 400);
    }

    function terlihatCiphertext(t) {
        const s = String(t).trim();

        if (/^(0x)?[0-9a-f]+$/i.test(s) && s.replace(/^0x/i, '').length % 2 === 0) {
            return true;
        }

        return /^[A-Za-z0-9+/]+={0,2}$/.test(s) && s.length % 4 === 0;
    }

    function renderHintDekripsi() {
        panel.className = 'flex-fill d-flex flex-column justify-content-center align-items-center text-center py-5';
        panel.innerHTML =
            '<i class="bi bi-info-circle display-4 text-info mb-3"></i>' +
            '<p class="fw-semibold mb-1">Mode Dekripsi menunggu ciphertext</p>' +
            '<p class="small text-muted-custom mb-3" style="max-width: 26rem;">' +
            'Jalankan Enkripsi dulu, lalu salin baris "Paket dekripsi (hex, iv||ct||tag)" ke Pesan — ' +
            'atau langsung pakai mode Bolak-balik.</p>' +
            '<button type="button" class="btn btn-sm btn-outline-primary" id="kembaliRoundtrip">' +
            '<i class="bi bi-arrow-repeat"></i> Pakai Bolak-balik sekarang</button>';

        document.getElementById('kembaliRoundtrip').addEventListener('click', () => {
            tandaiAksi('roundtrip');
            jadwalkan(true);
        });
    }

    // ============ interaktivitas Hybrid Trio ============

    async function jalankanTrio() {
        const pt = document.getElementById('plaintext').value.trim();
        const pass = document.getElementById('passphrase').value;

        if (!pt || !pass) {
            trioError.textContent = 'Pesan terang dan frasa sandi tidak boleh kosong.';
            trioError.classList.remove('d-none');
            return;
        }

        trioError.classList.add('d-none');
        trioSubmit.disabled = true;
        trioSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menghitung...';

        try {
            const res = await fetch('{{ route('lab.trio') }}', {
                method: 'POST',
                headers: headerPermintaan(),
                body: JSON.stringify({ plaintext: pt, passphrase: pass })
            });

            const data = await res.json().catch(() => null);

            if (!res.ok || !data || data.ok !== true) {
                throw new Error(data?.error || data?.message || 'Gagal menjalankan enkripsi Hybrid Trio.');
            }

            // Render paket ETM
            document.getElementById('trioCiphertext').textContent = data.ciphertextHex;
            document.getElementById('trioSalt').textContent = data.saltHex;
            document.getElementById('trioNonce').textContent = data.nonceHex;
            document.getElementById('trioTag').textContent = data.tagHex;

            // Render baris jejak komputasi per karakter
            const tbody = document.getElementById('trioTraceBody');
            tbody.innerHTML = (data.rows || []).map(r => `
                <tr>
                    <td class="text-muted-custom">${r.index + 1}</td>
                    <td><strong class="font-monospace text-neon">${escapeHtml(r.char)}</strong></td>
                    <td class="font-monospace">${r.plain}</td>
                    <td class="font-monospace text-info">${r.z_lfsr}</td>
                    <td class="font-monospace text-warning">${r.z_rc4}</td>
                    <td class="font-monospace text-primary">${r.z_chacha}</td>
                    <td class="font-monospace">${r.stage1}</td>
                    <td class="font-monospace">${r.stage2}</td>
                    <td><span class="badge text-bg-success font-monospace">${r.cipher}</span></td>
                </tr>
            `).join('');

            trioKosong.classList.add('d-none');
            trioBundle.classList.remove('d-none');
            trioTrace.classList.remove('d-none');
        } catch (err) {
            trioError.textContent = err.message || 'Terjadi kesalahan sistem.';
            trioError.classList.remove('d-none');
        } finally {
            trioSubmit.disabled = false;
            trioSubmit.innerHTML = '<i class="bi bi-shield-lock"></i> Enkripsi + Jejak';
        }
    }

    trioContoh?.addEventListener('click', () => {
        document.getElementById('plaintext').value = 'KRIPTO';
        document.getElementById('passphrase').value = 'KUNCI';
        jalankanTrio();
    });

    trioForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        jalankanTrio();
    });

    select.addEventListener('change', () => {
        if (currentAction === 'dekripsi' && !terlihatCiphertext(message.value)) {
            tandaiAksi('roundtrip');
        }

        render();
        jadwalkan(true);
    });

    message.addEventListener('input', () => jadwalkan(false));
    key.addEventListener('input', () => jadwalkan(false));

    document.querySelectorAll('#actionButtons button[data-action]').forEach(button => {
        button.addEventListener('click', () => {
            tandaiAksi(button.dataset.action);
            jadwalkan(true);
        });
    });

    runButton.addEventListener('click', () => jadwalkan(true));

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') jadwalkan(true);
    });

    render();
    jadwalkan(true);
})();
</script>
@endpush

@endsection
