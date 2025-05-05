<?php include 'bd.php'; 

if (isset($_POST['doctor_id'])) {
    $doctorId = intval($_POST['doctor_id']);
    $sql = "SELECT * FROM staff WHERE id_doctor = $doctorId";
    $result = $conn->query($sql);
    $doctor = $result->fetch_assoc();
}


$edit_doctor_sql_philter_field_of_medicine = "SELECT id_field, name_of_field FROM field_of_medicine;";
$edit_doctor_sql_philter_field_of_medicine_result = $conn->query($edit_doctor_sql_philter_field_of_medicine);
$edit_doctor_field_of_medicine = $edit_doctor_sql_philter_field_of_medicine_result ? $edit_doctor_sql_philter_field_of_medicine_result->fetch_all(MYSQLI_ASSOC) : [];

$new_doctor_sql_philter_polyclinic = "SELECT id_polyclinic, name_polyclinic FROM info_about_polyclinic;";
$new_doctor_sql_philter_polyclinic_result = $conn->query($new_doctor_sql_philter_polyclinic);
$new_doctor_polyclinics = $new_doctor_sql_philter_polyclinic_result ? $new_doctor_sql_philter_polyclinic_result->fetch_all(MYSQLI_ASSOC) : [];


$new_doctor_sql_philter_department = "SELECT id_department, name_department FROM department;";
$new_doctor_sql_philter_department_result = $conn->query($new_doctor_sql_philter_department);
$new_doctor_departments = $new_doctor_sql_philter_department_result ? $new_doctor_sql_philter_department_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="modal fade" id="staff_editModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staff_editModal_Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-2 text-uppercase" id="staff_editModal_Label">Информация о выбранном враче</h5>
                <!-- В форме образования -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="delete_edit_DoctorButton">Удалить</button>
                <button type="button" class="btn btn-primary" id="save_edit_DoctorButton">Сохранить</button>
            </div>
        </div>
    </div>
</div>


<script>
        // Функция для получения данных врача и открытия модального окна
