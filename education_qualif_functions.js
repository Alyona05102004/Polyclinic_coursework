// Глобальная переменная для хранения ID текущего врача
let currentDoctorId = 0;

// Функция для добавления нового образования
function addNewEducation() {
    const html = `
        <div class="mb-3">
            <label class="form-label">Вид образования</label>
            <input type="text" class="form-control" id="new_education_type">
        </div>
        <div class="mb-3">
            <label class="form-label">Учебное заведение</label>
            <input type="text" class="form-control" id="new_education_institution">
        </div>
        <div class="mb-3">
            <label class="form-label">Год начала</label>
            <input type="number" class="form-control" id="new_education_start_year" min="1900" max="2100">
        </div>
        <div class="mb-3">
            <label class="form-label">Год окончания</label>
            <input type="number" class="form-control" id="new_education_end_year" min="1900" max="2100">
        </div>
        <div class="mb-3">
            <label class="form-label">Направление в медицине</label>
            <input type="text" class="form-control" id="new_education_field">
        </div>
        <button class="btn btn-primary" onclick="confirmAddEducation()">Добавить</button>
        <button class="btn btn-secondary" onclick="cancelAddEducation()">Отмена</button>
    `;
    document.getElementById('education_fields_container').innerHTML = html;
}

function confirmAddEducation() {
    const type = document.getElementById('new_education_type').value;
    const institution = document.getElementById('new_education_institution').value;
    const start_year = document.getElementById('new_education_start_year').value;
    const end_year = document.getElementById('new_education_end_year').value;
    const field = document.getElementById('new_education_field').value;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=add&doctor_id=${currentDoctorId}&type=${encodeURIComponent(type)}&institution=${encodeURIComponent(institution)}&start_year=${start_year}&end_year=${end_year}&field_id=${field}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}

function cancelAddEducation() {
    updateEducationFields(document.getElementById('education_select'));
}

// Функция для удаления текущего образования
function deleteCurrentEducation() {
    const education_id = document.getElementById('education_id').value;
    
    if (!confirm('Вы уверены, что хотите удалить это образование?')) return;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=delete&doctor_id=${currentDoctorId}&education_id=${education_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}

// Функция для сохранения изменений в образовании
function saveEducationChanges() {
    const education_id = document.getElementById('education_id').value;
    const type = document.getElementById('educationType_edit_staff').value;
    const institution = document.getElementById('university_edit_staff').value;
    const start_year = document.getElementById('startYear_edit_staff').value;
    const end_year = document.getElementById('endYear_edit_staff').value;
    const field = document.getElementById('medicalField_edit_staff').value;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=update&doctor_id=${currentDoctorId}&education_id=${education_id}&type=${encodeURIComponent(type)}&institution=${encodeURIComponent(institution)}&start_year=${start_year}&end_year=${end_year}&field_id=${field}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Изменения сохранены!');
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}


//функция для добавления нового повышения квалификации
function addNewQualif() {
    const html = `
        <div class="mb-3">
            <label class="form-label">Вид образования</label>
            <input type="text" class="form-control" id="new_education_type">
        </div>
        <div class="mb-3">
            <label class="form-label">Учебное заведение</label>
            <input type="text" class="form-control" id="new_education_institution">
        </div>
        <div class="mb-3">
            <label class="form-label">Год начала</label>
            <input type="number" class="form-control" id="new_education_start_year" min="1900" max="2100">
        </div>
        <div class="mb-3">
            <label class="form-label">Год окончания</label>
            <input type="number" class="form-control" id="new_education_end_year" min="1900" max="2100">
        </div>
        <div class="mb-3">
            <label class="form-label">Направление в медицине</label>
            <input type="text" class="form-control" id="new_education_field">
        </div>
        <button class="btn btn-primary" onclick="confirmAddEducation()">Добавить</button>
        <button class="btn btn-secondary" onclick="cancelAddQualif()">Отмена</button>
    `;
    document.getElementById('education_fields_container').innerHTML = html;
}

function confirmAddQualif() {
    const type = document.getElementById('new_education_type').value;
    const institution = document.getElementById('new_education_institution').value;
    const start_year = document.getElementById('new_education_start_year').value;
    const end_year = document.getElementById('new_education_end_year').value;
    const field = document.getElementById('new_education_field').value;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=add&doctor_id=${currentDoctorId}&type=${encodeURIComponent(type)}&institution=${encodeURIComponent(institution)}&start_year=${start_year}&end_year=${end_year}&field_id=${field}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}

function cancelAddQualif() {
    updateQualificationFields(document.getElementById('qualification_select'));
}

// Функция для удаления текущего образования
function deleteCurrentQualif() {
    const education_id = document.getElementById('education_id').value;
    
    if (!confirm('Вы уверены, что хотите удалить это образование?')) return;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=delete&doctor_id=${currentDoctorId}&education_id=${education_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}

// Функция для сохранения изменений в образовании
function saveQualifChanges() {
    const education_id = document.getElementById('education_id').value;
    const type = document.getElementById('educationType_edit_staff').value;
    const institution = document.getElementById('university_edit_staff').value;
    const start_year = document.getElementById('startYear_edit_staff').value;
    const end_year = document.getElementById('endYear_edit_staff').value;
    const field = document.getElementById('medicalField_edit_staff').value;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=update&doctor_id=${currentDoctorId}&education_id=${education_id}&type=${encodeURIComponent(type)}&institution=${encodeURIComponent(institution)}&start_year=${start_year}&end_year=${end_year}&field_id=${field}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Изменения сохранены!');
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    });
}
