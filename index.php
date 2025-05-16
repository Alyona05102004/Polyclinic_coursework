
<?php
include 'bd.php'; 

// Получение данных для фильтра поликлиник
$sql_philter_polyclinic = "SELECT id_polyclinic, name_polyclinic FROM info_about_polyclinic";
$sql_philter_polyclinic_result = $conn->query($sql_philter_polyclinic);
$polyclinics = $sql_philter_polyclinic_result ? $sql_philter_polyclinic_result->fetch_all(MYSQLI_ASSOC) : [];

$sql_philter_address_patient="SELECT DISTINCT
                                TRIM(
                                    SUBSTRING(
                                    address,
                                    LOCATE('г.', address) + 2,
                                    CASE
                                        WHEN LOCATE(',', address) > 0 THEN LOCATE(',', address) - LOCATE('г.', address) - 2
                                        ELSE LENGTH(address) - LOCATE('г.', address) - 1
                                    END
                                    )
                                ) AS city
                                FROM information_about_patient
                                WHERE address LIKE 'г.%'";
$sql_philter_address_patient_result = $conn->query($sql_philter_address_patient);
$cities = $sql_philter_address_patient_result ? $sql_philter_address_patient_result->fetch_all(MYSQLI_ASSOC) : [];

$sql_philter_address_patient2 = "SELECT DISTINCT
    TRIM(
        SUBSTRING(
            address,
            LOCATE(',', address) + 1,
            CASE
                WHEN LOCATE(', д.', address) > 0 
                    THEN LOCATE(', д.', address) - LOCATE(',', address) - 1
                ELSE LENGTH(address) - LOCATE(',', address)
            END
        )
    ) AS street
FROM information_about_patient
WHERE address LIKE 'г.%' AND address LIKE '%,%'";
$sql_philter_address_patient_result2 = $conn->query($sql_philter_address_patient2);
$streets= $sql_philter_address_patient_result2 ? $sql_philter_address_patient_result2->fetch_all(MYSQLI_ASSOC) : [];