function fetchDoctorData(doctorId) {
    
    fetch('get_doctor.php?id=' + doctorId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Сеть не в порядке: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.doctor) {
                const doctor = data.doctor;
                const educations = data.educations || [];
                const qualifications = data.qualifications || [];
                currentDoctorId = doctorId;
                // Создаем HTML для модального окна
                let html = `
                    <h3>Личная информация</h3>
                    <div class="mb-3">
                        <label for="fullName_edit_staff" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="fullName_edit_staff" name="fullName_edit_staff" value="${doctor.full_name || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="birthDate_edit_staff" class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" id="birthDate_edit_staff" name="birthDate_edit_staff" value="${doctor.birthday || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="phoneNumber_edit_staff" class="form-label">Номер телефона</label>
                        <input type="text" class="form-control" id="phoneNumber_edit_staff" name="phoneNumber_edit_staff" value="${doctor.phone_number || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="doctorAdress_edit_staff" class="form-label">Адрес</label>
                        <input type="text" class="form-control" id="doctorAdress_edit_staff" name="doctorAdress_edit_staff" value="${doctor.address || ''}">
                    </div>
                    <h3>Работа</h3>
                    <div class="mb-3">
                        <label for="doctorPosition_edit_staff" class="form-label">Должность</label>
                        <input type="text" class="form-control" id="doctorPosition_edit_staff" name="doctorPosition_edit_staff" value="${doctor.post || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="doctorStatus_edit_staff" class="form-label">Статус</label>                            
                        <input type="text" class="form-control" id="doctorStatus_edit_staff" name="doctorStatus_edit_staff" value="${doctor.status || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="doctorNameDepartment_edit_staff" class="form-label">Отделение</label>
                        <select class="form-control" id="doctorNameDepartment_edit_staff" name="doctorNameDepartment_edit_staff">
                            <?php foreach ($new_doctor_departments as $new_doctor_department): ?>
                                <option value="<?= htmlspecialchars($new_doctor_department['id_department']) ?>"><?= htmlspecialchars($new_doctor_department['name_department']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="doctorNamePolyclinic_edit_staff" class="form-label">Поликлиника</label>
                        <select class="form-control" id="doctorNamePolyclinic_edit_staff" name="doctorNamePolyclinic_edit_staff">
                            <?php foreach ($new_doctor_polyclinics as $new_doctor_polyclinic): ?>
                                <option value="<?= htmlspecialchars($new_doctor_polyclinic['id_polyclinic']) ?>"><?= htmlspecialchars($new_doctor_polyclinic['name_polyclinic']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <h3>Образование</h3>
                    <div class="mb-3">
                        <label for="education_select" class="form-label">Выберите образование</label>
                        <select class="form-select" id="education_select" onchange="updateEducationFields(this)">
                            <option value="">Выберите образование</option>
                            ${educations.map((edu, index) => 
                                `<option value="${index}" 
                                    data-edu-id="${edu.id_education}"
                                    data-edu-institution="${edu.institution || ''}"
                                    data-edu-work-exp="${edu.work_experience || ''}"
                                    data-type="${edu.type_of_education || ''}"
                                    data-field="${edu.ed_name_of_field || ''}"
                                    data-id-field-edu="${edu.ed_id_field || ''}">
                                    ${edu.educational_institution} (${edu.year_of_start}-${edu.year_of_end})
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <button class="btn btn-success" onclick="addNewEducation()">Добавить образование</button>
                    <div id="education_fields_container">
                        <!-- Здесь будут динамически обновляться поля образования -->
                    </div>
                    <h3>Повышение квалификации</h3>
                    <div class="mb-3">
                        <label for="qualification_select" class="form-label">Выберите повышение квалификации</label>
                        <select class="form-select" id="qualification_select" onchange="updateQualificationFields(this)">
                            <option value="">Выберите повышение квалификации</option>
                            ${qualifications.map((qual, index) => 
                                `<option value="${index}" 
                                    data-qual-id="${qual.id_qualif_improv}"
                                    data-type="${qual.qe_type || ''}"
                                    data-organization="${qual.name_of_organizator || ''}"
                                    data-field="${qual.qe_name_of_field || ''}"
                                    data-id-field="${qual.qe_id_field || ''}">
                                    ${qual.qe_name} (${qual.date})
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <button class="btn btn-success" onclick="addNewQualif()">Добавить повышение квалификации</button>
                    <div id="qualification_fields_container">
                        <!-- Здесь будут динамически обновляться поля повышения квалификации -->
                    </div>
                `;

            
            

                document.querySelector('#staff_editModal .modal-body').innerHTML = html;
                
                // Инициализируем поля при первой загрузке
                if (educations.length > 0) {
                    updateEducationFields(document.getElementById('education_select'));
                }
                if (qualifications.length > 0) {
                    updateQualificationFields(document.getElementById('qualification_select'));
                }
                if (doctor.id_polyclinic) {
                    document.getElementById('doctorNamePolyclinic_edit_staff').value = doctor.id_polyclinic;
                }
                if (doctor.id_department) {
                    document.getElementById('doctorNameDepartment_edit_staff').value = doctor.id_department;
                }

                // Открыть модальное окно
                var modalElement = document.getElementById('staff_editModal');
                var modal = new bootstrap.Modal(modalElement);

                modalElement.addEventListener('hidden.bs.modal', function (event) {
                    // Перемещаем фокус обратно на ячейку с именем врача
                    const doctorNameCell = document.querySelector(`.doctor-name[data-id="${doctorId}"]`);
                    if (doctorNameCell) {
                        doctorNameCell.focus();
                    }
                });
                modal.show();

                const departmentSelect = document.getElementById('doctorNameDepartment_edit_staff');
                if (departmentSelect) {
                    departmentSelect.addEventListener('change', function() {
                        console.log('Событие change сработало');
                        var department_id = this.value;
                        var polyclinicSelect = document.getElementById('doctorNamePolyclinic_edit_staff');

                        // Очистить текущие опции поликлиник
                        polyclinicSelect.innerHTML = '';

                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', 'get_polyclinics.php?id_department=' + department_id, true);
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                if (xhr.responseText) {
                                    try {
                                        var polyclinics = JSON.parse(xhr.responseText);
                                        if (Array.isArray(polyclinics)) {
                                            polyclinics.forEach(function(polyclinic) {
                                                var option = document.createElement('option');
                                                option.value = polyclinic.id_polyclinic;
                                                option.textContent = polyclinic.name_polyclinic;
                                                polyclinicSelect.appendChild(option);
                                            });
                                            
                                        } else {
                                            console.error('Полученный ответ не является массивом:', polyclinics);
                                        }
                                    } catch (e) {
                                        console.error('Ошибка при разборе JSON:', e);
                                        console.error('Текст ответа:', xhr.responseText);
                                    }
                                } else {
                                    console.warn('Пустой ответ от сервера.');
                                }
                            } else {
                                console.error('Запрос не выполнен со статусом:', xhr.status);
                            }
                        };
                        xhr.onerror = function() {
                            console.error('Запрос не выполнен');
                        };
                        xhr.send();
                    });
                }    

            } else {
                console.error('Нет данных для врача с ID:', doctorId);
            }
        })
        .catch(error => console.error('Ошибка:', error));

        
} 


