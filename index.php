
<?php
include 'bd.php'; 

// Получение данных для фильтра поликлиник
$sql_philter_polyclinic = "SELECT id_polyclinic, name_polyclinic FROM info_about_polyclinic";
$sql_philter_polyclinic_result = $conn->query($sql_philter_polyclinic);
$polyclinics = $sql_philter_polyclinic_result ? $sql_philter_polyclinic_result->fetch_all(MYSQLI_ASSOC) : [];


// Функция для получения HTML-кода таблицы врачей (выносим в функцию для повторного использования)
function getDoctorsTable($conn, $polyclinic_id = null, $department_id = null, $letters_range = null)
{
    // Формирование SQL-запроса
    $sql_doctors = "SELECT staff.id_doctor, staff.full_name, staff.birthday, staff.post, staff.status, staff.address, staff.phone_number, department.name_department, info_about_polyclinic.name_polyclinic
                    FROM staff 
                    JOIN department ON department.id_department = staff.id_department
                    JOIN connection ON connection.id_department = department.id_department
                    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic = connection.id_polyclinic
                    WHERE 1=1";

    if ($polyclinic_id && $polyclinic_id != 'all') {
        $sql_doctors .= " AND info_about_polyclinic.id_polyclinic = " . intval($polyclinic_id);
    }
    if ($department_id && $department_id != 'all') {
        $sql_doctors .= " AND department.id_department = " . intval($department_id);
    }
    if ($letters_range && $letters_range != 'all') {
        $letters = explode('-', $letters_range);
        $first_letter = trim($letters[0]);
        $last_letter = trim($letters[1]);
        $sql_doctors .= " AND (staff.full_name BETWEEN '$first_letter' AND '$last_letter' OR staff.full_name Like '$first_letter%' OR staff.full_name LIKE '$last_letter%')";
    }

    $sql_doctors_result = $conn->query($sql_doctors);
    $doctors = $sql_doctors_result ? $sql_doctors_result->fetch_all(MYSQLI_ASSOC) : [];

    $output = '';
    if ($doctors) {
        $output .= "<table class='table'>";
        $output .= "<thead><tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Дата рождения</th>
                        <th>Должность</th>
                        <th>Статус</th>
                        <th>Адрес</th>
                        <th>Телефон</th>
                        <th>Название поликлиники</th>
                        <th>Отделение</th>
                    </tr></thead>";
        $output .= "<tbody>";
        foreach ($doctors as $doctor) {
            $output .= "<tr>
                            <td>" . htmlspecialchars($doctor['id_doctor']) . "</td>
                            <td style='cursor: pointer;' class='doctor-name' data-id='" . htmlspecialchars($doctor['id_doctor']) . "'>" . htmlspecialchars($doctor['full_name']) . "</td>
                            <td>" . htmlspecialchars($doctor['birthday']) . "</td>
                            <td>" . htmlspecialchars($doctor['post']) . "</td>
                            <td>" . htmlspecialchars($doctor['status']) . "</td>
                            <td>" . htmlspecialchars($doctor['address']) . "</td>
                            <td>" . htmlspecialchars($doctor['phone_number']) . "</td>
                            <td>" . htmlspecialchars($doctor['name_polyclinic']) . "</td>
                            <td>" . htmlspecialchars($doctor['name_department']) . "</td>
                        </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>Нет доступных врачей.</p>";
    }
    return $output;
}

// Обработка AJAX-запроса (если есть)
if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    $polyclinic_id = $_POST['polyclinic_id'] ?? null;
    $department_id = $_POST['department_id'] ?? null;
    $letters_range = $_POST['letters_range'] ?? null;

    // Возвращаем только HTML-код таблицы
    echo getDoctorsTable($conn, $polyclinic_id, $department_id, $letters_range);
    exit(); // Важно! Прекращаем выполнение скрипта после отправки данных
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <title>Polyclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex align-items-start">
    <div class="nav flex-column nav-pills me-5" style="background-color: #e3fdfd;" id="v-pills-tab" role="tablist"
         aria-orientation="vertical">
        <a class="nav-link active" id="menu-tab" data-bs-toggle="pill" href="#menu" role="tab" aria-controls="menu"
           aria-selected="true">Меню</a>
        <a class="nav-link" id="doctors-tab" data-bs-toggle="pill" href="#doctors" role="tab"
           aria-controls="doctors" aria-selected="true">Врачи</a>
        <a class="nav-link" id="pacients-tab" data-bs-toggle="pill" href="#pacients" role="tab"
           aria-controls="pacients" aria-selected="false">Пациенты</a>
        <a class="nav-link" id="appointment-tab" data-bs-toggle="pill" href="#appointment" role="tab"
           aria-controls="appointment" aria-selected="false">Записи</a>
        <a class="nav-link" id="referral-tab" data-bs-toggle="pill" href="#referral" role="tab"
           aria-controls="referral" aria-selected="false">Направления</a>
        <a class="nav-link" id="info-about-polyclinic-tab" data-bs-toggle="pill" href="#info-about-polyclinic"
           role="tab" aria-controls="info-about-polyclinic" aria-selected="false">Информация о поликилиниках</a>
        <a class="nav-link" id="reports-tab" data-bs-toggle="pill" href="#reports" role="tab"
           aria-controls="reports" aria-selected="false">Отчеты</a>
    </div>
    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane fade show active" id="menu" role="tabpanel" aria-labelledby="menu">
            <p class="fs-2 text-uppercase">Добро пожаловать в систему!</p>
            <?php echo "Соединение успешно установлено!"; ?>
            <p class="fs-6">В данной системе вы можете получить нужные данные, используя фильтры, а также сформировать
                отчеты.</p>
        </div>
        <div class="tab-pane fade show" id="doctors" role="tabpanel" aria-labelledby="doctors">
            <p class="fs-2 text-uppercase">Врачи</p>
            <p class="fs-4">Настроить фильтры</p>
            <div class="col col-lg-2 d-flex">
                <select id="polyclinic_id" class="form-select" style="width:100vh; margin-right: 20px;" aria-label="Default select">
                    <option value="all" selected>Все поликлиники</option>
                    <?php foreach ($polyclinics as $polyclinic): ?>
                        <option value="<?= htmlspecialchars($polyclinic['id_polyclinic']) ?>"><?= htmlspecialchars($polyclinic['name_polyclinic']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="department_id" class="form-select" style="width:100vh; margin-right: 20px;" aria-label="Выберите отделение">
                    <option value="all" selected>Все отделения</option>
                </select>
                <select id="letters_range" class="form-select" style="width:100vh; margin-right: 20px;" aria-label="Default select">
                    <option value="all" selected>Все диапазоны</option>
                    <option value="А-Г">А-Г</option>
                    <option value="Д-З">Д-З</option>
                    <option value="И-М">И-М</option>
                    <option value="Н-Р">Н-Р</option>
                    <option value="С-Ф">С-Ф</option>
                    <option value="Х-Ш">Х-Ш</option>
                    <option value="Щ-Я">Щ-Я</option>
                </select>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    Применить
                </button>
            </div>
            <div class="col col-lg-2 d-flex">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newDoctorModal" style="margin:20px 20px 20px 0px;">
                    Добавить врача
                </button>
            </div>
            <div id="doctors_table">
                <?php echo getDoctorsTable($conn); ?>
            </div>
        </div>
        <?php include 'make_new_doctor.php'; ?> 
        <?php include 'edit_doctor_modal.php'; ?> 


        <div class="tab-pane fade" id="pacients" role="tabpanel" aria-labelledby="pacients">
            <p class="fs-2 text-uppercase">Пациенты</p>
            <p class="fs-4">Настроить фильтры</p>
        </div>
        <div class="tab-pane fade" id="appointment" role="tabpanel" aria-labelledby="appointment">
            <p class="fs-2 text-uppercase">Записи</p>
            <p class="fs-4">Настроить фильтры</p>
        </div>
        <div class="tab-pane fade" id="referral" role="tabpanel" aria-labelledby="referral">
            <p class="fs-2 text-uppercase">Направления</p>
            <p class="fs-4">Настроить фильтры</p>
        </div>
        <div class="tab-pane fade" id="info-about-polyclinic" role="tabpanel" aria-labelledby="info-about-polyclinic">
            <p class="fs-2 text-uppercase">Информация о поликлиниках</p>
            <p class="fs-4">Настроить фильтры</p>
        </div>
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports">
            <p class="fs-2 text-uppercase">Отчеты</p>
            <p class="fs-4">Настроить фильтры</p>
        </div>
    </div>
</div>
</body>
<style>
    .nav {
        background-color: #e3fdfd;
        height: 100vh;
    }

    .nav-pills .nav-link.active {
        background-color: #71c9ce;
        color: white;
    }

    .nav-pills .nav-link:hover {
        background-color: #cbf1f5;
    }

    .nav-pills .nav-link {
        color: #323232;
    }
    .btn{
        background-color: #71c9ce;   
        border-color:#61c0bf; 
    }
    .btn:hover{
        background-color: #61c0bf;
        border-color:#11999e; 
    }

    .btn:active{
        background-color: #11999e;
        border-color:#11999e;
    }
    .btn:focus{
        background-color: #11999e;
        border-color:#11999e;
    }

</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
<script src="education_qualif_functions.js"></script>
<script>
    function applyFilters() {
        // Получаем значение выбранных фильтров и записываем их в переменные
        var polyclinic_id = document.getElementById('polyclinic_id').value;
        var department_id = document.getElementById('department_id').value;
        var letters_range = document.getElementById('letters_range').value;

        // Создаем объект XMLHttpRequest для AJAX-запроса
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById('doctors_table').innerHTML = xhr.responseText;
                bindDoctorNameClickEvents(); // Привязываем события после обновления таблицы
            } else {
                alert('Произошла ошибка при выполнении запроса.');
            }
        };
        xhr.onerror = function () {
            alert('Произошла ошибка при выполнении запроса.');
        };
        xhr.send('ajax=1&polyclinic_id=' + encodeURIComponent(polyclinic_id) + '&department_id=' + encodeURIComponent(department_id) + '&letters_range=' + encodeURIComponent(letters_range));
    }

    // Функция для привязки событий клика к ячейкам с именами врачей
    function bindDoctorNameClickEvents() {
        const doctorNameCells = document.querySelectorAll('.doctor-name');
        
        doctorNameCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const doctorId = this.getAttribute('data-id');
                fetchDoctorData(doctorId);
            });
        });
    }

    // Инициализация событий при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        bindDoctorNameClickEvents(); // Привязываем события при загрузке страницы
    });
    //функция изменения фильтра выбора поликлиники
    document.getElementById('polyclinic_id').addEventListener('change', function () {
        var polyclinic_id = this.value;
        var departmentSelect = document.getElementById('department_id');

        departmentSelect.innerHTML = '<option value="all" selected>Все отделения</option>';

        var xhr = new XMLHttpRequest();
        //преедаем polyclinic_id как параметр файлу get_departments.php
        xhr.open('GET', 'get_departments.php?id_polyclinic=' + polyclinic_id, true);
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                if (xhr.responseText) {
                    try {
                        var departments = JSON.parse(xhr.responseText);
                        if (Array.isArray(departments)) {
                            departments.forEach(function (department) {
                                var option = document.createElement('option');
                                option.value = department.id_department;
                                option.textContent = department.name_department;
                                departmentSelect.appendChild(option);
                            });
                        } else {
                            console.error('Полученный ответ не является массивом:', departments);
                        }
                    } catch (e) {
                        console.error('Ошибка при разраборе JSON:', e);
                        console.error('Текст ответа:', xhr.responseText);
                    }
                } else {
                    console.warn('Пустой ответ от сервера.');
                }
            } else {
                console.error('Запрос не выполнен со статусом:', xhr.status);
            }
        };
        xhr.onerror = function () {
            console.error('Запрос не выполнен');
        };
        xhr.send();

    });
    
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
                        <label for="doctorNamePolyclinic_edit_staff" class="form-label">Поликлиники</label>
                        <input type="text" class="form-control" id="doctorNamePolyclinic_edit_staff" name="doctorNamePolyclinic_edit_staff" value="${doctor.name_polyclinic || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="doctorNameDepartment_edit_staff" class="form-label">Отделение</label>
                        <input type="text" class="form-control" id="doctorNameDepartment_edit_staff" name="doctorNameDepartment_edit_staff" value="${doctor.name_department || ''}">
                    </div>
                    <h3>Образование</h3>
                    <div class="mb-3">
                        <label for="education_select" class="form-label">Выберите образование</label>
                        <select class="form-select" id="education_select" onchange="updateEducationFields(this)">
                            <option value="">Выберите образование</option>
                            ${educations.map((edu, index) => 
                                `<option value="${index}" 
                                    data-edu-id="${edu.id_education}"
                                    //data-edu-institution="${edu.institution || ''}"
                                    data-type="${edu.type_of_education || ''}"
                                    data-field="${edu.ed_name_of_field || ''}">
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
                                    data-field="${qual.qe_name_of_field || ''}">
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
            <label for="startYear_edit_staff" class="form-label">Год начала обучения</label>
            <input type="number" class="form-control" id="startYear_edit_staff" name="startYear_edit_staff" min="1900" max="2100" value="${startYear}">
        </div>
        <div class="mb-3">
            <label for="endYear_edit_staff" class="form-label">Год окончания обучения</label>
            <input type="number" class="form-control" id="endYear_edit_staff" name="endYear_edit_staff" min="1900" max="2100" value="${endYear}">
        </div>
        <div class="mb-3">
            <label for="medicalField_edit_staff" class="form-label">Направление в медицине</label>
            <input type="text" class="form-control" id="medicalField_edit_staff" name="medicalField_edit_staff" value="${option.dataset.field || ''}">
        </div>
        <input type="hidden" id="education_id" name="education_id" value="${eduId}">
        <div class="mb-3">
            <button type="button" class="btn btn-primary" onclick="saveEducationChanges()">Сохранить изменения</button>
            <button class="btn btn-danger" onclick="deleteCurrentEducation()">Удалить выбранное</button>
        </div>

    `;
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
            <input type="text" class="form-control" id="qualif_improv_medicalField_edit_staff" name="qualif_improv_medicalField_edit_staff" value="${option.dataset.field || ''}">
        </div>
        <input type="hidden" id="qualification_id" name="qualification_id" value="${qualId}">
        <div class="mb-3">
            <button type="button" class="btn btn-primary" onclick="saveQualifChanges()">Сохранить изменения</button>
            <button class="btn btn-danger" onclick="deleteCurrentQualif()">Удалить выбранное</button>
        </div>
    `;
}

</script>
</html>
