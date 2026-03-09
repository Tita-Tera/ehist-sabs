<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Booking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <header>
            <h1>Book an Appointment</h1>
        </header>

        <div class="main-content">
            <!-- Left Side: Booking Form -->
            <section class="card form-card">
                <h2>Schedule New</h2>
                <form id="appointmentForm">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" placeholder="john@example.com" required>
                    </div>

                    <div class="form-group">
                        <label for="service">Service</label>
                        <select id="service" required>
                            <option value="">Select a Service</option>
                            <option value="General Checkup">General Checkup</option>
                            <option value="Dental Cleaning">Dental Cleaning</option>
                            <option value="Eye Exam">Eye Exam</option>
                            <option value="Consultation">Consultation</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" required>
                        </div>
                        <div class="form-group">
                            <label for="time">Time</label>
                            <input type="time" id="time" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Book Appointment</button>
                </form>
            </section>

            <!-- Right Side: Appointments List -->
            <section class="card list-card">
                <h2>Upcoming Appointments</h2>
                <div id="appointmentList">
                    <!-- Appointments will be injected here by JavaScript -->
                    <p class="empty-msg">No appointments scheduled yet.</p>
                </div>
            </section>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>