// Функция для обновления полей образования при выборе из списка
function updateEducationFields(selectElement) {
    const index = selectElement.value;
    const container = document.getElementById('education_fields_container');
    
    if (index === "") {
        container.innerHTML = '';
        return;
    }
    
    // Получаем данные из options (можно также хранить данные в глобальной переменной)
    const option = selectElement.options[selectElement.selectedIndex];
    const eduId = option.getAttribute('data-edu-id');
    const text = option.textContent.trim();
    
    // Разбираем текст для получения учреждения и годов
    const matches = text.match(/(.*)\s\((\d+)-(\d+)\)/);
    const institution = matches ? matches[1] : '';
    const startYear = matches ? matches[2] : '';
    const endYear = matches ? matches[3] : '';
    const workExperience = option.getAttribute('data-edu-work-exp') || '';
    
    // Создаем HTML для полей образования
    container.innerHTML = `
        <div class="mb-3">
            <label for="educationType_edit_staff" class="form-label">Вид образования</label>
            <input type="text" class="form-control" id="educationType_edit_staff" name="educationType_edit_staff" value="${option.dataset.type || ''}">
        </div>
        <div class="mb-3">
            <label for="university_edit_staff" class="form-label">Наименование университета</label>
            <input type="text" class="form-control" id="university_edit_staff" name="university_edit_staff" value="${institution}">
        </div>
        <div class="mb-3">
            <label for="work_exp_edit_staff" class="form-label">Стаж работы по образованию</label>
            <input type="text" class="form-control" id="work_exp_edit_staff" name="work_exp_edit_staff" value="${workExperience}">
        </div>
        <div class="mb-3">
            <label for="startYear_edit_staff" class="form-label">Год начала обучения</label>
            <input type="number" class="form-control" id="startYear_edit_staff" name="startYear_edit_staff" min="1900" max="2100" value="${startYear}">
        </div>
        <div class="mb-3">
            <label for="endYear_edit_staff" class="form-label">Год окончания обучения</label>
            <input type="number" class="form-control" id="endYear_edit_staff" name="endYear_edit_staff" min="1900" max="2100" value="${endYear}">
        </div>
        <div class="mb-3">
            <label for="medicalField_edit_staff" class="form-label">Направление в медицине</label>
            <select class="form-select" id="medicalField_edit_staff">
                <?php foreach ($edit_doctor_field_of_medicine as $field): ?>
                    <option value="<?= htmlspecialchars($field['id_field']) ?>">
                        <?= htmlspecialchars($field['name_of_field']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" id="education_id" name="education_id" value="${eduId}">
        <div class="mb-3">
            <button type="button" class="btn btn-primary" onclick="saveEducationChanges()">Сохранить изменения</button>
            <button class="btn btn-danger" onclick="deleteCurrentEducation()">Удалить выбранное образование</button>
        </div>
    `;
    
    const idField = option.getAttribute('data-id-field-edu');
    if (idField) {
        document.getElementById('medicalField_edit_staff').value = idField;
    }
}

