<?php
include 'bd.php';

$new_doctor_sql_philter_polyclinic = "SELECT id_polyclinic, name_polyclinic FROM info_about_polyclinic;";
$new_doctor_sql_philter_polyclinic_result = $conn->query($new_doctor_sql_philter_polyclinic);
$new_doctor_polyclinics = $new_doctor_sql_philter_polyclinic_result ? $new_doctor_sql_philter_polyclinic_result->fetch_all(MYSQLI_ASSOC) : [];


$new_doctor_sql_philter_department = "SELECT id_department, name_department FROM department;";
$new_doctor_sql_philter_department_result = $conn->query($new_doctor_sql_philter_department);
$new_doctor_departments = $new_doctor_sql_philter_department_result ? $new_doctor_sql_philter_department_result->fetch_all(MYSQLI_ASSOC) : [];

$new_doctor_sql_philter_field_of_medicine = "SELECT id_field, name_of_field FROM field_of_medicine;";
$new_doctor_sql_philter_field_of_medicine_result = $conn->query($new_doctor_sql_philter_field_of_medicine);
$new_doctor_field_of_medicine = $new_doctor_sql_philter_field_of_medicine_result ? $new_doctor_sql_philter_field_of_medicine_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<div class="modal fade" id="newDoctorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newDoctorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-2 text-uppercase" id="newDoctorModalLabel">Добавить нового врача</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <form id="newDoctorForm">
                            <h3>Личная информация</h3>
                            <div class="mb-3">
                                <label for="firstName" class="form-label">Имя</label>
                                <input type="text" class="form-control" id="firstName" name="firstName">
                                <div class="invalid-feedback" id="firstNameError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Фамилия</label>
                                <input type="text" class="form-control" id="lastName" name="lastName">
                                <div class="invalid-feedback" id="lastNameError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="middleName" class="form-label">Отчество</label>
                                <input type="text" class="form-control" id="middleName" name="middleName">
                                <div class="invalid-feedback" id="middleNameError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="birthDate" class="form-label">Дата рождения</label>
                                <input type="date" class="form-control" id="birthDate" name="birthDate">
                            </div>
                            <div class="mb-3">
                                <label for="phoneNumber" class="form-label">Номер телефона</label>
                                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber">
                                <div class="invalid-feedback" id="phoneNumberError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="doctorAdress" class="form-label">Адрес</label>
                                <input type="text" class="form-control" id="doctorAdress" name="doctorAdress">
                                <div class="invalid-feedback" id="doctorAdressError"></div>
                            </div>
                            <h3>Работа</h3>
                            <div class="mb-3">
                                <label for="position" class="form-label">Должность</label>
                                <input type="text" class="form-control" id="position" name="position">
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Выбрать отделение</label>
                                <select class="form-control" id="department" name="department">
                                <?php foreach ($new_doctor_departments as $new_doctor_department): ?>
                                        <option value="<?= htmlspecialchars($new_doctor_department['id_department']) ?>"><?= htmlspecialchars($new_doctor_department['name_department']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="polyclinic" class="form-label">Выбрать поликлинику</label>
                                <select class="form-control" id="polyclinic" name="polyclinic">
                                    <?php foreach ($new_doctor_polyclinics as $new_doctor_polyclinic): ?>
                                        <option value="<?= htmlspecialchars($new_doctor_polyclinic['id_polyclinic']) ?>"><?= htmlspecialchars($new_doctor_polyclinic['name_polyclinic']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="byAppointment">По записи</option>
                                    <option value="available">По направлению</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="experience" class="form-label">Стаж работы</label>
                                <input type="text" class="form-control" id="experience" name="experience">
                                <div class="invalid-feedback" id="experienceError"></div>
                            </div>
                            <h3>Образование</h3>
                            <div class="mb-3">
                                <label for="educationType" class="form-label">Вид образования</label>
                                <select class="form-control" id="educationType" name="educationType">
                                    <option value="">Выберите вид образования</option>
                                    <option value="Высшее">Высшее</option>
                                    <option value="Среднее">Среднее</option>
                                    <option value="Аспирантура">Аспирантура</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="university" class="form-label">Наименование университета</label>
                                <input type="text" class="form-control" id="university" name="university">
                            </div>
                            <div class="mb-3">
                                <label for="startYear" class="form-label">Год начала обучения</label>
                                <input type="number" class="form-control" id="startYear" name="startYear" min="1900" max="2100">
                            </div>
                            <div class="mb-3">
                                <label for="endYear" class="form-label">Год окончания обучения</label>
                                <input type="number" class="form-control" id="endYear" name="endYear" min="1900" max="2100">
                            </div>
                            <div class="mb-3">
                                <label for="medicalField" class="form-label">Выберите направление в медицине</label>
                                <select class="form-control" id="medicalField" name="medicalField">
                                    <?php foreach ($new_doctor_field_of_medicine as $field): ?>
                                        <option value="<?= htmlspecialchars($field['id_field']) ?>"><?= htmlspecialchars($field['name_of_field']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <h3>Повышение квалификации</h3>
                            <div class="mb-3">
                                <label for="qualif_improv_date" class="form-label">Дата проведения</label>
                                <input type="date" class="form-control" id="qualif_improv_date" name="qualif_improv_date">
                            </div>
                            <div class="mb-3">
                                <label for="qualif_improv_name" class="form-label">Название</label>
                                <input type="text" class="form-control" id="qualif_improv_name" name="qualif_improv_name">
                            </div>
                            <div class="mb-3">
                                <label for="qualif_improv_type" class="form-label">Тип</label>
                                <select class="form-control" id="qualif_improv_type" name="qualif_improv_type">
                                    <option value="Курс">Курс</option>
                                    <option value="Семинар">Семинар</option>
                                    <option value="Лекция">Лекция</option>
                                    <option value="Мастер-класс">Мастер-класс</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="qualif_improv_nameOrganization" class="form-label">Наименование организации</label>
                                <input type="text" class="form-control" id="qualif_improv_nameOrganization" name="qualif_improv_nameOrganization">
                            </div>
                            <div class="mb-3">
                                <label for="medicalField_qe" class="form-label">Направление в медицине</label>
                                <select class="form-control" id="medicalField_qe" name="medicalField_qe">
                                    <?php foreach ($new_doctor_field_of_medicine as $field): ?>
                                        <option value="<?= htmlspecialchars($field['id_field']) ?>"><?= htmlspecialchars($field['name_of_field']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-primary" id="saveDoctorButton">Добавить</button>
                    </div>
                </div>
            </div>
        </div>

<script>
    const fields = [
            { id: 'firstName', regex: /^[А-ЯЁ][а-яё]+$/, errorMessage: 'Имя должно начинаться с заглавной буквы.' },
            { id: 'lastName', regex: /^[А-ЯЁ][а-яё]+$/, errorMessage: 'Фамилия должна начинаться с заглавной буквы.' },
            { id: 'middleName', regex: /^[А-ЯЁ][а-яё]+$/, errorMessage: 'Отчество должно начинаться с заглавной буквы.' },
            { id: 'phoneNumber', regex: /^\+7 \d{3} \d{3}-\d{2}-\d{2}$/, errorMessage: 'Номер телефона должен быть в формате: +7 985 658-98-78.' },
            { id: 'doctorAdress', regex: /^г\. [А-ЯЁ][а-яё]+, ул\. [А-ЯЁ][а-яё]+, д\. \d+$/, errorMessage: 'Адрес должен быть в формате: г. Москва, ул. Ленина, д. 1.' },
            { id: 'experience', regex: /^\d+$/, errorMessage: 'Стаж работы должен быть числом, например 7.' }
        ];  
    
    //функция изменения фильтра выбора отделения
    document.getElementById('department').addEventListener('change', function () {
        var department_id = this.value;
        var polyclinicSelect = document.getElementById('polyclinic');
        
        // Очистить текущие опции поликлиник
        polyclinicSelect.innerHTML = '';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_polyclinics.php?id_department=' + department_id, true);
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                if (xhr.responseText) {
                    try {
                        var polyclinics = JSON.parse(xhr.responseText);
                        if (Array.isArray(polyclinics)) {
                            polyclinics.forEach(function (polyclinic) {
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
        xhr.onerror = function () {
            console.error('Запрос не выполнен');
        };
        xhr.send();
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        

        fields.forEach(field => {
            const input = document.getElementById(field.id);
            const errorDiv = document.getElementById(field.id + 'Error');

            input.addEventListener('input', function() {
                if (!field.regex.test(input.value.trim())) {
                    input.classList.add('is-invalid');
                    errorDiv.textContent = field.errorMessage;
                } else {
                    input.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            });
        });
    });

    document.getElementById('saveDoctorButton').addEventListener('click', function() {
        let isValid = true;

        fields.forEach(field => {
            const input = document.getElementById(field.id);
            const errorDiv = document.getElementById(field.id + 'Error');

            if (!field.regex.test(input.value.trim())) {
                input.classList.add('is-invalid');
                errorDiv.textContent = field.errorMessage;
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
                errorDiv.textContent = '';
            }
        });

        if (isValid) {
            const formData = new FormData(document.getElementById('newDoctorForm'));

            // Создаем объект XMLHttpRequest для AJAX-запроса
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_new_doctor.php', true); // Укажите путь к вашему PHP-скрипту
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Врач успешно добавлен!');
                        var modal = bootstrap.Modal.getInstance(document.getElementById('newDoctorModal'));
                        modal.hide();
                        updateDoctorsTable();
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                } else {
                    alert('Произошла ошибка при выполнении запроса.');
                }
            };
            xhr.onerror = function () {
                alert('Произошла ошибка при выполнении запроса.');
            };
            xhr.send(formData); // Отправляем данные формы
        }
    });

    function updateDoctorsTable() {
    var polyclinic_id = document.getElementById('polyclinic_id').value;
    var department_id = document.getElementById('department_id').value;
    var letters_range = document.getElementById('letters_range').value;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            document.getElementById('doctors_table').innerHTML = xhr.responseText;
        } else {
            console.error('Ошибка при обновлении таблицы');
        }
    };
    xhr.onerror = function () {
        console.error('Ошибка при обновлении таблицы');
    };
    xhr.send('ajax=1&polyclinic_id=' + encodeURIComponent(polyclinic_id) + 
             '&department_id=' + encodeURIComponent(department_id) + 
             '&letters_range=' + encodeURIComponent(letters_range));
}
</script>
