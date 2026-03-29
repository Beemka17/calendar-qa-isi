import http from 'k6/http';
import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { Counter, Trend } from 'k6/metrics';

// ── Custom metrics ────────────────────────────────────────────────────────────
const wsMessagesReceived = new Counter('ws_messages_received');
const wsConnectTime = new Trend('ws_connect_time');

// ── Konfigurasi — sesuaikan 3 variabel ini ───────────────────────────────────
const BASE_URL = 'http://127.0.0.1:8000';
const REVERB_URL = 'ws://localhost:8080/app/k4eevwd3yz4k1gaxxmeq';
const CREDENTIALS = { username: 'admin', password: 'Admin@12345' };

// ── Opsi skenario ─────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        websocket_listeners: {
            executor: 'constant-vus',
            vus: 3,
            duration: '3m',
            exec: 'wsScenario',
        },
        http_actors: {
            executor: 'constant-vus',
            vus: 2,
            duration: '3m',
            exec: 'httpScenario',
            startTime: '5s',
        },
    },
    thresholds: {
        'http_req_duration': ['p(95)<8000'],
        'http_req_failed': ['rate<0.10'],
        'ws_messages_received': ['count>0'],
    },
};

// ── Helper: login dan kembalikan cookie string ────────────────────────────────
// Setiap VU login sendiri — menghindari masalah share state antar VU
function doLogin() {
    // Step 1: GET halaman login untuk ambil CSRF token dari form
    const loginPageRes = http.get(`${BASE_URL}/login`);

    if (loginPageRes.status !== 200) {
        console.error(`[LOGIN] Gagal GET login page, status: ${loginPageRes.status}`);
        return null;
    }

    const match = loginPageRes.body.match(/name="_token"\s+value="([^"]+)"/);
    if (!match) {
        console.error('[LOGIN] CSRF token tidak ditemukan di halaman login');
        return null;
    }
    const csrfToken = match[1];

    // Step 2: Ambil XSRF-TOKEN dari cookie response halaman login
    const xsrfCookie = loginPageRes.cookies['XSRF-TOKEN'];
    const xsrfToken = xsrfCookie ? decodeURIComponent(xsrfCookie[0].value) : '';

    // Step 3: POST login
    const loginRes = http.post(
        `${BASE_URL}/login`,
        {
            username: CREDENTIALS.username,
            password: CREDENTIALS.password,
            _token: csrfToken,
        },
        {
            headers: {
                'X-XSRF-TOKEN': xsrfToken,
                'Accept': 'text/html,application/json',
            },
            redirects: 5,
        }
    );

    // Step 4: Ambil session cookie dari response setelah redirect
    // Laravel menyimpan session di cookie setelah login berhasil
    const sessionCookieName = 'quality_dept_schedule_activity_session';
    const newXsrfCookie = loginRes.cookies['XSRF-TOKEN'];
    const sessionCookie = loginRes.cookies[sessionCookieName];

    if (!sessionCookie) {
        console.error(`[LOGIN] Session cookie tidak ditemukan. Status: ${loginRes.status}, URL akhir: ${loginRes.url}`);
        console.error(`[LOGIN] Cookies tersedia: ${JSON.stringify(Object.keys(loginRes.cookies))}`);
        return null;
    }

    const sessionValue = sessionCookie[0].value;
    const newXsrf = newXsrfCookie ? decodeURIComponent(newXsrfCookie[0].value) : xsrfToken;

    console.log(`[LOGIN] ✅ Berhasil. Session length: ${sessionValue.length}`);

    // Kembalikan cookie string siap pakai
    return {
        cookieStr: `XSRF-TOKEN=${encodeURIComponent(newXsrf)}; ${sessionCookieName}=${sessionValue}`,
        xsrfToken: newXsrf,
    };
}

// ── Skenario WebSocket ────────────────────────────────────────────────────────
export function wsScenario() {
    const startTime = Date.now();

    const res = ws.connect(REVERB_URL, {}, function (socket) {
        wsConnectTime.add(Date.now() - startTime);

        socket.on('open', () => {
            console.log('[WS] Koneksi terbuka, subscribe...');
            socket.send(JSON.stringify({
                event: 'pusher:subscribe',
                data: { channel: 'calendar' },
            }));

            // ✅ Set timeout — tutup koneksi setelah 170 detik
            // Ini BERBEDA dengan sleep() — socket tetap aktif menerima pesan
            // sleep() memblokir eksekusi, setTimeout tidak
            socket.setTimeout(function () {
                console.log('[WS] Timeout, menutup koneksi...');
                socket.close();
            }, 170000); // 170 detik dalam milidetik
        });

        socket.on('message', (rawData) => {
            let msg;
            try {
                msg = JSON.parse(rawData);
            } catch (e) {
                return;
            }

            wsMessagesReceived.add(1);
            console.log(`[WS] Pesan diterima: ${msg.event}`);

            if (msg.event === 'pusher:connection_established') {
                console.log('[WS] ✅ Handshake sukses');
            }

            if (msg.event === 'pusher_internal:subscription_succeeded') {
                console.log('[WS] ✅ Subscribe sukses — siap terima broadcast');
            }

            if (msg.event === 'calendar.changed') {
                let payload = {};
                try { payload = JSON.parse(msg.data || '{}'); } catch (e) { }

                check(payload, {
                    'broadcast: action valid': (p) =>
                        ['created', 'updated', 'deleted', 'moved', 'resized']
                            .includes(p.action),
                    'broadcast: eventId adalah number': (p) =>
                        typeof p.eventId === 'number',
                });

                console.log(`[WS] 🔥 BROADCAST: action=${payload.action}, eventId=${payload.eventId}`);
            }
        });

        socket.on('error', (e) => {
            const errMsg = e.error ? e.error() : String(e);
            if (!errMsg.includes('close sent')) {
                console.error('[WS] Error:', errMsg);
            }
        });

        socket.on('close', () => {
            console.log('[WS] Koneksi ditutup');
        });

        // ✅ HAPUS sleep(170) dari sini — diganti setTimeout di atas
        // sleep() di sini memblokir event loop k6 sehingga
        // socket.on('message') tidak bisa dieksekusi
    });

    check(res, {
        'WebSocket berhasil terhubung (101)': (r) => r && r.status === 101,
    });

    // sleep singkat sebelum reconnect jika skenario loop
    sleep(1);
}