// Функция для обновления полей повышения квалификации при выборе из списка
function updateQualificationFields(selectElement) {
    const index = selectElement.value;
    const container = document.getElementById('qualification_fields_container');
    
    if (index === "") {
        container.innerHTML = '';
        return;
    }
    
    // Получаем данные из options
    const option = selectElement.options[selectElement.selectedIndex];
    const qualId = option.getAttribute('data-qual-id');
    const text = option.textContent.trim();
    
    // Разбираем текст для получения названия и даты
    const matches = text.match(/(.*)\s\((\d{4}-\d{2}-\d{2}|\?)\)/);
    const name = matches ? matches[1] : '';
    const date = matches ? matches[2] : '';
    
    // Создаем HTML для полей повышения квалификации
    container.innerHTML = `
        <div class="mb-3">
            <label for="qualif_improv_date_edit_staff" class="form-label">Дата проведения</label>
            <input type="date" class="form-control" id="qualif_improv_date_edit_staff" name="qualif_improv_date_edit_staff" value="${date}">
        </div>
        <div class="mb-3">
            <label for="qualif_improv_name_edit_staff" class="form-label">Название</label>
            <input type="text" class="form-control" id="qualif_improv_name_edit_staff" name="qualif_improv_name_edit_staff" value="${name}">
        </div>
        <div class="mb-3">
            <label for="qualif_improv_type_edit_staff" class="form-label">Тип</label>
            <input type="text" class="form-control" id="qualif_improv_type_edit_staff" name="qualif_improv_type_edit_staff" value="${option.dataset.type || ''}">
        </div>
        <div class="mb-3">
            <label for="qualif_improv_nameOrganization_edit_staff" class="form-label">Наименование организации</label>
            <input type="text" class="form-control" id="qualif_improv_nameOrganization_edit_staff" name="qualif_improv_nameOrganization_edit_staff" value="${option.dataset.organization || ''}">
        </div>
        <div class="mb-3">
            <label for="qualif_improv_medicalField_edit_staff" class="form-label">Направление в медицине</label>
            <select class="form-select" id="qualif_improv_medicalField_edit_staff">
                <?php foreach ($edit_doctor_field_of_medicine as $field): ?>
                    <option value="<?= htmlspecialchars($field['id_field']) ?>">
                        <?= htmlspecialchars($field['name_of_field']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" id="qualification_id" name="qualification_id" value="${qualId}">
        <div class="mb-3">
            <button type="button" class="btn btn-primary" onclick="saveQualifChanges()">Сохранить изменения</button>
            <button class="btn btn-danger" onclick="deleteCurrentQualif()">Удалить выбранное повышение квалификации</button>
        </div>
    `;
    
    const idField = option.getAttribute('data-id-field');
    if (idField) {
        document.getElementById('qualif_improv_medicalField_edit_staff').value = idField;
    }
}


// Глобальная переменная для хранения ID текущего врача
let currentDoctorId = 0;

