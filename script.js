// 1. Select DOM Elements
const form = document.getElementById('appointmentForm');
const appointmentList = document.getElementById('appointmentList');
const dateInput = document.getElementById('date');

// 2. Set minimum date to today (Prevent booking in the past)
const today = new Date().toISOString().split('T')[0];
dateInput.setAttribute('min', today);

// 3. Initialize Appointments from LocalStorage or Empty Array
let appointments = JSON.parse(localStorage.getItem('appointments')) || [];

// 4. Function to render appointments to the DOM
function renderAppointments() {
    // Clear current list
    appointmentList.innerHTML = '';

    if (appointments.length === 0) {
        appointmentList.innerHTML = '<p class="empty-msg">No appointments scheduled yet.</p>';
        return;
    }

    // Sort appointments by date and time (Closest first)
    appointments.sort((a, b) => new Date(a.date + ' ' + a.time) - new Date(b.date + ' ' + b.time));

    appointments.forEach((apt, index) => {
        const div = document.createElement('div');
        div.className = 'appointment-item';
        
        // Format Date for display
        const dateObj = new Date(apt.date + 'T' + apt.time);
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('en-US', options);
        
        // Format Time (12 hour format)
        const formattedTime = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        div.innerHTML = `
            <h3>${apt.name}</h3>
            <p><strong>Service:</strong> ${apt.service}</p>
            <p><strong>Contact:</strong> ${apt.email}</p>
            <p><strong>When:</strong> ${formattedDate} at ${formattedTime}</p>
            <button class="btn-delete" onclick="deleteAppointment(${index})">Cancel</button>
        `;

        appointmentList.appendChild(div);
    });
}

// 5. Function to delete an appointment
function deleteAppointment(index) {
    if(confirm('Are you sure you want to cancel this appointment?')) {
        appointments.splice(index, 1);
        localStorage.setItem('appointments', JSON.stringify(appointments));
        renderAppointments();
    }
}

// 6. Event Listener for Form Submission
form.addEventListener('submit', (e) => {
    e.preventDefault();

    // Get values
    const name = document.getElementById('fullName').value;
    const email = document.getElementById('email').value;
    const service = document.getElementById('service').value;
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;

    // Simple Validation for duplicate time (Optional check)
    const isDuplicate = appointments.some(apt => apt.date === date && apt.time === time);
    
    if (isDuplicate) {
        alert('This time slot is already booked. Please choose another time.');
        return;
    }

    // Create appointment object
    const newAppointment = {
        name,
        email,
        service,
        date,
        time,
        id: Date.now() // Unique ID based on timestamp
    };

    // Add to array and Save to LocalStorage
    appointments.push(newAppointment);
    localStorage.setItem('appointments', JSON.stringify(appointments));

    // Re-render list and reset form
    renderAppointments();
    form.reset();
    alert('Appointment Booked Successfully!');
});

// 7. Initial Render on Page Load
renderAppointments();