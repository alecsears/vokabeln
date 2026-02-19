// API fetch wrapper
const API = {
    async post(url, data) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (e) {
            console.error('API error:', e);
            throw e;
        }
    },
    async get(url, params = {}) {
        const qs = new URLSearchParams(params).toString();
        const fullUrl = qs ? url + '?' + qs : url;
        try {
            const res = await fetch(fullUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (e) {
            console.error('API error:', e);
            throw e;
        }
    }
};
