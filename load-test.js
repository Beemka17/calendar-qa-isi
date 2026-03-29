import http from 'k6/http';
import { check, sleep } from 'k6';

// ⚙️ Konfigurasi skenario
export const options = {
    stages: [
        { duration: '30s', target: 10 },  // ramp up ke 10 user dalam 30 detik
        { duration: '1m', target: 10 },  // tahan 10 user selama 1 menit
        { duration: '30s', target: 0 },   // ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% request harus < 2 detik
        http_req_failed: ['rate<0.01'],  // error rate < 1%
    },
};

// 🔐 Login dulu untuk dapat session cookie
function login() {
    const res = http.post('http://127.0.0.1:8000/login', {
        username: 'admin',      // ← sesuaikan dengan user test Anda
        password: 'Admin@12345',   // ← sesuaikan
        _token: getCsrfToken(),
    });

    return res.cookies;
}

function getCsrfToken() {
    const res = http.get('http://127.0.0.1:8000/login');
    const match = res.body.match(/name="_token" value="([^"]+)"/);
    return match ? match[1] : '';
}

export default function () {
    const cookies = login();

    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const jar = http.cookieJar();
    Object.entries(cookies).forEach(([name, values]) => {
        jar.set('http://127.0.0.1:8000', name, values[0].value);
    });

    // ✅ Test GET /api/events
    const eventsRes = http.get(
        'http://127.0.0.1:8000/api/events?start=2025-01-01&end=2025-12-31',
        { headers }
    );

    check(eventsRes, {
        'GET /api/events → status 200': (r) => r.status === 200,
        'GET /api/events → response < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(1);

    // ✅ Test GET /api/event-groups
    const groupsRes = http.get(
        'http://127.0.0.1:8000/api/event-groups',
        { headers }
    );

    check(groupsRes, {
        'GET /api/event-groups → status 200': (r) => r.status === 200,
    });

    sleep(1);
}