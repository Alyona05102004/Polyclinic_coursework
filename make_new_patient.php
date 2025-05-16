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

<div class="modal fade" id="newPatientModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newPatientModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-2 text-uppercase" id="newPatientModalLabel">Добавить нового пациента</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <form id="newPatientForm">
                            <h3>Личная информация</h3>
                            <div class="mb-3">
                                <label for="firstName_patient" class="form-label">Имя</label>
                                <input type="text" class="form-control" id="firstName_patient" name="firstName_patient" required>
                                <div class="invalid-feedback" id="firstName_patientError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="lastName_patient" class="form-label">Фамилия</label>
                                <input type="text" class="form-control" id="lastName_patient" name="lastName_patient" required>
                                <div class="invalid-feedback" id="lastName_patientError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="middleName_patient" class="form-label">Отчество</label>
                                <input type="text" class="form-control" id="middleName_patient" name="middleName_patient">
                                <div class="invalid-feedback" id="middleName_patientError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="birthDate_patient" class="form-label">Дата рождения</label>
                                <input type="date" class="form-control" id="birthDate_patient" name="birthDate_patient" required>
                            </div>
                            <div class="mb-3">
                                <label for="patientAdress" class="form-label">Адрес</label>
                                <input type="text" class="form-control" id="patientAdress" name="patientAdress" required>
                                <div class="invalid-feedback" id="patientAdressError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="policy_number" class="form-label">Номер полиса</label>
                                <input type="text" class="form-control" id="policy_number" name="policy_number" required>
                                <div class="invalid-feedback" id="policy_numberError"></div>
                            </div>
                            <div class="mb-3">
                                <label for="new_patient_gender" class="form-label">Пол</label>
                                <div id="new_patient_gender">
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" value="М" id="new_patientM" name="new_patient_gender">
                                    <label class="form-check-label" for="new_patientM">Мужской</label>
                                    </div>
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" value="Ж" id="new_patientW" name="new_patient_gender">
                                    <label class="form-check-label" for="new_patientW">Женский</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-primary" id="savePatientButton">Добавить</button>
                    </div>
                </div>
            </div>
        </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const modalButton = document.querySelector('[data-bs-target="#newPatientModal"]');
    if (modalButton) {
        modalButton.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('newPatientModal'));
        modal.show();
        });
    }
    });
    
    const fields_patient = [
            { id: 'firstName_patient', regex: /^[А-ЯЁ][а-яё]+$/, errorMessage: 'Имя должно начинаться с заглавной буквы.' },
            { id: 'lastName_patient', regex: /^[А-ЯЁ][а-яё]+(?:-[А-ЯЁ][а-яё]+)*$/, errorMessage: 'Фамилия должна начинаться с заглавной буквы.' },
            { id: 'middleName_patient', regex: /^[А-ЯЁ][а-яё]+$/, errorMessage: 'Отчество должно начинаться с заглавной буквы.' },
            { id: 'patientAdress', regex: /^г\. [А-ЯЁ][а-яё]+, ул\. [А-ЯЁ][а-яё]+, д\. \d+$/, errorMessage: 'Адрес должен быть в формате: г. Москва, ул. Ленина, д. 1.' },
            { id: 'policy_number', regex: /^\d{16}$/, errorMessage: 'Полис должен состоять из 16 цифр' }
        ];  
    


    document.addEventListener('DOMContentLoaded', function() {
        
        fields_patient.forEach(fields => {
            const input = document.getElementById(fields.id);
            const errorDiv = document.getElementById(fields.id + 'Error');

            input.addEventListener('input', function() {
                if (!fields.regex.test(input.value.trim())) {
                    input.classList.add('is-invalid');
                    errorDiv.textContent = fields.errorMessage;
                } else {
                    input.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            });
        });
    });

    document.getElementById('savePatientButton').addEventListener('click', function() {
        let isValid = true;
        const genderSelected = document.querySelector('input[name="new_patient_gender"]:checked');
        if (!genderSelected) {
            alert('Выберите пол!');
            isValid = false;
        }
        fields_patient.forEach(fields => {
            const input = document.getElementById(fields.id);
            const errorDiv = document.getElementById(fields.id + 'Error');

            if (!fields.regex.test(input.value.trim())) {
                input.classList.add('is-invalid');
                errorDiv.textContent = fields.errorMessage;
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
                errorDiv.textContent = '';
            }
        });

        const birthDateInput = document.getElementById('birthDate_patient');
        if (!birthDateInput.value) {
            alert('Укажите дату рождения!');
            isValid = false;
        } else {
            const birthDate = new Date(birthDateInput.value);
            const today = new Date();
            if (birthDate > today) {
                alert('Дата рождения не может быть в будущем!');
                isValid = false;
            }
        }

        if (isValid) {
            const formData = new FormData(document.getElementById('newPatientForm'));

            // Создаем объект XMLHttpRequest для AJAX-запроса
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_new_patient.php', true); // Укажите путь к вашему PHP-скрипту
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Пациент успешно добавлен!');
                        var modal = bootstrap.Modal.getInstance(document.getElementById('newPatientModal'));
                        modal.hide();
                        updatePatientsTable();
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

    function updatePatientsTable() {
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
</script>