function getDoctorsTable($conn, $polyclinic_id = null, $department_id = null, $letters_range = null)
{
    // Формирование SQL-запроса
    $sql_doctors = "SELECT staff.id_doctor, staff.full_name, staff.birthday, staff.post, staff.status, staff.address, 
    staff.phone_number, department.name_department, info_about_polyclinic.name_polyclinic, SUM(
        CASE 
            WHEN education.work_experience IS NULL THEN 0 
            ELSE education.work_experience 
        END
     ) as total_exp
                    FROM staff 
                    JOIN department ON department.id_department = staff.id_department
                    JOIN connection ON connection.id_department = department.id_department
                    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic = connection.id_polyclinic
                    JOIN connection_education ON connection_education.id_doctor = staff.id_doctor
                    JOIN education ON connection_education.id_education = education.id_education
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
    $sql_doctors .= " GROUP BY staff.id_doctor";

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
                        <th>Общий стаж работы</th>
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
                            <td>" . htmlspecialchars($doctor['total_exp']) . "</td>
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

function getPatientsTable($conn, $birthday_date = null, $curent_address = null, $letters_range = null, $last_date=null, $currentGender=null)
{
    // Формирование SQL-запроса
    $sql_patients = "SELECT DISTINCT information_about_patient.id_patient, information_about_patient.full_name, information_about_patient.birthday, information_about_patient.policy_number, information_about_patient.address, information_about_patient.gender 
    FROM `information_about_patient` 
    WHERE 1=1";

    if($currentGender && $currentGender!='all'){
        $sql_patients .= " AND information_about_patient.gender LIKE '$currentGender%'";
    }
    if ($birthday_date && $birthday_date != 'all') {
        $sql_patients .= " AND information_about_patient.birthday LIKE '$birthday_date%'";
    }
    if ($curent_address && $curent_address != 'all') {
        $normalized_address = preg_replace('/^(ул\.|улица|пр\.|проспект|пр-кт\.?)\s*/iu', '', $curent_address);
        $sql_patients .= " AND (information_about_patient.address LIKE '%" . $conn->real_escape_string($normalized_address) . "%' 
                          OR information_about_patient.address LIKE '%ул. " . $conn->real_escape_string($normalized_address) . "%'
                          OR information_about_patient.address LIKE '%улица " . $conn->real_escape_string($normalized_address) . "%')";
    }
    if ($letters_range && $letters_range != 'all') {
        $letters = explode('-', $letters_range);
        $first_letter = trim($letters[0]);
        $last_letter = trim($letters[1]);
        $sql_patients .= " AND (information_about_patient.full_name BETWEEN '$first_letter' AND '$last_letter' OR information_about_patient.full_name Like '$first_letter%' OR information_about_patient.full_name LIKE '$last_letter%')";
    }

    if ($last_date && $last_date != 'all') {
        $sql_patients .= " AND EXISTS (
            SELECT 1 FROM appointment 
            WHERE appointment.id_patient = information_about_patient.id_patient
            AND appointment.date LIKE '" . $conn->real_escape_string($last_date) . "%'
        )";
    }

    $sql_patients_result = $conn->query($sql_patients);
    $patients = $sql_patients_result ? $sql_patients_result->fetch_all(MYSQLI_ASSOC) : [];

    $output = '';
    if ($patients) {
        $output .= "<table class='table'>";
        $output .= "<thead><tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Дата рождения</th>
                        <th>Номер полиса</th>
                        <th>Адрес</th>
                        <th>Пол</th>
                    </tr></thead>";
        $output .= "<tbody>";
        foreach ($patients as $patient) {
            $output .= "<tr>
                            <td>" . htmlspecialchars($patient['id_patient']) . "</td>
                            <td style='cursor: pointer;' class='patient-name' data-id='" . htmlspecialchars($patient['id_patient']) . "'>" . htmlspecialchars($patient['full_name']) . "</td>
                            <td>" . htmlspecialchars($patient['birthday']) . "</td>
                            <td>" . htmlspecialchars($patient['policy_number']) . "</td>
                            <td>" . htmlspecialchars($patient['address']) . "</td>
                            <td>" . htmlspecialchars($patient['gender']) . "</td>
                        </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>Нет доступных пациентов.</p>";
    }
    return $output;
}


// Обработка AJAX-запроса для пациентов
if (isset($_POST['ajax']) && $_POST['ajax'] == 2) { // Измените значение на 2 или другое уникальное
    $birthday_date = $_POST['birthdate'] ?? null;
    $curent_address = $_POST['curent_address'] ?? null; // Исправлено имя параметра
    $last_date = $_POST['last_date'] ?? null;
    $letters_range = $_POST['letters_range'] ?? null;
    $currentGender = $_POST['currentGender'] ?? null;

    // Возвращаем только HTML-код таблицы с правильными параметрами
    echo getPatientsTable($conn, $birthday_date, $curent_address, $letters_range, $last_date, $currentGender);
    exit();
}

