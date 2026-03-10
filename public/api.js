/**
 * API client for ehist-sabs. Uses session cookies (credentials: 'include').
 * Base path: /api.php (same origin).
 */
(function (global) {
    'use strict';

    const BASE = '/api.php';

    function getUrl(path, query) {
        const p = path.startsWith('/') ? path.slice(1) : path;
        let url = BASE + (p ? '/' + p : '');
        if (query && typeof query === 'object') {
            const params = new URLSearchParams();
            Object.keys(query).forEach(function (k) {
                if (query[k] !== undefined && query[k] !== null && query[k] !== '') {
                    params.set(k, query[k]);
                }
            });
            if (params.toString()) {
                url += '?' + params.toString();
            }
        }
        return url;
    }

    async function request(method, path, body, query) {
        const url = getUrl(path, query);
        const opts = {
            method,
            credentials: 'include',
            headers: {},
        };
        if (body !== undefined && body !== null && (method === 'POST' || method === 'PATCH' || method === 'PUT')) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const text = await res.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (_) {
            data = { error: text || 'Invalid response' };
        }
        if (!res.ok) {
            const err = new Error(data && data.error ? data.error : 'Request failed');
            err.status = res.status;
            err.data = data;
            throw err;
        }
        return data;
    }

    const api = {
        get: (path, query) => request('GET', path, undefined, query),
        post: (path, body) => request('POST', path, body),
        patch: (path, body) => request('PATCH', path, body),
        delete: (path) => request('DELETE', path),

        auth: {
            register: (body) => api.post('auth/register', body),
            login: (body) => api.post('auth/login', body),
            logout: () => api.post('auth/logout'),
            me: () => api.get('auth/me'),
            forgotPassword: (body) => api.post('auth/forgot-password', body),
            resetPassword: (body) => api.post('auth/reset-password', body),
        },
        services: {
            providers: () => api.get('services/providers'),
            byProvider: (providerId) => api.get('services/' + providerId),
        },
        timeSlots: {
            available: (providerId, date) => api.get('time-slots/available', { provider_id: providerId, date: date || undefined }),
        },
        bookings: {
            list: (params) => api.get('bookings', params),
            create: (body) => api.post('bookings', body),
            cancel: (id) => api.post('bookings/' + id + '/cancel'),
            updateStatus: (id, status) => api.post('bookings/' + id + '/status', { status }),
            reschedule: (id, body) => api.patch('bookings/' + id + '/reschedule', body),
        },
        notifications: {
            list: (unreadOnly) => api.get('notifications', unreadOnly ? { unread_only: '1' } : undefined),
            markRead: (id) => api.post('notifications/' + id + '/read'),
            markReadMany: (ids) => api.post('notifications/read', { ids }),
        },
        provider: {
            services: {
                list: () => api.get('provider/services'),
                create: (body) => api.post('provider/services', body),
                update: (id, body) => api.patch('provider/services/' + id, body),
                delete: (id) => api.delete('provider/services/' + id),
            },
            timeSlots: {
                list: (dateFrom, dateTo) => api.get('time-slots', { date_from: dateFrom || undefined, date_to: dateTo || undefined }),
                create: (body) => api.post('time-slots', body),
                update: (id, body) => api.patch('time-slots/' + id, body),
                delete: (id) => api.delete('time-slots/' + id),
            },
        },
        admin: {
            overview: () => api.get('admin/overview'),
            users: (includeDeleted) => api.get('admin/users', includeDeleted ? { deleted: '1' } : undefined),
            updateUser: (id, body) => api.patch('admin/users/' + id, body),
        },
        health: () => api.get(''),
    };

    global.api = api;
})(typeof window !== 'undefined' ? window : this);




