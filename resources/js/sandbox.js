/**
 * Standalone sandbox execution module.
 * Can be imported or used directly via script tag.
 */

const SandboxClient = {
    csrfToken: () => document.querySelector('meta[name="csrf-token"]')?.content ?? '',

    async execute(code, contextType = 'free', contextId = null) {
        const res = await fetch('/api/sandbox/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken(),
            },
            body: JSON.stringify({ code, context_type: contextType, context_id: contextId }),
        });

        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || `HTTP ${res.status}`);
        }

        return res.json();
    },

    async pollJob(jobId, { onStatusChange, maxAttempts = 30, intervalMs = 1000 } = {}) {
        let attempts = 0;

        while (attempts < maxAttempts) {
            await new Promise(r => setTimeout(r, intervalMs));
            attempts++;

            try {
                const res = await fetch(`/api/sandbox/job/${jobId}`);
                const data = await res.json();

                if (onStatusChange) onStatusChange(data.status, data);

                if (!['pending', 'running', 'queued'].includes(data.status)) {
                    return data;
                }
            } catch (e) {
                // network hiccup — continue polling
            }
        }

        throw new Error('Polling timeout exceeded');
    },

    previewUrl(submissionId) {
        return `/api/sandbox/preview/${submissionId}`;
    },

    async runAndPoll(code, contextType = 'free', contextId = null, callbacks = {}) {
        const { onQueued, onRunning, onComplete, onError } = callbacks;

        try {
            if (onQueued) onQueued();
            const jobData = await this.execute(code, contextType, contextId);
            const jobId = jobData.job_id || jobData.submission_id;

            const result = await this.pollJob(jobId, {
                onStatusChange: (status, data) => {
                    if (status === 'running' && onRunning) onRunning(data);
                },
            });

            if (onComplete) onComplete(result);
            return result;
        } catch (e) {
            if (onError) onError(e);
            throw e;
        }
    },
};

export default SandboxClient;
window.SandboxClient = SandboxClient;