function getAppointmentsTable($conn, $polyclinic_id = null, $department_id = null, $letters_range = null, $doctor_id=null, $date_start=null, $date_end=null, $status=null){
    // Формирование SQL-запроса
    $sql_appointments = "SELECT appointment.id_appointment, appointment.date, appointment.id_doctor, staff.full_name as doctorName, staff.post,
    appointment.id_ranges, operating_ranges.range_start, operating_ranges.range_end, appointment.id_patient, 
    information_about_patient.full_name as patientName, appointment.id_cabinet, cabinet.number_of_cabinet, appointment.id_referral, 
    appointment.id_medical_history, department.id_department, department.name_department, info_about_polyclinic.id_polyclinic, 
    info_about_polyclinic.name_polyclinic, info_about_polyclinic.address
    FROM appointment
    LEFT JOIN staff ON staff.id_doctor=appointment.id_doctor
    JOIN operating_ranges ON operating_ranges.id_ranges=appointment.id_ranges
    LEFT JOIN information_about_patient ON information_about_patient.id_patient=appointment.id_patient
    JOIN cabinet ON cabinet.id_cabinet=appointment.id_cabinet
    JOIN department ON cabinet.id_department=department.id_department
    JOIN connection ON connection.id_department=department.id_department
    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic=connection.id_polyclinic
    WHERE 1=1";

    if ($polyclinic_id && $polyclinic_id != 'all') {
        $sql_appointments .= " AND info_about_polyclinic.id_polyclinic = " . intval($polyclinic_id);
    }
    if ($department_id && $department_id != 'all') {
        $sql_appointments .= " AND department.id_department = " . intval($department_id);
    }
    if ($letters_range && $letters_range != 'all') {
        $letters = explode('-', $letters_range);
        $first_letter = trim($letters[0]);
        $last_letter = trim($letters[1]);
        $sql_appointments .= " AND (staff.full_name BETWEEN '$first_letter' AND '$last_letter' OR staff.full_name Like '$first_letter%' OR staff.full_name LIKE '$last_letter%')";
    }
    if ($doctor_id && $doctor_id != 'all') {
        $sql_appointments .= " AND appointment.id_doctor = " . intval($doctor_id);
    }

    if ($date_start && $date_start != 'all' && $date_end && $date_end != 'all') {
        $sql_appointments .= " AND appointment.date BETWEEN '$date_start' AND '$date_end'";
    }

    if($status=='busy'){
        $sql_appointments .= " AND appointment.id_doctor!=0 AND appointment.id_patient!=0";    
    }

    if($status=='free'){
        $sql_appointments .= " AND appointment.id_patient IS NULL AND appointment.id_doctor!=0";    
    }

    if($status=='without_doctor'){
        $sql_appointments .= " AND appointment.id_doctor IS NULL";    
    }

   // $sql_appointments .= " GROUP BY appointment.id_appointment";

    $sql_appointments_result = $conn->query($sql_appointments);
    if (!$sql_appointments_result) {
        die("Ошибка SQL запроса: " . $conn->error);
    }
    $appointments  = $sql_appointments_result ? $sql_appointments_result->fetch_all(MYSQLI_ASSOC) : [];

    $output = '';
    if ($appointments) {
        $output .= "<table class='table'>";
        $output .= "<thead><tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>ФИО врача</th>
                        <th>Должность врача</th>
                        <th>ФИО пациента</th>
                        <th>Кабинет</th>
                        <th>ID направления</th>
                        <th>Отделение</th>
                        <th>Наименование поликлиники</th>
                        <th>Адрес</th>                        
                    </tr></thead>";
        $output .= "<tbody>";
        foreach ($appointments as $appointment) {
            $output .= "<tr style='cursor: pointer;' class='appointment-id' data-id='" . htmlspecialchars($appointment['id_appointment']) . "'>
                            <td>" . htmlspecialchars($appointment['id_appointment']) . "</td>
                            <td>" . htmlspecialchars($appointment['date']) . "</td>
                            <td>" . htmlspecialchars($appointment['range_start']) . " - " . htmlspecialchars($appointment['range_end']) . "</td>
                            <td>" . htmlspecialchars($appointment['doctorName']) . "</td>
                            <td>" . htmlspecialchars($appointment['post']) . "</td>
                            <td>" . htmlspecialchars($appointment['patientName']) . "</td>
                            <td>" . htmlspecialchars($appointment['number_of_cabinet']) . "</td>
                            <td>" . htmlspecialchars($appointment['id_referral']) . "</td>
                            <td>" . htmlspecialchars($appointment['name_department']) . "</td>
                            <td>" . htmlspecialchars($appointment['name_polyclinic']) . "</td>
                            <td>" . htmlspecialchars($appointment['address']) . "</td>
                        </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>Нет доступных записей.</p>";
    }
    return $output;
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 3) { 
    $polyclinic_id = $_POST['polyclinic_id'] ?? null;
    $department_id = $_POST['department_id'] ?? null; 
    $letters_range = $_POST['letters_range'] ?? null;
    $doctor_id = $_POST['doctor_id'] ?? null;
    $date_start = $_POST['date_start'] ?? null;
    $date_end = $_POST['date_end'] ?? null;
    $status = $_POST['status'] ?? null;

    // Возвращаем только HTML-код таблицы с правильными параметрами
    echo getAppointmentsTable($conn, $polyclinic_id, $department_id, $letters_range, $doctor_id, $date_start, $date_end, $status);
    exit();
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
           aria-controls="pacients" aria-selected="true">Пациенты</a>
        <a class="nav-link" id="appointment-tab" data-bs-toggle="pill" href="#appointment" role="tab"
           aria-controls="appointment" aria-selected="true">Записи</a>
        <a class="nav-link" id="referral-tab" data-bs-toggle="pill" href="#referral" role="tab"
           aria-controls="referral" aria-selected="true">Направления</a>
        <a class="nav-link" id="info-about-polyclinic-tab" data-bs-toggle="pill" href="#info-about-polyclinic"
           role="tab" aria-controls="info-about-polyclinic" aria-selected="true">Информация о поликилиниках</a>
        <a class="nav-link" id="reports-tab" data-bs-toggle="pill" href="#reports" role="tab"
           aria-controls="reports" aria-selected="true">Отчеты</a>
    </div>
    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane fade show active" id="menu" role="tabpanel" aria-labelledby="menu">
            <p class="fs-2 text-uppercase">Добро пожаловать в систему!</p>
            <?php echo "Соединение успешно установлено!"; ?>
            <p class="fs-6">В данной системе вы можете получить нужные данные, используя фильтры, а также сформировать отчеты.</p>
        </div>
        <div class="tab-pane fade" id="doctors" role="tabpanel" aria-labelledby="doctors">
            <h2 class="mb-4">Врачи</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">Настроить фильтры</h4>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="polyclinic_id" class="form-label">Поликлиника</label>
                            <select id="polyclinic_id" class="form-select" aria-label="Выбор поликлиники">
                                <option value="all" selected>Все поликлиники</option>
                                <?php foreach ($polyclinics as $polyclinic): ?>
                                    <option value="<?= htmlspecialchars($polyclinic['id_polyclinic']) ?>"><?= htmlspecialchars($polyclinic['name_polyclinic']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="department_id" class="form-label">Отделение</label>
                            <select id="department_id" class="form-select" aria-label="Выбор отделения">
                                <option value="all" selected>Все отделения</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="letters_range" class="form-label">Диапазон фамилий</label>
                            <select id="letters_range" class="form-select" aria-label="Выбор диапазона">
                                <option value="all" selected>Все диапазоны</option>
                                <option value="А-Г">А-Г</option>
                                <option value="Д-З">Д-З</option>
                                <option value="И-М">И-М</option>
                                <option value="Н-Р">Н-Р</option>
                                <option value="С-Ф">С-Ф</option>
                                <option value="Х-Ш">Х-Ш</option>
                                <option value="Щ-Я">Щ-Я</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                Применить фильтры
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newDoctorModal">
                    <i class="bi bi-plus-circle"></i> Добавить врача
                </button>
            </div>
            <div class="card">
                <div class="card-body">
                    <div id="doctors_table">
                        <?php echo getDoctorsTable($conn); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'make_new_doctor.php'; ?> 
        <?php include 'edit_doctor_modal.php'; ?> 


        <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Детали записи</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="appointmentModalBody">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="referralModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Детали направления</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="referralModalBody">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

       
        <div class="tab-pane fade" id="pacients" role="tabpanel" aria-labelledby="pacients">
            <h2 class="mb-4">Пациенты</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">Настроить фильтры</h4>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="birthdate" class="form-label">Дата или год рождения</label>
                            <input type="text" class="form-control" id="birthdate" name="birthdate" data-inputmask="'mask': '9999-99-99'" placeholder="ГГГГ-ММ-ДД">
                        </div>
                        <div class="col-md-3">
                            <label for="city" class="form-label">Город</label>
                            <select id="city" class="form-select" aria-label="Выбор города">
                                <option value="all" selected>Все города</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city['city']) ?>"><?= htmlspecialchars($city['city']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="street" class="form-label">Улица</label>
                            <select id="street" class="form-select" aria-label="Выбор улицы">
                                <option value="all" selected>Все улицы</option>
                                <?php foreach ($streets as $street): ?>
                                    <option value="<?= htmlspecialchars($street['street']) ?>"><?= htmlspecialchars($street['street']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="currentGender" class="form-label">Пол</label>
                            <select id="currentGender" class="form-select">
                                <option value="all" selected>Все</option>
                                <option value="М">Мужской</option>
                                <option value="Ж">Женский</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="letters_range_patients" class="form-label">Диапазон фамилий</label>
                            <select id="letters_range_patients" class="form-select">
                                <option value="all" selected>Все</option>
                                <option value="А-Г">А-Г</option>
                                <option value="Д-З">Д-З</option>
                                <option value="И-М">И-М</option>
                                <option value="Н-Р">Н-Р</option>
                                <option value="С-Ф">С-Ф</option>
                                <option value="Х-Ш">Х-Ш</option>
                                <option value="Щ-Я">Щ-Я</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="lastVisitDate" class="form-label">Дата последнего посещения</label>
                            <input type="text" class="form-control" id="lastVisitDate" name="lastVisitDate" data-inputmask="'mask': '9999-99-99'" placeholder="ГГГГ-ММ-ДД">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="applyFiltersPatients()">
                                Применить фильтры
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newPatientModal">
                    <i class="bi bi-plus-circle"></i> Добавить пациента
                </button>
            </div>
            <div class="card">
                <div class="card-body">
                    <div id="patients_table">
                        <?php echo getPatientsTable($conn); ?>
                    </div>
                </div>
            </div>
            <div id="patient-details-container" class="mt-4 d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Информация о пациенте</h2>
                    <button type="button" class="btn btn-secondary" onclick="backToPatientsList()">
                        <i class="bi bi-arrow-left"></i> Назад к списку
                    </button>
                </div>
                <div id="patient-details-content"></div>
            </div>
        </div>
        <?php include 'make_new_patient.php'; ?>

        <div class="tab-pane fade " id="appointment" role="tabpanel" aria-labelledby="appointment">
            <h2 class="mb-4">Записи</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">Настроить фильтры</h4>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="polyclinic_id_appointment" class="form-label">Поликлиника</label>
                            <select id="polyclinic_id_appointment" class="form-select" aria-label="Выбор поликлиники">
                                <option value="all" selected>Все поликлиники</option>
                                <?php foreach ($polyclinics as $polyclinic): ?>
                                    <option value="<?= htmlspecialchars($polyclinic['id_polyclinic']) ?>"><?= htmlspecialchars($polyclinic['name_polyclinic']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="department_id_appointment" class="form-label">Отделение</label>
                            <select id="department_id_appointment" class="form-select" aria-label="Выбор отделения">
                                <option value="all" selected>Все отделения</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="doctor_id_appointment" class="form-label">Врачи</label>
                            <select id="doctor_id_appointment" class="form-select" aria-label="Выбор врача">
                                <option value="all" selected>Все врачи</option>
                            </select>    
                        </div>
                    </div>
                            
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="startDate_appointment" class="form-label">Записи в диапазоне с</label>
                            <input type="text" class="form-control" id="startDate_appointment" name="startDate_appointment" data-inputmask="'mask': '9999-99-99'" placeholder="ГГГГ-ММ-ДД">
                        </div>
                        <div class="col-md-4">
                            <label for="endDate_appointment" class="form-label">по</label>
                            <input type="text" class="form-control" id="endDate_appointment" name="endDate_appointment" data-inputmask="'mask': '9999-99-99'" placeholder="ГГГГ-ММ-ДД">
                        </div>
                    </div>   
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="letters_range_appointment" class="form-label">Диапазон фамилий</label>
                            <select id="letters_range_appointment" class="form-select" aria-label="Выбор диапазона">
                                <option value="all" selected>Все диапазоны</option>
                                <option value="А-Г">А-Г</option>
                                <option value="Д-З">Д-З</option>
                                <option value="И-М">И-М</option>
                                <option value="Н-Р">Н-Р</option>
                                <option value="С-Ф">С-Ф</option>
                                <option value="Х-Ш">Х-Ш</option>
                                <option value="Щ-Я">Щ-Я</option>
                            </select>
                        </div>
                        <div class="appointment-filter_radio">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="appointmentFilter_radio" id="appointment_all" value="all" checked>
                                <label class="form-check-label" for="appointment_all">Все записи</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="appointmentFilter_radio" id="appointment_busy" value="busy">
                                <label class="form-check-label" for="appointment_busy">Занятые записи</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="appointmentFilter_radio" id="appointment_free" value="free">
                                <label class="form-check-label" for="appointment_free">Свободные записи</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="appointmentFilter_radio" id="appointment_without_doctor" value="without_doctor">
                                <label class="form-check-label" for="appointment_without_doctor">Нет врача</label>
                            </div>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" onclick="applyFiltersAppointment()">
                                Применить фильтры
                            </button>
                        </div>
                    </div>             
                </div>
            </div>
                
            <!-- Кнопка добавления -->
            <div class="mb-4">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newListAppointmentModal">
                    <i class="bi bi-plus-circle"></i> Открыть запись
                </button>     
            </div>
                
                <!-- Таблица записей -->
            <div class="card">
                <div class="card-body">
                    <div id="appointments_table">
                        <?php echo getAppointmentsTable($conn); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'newListAppointmentModal.php'; ?>
        <div class="tab-pane fade" id="referral" role="tabpanel" aria-labelledby="referral">
            <h2 class="mb-4">Направления</h2>
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

    .fixed-height-btn {
        height: 38px;
    }

</style>



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

    function bindDoctorNameClickEvents() {
        const doctorNameCells = document.querySelectorAll('.doctor-name');
        
        doctorNameCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const doctorId = this.getAttribute('data-id');
                fetchDoctorData(doctorId);
            });
        });
    }

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

    document.getElementById('polyclinic_id_appointment').addEventListener('change', function () {
        var polyclinic_id = this.value;
        var departmentSelect = document.getElementById('department_id_appointment');

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

    document.getElementById('department_id_appointment').addEventListener('change', function () {
        var department_id = this.value;
        var doctorSelect = document.getElementById('doctor_id_appointment');

        doctorSelect.innerHTML = '<option value="all" selected>Все врачи</option>';

        var xhr = new XMLHttpRequest();

        xhr.open('GET', 'get_doctors_philter.php?id_department=' + department_id, true);
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                if (xhr.responseText) {
                    try {
                        var doctors = JSON.parse(xhr.responseText);
                        if (Array.isArray(doctors)) {
                            doctors.forEach(function (doctor) {
                                var option = document.createElement('option');
                                option.value = doctor.id_doctor;
                                option.textContent = doctor.full_name;
                                doctorSelect.appendChild(option);
                            });
                        } else {
                            console.error('Полученный ответ не является массивом:', doctors);
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

    function applyFiltersAppointment() {
        var polyclinic_id = document.getElementById('polyclinic_id_appointment').value;
        var department_id = document.getElementById('department_id_appointment').value;
        var letters_range = document.getElementById('letters_range_appointment').value;
        var doctor_id = document.getElementById('doctor_id_appointment').value;
        var date_start= document.getElementById('startDate_appointment').value;  
        var date_end= document.getElementById('endDate_appointment').value;
        var status=document.querySelector('input[name="appointmentFilter_radio"]:checked').value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('appointments_table').innerHTML = xhr.responseText;
                bindAppointmentClickEvents(); 
            } else {
                alert('Произошла ошибка при выполнении запроса.');
            }
        };
        xhr.onerror = function() {
            alert('Произошла ошибка при выполнении запроса.');
        };
        xhr.send('ajax=3&polyclinic_id=' + encodeURIComponent(polyclinic_id) + 
                '&department_id=' + encodeURIComponent(department_id) + 
                '&letters_range=' + encodeURIComponent(letters_range) +
                '&doctor_id=' + encodeURIComponent(doctor_id)+
                '&date_start=' + encodeURIComponent(date_start)+
                '&date_end=' + encodeURIComponent(date_end) +
                '&status='+ encodeURIComponent(status));
    }

    function applyFiltersPatients() {
        try {
            // Получаем значения фильтров
            var birthdate = document.getElementById('birthdate')?.value || '';
            var city = document.getElementById('city')?.value || 'all';
            var street = document.getElementById('street')?.value || 'all';
            var currentGender = document.getElementById('currentGender')?.value || 'all';
            var letters_range = document.getElementById('letters_range_patients')?.value || 'all';
            var lastVisitDate = document.getElementById('lastVisitDate')?.value || '';

            // Объединяем город и улицу в один адрес
            var addressParts = [];
            if (city !== 'all') addressParts.push('г. ' + city);
            if (street !== 'all') {
                street = street.replace(/^(ул\.|улица|пр\.|проспект|пр-кт\.?)\s*/i, '').trim();
                addressParts.push(street);
            }
            
            var address = addressParts.length > 0 ? addressParts.join(', ') : 'all';

            // Создаем AJAX-запрос
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('patients_table').innerHTML = xhr.responseText;
                    bindPatientNameClickEvents();
                } else {
                    alert('Произошла ошибка при выполнении запроса.');
                }
            };
            xhr.onerror = function() {
                alert('Произошла ошибка при выполнении запроса.');
            };
            
            // Отправляем данные
            xhr.send('ajax=2&birthdate=' + encodeURIComponent(birthdate) + 
                    '&curent_address=' + encodeURIComponent(address) + 
                    '&last_date=' + encodeURIComponent(lastVisitDate) + 
                    '&letters_range=' + encodeURIComponent(letters_range) + 
                    '&currentGender=' + encodeURIComponent(currentGender));
        } catch (e) {
            console.error('Ошибка в applyFiltersPatients:', e);
            alert('Произошла ошибка при применении фильтров.');
        }
    }

    function bindPatientNameClickEvents() {
        const patientNameCells = document.querySelectorAll('.patient-name');
        
        patientNameCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const patientId = this.getAttribute('data-id');
                showPatientDetails(patientId);
            });
        });
    }

    function showPatientDetails(patientId) {
        // Показываем контейнер с деталями
        document.getElementById('patient-details-container').classList.remove('d-none');
        // Скрываем таблицу пациентов
        document.getElementById('patients_table').classList.add('d-none');
        
        // Загружаем данные пациента через AJAX
        fetch('get_patient.php?id=' + patientId)
            .then(response => response.text())
            .then(data => {
                // Вставляем полученные данные
                document.getElementById('patient-details-content').innerHTML = data;
                bindPatientAppointmentClickEvents(patientId);
                bindPatientReferralClickEvents(patientId);
                
                // Добавляем обработчик событий делегирования для переключения таблиц
                document.getElementById('patient-details-content').addEventListener('change', function(e) {
                    if (e.target.id === 'appointmentsRadio') {
                        document.getElementById('appointmentsTable').style.display = 'block';
                        document.getElementById('referralsTable').style.display = 'none';
                    } else if (e.target.id === 'referralsRadio') {
                        document.getElementById('appointmentsTable').style.display = 'none';
                        document.getElementById('referralsTable').style.display = 'block';
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('patient-details-content').innerHTML = 
                    '<div class="alert alert-danger">Ошибка загрузки данных</div>';
            });
    }
    function savePatientInfo(patientId) {
        const form = document.getElementById('patientForm');
        const formData = new FormData(form);
        
        fetch('update_patient.php?id=' + patientId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Изменения сохранены!');
                showPatientDetails(patientId); // Обновляем данные
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при сохранении');
        });
    }

    function backToPatientsList() {
        // Показываем таблицу пациентов
        document.getElementById('patients_table').classList.remove('d-none');
        // Скрываем контейнер с деталями
        document.getElementById('patient-details-container').classList.add('d-none');
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindPatientNameClickEvents();
    });

    document.addEventListener('DOMContentLoaded', function() {
        bindPatientAppointmentClickEvents();
        bindPatientReferralClickEvents();
    });

    function bindPatientAppointmentClickEvents(patientId) {
        const appointmentRows = document.querySelectorAll('.patient-appointment');
        appointmentRows.forEach(row => {
            row.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                console.log(appointmentId);
                console.log("Вызов fetchAppointmentData с ID:", appointmentId);  
                fetchAppointmentData(appointmentId, patientId); // Передаем ID пациента
            });
        });
    }

    function fetchAppointmentData(appointmentId, patientId) {
        // Проверяем, загружен ли Bootstrap
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error("Bootstrap Modal не загружен!");
            return;
        }

        // Показываем модальное окно
        const modalElement = document.getElementById('appointmentModal');
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement); // Получаем экземпляр модального окна или создаем новый
        modal.show();

        // Очищаем содержимое модального окна и показываем индикатор загрузки
        document.getElementById('appointmentModalBody').innerHTML = 'Загрузка данных...';

        // Загружаем данные
        fetch('edit_appointment.php?id=' + appointmentId + '&patient_id=' + patientId) // Добавляем patientId
            .then(response => {
                if (!response.ok) throw new Error("Ошибка сети");
                return response.text();
            })
            .then(data => {
                document.getElementById('appointmentModalBody').innerHTML = data;
            })
            .catch(error => {
                console.error("Ошибка:", error);
                document.getElementById('appointmentModalBody').innerHTML =
                    '<div class="alert alert-danger">Не удалось загрузить данные</div>';
            });
    }

    function saveAppointmentData(appointmentId) {
        const form = document.getElementById('editHistoryForm');
        const formData = new FormData(form);
        
        // Добавляем ID приёма в FormData
        formData.append('id', appointmentId);
        
        fetch('update_history.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Изменения сохранены!');
                // Закрываем модальное окно после сохранения
                const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
                modal.hide();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при сохранении');
        });
    }

    function bindPatientReferralClickEvents(patientId) {
        const referralRows = document.querySelectorAll('.patient-referral');
        referralRows.forEach(row => {
            row.addEventListener('click', function() {
                const referralId = this.getAttribute('data-id');
                fetchReferralData(referralId, patientId); // Теперь передаём patientId
            });
        });
    }

    function fetchReferralData(referraltId, patientId) {

        // Показываем модальное окно
        const modalElement = document.getElementById('referralModal');
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement); // Получаем экземпляр модального окна или создаем новый
        modal.show();

        // Очищаем содержимое модального окна и показываем индикатор загрузки
        document.getElementById('referralModalBody').innerHTML = 'Загрузка данных...';

        // Загружаем данные
        fetch('edit_referral.php?id=' + referraltId + '&patient_id=' + patientId) // Добавляем patientId
            .then(response => {
                if (!response.ok) throw new Error("Ошибка сети");
                return response.text();
            })
            .then(data => {
                document.getElementById('referralModalBody').innerHTML = data;
            })
            .catch(error => {
                console.error("Ошибка:", error);
                document.getElementById('referralModalBody').innerHTML =
                    '<div class="alert alert-danger">Не удалось загрузить данные</div>';
            });
    }

    document.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
            if (modal) modal.hide();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        bindAppointmentClickEvents(); 
    });


    function bindAppointmentClickEvents() {
        const appointmentRows = document.querySelectorAll('.appointment-id');
        
        appointmentRows.forEach(row => {
            row.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                fetchAppointmentDetails(appointmentId);
            });
        });
    }
    function fetchAppointmentDetails(appointmentId) {
        // Проверяем, загружен ли Bootstrap
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error("Bootstrap Modal не загружен!");
            return;
        }

        // Показываем модальное окно
        const modalElement = document.getElementById('appointmentModal');
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();

        // Очищаем содержимое модального окна и показываем индикатор загрузки
        document.getElementById('appointmentModalBody').innerHTML = 'Загрузка данных...';

        // Загружаем данные
        fetch('get_appointment_details.php?id=' + appointmentId)
            .then(response => {
                if (!response.ok) throw new Error("Ошибка сети");
                return response.text();
            })
            .then(data => {
                document.getElementById('appointmentModalBody').innerHTML = data;
            })
            .catch(error => {
                console.error("Ошибка:", error);
                document.getElementById('appointmentModalBody').innerHTML =
                    '<div class="alert alert-danger">Не удалось загрузить данные</div>';
            });
    }
</script>
</html>