// Функция для добавления нового образования
function addNewEducation() {
    const html = `
        <div class="mb-3">
            <label class="form-label">Вид образования</label>
            <select class="form-select" id="new_education_type">
                <option value="Высшее">Высшее</option>
                <option value="Среднее">Среднее</option>
                <option value="Аспирантура">Аспирантура</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Учебное заведение</label>
            <input type="text" class="form-control" id="new_education_institution">
        </div>
        <div class="mb-3">
            <label for="new_education_work_exp" class="form-label">Стаж работы по образованию</label>
            <input type="text" class="form-control" id="new_education_work_exp" name="new_education_work_exp">
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
            <select class="form-select" id="new_education_field">
                <option value="">Выберите направление</option>
                <?php foreach ($edit_doctor_field_of_medicine as $field): ?>
                    <option value="<?= htmlspecialchars($field['id_field']) ?>"><?= htmlspecialchars($field['name_of_field']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" onclick="confirmAddEducation()">Добавить</button>
        <button class="btn btn-secondary" onclick="cancelAddEducation()">Отмена</button>
    `;
    document.getElementById('education_fields_container').innerHTML = html;
}

function confirmAddEducation() {
    const type = document.getElementById('new_education_type').value;
    const institution = document.getElementById('new_education_institution').value;
    const work_exp = document.getElementById('new_education_work_exp').value;
    const start_year = document.getElementById('new_education_start_year').value;
    const end_year = document.getElementById('new_education_end_year').value;
    const field = document.getElementById('new_education_field').value;
    
    // Проверка заполнения всех полей
    if (!type || !institution || !start_year || !end_year || !field || !work_exp) {
        alert('Пожалуйста, заполните все поля!');
        return;
    }
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=add&doctor_id=${currentDoctorId}&type=${encodeURIComponent(type)}&institution=${encodeURIComponent(institution)}&work_experience=${encodeURIComponent(work_exp)}
        &start_year=${start_year}&end_year=${end_year}&field_id=${field}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId);
        } else {
            alert('Ошибка при добавлении образования: ' + (data.message || ''));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при добавлении образования');
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
    const field_id = document.getElementById('medicalField_edit_staff').value;
    const work_exp = document.getElementById('work_exp_edit_staff').value;
    
    fetch('education_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=update&doctor_id=${currentDoctorId}&education_id=${education_id}&type=${encodeURIComponent(type)}
        &institution=${encodeURIComponent(institution)}&start_year=${start_year}&end_year=${end_year}&field_id=${field_id}&work_experience=${encodeURIComponent(work_exp)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Изменения сохранены!');
            fetchDoctorData(currentDoctorId);
        }
    });
}


//функция для добавления нового повышения квалификации
function addNewQualif() {
    const html = `
        <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" class="form-control" id="new_qualif_name">
        </div>
        <div class="mb-3">
            <label class="form-label">Тип</label>
            <select class="form-select" id="new_qualif_type">
                <option value="Курс">Курс</option>
                <option value="Семинар">Семинар</option>
                <option value="Лекция">Лекция</option>
                <option value="Мастер-класс">Мастер-класс</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Организатор</label>
            <input type="text" class="form-control" id="new_qualif_organizator">
        </div>
        <div class="mb-3">
            <label class="form-label">Дата</label>
            <input type="date" class="form-control" id="new_qualif_date">
        </div>
        <div class="mb-3">
            <label class="form-label">Направление в медицине</label>
            <select class="form-select" id="new_qualif_field">
                <option value="">Выберите направление</option>
                <?php foreach ($edit_doctor_field_of_medicine as $field): ?>
                    <option value="<?= htmlspecialchars($field['id_field']) ?>"><?= htmlspecialchars($field['name_of_field']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" onclick="confirmAddQualif()">Добавить</button>
        <button class="btn btn-secondary" onclick="cancelAddQualif()">Отмена</button>
    `;
    document.getElementById('qualification_fields_container').innerHTML = html;
}

