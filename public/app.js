/**
 * Single-page app: auth, views, booking, provider, admin.
 * Depends on api.js (global api).
 */
(function () {
    'use strict';

    const ROLE_ADMIN = 1;
    const ROLE_PROVIDER = 2;
    const ROLE_CUSTOMER = 3;

    let state = {
        user: null,
        view: 'login',
        providers: [],
        servicesByProvider: {},
        selectedSlot: null,
    };

    // ---------- DOM refs (set in init)
    let refs = {};

    function $(id) {
        return document.getElementById(id);
    }

    function show(el, show) {
        if (!el) return;
        el.classList.toggle('hidden', !show);
    }

    function setLoading(button, loading) {
        if (!button) return;
        button.disabled = loading;
        button.dataset.originalText = button.dataset.originalText || button.textContent;
        button.textContent = loading ? 'Please wait…' : (button.dataset.originalText || 'Submit');
    }

    function showMessage(text, isError) {
        const el = $('globalMessage');
        if (!el) return;
        el.textContent = text;
        el.className = 'global-message ' + (isError ? 'error' : 'success');
        show(el, true);
        setTimeout(function () {
            show(el, false);
        }, 5000);
    }

    function showApiError(err) {
        let msg = (err && err.data && err.data.error) ? err.data.error : (err && err.message) || 'Something went wrong';
        if (err && err.data && err.data.detail) {
            msg += ': ' + err.data.detail;
        }
        showMessage(msg, true);
    }

    // ---------- View switch
    function setView(viewName) {
        state.view = viewName;
        document.querySelectorAll('.view').forEach(function (v) {
            show(v, v.id === 'view-' + viewName);
        });
        const header = $('header');
        show(header, !!state.user);

        if (state.user) {
            $('userName').textContent = state.user.name || state.user.email || 'User';
            const role = parseInt(state.user.role_id, 10);
            document.querySelectorAll('.nav-provider').forEach(function (el) {
                show(el, role === ROLE_PROVIDER || role === ROLE_ADMIN);
            });
            document.querySelectorAll('.nav-admin').forEach(function (el) {
                show(el, role === ROLE_ADMIN);
            });
        }

        if (viewName === 'book') {
            loadBookProviders();
            loadBookingsPreview();
        } else if (viewName === 'my-bookings') {
            loadMyBookings();
        } else if (viewName === 'provider') {
            loadProviderServices();
            loadProviderSlots();
            loadProviderBookings();
        } else if (viewName === 'admin') {
            loadAdminOverview();
            loadAdminUsers();
            loadAdminBookings();
        }
    }

    // ---------- Auth
    async function checkAuth() {
        try {
            const data = await api.auth.me();
            state.user = data.user || data;
            setView(state.user.role_id === ROLE_CUSTOMER ? 'book' : (state.user.role_id === ROLE_ADMIN ? 'admin' : 'provider'));
            return;
        } catch (_) {
            state.user = null;
        }
        setView('login');
    }

    function initAuth() {
        const formLogin = $('formLogin');
        const formRegister = $('formRegister');
        if (formLogin) {
            formLogin.onsubmit = async function (e) {
                e.preventDefault();
                const btn = formLogin.querySelector('button[type="submit"]');
                setLoading(btn, true);
                try {
                    await api.auth.login({
                        email: $('loginEmail').value.trim(),
                        password: $('loginPassword').value,
                    });
                    await checkAuth();
                } catch (err) {
                    showApiError(err);
                }
                setLoading(btn, false);
            };
        }
        if (formRegister) {
            formRegister.onsubmit = async function (e) {
                e.preventDefault();
                const btn = formRegister.querySelector('button[type="submit"]');
                setLoading(btn, true);
                try {
                    await api.auth.register({
                        name: $('regName').value.trim(),
                        email: $('regEmail').value.trim(),
                        password: $('regPassword').value,
                        role_id: parseInt($('regRole').value, 10) || 3,
                    });
                    showMessage('Registered. Please sign in.');
                    setView('login');
                } catch (err) {
                    showApiError(err);
                }
                setLoading(btn, false);
            };
        }
        const linkRegister = document.getElementById('linkRegister');
        const linkLogin = document.getElementById('linkLogin');
        if (linkRegister) linkRegister.onclick = function (e) { e.preventDefault(); setView('register'); };
        if (linkLogin) linkLogin.onclick = function (e) { e.preventDefault(); setView('login'); };

        const btnLogout = $('btnLogout');
        if (btnLogout) {
            btnLogout.onclick = async function () {
                try {
                    await api.auth.logout();
                } catch (_) {}
                state.user = null;
                setView('login');
            };
        }
    }

    function initNav() {
        document.querySelectorAll('.nav-link[data-view], .logo[data-view]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const v = this.getAttribute('data-view');
                if (v) setView(v);
            });
        });
    }

    // ---------- Book flow
    async function loadBookProviders() {
        const sel = $('bookProvider');
        if (!sel) return;
        try {
            const data = await api.services.providers();
            state.providers = data.providers || [];
            sel.innerHTML = '<option value="">Select provider</option>';
            state.providers.forEach(function (p) {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name || p.email || 'Provider #' + p.id;
                sel.appendChild(opt);
            });
            $('bookService').innerHTML = '<option value="">Select service</option>';
            $('bookService').disabled = true;
            state.selectedSlot = null;
            renderBookSlots();
        } catch (err) {
            showApiError(err);
        }
    }

    async function loadServicesForProvider(providerId) {
        if (state.servicesByProvider[providerId]) return state.servicesByProvider[providerId];
        const data = await api.services.byProvider(providerId);
        const list = data.services || [];
        state.servicesByProvider[providerId] = list;
        return list;
    }

    function onBookProviderChange() {
        const pid = $('bookProvider').value;
        const sel = $('bookService');
        sel.innerHTML = '<option value="">Select service</option>';
        sel.disabled = !pid;
        if (!pid) {
            state.selectedSlot = null;
            renderBookSlots();
            return;
        }
        loadServicesForProvider(pid).then(function (services) {
            sel.innerHTML = '<option value="">Select service</option>';
            services.forEach(function (s) {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = (s.name || 'Service') + (s.duration_min ? ' (' + s.duration_min + ' min)' : '');
                sel.appendChild(opt);
            });
            state.selectedSlot = null;
            renderBookSlots();
        }).catch(showApiError);
    }

    async function loadAvailableSlots() {
        const providerId = $('bookProvider').value;
        const date = $('bookDate').value;
        if (!providerId || !date) {
            renderBookSlots([]);
            return;
        }
        try {
            const data = await api.timeSlots.available(providerId, date);
            renderBookSlots(data.slots || []);
        } catch (err) {
            showApiError(err);
            renderBookSlots([]);
        }
    }

    function renderBookSlots(slots) {
        const wrap = $('bookSlots');
        const hint = $('bookSlotsHint');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (!slots || slots.length === 0) {
            hint.classList.remove('hidden');
            hint.textContent = (slots && slots.length === 0) ? 'No available slots for this date.' : 'Select provider, service and date to see available slots.';
            return;
        }
        hint.classList.add('hidden');
        state.selectedSlot = null;
        slots.forEach(function (slot) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'slot-btn';
            const startStr = String(slot.start_time || '');
            const endStr = String(slot.end_time || '');
            const start = startStr.length >= 5 ? startStr.substring(0, 5) : startStr;
            const end = endStr.length >= 5 ? endStr.substring(0, 5) : endStr;
            btn.textContent = start + ' – ' + end;
            btn.dataset.start = startStr.length >= 8 ? startStr : (startStr.length === 5 ? startStr + ':00' : startStr);
            btn.dataset.end = endStr.length >= 8 ? endStr : (endStr.length === 5 ? endStr + ':00' : endStr);
            btn.onclick = function () {
                state.selectedSlot = { start_time: slot.start_time, end_time: slot.end_time };
                wrap.querySelectorAll('.slot-btn').forEach(function (b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
            };
            wrap.appendChild(btn);
        });
    }

    function initBookForm() {
        const form = $('formBook');
        const selProvider = $('bookProvider');
        const selService = $('bookService');
        const dateInput = $('bookDate');

        const today = new Date().toISOString().split('T')[0];
        if (dateInput) dateInput.setAttribute('min', today);

        if (selProvider) selProvider.addEventListener('change', onBookProviderChange);
        if (dateInput) dateInput.addEventListener('change', loadAvailableSlots);
        if (selService) selService.addEventListener('change', function () {
            state.selectedSlot = null;
            renderBookSlots([]);
            loadAvailableSlots();
        });

        document.querySelectorAll('input[name="bookSlotType"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                const isCustom = this.value === 'custom';
                show($('bookSlotsWrap'), !isCustom);
                show($('bookCustomTimeWrap'), isCustom);
                if (!isCustom) state.selectedSlot = null;
            });
        });
        show($('bookCustomTimeWrap'), false);

        if (form) {
            form.onsubmit = async function (e) {
                e.preventDefault();
                const providerId = selProvider.value;
                const serviceId = selService.value;
                const slotDate = dateInput.value;
                const isCustom = document.querySelector('input[name="bookSlotType"]:checked').value === 'custom';
                let startTime, endTime;
                if (isCustom) {
                    const customStart = $('bookCustomStart').value;
                    const customEnd = $('bookCustomEnd').value;
                    if (!customStart || !customEnd) {
                        showMessage('Please enter custom start and end time.', true);
                        return;
                    }
                    startTime = customStart.length === 5 ? customStart + ':00' : customStart;
                    endTime = customEnd.length === 5 ? customEnd + ':00' : customEnd;
                } else {
                    if (!state.selectedSlot) {
                        showMessage('Please select a time slot.', true);
                        return;
                    }
                    startTime = state.selectedSlot.start_time;
                    endTime = state.selectedSlot.end_time;
                }
                const btn = $('btnBookSubmit');
                setLoading(btn, true);
                try {
                    const result = await api.bookings.create({
                        provider_id: parseInt(providerId, 10),
                        service_id: parseInt(serviceId, 10),
                        slot_date: slotDate,
                        start_time: startTime,
                        end_time: endTime,
                    });
                    const confirmed = result && result.status === 'approved';
                    showMessage(confirmed ? 'Booking confirmed.' : 'Booking requested. Provider will accept, reschedule or reject.');
                    form.reset();
                    state.selectedSlot = null;
                    renderBookSlots([]);
                    document.querySelector('input[name="bookSlotType"][value="predefined"]').checked = true;
                    show($('bookSlotsWrap'), true);
                    show($('bookCustomTimeWrap'), false);
                    await loadBookingsPreview();
                } catch (err) {
                    showApiError(err);
                }
                setLoading(btn, false);
            };
        }
    }

    async function loadBookingsPreview() {
        const el = $('bookingsPreview');
        if (!el) return;
        try {
            const data = await api.bookings.list({ limit: 5 });
            const list = data.bookings || [];
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No bookings yet.</p>';
                return;
            }
            el.innerHTML = list.map(function (b) {
                return '<div class="preview-item">' + formatBookingShort(b) + '</div>';
            }).join('');
        } catch (_) {
            el.innerHTML = '<p class="empty-msg">Could not load bookings.</p>';
        }
    }

    function formatBookingShort(b) {
        const d = (b.slot_date || '').substring(0, 10);
        const t = (b.start_time || '').substring(0, 5);
        return d + ' ' + t + ' – #' + b.id + ' (' + (b.status || 'pending') + ')';
    }

    function formatBookingLine(b, providerName, serviceName) {
        const d = (b.slot_date || '').substring(0, 10);
        const t = (b.start_time || '').substring(0, 5);
        const prov = providerName || ('Provider #' + b.provider_id);
        const svc = serviceName || ('Service #' + b.service_id);
        return d + ' ' + t + ' · ' + prov + ' · ' + svc + ' · ' + (b.status || 'pending');
    }

    // ---------- My Bookings
    async function loadMyBookings() {
        const el = $('myBookingsList');
        if (!el) return;
        el.innerHTML = '<p class="loading">Loading…</p>';
        try {
            if (state.providers.length === 0) {
                const provData = await api.services.providers();
                state.providers = provData.providers || [];
            }
            const data = await api.bookings.list();
            const list = data.bookings || [];
            const providerIds = [...new Set(list.map(function (b) { return b.provider_id; }))];
            const nameMap = {};
            for (let i = 0; i < providerIds.length; i++) {
                const pid = providerIds[i];
                const prov = state.providers.find(function (p) { return p.id == pid; });
                if (prov) nameMap[pid] = prov.name || prov.email;
                else nameMap[pid] = null;
            }
            const serviceMap = {};
            for (let i = 0; i < providerIds.length; i++) {
                const pid = providerIds[i];
                const services = await loadServicesForProvider(pid);
                services.forEach(function (s) {
                    serviceMap[s.id] = s.name;
                });
            }
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No bookings.</p>';
                return;
            }
            el.innerHTML = list.map(function (b) {
                const canCancel = b.status === 'pending' || b.status === 'approved';
                const cancelBtn = canCancel
                    ? '<button type="button" class="btn-delete btn-sm" data-booking-id="' + b.id + '">Cancel</button>'
                    : '';
                return '<div class="booking-item" data-id="' + b.id + '">' +
                    '<div class="booking-info">' + formatBookingLine(b, nameMap[b.provider_id], serviceMap[b.service_id]) + '</div>' +
                    cancelBtn + '</div>';
            }).join('');
            el.querySelectorAll('.btn-delete[data-booking-id]').forEach(function (btn) {
                btn.onclick = function () {
                    if (!confirm('Cancel this booking?')) return;
                    const id = btn.dataset.bookingId;
                    api.bookings.cancel(id).then(function () {
                        loadMyBookings();
                        showMessage('Booking cancelled.');
                    }).catch(showApiError);
                };
            });
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Could not load bookings.</p>';
        }
    }

    // ---------- Provider
    function initProviderTabs() {
        document.querySelectorAll('#view-provider .tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                const name = this.dataset.tab;
                document.querySelectorAll('#view-provider .tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('#view-provider .tab-panel').forEach(function (p) {
                    show(p, p.id === 'tab-' + name);
                });
                this.classList.add('active');
                if (name === 'provider-services') loadProviderServices();
                if (name === 'provider-slots') loadProviderSlots();
                if (name === 'provider-bookings') loadProviderBookings();
            });
        });
    }

    async function loadProviderBookings() {
        const el = $('providerBookingsList');
        if (!el) return;
        try {
            const data = await api.bookings.list({ _: Date.now() });
            const list = Array.isArray(data.bookings) ? data.bookings : [];
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No bookings yet.</p>';
                return;
            }
            const serviceMap = {};
            const providerIds = [...new Set(list.map(function (b) { return b.provider_id; }))];
            for (let i = 0; i < providerIds.length; i++) {
                const services = await loadServicesForProvider(providerIds[i]);
                services.forEach(function (s) {
                    serviceMap[s.id] = s.name;
                });
            }
            el.innerHTML = list.map(function (b) {
                const d = (b.slot_date || '').substring(0, 10);
                const t = String(b.start_time || '').substring(0, 5) + '–' + String(b.end_time || '').substring(0, 5);
                const svc = serviceMap[b.service_id] || ('Service #' + b.service_id);
                const statusClass = b.status === 'pending' ? 'status-pending' : (b.status === 'approved' ? 'status-approved' : (b.status === 'rejected' ? 'status-rejected' : ''));
                let actions = '<span class="booking-actions">';
                if (b.status === 'pending') {
                    actions += '<button type="button" class="btn-sm btn-approve" data-id="' + b.id + '">Accept</button> ';
                    actions += '<button type="button" class="btn-sm btn-reject" data-id="' + b.id + '">Reject</button> ';
                }
                if (b.status === 'pending' || b.status === 'approved') {
                    actions += '<button type="button" class="btn-sm btn-reschedule" data-id="' + b.id + '" data-date="' + (b.slot_date || '') + '" data-start="' + String(b.start_time || '').substring(0, 5) + '" data-end="' + String(b.end_time || '').substring(0, 5) + '">Reschedule</button>';
                }
                actions += '</span>';
                return '<div class="booking-item ' + statusClass + '" data-booking-id="' + b.id + '">' +
                    '<div class="booking-info">' + d + ' ' + t + ' · ' + svc + ' · Customer #' + b.customer_id + ' · <strong>' + (b.status || '') + '</strong></div>' +
                    actions + '</div>';
            }).join('');
            el.querySelectorAll('.btn-approve').forEach(function (btn) {
                btn.onclick = function () {
                    api.bookings.updateStatus(btn.dataset.id, 'approved').then(function () {
                        loadProviderBookings();
                        showMessage('Booking accepted.');
                    }).catch(showApiError);
                };
            });
            el.querySelectorAll('.btn-reject').forEach(function (btn) {
                btn.onclick = function () {
                    api.bookings.updateStatus(btn.dataset.id, 'rejected').then(function () {
                        loadProviderBookings();
                        showMessage('Booking rejected.');
                    }).catch(showApiError);
                };
            });
            el.querySelectorAll('.btn-reschedule').forEach(function (btn) {
                btn.onclick = function () {
                    const newDate = prompt('New date (YYYY-MM-DD):', btn.dataset.date || '');
                    if (newDate === null) return;
                    const newStart = prompt('New start time (HH:MM):', btn.dataset.start || '09:00');
                    if (newStart === null) return;
                    const newEnd = prompt('New end time (HH:MM):', btn.dataset.end || '09:30');
                    if (newEnd === null) return;
                    const startTime = newStart.length === 5 ? newStart + ':00' : newStart;
                    const endTime = newEnd.length === 5 ? newEnd + ':00' : newEnd;
                    api.bookings.reschedule(btn.dataset.id, { slot_date: newDate, start_time: startTime, end_time: endTime }).then(function () {
                        loadProviderBookings();
                        showMessage('Booking rescheduled. Customer will be notified.');
                    }).catch(showApiError);
                };
            });
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading bookings.</p>';
        }
    }

    async function loadProviderServices() {
        const el = $('providerServicesList');
        if (!el) return;
        try {
            const data = await api.provider.services.list();
            const list = (data.services !== undefined) ? data.services : (data || []);
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No services. Add one below.</p>';
            } else {
                el.innerHTML = list.map(function (s) {
                    return '<div class="list-row">' +
                        '<span>' + (s.name || '') + '</span> ' +
                        '<span class="muted">' + (s.duration_min || 60) + ' min</span> ' +
                        '<button type="button" class="btn-delete btn-sm" data-service-id="' + s.id + '">Delete</button>' +
                        '</div>';
                }).join('');
                el.querySelectorAll('.btn-delete[data-service-id]').forEach(function (btn) {
                    btn.onclick = function () {
                        if (!confirm('Delete this service?')) return;
                        api.provider.services.delete(btn.dataset.serviceId).then(function () {
                            loadProviderServices();
                            state.servicesByProvider = {};
                        }).catch(showApiError);
                    };
                });
            }
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading services.</p>';
        }
    }

    async function loadProviderSlots() {
        const el = $('providerSlotsList');
        if (!el) return;
        try {
            const data = await api.provider.timeSlots.list();
            const list = (data.slots !== undefined) ? data.slots : (data || []);
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No time slots. Add one above.</p>';
            } else {
                el.innerHTML = list.map(function (s) {
                    const st = String(s.start_time || '');
                    const et = String(s.end_time || '');
                    return '<div class="list-row">' +
                        (s.slot_date || '') + ' ' + (st.substring(0, 5)) + '–' + (et.substring(0, 5)) +
                        ' <button type="button" class="btn-delete btn-sm" data-slot-id="' + s.id + '">Delete</button>' +
                        '</div>';
                }).join('');
                el.querySelectorAll('.btn-delete[data-slot-id]').forEach(function (btn) {
                    btn.onclick = function () {
                        if (!confirm('Delete this slot?')) return;
                        api.provider.timeSlots.delete(btn.dataset.slotId).then(function () {
                            loadProviderSlots();
                        }).catch(showApiError);
                    };
                });
            }
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading slots.</p>';
        }
    }

    function initProviderForms() {
        const formService = $('formProviderService');
        if (formService) {
            formService.onsubmit = async function (e) {
                e.preventDefault();
                const name = $('psName').value.trim();
                const duration = parseInt($('psDuration').value, 10) || 60;
                try {
                    await api.provider.services.create({ name: name, duration_min: duration });
                    $('psName').value = '';
                    loadProviderServices();
                    showMessage('Service added.');
                } catch (err) {
                    showApiError(err);
                }
            };
        }
        const formSlot = $('formProviderSlot');
        if (formSlot) {
            const today = new Date().toISOString().split('T')[0];
            $('psSlotDate').setAttribute('min', today);
            formSlot.onsubmit = async function (e) {
                e.preventDefault();
                const slotDate = $('psSlotDate').value;
                const start = $('psStart').value;
                const end = $('psEnd').value;
                if (!slotDate || !start || !end) return;
                const startTime = start.length === 5 ? start + ':00' : start;
                const endTime = end.length === 5 ? end + ':00' : end;
                try {
                    await api.provider.timeSlots.create({
                        slot_date: slotDate,
                        start_time: startTime,
                        end_time: endTime,
                    });
                    loadProviderSlots();
                    showMessage('Slot added.');
                } catch (err) {
                    showApiError(err);
                }
            };
        }
    }

    // ---------- Admin
    function initAdminTabs() {
        document.querySelectorAll('#view-admin .tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                const name = this.dataset.tab;
                document.querySelectorAll('#view-admin .tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('#view-admin .tab-panel').forEach(function (p) {
                    show(p, p.id === 'tab-' + name);
                });
                this.classList.add('active');
                if (name === 'admin-overview') loadAdminOverview();
                if (name === 'admin-users') loadAdminUsers();
                if (name === 'admin-bookings') loadAdminBookings();
            });
        });
        const cb = $('adminUsersDeleted');
        if (cb) cb.addEventListener('change', loadAdminUsers);
    }

    async function loadAdminOverview() {
        const el = $('adminOverview');
        if (!el) return;
        try {
            const data = await api.admin.overview();
            const bookings = data.bookings || {};
            el.innerHTML = '<div class="stat"><span class="stat-value">' + (data.users_total || 0) + '</span> Users</div>' +
                '<div class="stat"><span class="stat-value">' + (bookings.pending || 0) + '</span> Pending</div>' +
                '<div class="stat"><span class="stat-value">' + (bookings.approved || 0) + '</span> Approved</div>' +
                '<div class="stat"><span class="stat-value">' + (bookings.rejected || 0) + '</span> Rejected</div>' +
                '<div class="stat"><span class="stat-value">' + (bookings.cancelled || 0) + '</span> Cancelled</div>';
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading overview.</p>';
        }
    }

    async function loadAdminUsers() {
        const el = $('adminUsersList');
        if (!el) return;
        const includeDeleted = $('adminUsersDeleted') && $('adminUsersDeleted').checked;
        try {
            const data = await api.admin.users(includeDeleted);
            const list = data.users || [];
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No users.</p>';
                return;
            }
            el.innerHTML = '<table class="data-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody>' +
                list.map(function (u) {
                    const del = u.deleted_at ? ' <span class="muted">(deleted)</span>' : '';
                    return '<tr data-user-id="' + u.id + '">' +
                        '<td>' + u.id + '</td>' +
                        '<td>' + (u.name || '') + del + '</td>' +
                        '<td>' + (u.email || '') + '</td>' +
                        '<td>' + (u.role_name || u.role_id) + '</td>' +
                        '<td><button type="button" class="btn-sm btn-edit-user" data-id="' + u.id + '">Edit</button></td>' +
                        '</tr>';
                }).join('') + '</tbody></table>';
            el.querySelectorAll('.btn-edit-user').forEach(function (btn) {
                btn.onclick = function () {
                    const id = btn.dataset.id;
                    const name = prompt('New name (or leave blank to keep):');
                    if (name === null) return;
                    const body = {};
                    if (name !== '') body.name = name;
                    const role = prompt('Role ID (1=admin, 2=provider, 3=customer) or leave blank:');
                    if (role !== null && role !== '') body.role_id = parseInt(role, 10);
                    if (Object.keys(body).length === 0) return;
                    api.admin.updateUser(id, body).then(function () {
                        loadAdminUsers();
                        showMessage('User updated.');
                    }).catch(showApiError);
                };
            });
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading users.</p>';
        }
    }

    async function loadAdminBookings() {
        const el = $('adminBookingsList');
        if (!el) return;
        try {
            if (state.providers.length === 0) {
                const provData = await api.services.providers();
                state.providers = provData.providers || [];
            }
            const data = await api.bookings.list({ limit: 100 });
            const list = data.bookings || [];
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No bookings.</p>';
                return;
            }
            const providerIds = [...new Set(list.map(function (b) { return b.provider_id; }))];
            const serviceMap = {};
            for (let i = 0; i < providerIds.length; i++) {
                const services = await loadServicesForProvider(providerIds[i]);
                services.forEach(function (s) {
                    serviceMap[s.id] = s.name;
                });
            }
            const nameMap = {};
            state.providers.forEach(function (p) {
                nameMap[p.id] = p.name || p.email;
            });
            el.innerHTML = list.map(function (b) {
                const prov = nameMap[b.provider_id] || ('#' + b.provider_id);
                const svc = serviceMap[b.service_id] || ('#' + b.service_id);
                const approveReject = (b.status === 'pending')
                    ? '<button type="button" class="btn-sm btn-approve" data-id="' + b.id + '">Approve</button> <button type="button" class="btn-sm btn-reject" data-id="' + b.id + '">Reject</button>'
                    : '';
                return '<div class="booking-item">' +
                    '<div class="booking-info">' + formatBookingLine(b, prov, svc) + '</div> ' +
                    approveReject + '</div>';
            }).join('');
            el.querySelectorAll('.btn-approve').forEach(function (btn) {
                btn.onclick = function () {
                    api.bookings.updateStatus(btn.dataset.id, 'approved').then(function () {
                        loadAdminBookings();
                        loadAdminOverview();
                        showMessage('Booking approved.');
                    }).catch(showApiError);
                };
            });
            el.querySelectorAll('.btn-reject').forEach(function (btn) {
                btn.onclick = function () {
                    api.bookings.updateStatus(btn.dataset.id, 'rejected').then(function () {
                        loadAdminBookings();
                        loadAdminOverview();
                        showMessage('Booking rejected.');
                    }).catch(showApiError);
                };
            });
        } catch (err) {
            showApiError(err);
            el.innerHTML = '<p class="empty-msg">Error loading bookings.</p>';
        }
    }

    // ---------- Notifications
    function initNotifications() {
        const btn = $('btnNotifications');
        const dropdown = $('notificationDropdown');
        if (!btn || !dropdown) return;
        btn.onclick = function () {
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                loadNotifications();
            }
        };
        document.addEventListener('click', function (e) {
            if (dropdown && !dropdown.classList.contains('hidden') && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    async function loadNotifications() {
        const el = $('notificationDropdown');
        if (!el) return;
        try {
            const data = await api.notifications.list(false);
            const list = data.notifications || [];
            if (list.length === 0) {
                el.innerHTML = '<p class="empty-msg">No notifications.</p>';
            } else {
                el.innerHTML = list.slice(0, 10).map(function (n) {
                    return '<div class="notification-item" data-id="' + n.id + '">' +
                        '<strong>' + (n.title || 'Notification') + '</strong><br><span class="small">' + (n.body || '') + '</span>' +
                        '</div>';
                }).join('');
                el.querySelectorAll('.notification-item').forEach(function (item) {
                    item.onclick = function () {
                        api.notifications.markRead(item.dataset.id).then(function () {
                            loadNotifications();
                        }).catch(function () {});
                    };
                });
            }
        } catch (_) {
            el.innerHTML = '<p class="empty-msg">Could not load notifications.</p>';
        }
    }

    // ---------- Init
    function init() {
        initAuth();
        initNav();
        initBookForm();
        initProviderTabs();
        initProviderForms();
        initAdminTabs();
        initNotifications();
        checkAuth();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
