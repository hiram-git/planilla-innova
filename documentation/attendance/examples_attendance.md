// JavaScript Example: Reading Entities
// Filterable fields: employee_email, employee_name, type, photo_url, latitude, longitude, location_name, timestamp, hours_worked, is_late, is_early_exit, notes
async function fetchAttendanceEntities() {
    const response = await fetch(https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Attendance, {
        headers: {
            'api_key': '40162908d71941b98636b38106be556e', // or use await User.me() to get the API key
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    console.log(data);
}

// JavaScript Example: Updating an Entity
// Filterable fields: employee_email, employee_name, type, photo_url, latitude, longitude, location_name, timestamp, hours_worked, is_late, is_early_exit, notes
async function updateAttendanceEntity(entityId, updateData) {
    const response = await fetch(https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Attendance/${entityId}, {
        method: 'PUT',
        headers: {
            'api_key': '40162908d71941b98636b38106be556e', // or use await User.me() to get the API key
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updateData)
    });
    const data = await response.json();
    console.log(data);
}