function confirmAddQualif() {
    const name = document.getElementById('new_qualif_name').value;
    const type = document.getElementById('new_qualif_type').value;
    const organizator = document.getElementById('new_qualif_organizator').value;
    const date = document.getElementById('new_qualif_date').value;
    const field_id = document.getElementById('new_qualif_field').value;
    
    fetch('qualification_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=add&doctor_id=${currentDoctorId}&name=${encodeURIComponent(name)}&type=${encodeURIComponent(type)}&organizator=${encodeURIComponent(organizator)}&date=${date}&field_id=${field_id}`
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

// Функция для удаления текущего повышения квалификации
function deleteCurrentQualif() {
    const qualification_id = document.getElementById('qualification_id').value;
    if (!confirm('Вы уверены, что хотите удалить это повышение квалификации?')) return;
    
    fetch('qualification_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=delete&doctor_id=${currentDoctorId}&qualif_id=${qualification_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchDoctorData(currentDoctorId); // Обновляем данные
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при удалении');
    });
}

// Функция для сохранения изменений в образовании
function saveQualifChanges() {
    const qualif_id = document.getElementById('qualification_id').value;
    const date = document.getElementById('qualif_improv_date_edit_staff').value;
    const name = document.getElementById('qualif_improv_name_edit_staff').value;
    const type = document.getElementById('qualif_improv_type_edit_staff').value;
    const organizator = document.getElementById('qualif_improv_nameOrganization_edit_staff').value;
    const field_id = document.getElementById('qualif_improv_medicalField_edit_staff').value;

    alert(qualif_id+", "+ date+", "+ name +", "+type+", "+organizator+", " + field_id)
    
    fetch('qualification_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=update&doctor_id=${currentDoctorId}&qualif_id=${qualif_id}&name=${encodeURIComponent(name)}&type=${encodeURIComponent(type)}&organizator=${encodeURIComponent(organizator)}&date=${date}&field_id=${field_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Изменения сохранены!');
            fetchDoctorData(currentDoctorId);
        }
    });
}


document.getElementById('save_edit_DoctorButton').addEventListener('click', function() {
    saveDoctorInfo()
        .then(() => {
            if (document.getElementById('education_id')) {
                return saveEducationChanges();
            }
        })
        .then(() => {
            if (document.getElementById('qualification_id')) {
                return saveQualifChanges();
            }
        })
        .then(() => {
            alert('Все изменения сохранены!');
            fetchDoctorData(currentDoctorId);
        })
        .catch(error => {
            console.error('Ошибка при сохранении:', error);
            alert('Ошибка при сохранении: ' + error.message);
        });
});

function saveDoctorInfo() {
    return new Promise((resolve, reject) => {
        const fullName = document.getElementById('fullName_edit_staff').value;
        const birthDate = document.getElementById('birthDate_edit_staff').value;
        const phoneNumber = document.getElementById('phoneNumber_edit_staff').value;
        const address = document.getElementById('doctorAdress_edit_staff').value;
        const position = document.getElementById('doctorPosition_edit_staff').value;
        const status = document.getElementById('doctorStatus_edit_staff').value;
        const id_department = document.getElementById('doctorNameDepartment_edit_staff').value;
        
        fetch('update_doctor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `doctor_id=${currentDoctorId}&full_name=${encodeURIComponent(fullName)}&birthday=${birthDate}&phone_number=${phoneNumber}&address=${encodeURIComponent(address)}&post=${encodeURIComponent(position)}&status=${encodeURIComponent(status)}&id_department=${id_department}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resolve();
            } else {
                reject(data.message || 'Неизвестная ошибка');
            }
        })
        .catch(error => reject(error));
    });
}


document.getElementById('delete_edit_DoctorButton').addEventListener('click', function() {
    DeleteDoctorInfo()
        .then(() => {
            alert('Запись о враче удалена!');
            //fetchDoctorData(currentDoctorId);
        })
        .catch(error => {
            console.error('Ошибка при удалении:', error);
            alert('Ошибка при удалении: ' + error.message);
        });
});

function DeleteDoctorInfo() {
    return new Promise((resolve, reject) => {
        
        fetch('delete_doctor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `doctor_id=${currentDoctorId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resolve();
            } else {
                reject(data.message || 'Неизвестная ошибка');
            }
        })
        .catch(error => reject(error));
    });
}


</script>