// ── Skenario HTTP ─────────────────────────────────────────────────────────────
export function httpScenario() {

    // Login di awal setiap VU (bukan setiap iterasi)
    // __VU adalah nomor VU saat ini, __ITER adalah nomor iterasi
    // Login hanya dilakukan di iterasi pertama setiap VU
    if (__ITER === 0) {
        const auth = doLogin();
        if (!auth) {
            console.error('[HTTP] Login gagal, skip semua iterasi VU ini');
            sleep(5);
            return;
        }
        // Simpan di global VU state menggunakan exec.vu.tags (workaround k6)
        // Cara paling sederhana: gunakan variabel module-level per VU
        httpState.cookieStr = auth.cookieStr;
        httpState.xsrfToken = auth.xsrfToken;
        console.log(`[HTTP] VU ${__VU} login berhasil`);
    }

    if (!httpState.cookieStr) {
        console.warn('[HTTP] Tidak ada session, coba login ulang...');
        const auth = doLogin();
        if (!auth) {
            sleep(3);
            return;
        }
        httpState.cookieStr = auth.cookieStr;
        httpState.xsrfToken = auth.xsrfToken;
    }

    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Cookie': httpState.cookieStr,
        'X-XSRF-TOKEN': httpState.xsrfToken,
    };

    // ── GET event groups ──────────────────────────────────────────────────────
    const groupsRes = http.get(
        `${BASE_URL}/api/event-groups`,
        { headers }
    );

    if (groupsRes.status === 401) {
        console.warn('[HTTP] Session expired, login ulang...');
        const auth = doLogin();
        if (auth) {
            httpState.cookieStr = auth.cookieStr;
            httpState.xsrfToken = auth.xsrfToken;
        }
        sleep(2);
        return;
    }

    check(groupsRes, {
        'GET /api/event-groups → 200': (r) => r.status === 200,
    });

    let groups;
    try {
        groups = groupsRes.json();
    } catch (e) {
        console.error('[HTTP] Gagal parse response event-groups:', groupsRes.body.substring(0, 100));
        sleep(2);
        return;
    }

    const activeGrp = Array.isArray(groups) ? groups.find(g => g.is_active) : null;

    if (!activeGrp) {
        console.warn(`[HTTP] Tidak ada event group aktif. Status: ${groupsRes.status}`);
        sleep(2);
        return;
    }

    // ── POST event — trigger broadcast 'created' ──────────────────────────────
    const storeRes = http.post(
        `${BASE_URL}/api/events`,
        JSON.stringify({
            title: `Load Test ${__VU}-${__ITER}-${Date.now()}`,
            event_group_id: activeGrp.id,
            start_at: '2025-08-01 09:00:00',
            end_at: '2025-08-01 17:00:00',
        }),
        { headers }
    );

    console.log(`[HTTP] VU${__VU} POST → ${storeRes.status}`);

    check(storeRes, {
        'POST /api/events → 201': (r) => r.status === 201,
    });

    if (storeRes.status !== 201) {
        console.error('[HTTP] POST gagal:', storeRes.body.substring(0, 200));
        sleep(2);
        return;
    }

    const eventId = storeRes.json('id');
    sleep(1);

    // ── PATCH event — trigger broadcast 'updated' ─────────────────────────────
    const updateRes = http.patch(
        `${BASE_URL}/api/events/${eventId}`,
        JSON.stringify({ title: `Updated VU${__VU} ${Date.now()}` }),
        { headers }
    );

    check(updateRes, {
        'PATCH /api/events/:id → 200': (r) => r.status === 200,
    });

    console.log(`[HTTP] VU${__VU} PATCH → ${updateRes.status}`);
    sleep(1);

    // ── DELETE event — trigger broadcast 'deleted' ────────────────────────────
    const deleteRes = http.del(
        `${BASE_URL}/api/events/${eventId}`,
        null,
        { headers }
    );

    check(deleteRes, {
        'DELETE /api/events/:id → 204': (r) => r.status === 204,
    });

    console.log(`[HTTP] VU${__VU} DELETE → ${deleteRes.status}`);
    sleep(2);
}

// ── State per VU — setiap VU punya instance sendiri ──────────────────────────
// Di k6, module-level variables di-isolasi per VU secara otomatis
const httpState = {
    cookieStr: null,
    xsrfToken: null,
};