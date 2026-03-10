<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Booking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="app" class="app">
        <!-- Header: only when logged in -->
        <header id="header" class="header hidden">
            <div class="header-inner">
                <a href="#" class="logo" data-view="book">Appointment Booking</a>
                <nav class="nav">
                    <span id="userName" class="user-name"></span>
                    <button type="button" id="btnNotifications" class="btn-icon" title="Notifications" aria-label="Notifications"></button>
                    <a href="#" class="nav-link" data-view="book">Book</a>
                    <a href="#" class="nav-link" data-view="my-bookings">My Bookings</a>
                    <a href="#" class="nav-link nav-provider hidden" data-view="provider">Provider</a>
                    <a href="#" class="nav-link nav-admin hidden" data-view="admin">Admin</a>
                    <button type="button" id="btnLogout" class="btn-logout">Logout</button>
                </nav>
            </div>
            <div id="notificationDropdown" class="notification-dropdown hidden"></div>
        </header>

        <!-- Global message (errors/success) -->
        <div id="globalMessage" class="global-message hidden"></div>

        <main class="main">
            <!-- Login -->
            <section id="view-login" class="view card-view">
                <div class="card">
                    <h1>Sign in</h1>
                    <form id="formLogin">
                        <div class="form-group">
                            <label for="loginEmail">Email</label>
                            <input type="email" id="loginEmail" required placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" id="loginPassword" required>
                        </div>
                        <button type="submit" class="btn-primary">Sign in</button>
                    </form>
                    <p class="form-footer">Don't have an account? <a href="#" id="linkRegister">Register</a></p>
                </div>
            </section>

            <!-- Register -->
            <section id="view-register" class="view card-view hidden">
                <div class="card">
                    <h1>Register</h1>
                    <form id="formRegister">
                        <div class="form-group">
                            <label for="regName">Full name</label>
                            <input type="text" id="regName" required placeholder="Jane Doe">
                        </div>
                        <div class="form-group">
                            <label for="regEmail">Email</label>
                            <input type="email" id="regEmail" required placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label for="regPassword">Password (min 6 characters)</label>
                            <input type="password" id="regPassword" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="regRole">I am a</label>
                            <select id="regRole" name="regRole" required>
                                <option value="3" selected>Customer (book appointments)</option>
                                <option value="2">Provider (offer services)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Register</button>
                    </form>
                    <p class="form-footer">Already have an account? <a href="#" id="linkLogin">Sign in</a></p>
                </div>
            </section>

            <!-- Book (customer) -->
            <section id="view-book" class="view hidden">
                <div class="main-content two-cols">
                    <section class="card form-card">
                        <h2>Book an appointment</h2>
                        <form id="formBook">
                            <div class="form-group">
                                <label for="bookProvider">Provider</label>
                                <select id="bookProvider" required>
                                    <option value="">Select provider</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bookService">Service</label>
                                <select id="bookService" required disabled>
                                    <option value="">Select service</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Time</label>
                                <div class="slot-choice">
                                    <label class="radio-label"><input type="radio" name="bookSlotType" value="predefined" checked> Predefined slot</label>
                                    <label class="radio-label"><input type="radio" name="bookSlotType" value="custom"> Custom time (request)</label>
                                </div>
                            </div>
                            <div class="form-group" id="bookDateWrap">
                                <label for="bookDate">Date</label>
                                <input type="date" id="bookDate" required>
                            </div>
                            <div class="form-group" id="bookSlotsWrap">
                                <label>Predefined slot</label>
                                <div id="bookSlots" class="slot-list"></div>
                                <p id="bookSlotsHint" class="hint">Select provider, service and date to see available slots.</p>
                            </div>
                            <div class="form-group hidden" id="bookCustomTimeWrap">
                                <label>Custom time (provider can accept, reschedule or reject)</label>
                                <div class="row">
                                    <div class="form-group">
                                        <label for="bookCustomStart">Start</label>
                                        <input type="time" id="bookCustomStart">
                                    </div>
                                    <div class="form-group">
                                        <label for="bookCustomEnd">End</label>
                                        <input type="time" id="bookCustomEnd">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary" id="btnBookSubmit">Book</button>
                        </form>
                    </section>
                    <section class="card list-card">
                        <h2>My upcoming bookings</h2>
                        <div id="bookingsPreview" class="preview-list"></div>
                    </section>
                </div>
            </section>

            <!-- My Bookings (full list + cancel) -->
            <section id="view-my-bookings" class="view hidden">
                <div class="card">
                    <h2>My bookings</h2>
                    <div id="myBookingsList" class="booking-list"></div>
                </div>
            </section>

            <!-- Provider: services + time slots + bookings -->
            <section id="view-provider" class="view hidden">
                <div class="tabs">
                    <button type="button" class="tab" data-tab="provider-services">My services</button>
                    <button type="button" class="tab" data-tab="provider-slots">My time slots</button>
                    <button type="button" class="tab active" data-tab="provider-bookings">Bookings</button>
                </div>
                <div id="tab-provider-services" class="tab-panel hidden">
                    <div class="card">
                        <h2>My services</h2>
                        <div id="providerServicesList"></div>
                        <form id="formProviderService" class="form-inline">
                            <input type="text" id="psName" placeholder="Service name" required>
                            <input type="number" id="psDuration" placeholder="Duration (min)" min="1" max="1440" value="60">
                            <button type="submit" class="btn-primary">Add service</button>
                        </form>
                    </div>
                </div>
                <div id="tab-provider-slots" class="tab-panel hidden">
                    <div class="card">
                        <h2>My time slots</h2>
                        <form id="formProviderSlot" class="form-inline">
                            <input type="date" id="psSlotDate" required>
                            <input type="time" id="psStart" required>
                            <input type="time" id="psEnd" required>
                            <button type="submit" class="btn-primary">Add slot</button>
                        </form>
                        <div id="providerSlotsList" class="slot-list-rows"></div>
                    </div>
                </div>
                <div id="tab-provider-bookings" class="tab-panel">
                    <div class="card">
                        <h2>Incoming bookings</h2>
                        <p class="hint">Accept or reject pending (custom) requests; reschedule any booking.</p>
                        <div id="providerBookingsList" class="booking-list"></div>
                    </div>
                </div>
            </section>

            <!-- Admin: overview, users, bookings -->
            <section id="view-admin" class="view hidden">
                <div class="tabs">
                    <button type="button" class="tab active" data-tab="admin-overview">Overview</button>
                    <button type="button" class="tab" data-tab="admin-users">Users</button>
                    <button type="button" class="tab" data-tab="admin-bookings">Bookings</button>
                </div>
                <div id="tab-admin-overview" class="tab-panel">
                    <div class="card">
                        <h2>Overview</h2>
                        <div id="adminOverview" class="overview-grid"></div>
                    </div>
                </div>
                <div id="tab-admin-users" class="tab-panel hidden">
                    <div class="card">
                        <h2>Users</h2>
                        <label><input type="checkbox" id="adminUsersDeleted"> Include deleted</label>
                        <div id="adminUsersList" class="table-wrap"></div>
                    </div>
                </div>
                <div id="tab-admin-bookings" class="tab-panel hidden">
                    <div class="card">
                        <h2>All bookings</h2>
                        <div id="adminBookingsList" class="booking-list"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="api.js"></script>
    <script src="app.js"></script>
</body>
</html>
