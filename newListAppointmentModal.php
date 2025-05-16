<div class="modal fade" id="newListAppointmentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newListAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="newListAppointmentModalBody">
            <div class="modal-header">
                <h5 class="modal-title">Открыть запись на прием</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="openAppointmentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="appointmentDateFrom" class="form-label">Дата c</label>
                            <input type="text" class="form-control" id="appointmentDateFrom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="appointmentDateTo" class="form-label">Дата по</label>
                            <input type="text" class="form-control" id="appointmentDateTo" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointmentListPolyclinic" class="form-label">Поликлиника</label>
                        <select class="form-select" id="appointmentListPolyclinic" required>
                            <option value="" selected disabled>Выберите поликлинику</option>
                            <?php foreach ($polyclinics as $polyclinic): ?>
                                <option value="<?= $polyclinic['id_polyclinic'] ?>"><?= htmlspecialchars($polyclinic['name_polyclinic']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointmentListDepartment" class="form-label">Отделение</label>
                        <select class="form-select" id="appointmentListDepartment" disabled required>
                            <option value="" selected disabled>Сначала выберите поликлинику</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointmentListDoctor" class="form-label">Врач</label>
                        <select class="form-select" id="appointmentListDoctor" disabled required>
                            <option value="" selected disabled>Сначала выберите отделение</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointmentCabinet" class="form-label">Кабинет</label>
                        <select class="form-select" id="appointmentCabinet" disabled required>
                            <option value="" selected disabled>Сначала выберите отделение</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="appointmentShift" class="form-label">Смена</label>
                            <select class="form-select" id="appointmentShift" required>
                                <option value="morning">Утренняя (08:00-12:00)</option>
                                <option value="day">Дневная (12:00-16:00)</option>
                                <option value="evening">Вечерняя (16:00-20:00)</option>
                                <option value="full">Полный день (08:00-20:00)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="appointmentInterval" class="form-label">Интервал приема (мин)</label>
                            <select class="form-select" id="appointmentInterval" required>
                                <option value="15">15 минут</option>
                                <option value="30">30 минут</option>
                                <option value="45">45 минут</option>
                                <option value="60">60 минут</option>
                            </select>
                        </div>
                    </div>
                </form>
                
                <div id="appointmentPreview" class="mt-4 d-none">
                    <h5>Предварительный просмотр записей</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>День недели</th>
                                    <th>Время начала</th>
                                    <th>Время окончания</th>
                                    <th>Кабинет</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentPreviewBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-info" id="previewAppointmentsBtn" onclick="previewAppointments()">Предпросмотр</button>
                <button type="button" class="btn btn-primary d-none" id="saveAppointmentsBtn" onclick="saveAppointments()">Сохранить записи</button>
            </div>
        </div>
    </div>
</div>

    <script>
        // Обработчик изменения поликлиники
        document.getElementById('appointmentListPolyclinic').addEventListener('change', function() {
            const polyclinicId = this.value;
            const departmentSelect = document.getElementById('appointmentListDepartment');
            
            if (polyclinicId) {
                // AJAX запрос для получения отделений
                fetch('get_departments.php?id_polyclinic=' + polyclinicId)
                    .then(response => response.json())
                    .then(data => {
                        departmentSelect.innerHTML = '<option value="" selected disabled>Выберите отделение</option>';
                        data.forEach(department => {
                            departmentSelect.innerHTML += `<option value="${department.id_department}">${department.name_department}</option>`;
                        });
                        departmentSelect.disabled = false;
                    });
            } else {
                departmentSelect.innerHTML = '<option value="" selected disabled>Сначала выберите поликлинику</option>';
                departmentSelect.disabled = true;
                document.getElementById('appointmentListDoctor').disabled = true;
                document.getElementById('appointmentCabinet').disabled = true;
            }
        });

        // Обработчик изменения отделения
        document.getElementById('appointmentListDepartment').addEventListener('change', function() {
            const departmentId = this.value;
            const doctorSelect = document.getElementById('appointmentListDoctor');
            const cabinetSelect = document.getElementById('appointmentCabinet');
            
            if (departmentId) {
                // AJAX запрос для получения врачей
                fetch('get_doctors_philter.php?id_department=' + departmentId)
                    .then(response => response.json())
                    .then(data => {
                        doctorSelect.innerHTML = '<option value="" selected disabled>Выберите врача</option>';
                        data.forEach(doctor => {
                            doctorSelect.innerHTML += `<option value="${doctor.id_doctor}">${doctor.full_name} (${doctor.post})</option>`;
                        });
                        doctorSelect.disabled = false;
                    });
                
                // AJAX запрос для получения кабинетов
                fetch('get_cabinet.php?id_department=' + departmentId)
                    .then(response => response.json())
                    .then(data => {
                        cabinetSelect.innerHTML = '<option value="" selected disabled>Выберите кабинет</option>';
                        data.forEach(cabinet => {
                            cabinetSelect.innerHTML += `<option value="${cabinet.id_cabinet}">${cabinet.number_of_cabinet}</option>`;
                        });
                        cabinetSelect.disabled = false;
                    });
            } else {
                doctorSelect.innerHTML = '<option value="" selected disabled>Сначала выберите отделение</option>';
                cabinetSelect.innerHTML = '<option value="" selected disabled>Сначала выберите отделение</option>';
                doctorSelect.disabled = true;
                cabinetSelect.disabled = true;
            }
        });

        // Функция предпросмотра записей
        function previewAppointments() {
            // Получаем выбранные значения
        const dateFrom = document.getElementById('appointmentDateFrom').value;
        const dateTo = document.getElementById('appointmentDateTo').value;
        const doctorId = document.getElementById('appointmentListDoctor').value;
        const cabinetId = document.getElementById('appointmentCabinet').value;
        const shift = document.getElementById('appointmentShift').value;
        const interval = parseInt(document.getElementById('appointmentInterval').value);
        
        // Проверяем, что все поля заполнены
        if (!dateFrom || !dateTo || !doctorId || !cabinetId) {
            alert('Пожалуйста, заполните все обязательные поля');
            return;
        }

        // Показываем лоадер
        const previewBtn = document.getElementById('previewAppointmentsBtn');
        previewBtn.disabled = true;
        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Загрузка...';

        // Определяем часы работы для выбранной смены
        let shiftHours;
        switch(shift) {
            case 'morning':
                shiftHours = { start: 8, end: 12 }; // 8:00-12:00
                break;
            case 'day':
                shiftHours = { start: 12, end: 16 }; // 12:00-16:00
                break;
            case 'evening':
                shiftHours = { start: 16, end: 20 }; // 16:00-20:00
                break;
            case 'full':
                shiftHours = { start: 8, end: 20 }; // 8:00-20:00
                break;
            default:
                shiftHours = { start: 8, end: 12 };
        }

        // Создаем данные для отправки
        const data = {
            dateFrom: dateFrom,
            dateTo: dateTo,
            doctorId: doctorId,
            cabinetId: cabinetId,
            shiftHours: shiftHours,
            interval: interval,
            polyclinicId: document.getElementById('appointmentListPolyclinic').value
        };

        // Отправляем запрос на сервер для проверки и создания предпросмотра
        fetch('preview_appointments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            previewBtn.disabled = false;
            previewBtn.innerHTML = 'Предпросмотр';
            
            if (data.error) {
                alert(data.error);
                return;
            }

            // Отображаем предпросмотр записей
            const previewBody = document.getElementById('appointmentPreviewBody');
            previewBody.innerHTML = '';
            
            data.appointments.forEach(appointment => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${appointment.date}</td>
                    <td>${appointment.dayOfWeek}</td>
                    <td>${appointment.timeStart}</td>
                    <td>${appointment.timeEnd}</td>
                    <td>${appointment.cabinetNumber}</td>
                `;
                previewBody.appendChild(row);
            });

            // Показываем кнопку сохранения и таблицу предпросмотра
            document.getElementById('saveAppointmentsBtn').classList.remove('d-none');
            document.getElementById('appointmentPreview').classList.remove('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            previewBtn.disabled = false;
            previewBtn.innerHTML = 'Предпросмотр';
            alert('Произошла ошибка при создании предпросмотра');
        });
    }

        // Функция сохранения записей
    async function saveAppointments() {
        const saveBtn = document.getElementById('saveAppointmentsBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Сохранение...';

        try {
            // Собираем данные из формы
            const doctorId = document.getElementById('appointmentListDoctor').value;
            const cabinetId = document.getElementById('appointmentCabinet').value;
            
            if (!doctorId || !cabinetId) {
                throw new Error('Не выбран врач или кабинет');
            }

            // Собираем данные из предпросмотра (исправленная версия)
            const appointments = [];
            const rows = document.querySelectorAll('#appointmentPreviewBody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 5) {
                    appointments.push({
                        date: cells[0].textContent.trim(),
                        timeStart: cells[2].textContent.trim(),
                        timeEnd: cells[3].textContent.trim(),
                        cabinetNumber: cells[4].textContent.trim()
                    });
                }
            });

           

            if (appointments.length === 0) {
                throw new Error('Нет записей для сохранения');
            }

            const response = await fetch('save_appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointments: appointments,
                    doctorId: doctorId,
                    cabinetId: cabinetId
                })
            });

            const result = await response.json();

            if (result.error) {
                throw new Error(result.error);
            }

            alert(`Успешно создано ${result.created} записей`);
            
            // Закрываем модальное окно и обновляем страницу
            const modal = bootstrap.Modal.getInstance(document.getElementById('newListAppointmentModal'));
            if (modal) {
                modal.hide();
            }
            

        } catch (error) {
            console.error('Ошибка сохранения:', error);
            alert('Ошибка сохранения: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Сохранить записи';
        }
    }
    
    $(document).ready(function(){
        
        $('#appointmentDateFrom').inputmask('9999-99-99', {
            placeholder: 'гггг-мм-дд',
            clearIncomplete: true
        });
        $('#appointmentDateTo').inputmask('9999-99-99', {
            placeholder: 'гггг-мм-дд',
            clearIncomplete: true
        });
    });
    function validateDate(dateString) {
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateString)) return false;
        
        const parts = dateString.split('-');
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10);
        const day = parseInt(parts[2], 10);
        
        // Проверка корректности даты
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && 
               date.getMonth() === month - 1 && 
               date.getDate() === day;
    }

    // Обработчик для проверки даты при потере фокуса
    $('#appointmentDateFrom, #appointmentDateTo').on('blur', function() {
        const input = $(this);
        if (!validateDate(input.val())) {
            input.addClass('is-invalid');
            input.after('<div class="invalid-feedback">Пожалуйста, введите корректную дату в формате гггг-мм-дд</div>');
        } else {
            input.removeClass('is-invalid');
            input.next('.invalid-feedback').remove();
        }
    });


    
</script>