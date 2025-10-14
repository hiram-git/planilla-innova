// JavaScript Example: Reading Entities
// Filterable fields: user_email, full_name, employee_id, department, position, phone, profile_photo_url, assigned_location_ids, work_schedules, is_active
async function fetchEmployeeEntities() {
    const response = await fetch(https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Employee, {
        headers: {
            'api_key': '40162908d71941b98636b38106be556e', // or use await User.me() to get the API key
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    console.log(data);
}

// JavaScript Example: Updating an Entity
// Filterable fields: user_email, full_name, employee_id, department, position, phone, profile_photo_url, assigned_location_ids, work_schedules, is_active
async function updateEmployeeEntity(entityId, updateData) {
    const response = await fetch(https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Employee/${entityId}, {
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