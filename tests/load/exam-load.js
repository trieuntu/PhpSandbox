// Usage: k6 run -e BASE_URL=https://host -e EXAM_ID=1 -e COUNT=80 tests/load/exam-load.js  (see loadtest:prepare)
import http from 'k6/http';
import exec from 'k6/execution';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE = (__ENV.BASE_URL || 'http://localhost').replace(/\/$/, '');
const EXAM_ID = __ENV.EXAM_ID;
const COUNT = parseInt(__ENV.COUNT || '80', 10);
const PASSWORD = __ENV.PASSWORD || 'LoadTest#2026';
const HOLD = __ENV.HOLD || '5m';
const THINK_MIN = parseInt(__ENV.THINK_MIN || '15', 10);
const THINK_MAX = parseInt(__ENV.THINK_MAX || '40', 10);

const runToResult = new Trend('run_to_result_ms', true);
const runFailed = new Rate('run_failed');
const throttled = new Rate('throttled_429');

export const options = {
    scenarios: {
        students: {
            executor: 'ramping-vus',
            stages: [
                { duration: '20s', target: COUNT },
                { duration: HOLD, target: COUNT },
                { duration: '10s', target: 0 },
            ],
            gracefulRampDown: '30s',
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{kind:page}': ['p(95)<2000'],
        'http_req_duration{kind:api}': ['p(95)<1000'],
        run_to_result_ms: ['p(95)<8000'],
        run_failed: ['rate<0.01'],
        throttled_429: ['rate<0.001'],
    },
};

const CODE = '<?php $a = []; for ($i = 0; $i < 20000; $i++) { $a[] = $i * $i; } echo "Sum: " . array_sum($a); ?>';

let ready = false;
let csrf = '';

function token(html, re) {
    const m = html.match(re);
    return m ? m[1] : '';
}

function metaToken(html) {
    return token(html, /<meta name="csrf-token" content="([^"]+)"/);
}

function login(email) {
    const page = http.get(`${BASE}/login`, { tags: { kind: 'page' } });
    const res = http.post(`${BASE}/login`, {
        _token: token(page.body, /name="_token" value="([^"]+)"/),
        email,
        password: PASSWORD,
    }, { tags: { kind: 'page' } });
    return check(res, { 'login ok': (r) => r.status === 200 && !r.url.includes('/login') });
}

function enterExam() {
    const lobby = http.get(`${BASE}/exams/${EXAM_ID}`, { tags: { kind: 'page' } });
    http.post(`${BASE}/exams/${EXAM_ID}/start`, { _token: metaToken(lobby.body) }, { tags: { kind: 'page' } });
    const editor = http.get(`${BASE}/exams/${EXAM_ID}/editor`, { tags: { kind: 'page' } });
    check(editor, { 'editor 200': (r) => r.status === 200 });
    csrf = metaToken(editor.body);
}

function runCode() {
    const started = Date.now();
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf };
    const exe = http.post(`${BASE}/api/sandbox/execute`, JSON.stringify({
        code: CODE, context_type: 'exam', context_id: parseInt(EXAM_ID, 10),
    }), { headers, tags: { kind: 'api' } });
    throttled.add(exe.status === 429);
    if (exe.status !== 200) { runFailed.add(true); return; }

    const jobId = exe.json('job_id');
    for (let i = 0; i < 30; i++) {
        sleep(1);
        const poll = http.get(`${BASE}/api/sandbox/job/${jobId}`, { headers: { Accept: 'application/json' }, tags: { kind: 'api' } });
        throttled.add(poll.status === 429);
        const status = poll.status === 200 ? poll.json('status') : 'error';
        if (!['pending', 'running', 'queued'].includes(status)) {
            http.get(`${BASE}/api/sandbox/preview/${jobId}`, { tags: { kind: 'api' } });
            runToResult.add(Date.now() - started);
            runFailed.add(status !== 'success');
            return;
        }
    }
    runFailed.add(true);
}

export default function () {
    if (!ready) {
        const n = ((exec.vu.idInTest - 1) % COUNT) + 1;
        if (!login(`loadtest${String(n).padStart(3, '0')}@loadtest.local`)) { sleep(5); return; }
        enterExam();
        ready = true;
    }
    runCode();
    http.post(`${BASE}/api/heartbeat`, null, { headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, tags: { kind: 'api' } });
    sleep(THINK_MIN + Math.random() * (THINK_MAX - THINK_MIN));
}
