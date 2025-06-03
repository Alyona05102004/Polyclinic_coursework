-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 03 2025 г., 17:35
-- Версия сервера: 5.6.51
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `polyclinic`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`root`@`%` PROCEDURE `cabinet_philter` (IN `department_id` INT)   BEGIN
    IF department_id IS NOT NULL THEN
        SET @sql_cabinet = CONCAT('SELECT id_cabinet, number_of_cabinet FROM cabinet WHERE id_department = ', department_id);
    ELSE
        SET @sql_cabinet = 'SELECT id_cabinet, number_of_cabinet FROM cabinet'; 
    END IF;


    PREPARE stmt FROM @sql_cabinet;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `checkActiveAppointment` (IN `patient_id` INT, IN `doctor_id` INT, IN `appointment_id` INT)   BEGIN
 SELECT COUNT(*) AS count 
        FROM appointment 
        JOIN staff ON appointment.id_doctor = staff.id_doctor 
        WHERE appointment.id_patient = patient_id 
        AND appointment.id_appointment != appointment_id
        AND (
            appointment.date > CURDATE() 
            OR (
                appointment.date = CURDATE() 
                AND EXISTS (
                    SELECT 1 
                    FROM operating_ranges 
                    WHERE operating_ranges.id_ranges = appointment.id_ranges 
                    AND operating_ranges.range_start > CURTIME()
                )
            )
        ) 
        AND staff.id_department = (SELECT id_department FROM staff WHERE id_doctor = doctor_id);
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `checkDoctor` (IN `appointment_id` INT)   BEGIN
SELECT id_doctor FROM appointment WHERE id_appointment = appointment_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `checkPatient` (IN `patient_id` INT)   BEGIN
SELECT id_patient FROM information_about_patient WHERE id_patient = patient_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `createMedicalHistory` (IN `appointment_id` INT)   BEGIN
    DECLARE patient_id INT;
    DECLARE doctor_id INT;
    DECLARE id_medical_history INT;
    SELECT id_patient, id_medical_history, id_doctor INTO patient_id, id_medical_history, doctor_id
    FROM appointment WHERE id_appointment = appointment_id;
    IF id_medical_history IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'История болезни уже создана';
    END IF;
    IF doctor_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Врач не существует';
    END IF;
    IF patient_id > 0 THEN
        IF NOT EXISTS (SELECT 1 FROM information_about_patient WHERE id_patient = patient_id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Пациент не существует';
        END IF;
    END IF;
    INSERT INTO medical_history (complaints, id_disease) VALUES ('', NULL);
    SET id_medical_history = LAST_INSERT_ID();
    UPDATE appointment SET id_medical_history = id_medical_history WHERE id_appointment = appointment_id;
    SELECT id_medical_history AS history_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `delete_doctor` (IN `doctor_id` INT)   BEGIN
DELETE FROM connection_qualif_improve WHERE id_doctors=doctor_id;
DELETE FROM connection_education WHERE id_doctor=doctor_id;
DELETE FROM staff WHERE id_doctor=doctor_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `delete_education` (IN `education_id` INT)   BEGIN
DELETE FROM connection_education WHERE id_education=education_id;
DELETE FROM education WHERE id_education=education_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `delete_qualification` (IN `qualif_id` INT)   BEGIN
DELETE FROM connection_qualif_improve WHERE id_qualif_improve=qualif_id;
DELETE FROM qualification_improvement WHERE id_qualif_improv=qualif_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `department_philter` (IN `polyclinic_id` INT)   BEGIN
    IF polyclinic_id = 0 THEN
        SET @sql_statement = 'SELECT department.id_department, department.name_department FROM department';
    ELSE
        SET @sql_statement = CONCAT(
            'SELECT department.id_department, department.name_department FROM department ',
            'JOIN connection ON connection.id_department = department.id_department ',
            'WHERE connection.id_polyclinic = ', polyclinic_id
        );
    END IF;

    PREPARE stmt FROM @sql_statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `doctor_philter` (IN `department_id` INT)   BEGIN
    IF department_id = 0 THEN
        SET @sql_doctor = 'SELECT staff.id_doctor, staff.full_name FROM staff';
    ELSE
        SET @sql_doctor = CONCAT(
            'SELECT staff.id_doctor, staff.full_name, staff.post FROM staff WHERE staff.id_department =', department_id
        );
    END IF;

    PREPARE stmt FROM @sql_doctor;
    EXECUTE stmt;
end$$

CREATE DEFINER=`root`@`%` PROCEDURE `getreportsTable` (IN `city` VARCHAR(255), IN `gender` VARCHAR(255), IN `id_field` INT, IN `age_partition` INT, IN `date_start` DATE, IN `date_end` DATE, IN `min_kol` INT, IN `group_1` INT, IN `group_2` INT, IN `group_3` INT)   BEGIN 
	SET @age_partition=age_partition;
    SET @podzapros = CONCAT(
    'SELECT CONCAT(FLOOR(DATEDIFF(CURDATE(), information_about_patient.birthday) / 365.25 / ', @age_partition, ') * ', @age_partition, ', ''-'', FLOOR(DATEDIFF(CURDATE(), information_about_patient.birthday) / 365.25 / ', @age_partition, ') * ', @age_partition, ' + ', @age_partition, ' - 1) AS age_group,
    disease.name_of_disease,
    COUNT(appointment.id_appointment) AS disease_kol, YEAR(appointment.date) as appointment_year, MONTH(appointment.date) as appointment_month,
   	information_about_patient.gender AS gender,
    YEAR(information_about_patient.birthday) AS birth_year,
    TRIM(
            SUBSTRING(
                information_about_patient.address,
                LOCATE(''г.'', information_about_patient.address) + 2,
                CASE
                    WHEN LOCATE('','', information_about_patient.address) > 0 
                    THEN LOCATE('','', information_about_patient.address) - LOCATE(''г.'', information_about_patient.address) - 2
                    ELSE LENGTH(information_about_patient.address) - LOCATE(''г.'', information_about_patient.address) - 1
                END
            )
        ) AS city
    FROM disease
    JOIN medical_history ON medical_history.id_disease = disease.id_disease
    JOIN appointment ON appointment.id_medical_history = medical_history.id_history
    JOIN information_about_patient ON appointment.id_patient = information_about_patient.id_patient
    JOIN field_of_medicine ON disease.id_field = field_of_medicine.id_field
    WHERE 1=1'
);
    
    IF city IS NOT NULL AND city != 'all' THEN
        SET @podzapros = CONCAT(@podzapros, ' AND information_about_patient.address LIKE "%', city, '%"'); 
    END IF;
    
    IF gender IS NOT NULL AND gender != 'all' THEN
        SET @podzapros = CONCAT(@podzapros, ' AND information_about_patient.gender = "', gender, '"'); 
    END IF;
    
    IF id_field IS NOT NULL AND id_field != 0 THEN
        SET @podzapros = CONCAT(@podzapros, ' AND disease.id_field = ', id_field); 
    END IF;
    
    IF date_start IS NOT NULL THEN
        SET @podzapros = CONCAT(@podzapros, ' AND (appointment.date >= "', date_start, '" OR appointment.date LIKE "%', date_start, '%")'); 
    END IF;
    
    IF date_end IS NOT NULL THEN
        SET @podzapros = CONCAT(@podzapros, ' AND (appointment.date <= "', date_end, '" OR appointment.date LIKE "%', date_end, '%")'); 
    END IF;
    
    SET @podzapros = CONCAT(@podzapros, ' GROUP BY age_group, disease.name_of_disease');
    
    IF min_kol IS NOT NULL AND min_kol != 0 THEN
        SET @podzapros = CONCAT(@podzapros, ' HAVING disease_kol >= ', min_kol);
    END IF;
    
    SET @sql = CONCAT('SELECT age_group, name_of_disease, SUM(disease_kol) AS total_appointments, appointment_year, appointment_month, 
                      (SUM(disease_kol) / (SELECT SUM(disease_kol) FROM (', @podzapros, ') AS t)) * 100 AS procent, gender, city, birth_year
                      FROM (', @podzapros, ') AS PODZAPROS');
    
    IF group_1 IS NOT NULL AND group_1 = 1 THEN
    	SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease, appointment_year');
    ELSEIF group_1 IS NOT NULL AND group_1 = 2 THEN
    	SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease, appointment_month');    
    ELSEIF group_1 IS NOT NULL AND group_1 = 3 THEN
    	SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease, city');
    ELSEIF group_1 IS NOT NULL AND group_1 = 4 THEN
    	SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease, gender');
    ELSEIF group_1 IS NOT NULL AND group_1 = 5 THEN
    	SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease, birth_year');
    ELSE SET @sql = CONCAT(@sql, ' GROUP BY name_of_disease');
    END IF;
    
    
    IF group_1 IS NOT NULL AND group_1 != 0 THEN
        IF group_2 IS NOT NULL AND group_2 = 1 THEN
            SET @sql = CONCAT(@sql, ', appointment_year');
        ELSEIF group_2 IS NOT NULL AND group_2 = 2 THEN
            SET @sql = CONCAT(@sql, ', appointment_month');    
        ELSEIF group_2 IS NOT NULL AND group_2 = 3 THEN
            SET @sql = CONCAT(@sql, ', city');
        ELSEIF group_2 IS NOT NULL AND group_2 = 4 THEN
            SET @sql = CONCAT(@sql, ', gender');
        ELSEIF group_2 IS NOT NULL AND group_2 = 5 THEN
            SET @sql = CONCAT(@sql, ', birth_year');
        END IF;
    END IF;
    
    IF group_1 IS NOT NULL AND group_1 != 0 THEN
    	IF group_2 IS NOT NULL AND group_2 != 0 THEN
            IF group_3 IS NOT NULL AND group_3 = 1 THEN
                SET @sql = CONCAT(@sql, ', appointment_year');
            ELSEIF group_3 IS NOT NULL AND group_3 = 2 THEN
                SET @sql = CONCAT(@sql, ', appointment_month');    
            ELSEIF group_3 IS NOT NULL AND group_3 = 3 THEN
               SET @sql = CONCAT(@sql, ' city');
            ELSEIF group_3 IS NOT NULL AND group_3 = 4 THEN
                SET @sql = CONCAT(@sql, ', gender');
            ELSEIF group_3 IS NOT NULL AND group_3 = 5 THEN
                SET @sql = CONCAT(@sql, ', birth_year');
            END IF;
        END IF;
    END IF;   
    SET @sql = CONCAT(@sql, ', age_group ORDER BY total_appointments DESC');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;  
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_appointments_table` (IN `polyclinic_id` INT, IN `department_id` INT, IN `letters_range` VARCHAR(255), IN `doctor_id` INT, IN `date_start` DATE, IN `date_end` DATE, IN `status` VARCHAR(90))   BEGIN
    SET @sql_appointments = 'SELECT appointment.id_appointment, appointment.date, appointment.id_doctor, staff.full_name as doctorName, staff.post,
    appointment.id_ranges, operating_ranges.range_start, operating_ranges.range_end, appointment.id_patient, 
    information_about_patient.full_name as patientName, appointment.id_cabinet, cabinet.number_of_cabinet, appointment.id_referral, 
    appointment.id_medical_history, department.id_department, department.name_department, info_about_polyclinic.id_polyclinic, 
    info_about_polyclinic.name_polyclinic, info_about_polyclinic.address
    FROM appointment
    LEFT JOIN staff ON staff.id_doctor=appointment.id_doctor
    JOIN operating_ranges ON operating_ranges.id_ranges=appointment.id_ranges
    LEFT JOIN information_about_patient ON information_about_patient.id_patient=appointment.id_patient
    LEFT JOIN cabinet ON cabinet.id_cabinet=appointment.id_cabinet
    JOIN department ON cabinet.id_department=department.id_department
    JOIN connection ON connection.id_department=department.id_department
    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic=connection.id_polyclinic
    WHERE 1=1';

    IF polyclinic_id IS NOT NULL AND polyclinic_id != 0 THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND info_about_polyclinic.id_polyclinic = ', polyclinic_id); 
    END IF;
    
    IF department_id IS NOT NULL AND department_id != 0 THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND department.id_department = ', department_id); 
    END IF;
    
    IF letters_range IS NOT NULL AND letters_range != 'all' THEN
        SET @letters = SUBSTRING_INDEX(letters_range, '-', 1);
        SET @first_letter = TRIM(@letters);
        SET @last_letter = TRIM(SUBSTRING_INDEX(letters_range, '-', -1));
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND (information_about_patient.full_name BETWEEN "', @first_letter, '" AND "', @last_letter, '" OR information_about_patient.full_name LIKE "', @first_letter, '%" OR information_about_patient.full_name LIKE "', @last_letter, '%")');
    END IF;
    
    IF doctor_id IS NOT NULL AND doctor_id != 0 THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND appointment.id_doctor = ', doctor_id); 
    END IF;

    IF date_start IS NOT NULL AND date_end IS NOT NULL THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND (appointment.date BETWEEN "', date_start, '" AND "', date_end, '")'); 
    END IF;   
    
    IF status = 'busy' THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND appointment.id_doctor != 0 AND appointment.id_patient != 0'); 
    END IF;
    IF status = 'free' THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND (appointment.id_patient IS NULL)  AND appointment.id_doctor != 0'); 
    END IF;
    IF status = 'without_doctor' THEN
        SET @sql_appointments = CONCAT(@sql_appointments, ' AND appointment.id_doctor IS NULL'); 
    END IF;

    PREPARE stmt FROM @sql_appointments;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_cities` (IN `table_name` VARCHAR(255))   BEGIN
    SET @sql = CONCAT('SELECT DISTINCT
        TRIM(
            SUBSTRING(
                address,
                LOCATE(''г.'', address) + 2,
                CASE
                    WHEN LOCATE('','', address) > 0 THEN LOCATE('','', address) - LOCATE(''г.'', address) - 2
                    ELSE LENGTH(address) - LOCATE(''г.'', address) - 1
                END
            )
        ) AS city
        FROM ', table_name, '
        WHERE address LIKE ''г.%'';');

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_disease` ()   BEGIN
SELECT id_disease, name_of_disease FROM disease;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_doctor` (IN `doctor_id` INT)   BEGIN
    SET @doctor_data = CONCAT('SELECT DISTINCT staff.id_doctor, staff.full_name, staff.birthday, staff.post, staff.status, 
                               staff.address, staff.phone_number, staff.id_department, department.name_department, 
                               info_about_polyclinic.name_polyclinic, info_about_polyclinic.id_polyclinic 
                        FROM staff 
                        JOIN department ON department.id_department = staff.id_department
                        JOIN connection ON connection.id_department = department.id_department
                        JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic = connection.id_polyclinic
                        WHERE staff.id_doctor = ', doctor_id);
    PREPARE stmt FROM @doctor_data;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @education = CONCAT('SELECT education.id_education, education.work_experience, education.type_of_education,  
                             education.educational_institution, education.year_of_start, education.year_of_end,
                             education.id_field as ed_id_field, ed_field.name_of_field AS ed_name_of_field
                      FROM staff 
                      LEFT JOIN connection_education ON connection_education.id_doctor = staff.id_doctor
                      LEFT JOIN education ON education.id_education = connection_education.id_education
                      LEFT JOIN field_of_medicine as ed_field ON education.id_field = ed_field.id_field
                      WHERE staff.id_doctor = ', doctor_id);
    PREPARE stmt FROM @education;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @qualifications = CONCAT('SELECT qualification_improvement.id_qualif_improv, qualification_improvement.name as qe_name, 
                                  qualification_improvement.type as qe_type, qualification_improvement.name_of_organizator, 
                                  qualification_improvement.date, qualification_improvement.id_field as qe_id_field, 
                                  qe_field.name_of_field AS qe_name_of_field
                           FROM staff 
                           LEFT JOIN connection_qualif_improve ON connection_qualif_improve.id_doctors = staff.id_doctor
                           LEFT JOIN qualification_improvement ON connection_qualif_improve.id_qualif_improve = qualification_improvement.id_qualif_improv
                           LEFT JOIN field_of_medicine as qe_field ON qualification_improvement.id_field = qe_field.id_field
                           WHERE staff.id_doctor = ', doctor_id);
    PREPARE stmt FROM @qualifications;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_doctors_table` (IN `polyclinic_id` INT, IN `department_id` INT, IN `letters_range` VARCHAR(255))   BEGIN
    SET @sql_doctors = 'SELECT staff.id_doctor, staff.full_name, staff.birthday, staff.post, staff.status, staff.address, 
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
                    WHERE 1=1';

    IF polyclinic_id IS NOT NULL AND polyclinic_id!=0 THEN
        SET @sql_doctors = CONCAT(@sql_doctors, ' AND info_about_polyclinic.id_polyclinic = ', polyclinic_id); 
    END IF;

    IF department_id IS NOT NULL AND department_id!=0 THEN
        SET @sql_doctors = CONCAT(@sql_doctors, ' AND department.id_department = ', department_id); 
    END IF;

    IF letters_range IS NOT NULL AND letters_range != 'all' THEN
        SET @letters = SUBSTRING_INDEX(letters_range, '-', 1);
        SET @first_letter = TRIM(@letters);
        SET @last_letter = TRIM(SUBSTRING_INDEX(letters_range, '-', -1));
        SET @sql_doctors = CONCAT(@sql_doctors, " AND (staff.full_name BETWEEN '", @first_letter, "' AND '", @last_letter, "' OR staff.full_name LIKE '", @first_letter, "%' OR staff.full_name LIKE '", @last_letter, "%')");
    END IF;

    SET @sql_doctors = CONCAT(@sql_doctors, ' GROUP BY staff.id_doctor');

    PREPARE stmt FROM @sql_doctors;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_info_about_polyclinic_table` (IN `city` INT, IN `polyclinic_id` INT)   BEGIN
    SET @sql_info_polyclinics = 'SELECT 
    info_about_polyclinic.id_polyclinic, 
    info_about_polyclinic.name_polyclinic, 
    info_about_polyclinic.address,
    GROUP_CONCAT(
        CASE 
            WHEN day_of_week = 0 THEN ''Вс: выходной''
            WHEN day_of_week = 1 THEN CONCAT(''Пн: '', start_time, ''-'', end_time)
            WHEN day_of_week = 2 THEN CONCAT(''Вт: '', start_time, ''-'', end_time)
            WHEN day_of_week = 3 THEN CONCAT(''Ср: '', start_time, ''-'', end_time)
            WHEN day_of_week = 4 THEN CONCAT(''Чт: '', start_time, ''-'', end_time)
            WHEN day_of_week = 5 THEN CONCAT(''Пт: '', start_time, ''-'', end_time)
            WHEN day_of_week = 6 THEN CONCAT(''Сб: '', start_time, ''-'', end_time)
        END
    ) AS work_schedule
FROM 
    info_about_polyclinic 
JOIN 
    polyclinic_schedule ON polyclinic_schedule.polyclinic_id = info_about_polyclinic.id_polyclinic
    WHERE 1=1';

    IF polyclinic_id IS NOT NULL AND polyclinic_id != 0 THEN
        SET @sql_info_polyclinics = CONCAT(@sql_info_polyclinics, ' AND info_about_polyclinic.id_polyclinic = ', polyclinic_id); 
    END IF;

    IF city IS NOT NULL AND city != 'all' THEN
        SET @sql_info_polyclinics = CONCAT(@sql_info_polyclinics, ' AND info_about_polyclinic.address LIKE ''%', department_id, '%'''); 
    END IF;

    SET @sql_info_polyclinics = CONCAT(@sql_info_polyclinics, ' GROUP BY info_about_polyclinic.id_polyclinic;');

    PREPARE stmt FROM @sql_info_polyclinics;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_patient` (IN `patient_id` INT)   BEGIN
    SET @patient_data = CONCAT('SELECT * FROM `information_about_patient` WHERE id_patient = ', patient_id);
    PREPARE stmt FROM @patient_data;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @appointments = CONCAT('SELECT appointment.id_appointment, appointment.date, 
                        staff.full_name AS doctor_name, staff.post AS doctor_post, 
                        cabinet.number_of_cabinet,
                        operating_ranges.range_start, operating_ranges.range_end
                        FROM appointment 
                        JOIN staff ON staff.id_doctor = appointment.id_doctor
                        JOIN cabinet ON cabinet.id_cabinet = appointment.id_cabinet
                        JOIN operating_ranges ON operating_ranges.id_ranges = appointment.id_ranges
                        WHERE appointment.id_patient =', patient_id);
    PREPARE stmt FROM @appointments;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @referrals = CONCAT('SELECT referral.id_referral, referral.date_of_start, DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) AS date_of_end, referral.id_patient, referral.id_doctor, 
                    staff_doctor.full_name AS doctorName,staff_doctor.post AS DoctorPost, referral.refrerral_doctor, staff_referral.full_name AS referralDoctorName , staff_referral.post AS referralDoctorPost
                    FROM `referral`
                    JOIN staff AS staff_doctor ON staff_doctor.id_doctor = referral.id_doctor
                    JOIN staff AS staff_referral ON staff_referral.id_doctor = referral.refrerral_doctor
                    WHERE referral.id_patient = ', patient_id);
    PREPARE stmt FROM @referrals;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_patients_table` (IN `birthday_date` DATE, IN `curent_address` VARCHAR(255), IN `letters_range` VARCHAR(255), IN `last_date` DATE, IN `currentGender` VARCHAR(10))   BEGIN
    SET @sql_patients = 'SELECT DISTINCT information_about_patient.id_patient, information_about_patient.full_name, information_about_patient.birthday, information_about_patient.policy_number, information_about_patient.address, information_about_patient.gender 
    FROM `information_about_patient` 
    WHERE 1=1';

    IF birthday_date IS NOT NULL AND birthday_date != NULL THEN
        SET @sql_patients = CONCAT(@sql_patients, ' AND information_about_patient.birthday = "', birthday_date, '"'); 
    END IF;

    IF curent_address IS NOT NULL AND curent_address != 'all' THEN
        SET @normalized_address = curent_address; -- Нормализация должна быть выполнена в PHP
        SET @sql_patients = CONCAT(@sql_patients, ' AND (information_about_patient.address LIKE "%', @normalized_address, '%" 
                          OR information_about_patient.address LIKE "%ул. ', @normalized_address, '%"
                          OR information_about_patient.address LIKE "%улица ', @normalized_address, '%")'); 
    END IF;

    IF last_date IS NOT NULL AND last_date != NULL THEN
        SET @sql_patients = CONCAT(@sql_patients, ' AND EXISTS (
            SELECT 1 FROM appointment 
            WHERE appointment.id_patient = information_about_patient.id_patient
            AND appointment.date = "', last_date, '")'); 
    END IF;

    IF currentGender IS NOT NULL AND currentGender != 'all' THEN
        SET @sql_patients = CONCAT(@sql_patients, ' AND information_about_patient.gender = "', currentGender, '"'); 
    END IF;

    IF letters_range IS NOT NULL AND letters_range != 'all' THEN
        SET @letters = SUBSTRING_INDEX(letters_range, '-', 1);
        SET @first_letter = TRIM(@letters);
        SET @last_letter = TRIM(SUBSTRING_INDEX(letters_range, '-', -1));
        SET @sql_patients = CONCAT(@sql_patients, ' AND (information_about_patient.full_name BETWEEN "', @first_letter, '" AND "', @last_letter, '" OR information_about_patient.full_name LIKE "', @first_letter, '%" OR information_about_patient.full_name LIKE "', @last_letter, '%")');
    END IF;

    SET @sql_patients = CONCAT(@sql_patients, ' GROUP BY information_about_patient.id_patient');

    PREPARE stmt FROM @sql_patients;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_referral_table` (IN `polyclinic_id` INT, IN `department_id` INT, IN `doctor_id` INT, IN `date_start` DATE, IN `date_end` DATE, IN `status_ref` INT)   BEGIN
    SET @sql = 'SELECT 
        referral.id_referral, 
        referral.date_of_start, 
        DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) AS date_of_end, 
        information_about_patient.full_name, 
        staff_doctor.id_doctor AS id_doctor,
        staff_doctor.full_name AS doctor,
        staff_doctor.post AS doctor_post,
        staff_referral.id_doctor AS id_doctor_referral,
        staff_referral.full_name AS doctor_referral,
        staff_referral.post AS doctor_referral_post
    FROM referral
    JOIN information_about_patient ON information_about_patient.id_patient = referral.id_patient
    JOIN staff AS staff_doctor ON staff_doctor.id_doctor = referral.id_doctor
    JOIN staff AS staff_referral ON staff_referral.id_doctor = referral.refrerral_doctor
    JOIN department ON department.id_department = staff_referral.id_department
    JOIN connection ON connection.id_department = department.id_department
    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic = connection.id_polyclinic
    WHERE 1=1';

    IF polyclinic_id IS NOT NULL AND polyclinic_id != 0 THEN
        SET @sql = CONCAT(@sql, ' AND info_about_polyclinic.id_polyclinic = ', polyclinic_id); 
    END IF;
    
    IF department_id IS NOT NULL AND department_id != 0 THEN
        SET @sql = CONCAT(@sql, ' AND department.id_department = ', department_id); 
    END IF;
    
    IF doctor_id IS NOT NULL AND doctor_id != 0 THEN
        SET @sql = CONCAT(@sql, ' AND referral.refrerral_doctor = ', doctor_id); 
    END IF;

    IF date_start IS NOT NULL AND date_end IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND referral.date_of_start >= "', date_start, '" AND DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) <= "', date_end, '"'); 
    END IF;   
    
    IF status_ref = 1 THEN
        SET @sql = CONCAT(@sql, ' AND DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) > CURDATE()'); 
    END IF;
    IF status_ref = 2 THEN
        SET @sql = CONCAT(@sql, ' AND DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) <= CURDATE()'); 
    END IF;

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_street` (IN `table_name` VARCHAR(255))   BEGIN
    SET @sql = CONCAT('SELECT DISTINCT
    TRIM(
        SUBSTRING(
            address,
            LOCATE(\",\", address) + 1,
            CASE
                WHEN LOCATE(\", д.\", address) > 0 
                    THEN LOCATE(\", д.\", address) - LOCATE(\",\", address) - 1
                ELSE LENGTH(address) - LOCATE(\",\", address)
            END
        )
    ) AS street
    FROM `', table_name, '`
    WHERE address LIKE \"г.%\" AND address LIKE \"%,%\"');

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `insertAppointments` (IN `doctorId` INT, IN `cabinetId` INT, IN `appointmentTimeStart` VARCHAR(255), IN `appointmentTimeEnd` VARCHAR(255), IN `appointmentDate` DATE)   BEGIN
    DECLARE rangeId INT;
    SELECT id_ranges INTO rangeId
    FROM operating_ranges
    WHERE range_start = appointmentTimeStart AND range_end = appointmentTimeEnd;
    IF rangeId IS NULL THEN
        INSERT INTO operating_ranges (range_start, range_end)
        VALUES (appointmentTimeStart, appointmentTimeEnd);
        SET rangeId = LAST_INSERT_ID();
    END IF;
    INSERT INTO appointments (appointmentDate, doctorId, rangeId, cabinetId)
    VALUES (appointmentDate, doctorId, rangeId, cabinetId);
    SELECT 'Успешно создано записей' AS message;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `insert_Doctor` (IN `fullName` VARCHAR(255), IN `birthDate` DATE, IN `position` VARCHAR(90), IN `statusValue` INT, IN `doctorAdress` VARCHAR(255), IN `phoneNumber` VARCHAR(90), IN `id_department` INT, IN `experience` INT, IN `educationType` VARCHAR(20), IN `university` VARCHAR(255), IN `startYear` INT, IN `endYear` INT, IN `id_medicalField` INT, IN `qualif_improv_name` VARCHAR(180), IN `qualif_improv_type` VARCHAR(90), `qualif_improv_nameOrganization` VARCHAR(255), IN `qualif_improv_date` DATE, IN `id_medicalField_qe` INT)   BEGIN
	DECLARE lastDoctorId INT;
    DECLARE lastEducationId INT;
    DECLARE lastQeId INT;

    INSERT INTO `staff` (`full_name`, `birthday`, `post`, `status`, `address`, `phone_number`, `id_department`)
    VALUES (fullName, birthDate, position, statusValue, doctorAdress, phoneNumber, id_department);
    SET lastDoctorId = LAST_INSERT_ID();

    IF experience IS NOT NULL AND educationType IS NOT NULL AND university IS NOT NULL AND startYear IS NOT NULL AND endYear IS NOT NULL AND id_medicalField IS NOT NULL THEN
        INSERT INTO `education` (`work_experience`, `type_of_education`, `educational_institution`, `year_of_start`, `year_of_end`, `id_field`)
        VALUES (experience, educationType, university, startYear, endYear, id_medicalField);
        SET lastEducationId = LAST_INSERT_ID();
        INSERT INTO `connection_education` (`id_doctor`, `id_education`) VALUES (lastDoctorId, lastEducationId);
    END IF;

    IF qualif_improv_name IS NOT NULL AND qualif_improv_type IS NOT NULL AND qualif_improv_nameOrganization IS NOT NULL AND qualif_improv_date IS NOT NULL AND id_medicalField_qe IS NOT NULL THEN
        INSERT INTO `qualification_improvement` (`name`, `type`, `name_of_organizator`, `date`, `id_field`)
        VALUES (qualif_improv_name, qualif_improv_type, qualif_improv_nameOrganization, qualif_improv_date, id_medicalField_qe);
        SET lastQeId = LAST_INSERT_ID();
        INSERT INTO `connection_qualif_improve` (`id_doctors`, `id_qualif_improve`) VALUES (lastDoctorId, lastQeId);
    END IF;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `insert_education` (IN `work_experience` INT, IN `type` VARCHAR(20), IN `institution` VARCHAR(255), IN `start_year` INT(11), IN `end_year` INT(11), IN `field_id` INT)   BEGIN
INSERT INTO education (work_experience, type_of_education, educational_institution, year_of_start, year_of_end, id_field) 
VALUES (work_experience, type, institution, start_year, end_year, field_id);
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `insert_qualification` (IN `name` VARCHAR(180), IN `type` VARCHAR(90), IN `organizator` VARCHAR(255), IN `date` DATE, IN `field_id` INT)   BEGIN
INSERT INTO qualification_improvement (name, type, name_of_organizator, date, id_field) 
VALUES (name, type, organizator, date, field_id);
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `polyclinic_philter` ()  COMMENT 'Процедура для вывода всех поликлиник' BEGIN
	SELECT id_polyclinic, name_polyclinic FROM info_about_polyclinic;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `updateAppointment` (IN `doctor_id` INT, IN `patient_id` INT, IN `cabinet_id` INT, IN `appointment_id` INT)   BEGIN
UPDATE appointment SET id_doctor=doctor_id, id_patient=patient_id, id_cabinet=cabinet_id WHERE id_appointment=appointment_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `updateMedicalHistory_and_disease` (IN `p_complaints` VARCHAR(255), IN `p_symptoms` VARCHAR(255), IN `p_diagnosis` VARCHAR(255), IN `p_treatment` VARCHAR(255), IN `p_medications` VARCHAR(255), IN `p_field_id` INT, IN `p_disease_id` INT, IN `p_history_id` INT, IN `p_appointment_id` INT)   BEGIN
    DECLARE v_disease_id INT;
    DECLARE v_history_id INT;
    
    -- Initialize variables with input parameters
    SET v_disease_id = p_disease_id;
    SET v_history_id = p_history_id;
    
    -- Check if we're using existing disease or creating new one
    IF p_disease_id > 0 THEN 
        -- Verify disease exists
        IF NOT EXISTS (SELECT 1 FROM disease WHERE id_disease = p_disease_id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Указанная болезнь не существует';
        END IF;
    ELSE 
        -- Validate field_id when creating new disease
        IF p_field_id <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Не выбрана область медицины';
        END IF;
        
        -- Create new disease
        INSERT INTO disease (name_of_disease, symptoms, treatment_recommendations, medicament, id_field)
        VALUES (p_diagnosis, p_symptoms, p_treatment, p_medications, p_field_id);
        
        SET v_disease_id = LAST_INSERT_ID();
    END IF;

    -- Update or create medical history
    IF p_history_id != 0 THEN
        UPDATE medical_history SET 
            complaints = p_complaints, 
            id_disease = v_disease_id 
        WHERE id_history = p_history_id;
    ELSE
        INSERT INTO medical_history (complaints, id_disease) 
        VALUES (p_complaints, v_disease_id);
        
        SET v_history_id = LAST_INSERT_ID();
    END IF;

    -- Link to appointment
    UPDATE appointment SET id_medical_history = v_history_id 
    WHERE id_appointment = p_appointment_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `update_doctor` (IN `full_name` VARCHAR(255), IN `birthday` DATE, IN `phone_number` VARCHAR(90), IN `address` VARCHAR(255), IN `post` VARCHAR(90), IN `status` TINYINT(11), IN `id_department` INT, IN `doctor_id` INT)   BEGIN
UPDATE staff SET full_name=full_name, 	birthday=birthday, phone_number=phone_number, address=address, post=post, 	status=status, id_department=id_department WHERE id_doctor=doctor_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `update_education` (IN `work_experience` INT, IN `type` VARCHAR(20), IN `institution` VARCHAR(255), IN `start_year` INT(11), IN `end_year` INT(11), IN `field_id` INT, IN `education_id` INT)   BEGIN
UPDATE education SET work_experience=work_experience, type_of_education=type, educational_institution=institution, 
year_of_start=start_year, year_of_end=end_year, id_field=field_id WHERE id_education=education_id;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `update_qualification` (IN `name` VARCHAR(180), IN `type` VARCHAR(90), IN `organizator` VARCHAR(255), IN `date` DATE, IN `field_id` INT, IN `qualif_id` INT)   BEGIN
UPDATE qualification_improvement SET name=name, type=type, name_of_organizator=organizator, date=date, id_field=field_id WHERE id_qualif_improv=qualif_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `appointment`
--

CREATE TABLE `appointment` (
  `id_appointment` int(11) NOT NULL,
  `date` date NOT NULL,
  `id_doctor` int(11) DEFAULT NULL,
  `id_ranges` int(11) NOT NULL,
  `id_patient` int(11) DEFAULT NULL,
  `id_cabinet` int(11) NOT NULL,
  `id_referral` int(11) DEFAULT NULL,
  `id_medical_history` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `appointment`
--

INSERT INTO `appointment` (`id_appointment`, `date`, `id_doctor`, `id_ranges`, `id_patient`, `id_cabinet`, `id_referral`, `id_medical_history`) VALUES
(1, '2023-10-01', 1, 1, 1, 1, NULL, 28),
(2, '2023-10-02', 1, 2, 60, 1, NULL, 27),
(3, '2023-10-03', 9, 1, 61, 5, NULL, 3),
(4, '2023-10-04', 14, 5, 62, 7, NULL, 4),
(5, '2023-10-05', 17, 2, 63, 1, NULL, 5),
(6, '2023-10-06', 3, 10, 64, 1, NULL, 6),
(7, '2023-10-07', 4, 22, 65, 1, NULL, 47),
(8, '2023-10-08', 2, 13, 66, 2, NULL, 8),
(9, '2023-10-09', 29, 40, 67, 17, NULL, 9),
(10, '2023-10-10', 32, 20, 68, 19, NULL, 10),
(11, '2023-10-11', 37, 38, 1, 23, NULL, 11),
(12, '2023-10-12', 6, 33, 60, 3, NULL, 12),
(13, '2023-10-13', 9, 39, 61, 6, NULL, 13),
(14, '2023-10-14', 13, 15, 62, 8, NULL, 14),
(15, '2023-10-15', 4, 24, 63, 1, NULL, 15),
(16, '2023-10-16', 2, 13, 70, 2, NULL, 16),
(17, '2023-10-17', 30, 23, 88, 18, NULL, 17),
(18, '2023-10-18', 32, 18, 84, 20, NULL, 18),
(19, '2023-10-19', 2, 13, 79, 2, NULL, 26),
(20, '2023-10-20', 6, 37, 74, 4, NULL, 20),
(50, '2025-05-26', 9, 49, NULL, 5, NULL, NULL),
(51, '2025-05-26', 9, 50, NULL, 5, NULL, NULL),
(52, '2025-05-26', 9, 51, 70, 5, NULL, NULL),
(53, '2025-05-26', 9, 52, NULL, 5, NULL, NULL),
(54, '2025-05-27', 9, 49, NULL, 5, NULL, NULL),
(55, '2025-05-27', 9, 50, NULL, 5, NULL, NULL),
(56, '2025-05-27', 9, 51, NULL, 5, NULL, NULL),
(57, '2025-05-27', 9, 52, NULL, 5, NULL, NULL),
(58, '2025-05-28', 9, 49, NULL, 5, NULL, NULL),
(59, '2025-05-28', 9, 50, NULL, 5, NULL, NULL),
(60, '2025-05-28', 9, 51, NULL, 5, NULL, NULL),
(61, '2025-05-28', 9, 52, 78, 5, NULL, NULL),
(62, '2025-05-29', 9, 49, NULL, 5, NULL, NULL),
(63, '2025-05-29', 9, 50, NULL, 5, NULL, NULL),
(64, '2025-05-29', 9, 51, NULL, 5, NULL, NULL),
(65, '2025-05-29', 9, 52, NULL, 5, NULL, NULL),
(66, '2025-05-30', 9, 49, NULL, 5, NULL, NULL),
(67, '2025-05-30', 9, 50, NULL, 5, NULL, NULL),
(68, '2025-05-30', 9, 51, NULL, 5, NULL, NULL),
(69, '2025-05-30', 9, 52, NULL, 5, NULL, NULL),
(70, '2025-05-31', 9, 50, NULL, 5, NULL, NULL),
(71, '2025-05-31', 9, 51, NULL, 5, NULL, NULL),
(72, '2025-05-31', 9, 52, NULL, 5, NULL, NULL),
(73, '2025-05-26', 23, 1, NULL, 13, NULL, NULL),
(74, '2025-05-26', 23, 2, NULL, 13, NULL, NULL),
(75, '2025-05-26', 23, 3, 65, 13, NULL, NULL),
(76, '2025-05-26', 23, 4, NULL, 13, NULL, NULL),
(77, '2025-05-26', 23, 5, NULL, 13, NULL, NULL),
(78, '2025-05-26', 23, 6, NULL, 13, NULL, NULL),
(79, '2025-05-26', 23, 7, NULL, 13, NULL, NULL),
(80, '2025-05-26', 23, 8, 89, 13, NULL, 22),
(81, '2025-05-26', 23, 9, NULL, 13, NULL, NULL),
(82, '2025-05-26', 23, 10, NULL, 13, NULL, NULL),
(83, '2025-05-26', 23, 11, NULL, 13, NULL, NULL),
(84, '2025-05-26', 23, 12, NULL, 13, NULL, NULL),
(85, '2025-05-26', 23, 13, NULL, 13, NULL, NULL),
(86, '2025-05-26', 23, 14, NULL, 13, NULL, NULL),
(87, '2025-05-26', 23, 15, NULL, 13, NULL, NULL),
(88, '2025-05-26', 23, 16, NULL, 13, NULL, NULL),
(89, '2025-05-27', 23, 1, NULL, 13, NULL, NULL),
(90, '2025-05-27', 23, 2, NULL, 13, NULL, NULL),
(91, '2025-05-27', 23, 3, NULL, 13, NULL, NULL),
(92, '2025-05-27', 23, 4, NULL, 13, NULL, NULL),
(93, '2025-05-27', 23, 5, NULL, 13, NULL, NULL),
(94, '2025-05-27', 23, 6, NULL, 13, NULL, NULL),
(95, '2025-05-27', 23, 7, NULL, 13, NULL, NULL),
(96, '2025-05-27', 23, 8, NULL, 13, NULL, NULL),
(97, '2025-05-27', 23, 9, NULL, 13, NULL, NULL),
(98, '2025-05-27', 23, 10, NULL, 13, NULL, NULL),
(99, '2025-05-27', 23, 11, NULL, 13, NULL, NULL),
(100, '2025-05-27', 23, 12, NULL, 13, NULL, NULL),
(101, '2025-05-27', 23, 13, NULL, 13, NULL, NULL),
(102, '2025-05-27', 23, 14, NULL, 13, NULL, NULL),
(103, '2025-05-27', 23, 15, NULL, 13, NULL, NULL),
(104, '2025-05-27', 23, 16, NULL, 13, NULL, NULL),
(105, '2025-05-28', 23, 1, NULL, 13, NULL, NULL),
(106, '2025-05-28', 23, 2, NULL, 13, NULL, NULL),
(107, '2025-05-28', 23, 3, NULL, 13, NULL, NULL),
(108, '2025-05-28', 23, 4, NULL, 13, NULL, NULL),
(109, '2025-05-28', 23, 5, NULL, 13, NULL, NULL),
(110, '2025-05-28', 23, 6, NULL, 13, NULL, NULL),
(111, '2025-05-28', 23, 7, NULL, 13, NULL, NULL),
(112, '2025-05-28', 23, 8, NULL, 13, NULL, NULL),
(113, '2025-05-28', 23, 9, NULL, 13, NULL, NULL),
(114, '2025-05-28', 23, 10, NULL, 13, NULL, NULL),
(115, '2025-05-28', 23, 11, NULL, 13, NULL, NULL),
(116, '2025-05-28', 23, 12, NULL, 13, NULL, NULL),
(117, '2025-05-28', 23, 13, NULL, 13, NULL, NULL),
(118, '2025-05-28', 23, 14, NULL, 13, NULL, NULL),
(119, '2025-05-28', 23, 15, NULL, 13, NULL, NULL),
(120, '2025-05-28', 23, 16, NULL, 13, NULL, NULL),
(121, '2025-05-29', 23, 1, NULL, 13, NULL, NULL),
(122, '2025-05-29', 23, 2, NULL, 13, NULL, NULL),
(123, '2025-05-29', 23, 3, NULL, 13, NULL, NULL),
(124, '2025-05-29', 23, 4, NULL, 13, NULL, NULL),
(125, '2025-05-29', 23, 5, NULL, 13, NULL, NULL),
(126, '2025-05-29', 23, 6, NULL, 13, NULL, NULL),
(127, '2025-05-29', 23, 7, NULL, 13, NULL, NULL),
(128, '2025-05-29', 23, 8, NULL, 13, NULL, NULL),
(129, '2025-05-29', 23, 9, NULL, 13, NULL, NULL),
(130, '2025-05-29', 23, 10, NULL, 13, NULL, NULL),
(131, '2025-05-29', 23, 11, NULL, 13, NULL, NULL),
(132, '2025-05-29', 23, 12, NULL, 13, NULL, NULL),
(133, '2025-05-29', 23, 13, NULL, 13, NULL, NULL),
(134, '2025-05-29', 23, 14, NULL, 13, NULL, NULL),
(135, '2025-05-29', 23, 15, NULL, 13, NULL, NULL),
(136, '2025-05-29', 23, 16, NULL, 13, NULL, NULL),
(137, '2025-05-30', 23, 1, NULL, 13, NULL, NULL),
(138, '2025-05-30', 23, 2, NULL, 13, NULL, NULL),
(139, '2025-05-30', 23, 3, NULL, 13, NULL, NULL),
(140, '2025-05-30', 23, 4, NULL, 13, NULL, NULL),
(141, '2025-05-30', 23, 5, NULL, 13, NULL, NULL),
(142, '2025-05-30', 23, 6, NULL, 13, NULL, NULL),
(143, '2025-05-30', 23, 7, NULL, 13, NULL, NULL),
(144, '2025-05-30', 23, 8, NULL, 13, NULL, NULL),
(145, '2025-05-30', 23, 9, NULL, 13, NULL, NULL),
(146, '2025-05-30', 23, 10, NULL, 13, NULL, NULL),
(147, '2025-05-30', 23, 11, NULL, 13, NULL, NULL),
(148, '2025-05-30', 23, 12, NULL, 13, NULL, NULL),
(149, '2025-05-30', 23, 13, NULL, 13, NULL, NULL),
(150, '2025-05-30', 23, 14, NULL, 13, NULL, NULL),
(151, '2025-05-30', 23, 15, NULL, 13, NULL, NULL),
(152, '2025-05-30', 23, 16, NULL, 13, NULL, NULL),
(153, '2025-05-31', 23, 5, NULL, 13, NULL, NULL),
(154, '2025-05-31', 23, 6, NULL, 13, NULL, NULL),
(155, '2025-05-31', 23, 7, NULL, 13, NULL, NULL),
(156, '2025-05-31', 23, 8, NULL, 13, NULL, NULL),
(157, '2025-05-31', 23, 9, NULL, 13, NULL, NULL),
(158, '2025-05-31', 23, 10, NULL, 13, NULL, NULL),
(159, '2025-05-31', 23, 11, NULL, 13, NULL, NULL),
(160, '2025-05-31', 23, 12, NULL, 13, NULL, NULL),
(161, '2025-05-31', 23, 13, NULL, 13, NULL, NULL),
(162, '2025-05-31', 23, 14, NULL, 13, NULL, NULL),
(163, '2025-05-31', 23, 15, NULL, 13, NULL, NULL),
(164, '2025-05-31', 23, 16, NULL, 13, NULL, NULL),
(165, '2025-06-02', 29, 53, 90, 17, NULL, NULL),
(166, '2025-06-02', 29, 54, 70, 17, NULL, NULL),
(167, '2025-06-02', 29, 55, 71, 17, NULL, NULL),
(168, '2025-06-02', 29, 56, 87, 17, NULL, NULL),
(169, '2025-06-02', 29, 57, 67, 17, NULL, NULL),
(170, '2025-06-02', 29, 58, 70, 17, NULL, NULL),
(171, '2025-06-02', 29, 59, 69, 17, NULL, 60),
(172, '2025-06-02', 29, 60, NULL, 17, NULL, NULL),
(173, '2025-06-03', 29, 53, 79, 17, NULL, NULL),
(174, '2025-06-03', 29, 54, 69, 17, NULL, 53),
(175, '2025-06-03', 29, 55, NULL, 17, NULL, NULL),
(176, '2025-06-03', 29, 56, NULL, 17, NULL, NULL),
(177, '2025-06-03', 29, 57, NULL, 17, NULL, NULL),
(178, '2025-06-03', 29, 58, NULL, 17, NULL, NULL),
(179, '2025-06-03', 29, 59, NULL, 17, NULL, NULL),
(180, '2025-06-03', 29, 60, 89, 17, NULL, NULL),
(181, '2025-06-04', 29, 53, NULL, 17, NULL, NULL),
(182, '2025-06-04', 29, 54, 65, 17, NULL, NULL),
(183, '2025-06-04', 29, 55, NULL, 17, NULL, NULL),
(184, '2025-06-04', 29, 56, 71, 17, NULL, NULL),
(185, '2025-06-04', 29, 57, NULL, 17, NULL, NULL),
(186, '2025-06-04', 29, 58, NULL, 17, NULL, NULL),
(187, '2025-06-04', 29, 59, 88, 17, NULL, NULL),
(188, '2025-06-04', 29, 60, NULL, 17, NULL, NULL),
(189, '2025-06-05', 29, 53, NULL, 17, NULL, NULL),
(190, '2025-06-05', 29, 54, NULL, 17, NULL, NULL),
(191, '2025-06-05', 29, 55, NULL, 17, NULL, NULL),
(192, '2025-06-05', 29, 56, 64, 17, NULL, NULL),
(193, '2025-06-05', 29, 57, NULL, 17, NULL, NULL),
(194, '2025-06-05', 29, 58, NULL, 17, NULL, NULL),
(195, '2025-06-05', 29, 59, 63, 17, NULL, NULL),
(196, '2025-06-05', 29, 60, 62, 17, NULL, NULL),
(197, '2025-06-06', 29, 53, NULL, 17, NULL, NULL),
(198, '2025-06-06', 29, 54, NULL, 17, NULL, NULL),
(199, '2025-06-06', 29, 55, NULL, 17, NULL, NULL),
(200, '2025-06-06', 29, 56, NULL, 17, NULL, NULL),
(201, '2025-06-06', 29, 57, NULL, 17, NULL, NULL),
(202, '2025-06-06', 29, 58, NULL, 17, NULL, NULL),
(203, '2025-06-06', 29, 59, NULL, 17, NULL, NULL),
(204, '2025-06-06', 29, 60, NULL, 17, NULL, NULL),
(205, '2025-06-07', 29, 55, NULL, 17, NULL, NULL),
(206, '2025-06-07', 29, 56, NULL, 17, NULL, NULL),
(207, '2025-06-07', 29, 57, NULL, 17, NULL, NULL),
(208, '2025-06-07', 29, 58, NULL, 17, NULL, NULL),
(209, '2025-06-07', 29, 59, NULL, 17, NULL, NULL),
(210, '2025-06-07', 29, 60, NULL, 17, NULL, NULL),
(211, '2025-06-02', 36, 61, NULL, 23, NULL, NULL),
(212, '2025-06-02', 36, 62, NULL, 23, NULL, NULL),
(213, '2025-06-02', 36, 63, NULL, 23, NULL, NULL),
(214, '2025-06-02', 36, 64, NULL, 23, NULL, NULL),
(215, '2025-06-02', 36, 65, 63, 23, NULL, NULL),
(216, '2025-06-02', 36, 66, 70, 23, NULL, NULL),
(217, '2025-06-02', 36, 67, NULL, 23, NULL, NULL),
(218, '2025-06-02', 36, 68, NULL, 23, NULL, NULL),
(219, '2025-06-03', 36, 61, NULL, 23, NULL, NULL),
(220, '2025-06-03', 36, 62, 79, 23, NULL, NULL),
(221, '2025-06-03', 36, 63, NULL, 23, NULL, NULL),
(222, '2025-06-03', 36, 64, NULL, 23, NULL, NULL),
(223, '2025-06-03', 36, 65, NULL, 23, NULL, NULL),
(224, '2025-06-03', 36, 66, NULL, 23, NULL, NULL),
(225, '2025-06-03', 36, 67, NULL, 23, NULL, NULL),
(226, '2025-06-03', 36, 68, NULL, 23, NULL, NULL),
(227, '2025-06-04', 36, 61, NULL, 23, NULL, NULL),
(228, '2025-06-04', 36, 62, NULL, 23, NULL, NULL),
(229, '2025-06-04', 36, 63, NULL, 23, NULL, NULL),
(230, '2025-06-04', 36, 64, NULL, 23, NULL, NULL),
(231, '2025-06-04', 36, 65, NULL, 23, NULL, NULL),
(232, '2025-06-04', 36, 66, NULL, 23, NULL, NULL),
(233, '2025-06-04', 36, 67, NULL, 23, NULL, NULL),
(234, '2025-06-04', 36, 68, NULL, 23, NULL, NULL),
(235, '2025-06-05', 36, 61, NULL, 23, NULL, NULL),
(236, '2025-06-05', 36, 62, NULL, 23, NULL, NULL),
(237, '2025-06-05', 36, 63, NULL, 23, NULL, NULL),
(238, '2025-06-05', 36, 64, NULL, 23, NULL, NULL),
(239, '2025-06-05', 36, 65, NULL, 23, NULL, NULL),
(240, '2025-06-05', 36, 66, NULL, 23, NULL, NULL),
(241, '2025-06-05', 36, 67, NULL, 23, NULL, NULL),
(242, '2025-06-05', 36, 68, NULL, 23, NULL, NULL),
(243, '2025-06-06', 36, 61, NULL, 23, NULL, NULL),
(244, '2025-06-06', 36, 62, NULL, 23, NULL, NULL),
(245, '2025-06-06', 36, 63, NULL, 23, NULL, NULL),
(246, '2025-06-06', 36, 64, NULL, 23, NULL, NULL),
(247, '2025-06-06', 36, 65, NULL, 23, NULL, NULL),
(248, '2025-06-06', 36, 66, NULL, 23, NULL, NULL),
(249, '2025-06-06', 36, 67, NULL, 23, NULL, NULL),
(250, '2025-06-06', 36, 68, NULL, 23, NULL, NULL),
(251, '2025-06-07', 36, 61, NULL, 23, NULL, NULL),
(252, '2025-06-07', 36, 62, NULL, 23, NULL, NULL),
(253, '2025-06-07', 36, 63, NULL, 23, NULL, NULL),
(254, '2025-06-07', 36, 64, NULL, 23, NULL, NULL),
(255, '2025-07-01', 44, 69, NULL, 32, NULL, NULL),
(256, '2025-07-01', 44, 70, NULL, 32, NULL, NULL),
(257, '2025-07-01', 44, 71, NULL, 32, NULL, NULL),
(258, '2025-07-01', 44, 72, NULL, 32, NULL, NULL),
(259, '2025-07-01', 44, 73, NULL, 32, NULL, NULL),
(260, '2025-07-01', 44, 74, 1, 32, NULL, NULL),
(261, '2025-07-02', 44, 69, NULL, 32, NULL, NULL),
(262, '2025-07-02', 44, 70, NULL, 32, NULL, NULL),
(263, '2025-07-02', 44, 71, NULL, 32, NULL, NULL),
(264, '2025-07-02', 44, 72, NULL, 32, NULL, NULL),
(265, '2025-07-02', 44, 73, NULL, 32, NULL, NULL),
(266, '2025-07-02', 44, 74, NULL, 32, NULL, NULL),
(267, '2025-07-03', 44, 69, NULL, 32, NULL, NULL),
(268, '2025-07-03', 44, 70, NULL, 32, NULL, NULL),
(269, '2025-07-03', 44, 71, NULL, 32, NULL, NULL),
(270, '2025-07-03', 44, 72, NULL, 32, NULL, NULL),
(271, '2025-07-03', 44, 73, NULL, 32, NULL, NULL),
(272, '2025-07-03', 44, 74, NULL, 32, NULL, NULL),
(273, '2025-07-04', 44, 69, NULL, 32, NULL, NULL),
(274, '2025-07-04', 44, 70, NULL, 32, NULL, NULL),
(275, '2025-07-04', 44, 71, NULL, 32, NULL, NULL),
(276, '2025-07-04', 44, 72, NULL, 32, NULL, NULL),
(277, '2025-07-04', 44, 73, NULL, 32, NULL, NULL),
(278, '2025-07-04', 44, 74, NULL, 32, NULL, NULL),
(279, '2025-07-07', 44, 69, NULL, 32, NULL, NULL),
(280, '2025-07-07', 44, 70, NULL, 32, NULL, NULL),
(281, '2025-07-07', 44, 71, NULL, 32, NULL, NULL),
(282, '2025-07-07', 44, 72, NULL, 32, NULL, NULL),
(283, '2025-07-07', 44, 73, NULL, 32, NULL, NULL),
(284, '2025-07-07', 44, 74, NULL, 32, NULL, NULL),
(285, '2025-06-02', 7, 75, 60, 3, NULL, NULL),
(286, '2025-06-02', 7, 76, 74, 3, NULL, 42),
(287, '2025-06-02', 7, 77, NULL, 3, NULL, NULL),
(288, '2025-06-02', 7, 78, NULL, 3, NULL, NULL),
(289, '2025-06-02', 7, 79, 60, 3, NULL, 54),
(290, '2025-05-23', 14, 53, 68, 7, NULL, NULL),
(291, '2025-05-23', 14, 54, NULL, 7, NULL, NULL),
(292, '2025-05-23', 14, 55, NULL, 7, NULL, NULL),
(293, '2025-05-23', 14, 56, NULL, 7, NULL, NULL),
(294, '2025-05-23', 14, 57, 81, 7, NULL, 44),
(295, '2025-05-23', 14, 58, NULL, 7, NULL, NULL),
(296, '2025-05-23', 14, 59, 68, 7, NULL, NULL),
(297, '2025-05-23', 14, 60, 62, 7, NULL, NULL),
(298, '2025-07-14', 34, 49, NULL, 21, NULL, NULL),
(299, '2025-07-14', 34, 50, NULL, 21, NULL, NULL),
(300, '2025-07-14', 34, 51, 71, 21, NULL, NULL),
(301, '2025-07-14', 34, 52, NULL, 21, NULL, NULL),
(302, '2025-07-15', 34, 49, 60, 21, NULL, NULL),
(303, '2025-07-15', 34, 50, NULL, 21, NULL, NULL),
(304, '2025-07-15', 34, 51, NULL, 21, NULL, NULL),
(305, '2025-07-15', 34, 52, NULL, 21, NULL, NULL),
(306, '2025-07-16', 34, 49, NULL, 21, NULL, NULL),
(307, '2025-07-16', 34, 50, NULL, 21, NULL, NULL),
(308, '2025-07-16', 34, 51, NULL, 21, NULL, NULL),
(309, '2025-07-16', 34, 52, NULL, 21, NULL, NULL),
(310, '2025-07-17', 34, 49, NULL, 21, NULL, NULL),
(311, '2025-07-17', 34, 50, NULL, 21, NULL, NULL),
(312, '2025-07-17', 34, 51, NULL, 21, NULL, NULL),
(313, '2025-07-17', 34, 52, NULL, 21, NULL, NULL),
(314, '2025-07-18', 34, 49, NULL, 21, NULL, NULL),
(315, '2025-07-18', 34, 50, NULL, 21, NULL, NULL),
(316, '2025-07-18', 34, 51, NULL, 21, NULL, NULL),
(317, '2025-07-18', 34, 52, NULL, 21, NULL, NULL),
(318, '2025-04-01', 47, 80, NULL, 47, NULL, NULL),
(319, '2025-04-01', 47, 81, NULL, 47, NULL, NULL),
(320, '2025-04-01', 47, 82, NULL, 47, NULL, NULL),
(321, '2025-04-01', 47, 83, NULL, 47, NULL, NULL),
(322, '2024-03-02', 3, 55, NULL, 2, NULL, NULL),
(323, '2024-03-02', 3, 56, 84, 1, NULL, NULL),
(324, '2024-03-02', 3, 57, 69, 1, NULL, 30),
(325, '2024-03-02', 3, 58, 70, 1, NULL, 40),
(326, '2024-03-02', 3, 59, 69, 1, NULL, NULL),
(327, '2024-03-02', 3, 60, 86, 1, NULL, 32),
(328, '2024-03-02', 3, 61, 71, 1, NULL, 35),
(329, '2024-03-02', 3, 62, 74, 1, NULL, 56),
(330, '2024-03-02', 3, 63, 70, 1, NULL, 39),
(331, '2024-03-02', 3, 64, 67, 1, NULL, 58),
(332, '2024-03-02', 3, 65, NULL, 1, NULL, NULL),
(333, '2024-03-02', 3, 66, 60, 1, NULL, 37),
(334, '2025-05-26', 32, 49, 67, 19, NULL, NULL),
(335, '2025-05-26', 32, 50, NULL, 19, NULL, NULL),
(336, '2025-05-26', 32, 51, NULL, 19, NULL, NULL),
(337, '2025-05-26', 32, 52, NULL, 19, NULL, NULL),
(338, '2025-05-27', 32, 49, NULL, 19, NULL, NULL),
(339, '2025-05-27', 32, 50, NULL, 19, NULL, NULL),
(340, '2025-05-27', 32, 51, NULL, 19, NULL, NULL),
(341, '2025-05-27', 32, 52, NULL, 19, NULL, NULL),
(342, '2025-06-02', 9, 49, NULL, 5, NULL, NULL),
(343, '2025-06-02', 9, 50, NULL, 5, NULL, NULL),
(344, '2025-06-02', 9, 51, NULL, 5, NULL, NULL),
(345, '2025-06-02', 9, 52, NULL, 5, NULL, NULL),
(346, '2025-06-06', 27, 84, NULL, 15, NULL, NULL),
(347, '2025-06-06', 27, 85, NULL, 15, NULL, NULL),
(348, '2025-06-06', 27, 86, NULL, 15, NULL, NULL),
(349, '2025-06-06', 27, 87, NULL, 15, NULL, NULL),
(350, '2025-06-06', 27, 88, NULL, 15, NULL, NULL),
(351, '2025-06-07', 27, 84, NULL, 15, NULL, NULL),
(352, '2025-06-07', 27, 85, NULL, 15, NULL, NULL),
(353, '2025-06-07', 27, 86, NULL, 15, NULL, NULL),
(354, '2025-06-07', 27, 87, NULL, 15, NULL, NULL),
(355, '2025-06-09', 27, 84, NULL, 15, NULL, NULL),
(356, '2025-06-09', 27, 85, NULL, 15, NULL, NULL),
(357, '2025-06-09', 27, 86, NULL, 15, NULL, NULL),
(358, '2025-06-09', 27, 87, NULL, 15, NULL, NULL),
(359, '2025-06-09', 27, 88, NULL, 15, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `cabinet`
--

CREATE TABLE `cabinet` (
  `id_cabinet` int(11) NOT NULL,
  `number_of_cabinet` int(11) NOT NULL,
  `id_department` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cabinet`
--

INSERT INTO `cabinet` (`id_cabinet`, `number_of_cabinet`, `id_department`) VALUES
(1, 101, 1),
(2, 102, 1),
(3, 103, 2),
(4, 104, 2),
(5, 105, 3),
(6, 106, 3),
(7, 107, 4),
(8, 108, 4),
(9, 109, 5),
(10, 1010, 5),
(11, 1011, 6),
(12, 1012, 6),
(13, 1013, 7),
(14, 1014, 7),
(15, 1015, 8),
(16, 1016, 8),
(17, 1017, 9),
(18, 1018, 9),
(19, 1019, 10),
(20, 1020, 10),
(21, 1021, 11),
(22, 1022, 11),
(23, 101, 12),
(24, 102, 12),
(25, 103, 13),
(26, 104, 13),
(27, 105, 14),
(28, 106, 14),
(29, 107, 15),
(30, 108, 15),
(31, 109, 16),
(32, 1010, 16),
(33, 201, 17),
(34, 202, 17),
(35, 203, 18),
(36, 204, 18),
(37, 205, 19),
(38, 206, 19),
(39, 207, 20),
(40, 208, 20),
(41, 209, 21),
(42, 210, 21),
(43, 201, 22),
(44, 202, 22),
(45, 203, 23),
(46, 204, 23),
(47, 205, 24),
(48, 206, 24),
(49, 207, 25),
(50, 208, 25),
(51, 209, 26),
(52, 210, 26),
(53, 301, 27),
(54, 302, 27),
(55, 303, 28),
(56, 304, 28),
(57, 305, 29),
(58, 306, 30),
(59, 307, 30),
(60, 308, 31),
(61, 309, 31);

-- --------------------------------------------------------

--
-- Структура таблицы `connection`
--

CREATE TABLE `connection` (
  `id_connection` int(11) NOT NULL,
  `id_polyclinic` int(11) NOT NULL,
  `id_department` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `connection`
--

INSERT INTO `connection` (`id_connection`, `id_polyclinic`, `id_department`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 8),
(4, 1, 9),
(5, 1, 21),
(6, 1, 19),
(7, 1, 31),
(8, 1, 25),
(9, 1, 24),
(10, 2, 7),
(11, 2, 12),
(12, 2, 13),
(13, 2, 29),
(14, 2, 14),
(15, 2, 15),
(16, 2, 23),
(17, 2, 16),
(18, 2, 30),
(19, 2, 20),
(20, 2, 22),
(21, 3, 4),
(22, 3, 10),
(23, 4, 3),
(24, 5, 11),
(25, 5, 18),
(26, 5, 26),
(27, 1, 5),
(28, 5, 6),
(29, 1, 17),
(30, 3, 27),
(31, 1, 28);

-- --------------------------------------------------------

--
-- Структура таблицы `connection_education`
--

CREATE TABLE `connection_education` (
  `id_connection_education` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `id_education` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `connection_education`
--

INSERT INTO `connection_education` (`id_connection_education`, `id_doctor`, `id_education`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4),
(5, 5, 5),
(6, 6, 6),
(7, 7, 7),
(8, 8, 8),
(9, 9, 13),
(10, 10, 14),
(11, 11, 15),
(12, 12, 16),
(13, 13, 17),
(14, 14, 18),
(15, 15, 19),
(16, 16, 20),
(17, 17, 21),
(18, 18, 22),
(19, 19, 23),
(20, 20, 24),
(21, 21, 25),
(22, 22, 26),
(23, 23, 27),
(24, 24, 28),
(25, 25, 29),
(26, 26, 30),
(27, 27, 31),
(28, 28, 32),
(29, 29, 33),
(30, 30, 34),
(31, 31, 35),
(32, 32, 36),
(33, 33, 37),
(34, 34, 38),
(35, 35, 39),
(36, 36, 40),
(37, 37, 41),
(38, 38, 42),
(39, 39, 43),
(40, 40, 44),
(41, 41, 45),
(42, 42, 46),
(43, 43, 47),
(44, 44, 48),
(45, 45, 49),
(46, 46, 50),
(47, 47, 78),
(48, 48, 79),
(49, 49, 80),
(53, 5, 84),
(54, 50, 85);

-- --------------------------------------------------------

--
-- Структура таблицы `connection_qualif_improve`
--

CREATE TABLE `connection_qualif_improve` (
  `id_connection_qualif_improve` int(11) NOT NULL,
  `id_doctors` int(11) NOT NULL,
  `id_qualif_improve` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `connection_qualif_improve`
--

INSERT INTO `connection_qualif_improve` (`id_connection_qualif_improve`, `id_doctors`, `id_qualif_improve`) VALUES
(6, 5, 2),
(7, 7, 2),
(8, 9, 4),
(9, 10, 4),
(10, 11, 4),
(11, 12, 4),
(12, 13, 5),
(13, 14, 5),
(14, 15, 5),
(15, 16, 5),
(16, 20, 6),
(17, 21, 6),
(18, 22, 6),
(19, 23, 7),
(20, 24, 7),
(21, 25, 7),
(22, 26, 8),
(23, 27, 8),
(24, 28, 8),
(25, 30, 9),
(26, 31, 9),
(27, 32, 10),
(28, 33, 10),
(29, 34, 11),
(30, 35, 11),
(31, 36, 12),
(32, 37, 12),
(33, 38, 13),
(34, 39, 13),
(35, 40, 14),
(36, 41, 14),
(37, 42, 15),
(38, 43, 15),
(39, 44, 16),
(40, 46, 16),
(41, 46, 17),
(42, 8, 24),
(43, 29, 24),
(44, 6, 25),
(45, 17, 26),
(46, 18, 26),
(47, 19, 26),
(48, 47, 27),
(49, 48, 28),
(50, 49, 29),
(54, 5, 32),
(55, 1, 33),
(56, 50, 34);

-- --------------------------------------------------------

--
-- Структура таблицы `department`
--

CREATE TABLE `department` (
  `id_department` int(11) NOT NULL,
  `name_department` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `department`
--

INSERT INTO `department` (`id_department`, `name_department`) VALUES
(1, 'Терапевтическое отделение'),
(2, 'Хирургическое отделение'),
(3, 'Стоматологическое отделение'),
(4, 'Гинекологическое отделение'),
(5, 'Офтальмологическое отделение'),
(6, 'Отделение физиотерапии'),
(7, 'Отделение лабораторной диагностики'),
(8, 'Неврологическое отделение'),
(9, 'Кардиологическое отделение'),
(10, 'Эндокринологическое отделение'),
(11, 'Отделение реабилитации'),
(12, 'Отделение УЗИ'),
(13, 'Отделение рентгенологии'),
(14, 'Отделение аллергологии'),
(15, 'Отделение дерматологии'),
(16, 'Отделение гастроэнтерологии'),
(17, 'Отделение отоларингологии'),
(18, 'Отделение психиатрии'),
(19, 'Отделение травматологии'),
(20, 'Отделение онкологии'),
(21, 'Отделение урологии'),
(22, 'Отделение гематологии'),
(23, 'Отделение инфекционных болезней'),
(24, 'Отделение кардиохирургии'),
(25, 'Отделение нейрохирургии'),
(26, 'Отделение спортивной медицины'),
(27, 'Отделение медицинской генетики'),
(28, 'Отделение гастроэнтерологии'),
(29, 'Отделение эндоскопии'),
(30, 'Отделение пульмонологии'),
(31, 'Отделение ревматологии');

-- --------------------------------------------------------

--
-- Структура таблицы `disease`
--

CREATE TABLE `disease` (
  `id_disease` int(11) NOT NULL,
  `name_of_disease` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symptoms` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `treatment_recommendations` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medicament` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_field` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `disease`
--

INSERT INTO `disease` (`id_disease`, `name_of_disease`, `symptoms`, `treatment_recommendations`, `medicament`, `id_field`) VALUES
(1, 'Грипп', 'Лихорадка, кашель, головная боль, насморк', 'Покой, обильное питье, противовирусные препараты', 'Тамифлю', 1),
(2, 'Аппендицит', 'Боль в правом нижнем квадранте живота', 'Хирургическое вмешательство', 'Антибиотики', 2),
(3, 'Кариес', 'Боль в зубах, чувствительность', 'Пломбирование, удаление зуба', 'Обезболивающие', 4),
(4, 'Эндометриоз', 'Боль внизу живота, менструальные нарушения', 'Гормональная терапия, хирургия', 'Гормональные препараты', 5),
(5, 'Катаракта', 'Размытость зрения, трудности с ночным зрением', 'Хирургическое вмешательство', 'Офтальмологические капли', 6),
(6, 'Остеоартрит', 'Боль в суставах, скованность', 'Физиотерапия, противовоспалительные препараты', 'Ибупрофен', 7),
(7, 'Диабет', 'Чувство жажды, частое мочеиспускание', 'Контроль уровня сахара, инсулинотерапия', 'Инсулин', 8),
(8, 'Мигрень', 'Сильная головная боль, тошнота', 'Обезболивающие, отдых', 'Триптаны', 9),
(9, 'Гипертония', 'Головные боли, головокружение', 'Контроль давления, медикаментозная терапия', 'Антигипертензивные препараты', 10),
(10, 'Щитовидная недостаточность', 'Усталость, увеличение веса, медлительностью мышления и речи, зябкость, нарушение менструального цикла', 'Гормональная терапия', 'Левотироксин', 11),
(11, 'Ревматоидный артрит', 'Боль и отек суставов', 'Противовоспалительные препараты, физиотерапия', 'Метотрексат', 12),
(12, 'Пневмония', 'Кашель, одышка, высокая температура', 'Антибиотики, покой', 'Амоксициллин', 13),
(13, 'Аллергический ринит', 'Чихание, зуд в носу', 'Антигистаминные препараты', 'Лоратадин', 14),
(14, 'Экзема', 'Зуд, покраснение кожи', 'Увлажняющие кремы, кортикостероиды', 'Гидрокортизон', 15),
(15, 'Гастрит', 'Боль в животе, тошнота', 'Диета, антациды', 'Ранитидин', 16),
(16, 'Отит', 'Боль в ухе, снижение слуха', 'Антибиотики, обезболивающие', 'Амоксициллин', 18),
(17, 'Депрессия', 'Усталость, потеря интереса', 'Психотерапия, антидепрессанты', 'Сертралин', 18),
(18, 'Травма колена', 'Боль, отек', 'Физиотерапия, покой', 'Обезболивающие', 19),
(19, 'Рак легких', 'Кашель, одышка, потеря веса', 'Химиотерапия, радиотерапия', 'Цисплатин', 20),
(20, 'Простатит', 'Боль в области таза, частое мочеиспускание', 'Противовоспалительные препараты', 'Тамсулозин', 21),
(21, 'Лейкемия', 'Усталость, частые инфекции', 'Химиотерапия', 'Цитарабин', 22),
(22, 'Гепатит', 'Усталость, желтуха', 'Диета, противовирусные препараты', 'Софосбувир', 23),
(23, 'Аневризма', 'Боль в груди, головокружение', 'Хирургическое вмешательство', 'Антигипертензивные препараты', 24),
(24, 'Инфекционный мононуклеоз', 'Лихорадка, боль в горле, увеличение лимфоузлов', 'Покой, обильное питье', 'Обезболивающие', 25),
(25, 'Диагноз не установлен', 'Одышка, отеки', 'Медикаментозная терапия, изменение образа жизни', 'Диуретики', 10),
(26, 'Спортивная травма', 'Боль, отек, ограничение движений', 'Покой, физиотерапия', 'Обезболивающие', 27),
(27, 'Медицинская генетика', 'Наследственные заболевания', 'Генетическое консультирование', 'Специфические препараты', 28),
(28, 'Гастроэзофагеальная рефлюксная болезнь', 'Изжога, регургитация', 'Изменение диеты, антациды', 'Пантопразол', 29),
(29, 'Хроническая обструктивная болезнь легких', 'Одышка, хронический кашель', 'Бронходилататоры, кислородная терапия', 'Сальбутамол', 30),
(30, 'Ревматизм', 'Боль в суставах, усталость', 'Противовоспалительные препараты', 'Ибупрофен', 31),
(31, 'Астма', 'Одышка, свистящее дыхание, кашель, сжатие в груди', 'Избегать триггеров, ингаляционные кортикостероиды, бронходилататоры', 'Албутерол, Флутиказон', 15),
(32, 'Синдром поликистозных яичников', 'Нарушение менструального цикла, боль в области малого таза, лишний вес, изенение состояния колжи и волос, бесплодие', 'Снижение массы тела, улучшение психологического сотояния, физические нагрузки', 'Противозачаточные средства, Метформин, Кломифен, Прогестагены', 5),
(33, 'Хронический гастрит', 'Чувство тяжести в эпигастральной области во время или сразу после еды, отрыжка, изжога, расстройства стула, непереносимость определенной пищи, лекарств, боли на голодный желудок, тошнота, рвота', 'Регулярное питание без голоданий и перекусов, употреблять пищу небольшими порциями 5-6 раз в день. Ультразвуковая терапия, электрофорез, фонофорез. ', 'Дротаверин, домперидон', 1),
(34, 'Диагноз не установлен', '', '', '', 1),
(35, 'Диагноз не установлен', '', '', '', 1),
(36, 'Диагноз не установлен', '', '', '', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `education`
--

CREATE TABLE `education` (
  `id_education` int(11) NOT NULL,
  `work_experience` int(11) NOT NULL,
  `type_of_education` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `educational_institution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_of_start` int(11) NOT NULL,
  `year_of_end` int(11) NOT NULL,
  `id_field` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `education`
--

INSERT INTO `education` (`id_education`, `work_experience`, `type_of_education`, `educational_institution`, `year_of_start`, `year_of_end`, `id_field`) VALUES
(1, 5, 'Высшее', 'Медицинский университет', 2010, 2016, 1),
(2, 3, 'Высшее', 'Медицинская академия', 2012, 2018, 1),
(3, 4, 'Высшее', 'Медицинский университет', 2011, 2015, 1),
(4, 6, 'Высшее', 'Научный центр медицины', 2010, 2016, 1),
(5, 7, 'Высшее', 'Медицинский университет', 2010, 2017, 2),
(6, 4, 'Высшее', 'Научный центр медицины', 2013, 2017, 2),
(7, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 2),
(8, 3, 'Высшее', 'Медицинский университет', 2014, 2020, 2),
(9, 6, 'Высшее', 'Медицинская академия', 2011, 2017, 3),
(10, 2, 'Высшее', 'Медицинский университет', 2014, 2020, 3),
(11, 4, 'Высшее', 'Научный центр медицины', 2013, 2017, 3),
(12, 3, 'Высшее', 'Медицинская академия', 2015, 2021, 3),
(13, 8, 'Высшее', 'Медицинская академия', 2010, 2018, 4),
(14, 5, 'Высшее', 'Стоматологическая ассоциация', 2012, 2017, 4),
(15, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 4),
(16, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 4),
(17, 10, 'Высшее', 'Медицинский университет', 2009, 2019, 5),
(18, 3, 'Высшее', 'Медицинская академия', 2015, 2021, 5),
(19, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 5),
(20, 2, 'Высшее', 'Научный центр медицины', 2016, 2022, 5),
(21, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 6),
(22, 3, 'Высшее', 'Медицинский университет', 2014, 2020, 6),
(23, 4, 'Высшее', 'Научный центр медицины', 2013, 2017, 6),
(24, 6, 'Высшее', 'Медицинская академия', 2011, 2017, 7),
(25, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 7),
(26, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 7),
(27, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 8),
(28, 3, 'Высшее', 'Медицинский университет', 2014, 2020, 8),
(29, 4, 'Высшее', 'Научный центр медицины', 2013, 2017, 8),
(30, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 9),
(31, 6, 'Высшее', 'Научный центр медицины', 2011, 2017, 9),
(32, 5, 'Высшее', 'Медицинский университет', 2012, 2017, 9),
(33, 7, 'Высшее', 'Медицинская академия', 2010, 2017, 10),
(34, 3, 'Высшее', 'Медицинский университет', 2014, 2020, 10),
(35, 5, 'Высшее', 'Научный центр медицины', 2012, 2017, 10),
(36, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 11),
(37, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 11),
(38, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 12),
(39, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 12),
(40, 6, 'Высшее', 'Медицинская академия', 2011, 2017, 13),
(41, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 13),
(42, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 14),
(43, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 14),
(44, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 15),
(45, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 15),
(46, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 16),
(47, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 16),
(48, 6, 'Высшее', 'Медицинская академия', 2011, 2017, 17),
(49, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 17),
(50, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 18),
(51, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 18),
(52, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 19),
(53, 6, 'Высшее', 'Медицинский университет', 2011, 2017, 19),
(54, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 20),
(55, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 20),
(56, 7, 'Высшее', 'Медицинская академия', 2010, 2017, 21),
(57, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 21),
(58, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 22),
(59, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 22),
(60, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 23),
(61, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 23),
(62, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 24),
(63, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 24),
(64, 6, 'Высшее', 'Медицинская академия', 2011, 2017, 25),
(65, 4, 'Высшее', 'Медицинский университет', 2013, 2017, 25),
(66, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 26),
(67, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 26),
(68, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 27),
(69, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 27),
(70, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 28),
(71, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 28),
(72, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 29),
(73, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 29),
(74, 5, 'Высшее', 'Медицинская академия', 2011, 2016, 30),
(75, 3, 'Высшее', 'Научный центр медицины', 2014, 2020, 30),
(76, 4, 'Высшее', 'Медицинская академия', 2013, 2017, 31),
(77, 2, 'Высшее', 'Медицинский университет', 2015, 2021, 31),
(78, 6, 'Высшее', 'Московский медицинский университет', 2012, 2019, 25),
(79, 3, 'Высшее', 'Московский медицинский университет', 2015, 2022, 22),
(80, 9, 'Высшее', 'Московский медицинский университет', 2009, 2016, 22),
(82, 1, 'Высшее', 'Московский медицинский университет', 2017, 2023, 18),
(83, 0, 'Аспирантура', 'Научный центр медицины', 2017, 2020, 1),
(84, 5, 'Аспирантура', 'Научный медицинский центр', 2018, 2022, 2),
(85, 20, 'Высшее', 'Московский медицинский университет', 1998, 2005, 17);

-- --------------------------------------------------------

--
-- Структура таблицы `field_of_medicine`
--

CREATE TABLE `field_of_medicine` (
  `id_field` int(11) NOT NULL,
  `name_of_field` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `field_of_medicine`
--

INSERT INTO `field_of_medicine` (`id_field`, `name_of_field`) VALUES
(1, 'Терапия'),
(2, 'Хирургия'),
(3, 'Педиатрия'),
(4, 'Стоматология'),
(5, 'Гинекология'),
(6, 'Офтальмология'),
(7, 'Физиотерапия'),
(8, 'Лабораторная диагностика'),
(9, 'Неврология'),
(10, 'Кардиология'),
(11, 'Эндокринология'),
(12, 'Реабилитация'),
(13, 'Ультразвуковая диагностика'),
(14, 'Рентгенология'),
(15, 'Аллергология'),
(16, 'Дерматология'),
(17, 'Гастроэнтерология'),
(18, 'Отоларингология'),
(19, 'Психиатрия'),
(20, 'Травматология'),
(21, 'Онкология'),
(22, 'Урология'),
(23, 'Гематология'),
(24, 'Инфекционные болезни'),
(25, 'Кардиохирургия'),
(26, 'Нейрохирургия'),
(27, 'Спортивная медицина'),
(28, 'Медицинская генетика'),
(29, 'Эндоскопия'),
(30, 'Пульмонология'),
(31, 'Ревматология');

-- --------------------------------------------------------

--
-- Структура таблицы `information_about_patient`
--

CREATE TABLE `information_about_patient` (
  `id_patient` int(11) NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthday` date NOT NULL,
  `policy_number` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `information_about_patient`
--

INSERT INTO `information_about_patient` (`id_patient`, `full_name`, `birthday`, `policy_number`, `address`, `gender`) VALUES
(1, 'Иванов Иван Иванович', '1985-03-15', '1234567890123456', 'г. Москва, ул. Ленина, д. 1', 'М'),
(60, 'Петрова Анна Васильевна', '1990-07-22', '9877543210987654', 'г. Москва, просп. Невский, д. 5', 'Ж'),
(61, 'Сидоров Алексей Викторович', '1982-11-30', '4567890234567890', 'г. Москва, ул. Баумана, д. 10', 'М'),
(62, 'Кузнецова Мария Андреевна', '1995-01-10', '3216549878543210', 'г. Москва, ул. Малышева, д. 15', 'Ж'),
(63, 'Смирнов Дмитрий Александрович', '1988-05-25', '6543317890123456', 'г. Москва, ул. Красный проспект, д. 20', 'М'),
(64, 'Федорова Ольга Николаевна', '1992-09-12', '7894561234579890', 'г. Москва, ул. Горького, д. 30', 'Ж'),
(65, 'Морозов Сергей Владимирович', '1980-12-05', '1472583690123456', 'г. Москва, ул. Труда, д. 25', 'М'),
(66, 'Коваленко Светлана Петровна', '1987-04-18', '2583691470123456', 'г. Москва, ул. Садовая, д. 8', 'Ж'),
(67, 'Лебедев Андрей Юрьевич', '1993-06-20', '3692581470123456', 'г. Москва, ул. Ленина, д. 12', 'М'),
(68, 'Григорьева Наталья Васильевна', '1984-10-30', '1597534860123456', 'г. Москва, ул. Фрунзе, д. 18', 'Ж'),
(69, 'Соловьев Виктор Сергеевич', '1981-02-14', '7531594860123456', 'г. Москва, ул. Пушкина, д. 22', 'М'),
(70, 'Тихонов Ирина Валерьевна', '1994-08-09', '9517538520123456', 'г. Москва, ул. Советская, д. 14', 'Ж'),
(71, 'Зайцева Екатерина Дмитриевна', '1986-03-03', '8524569630123456', 'г. Москва, ул. Красная, д. 7', 'Ж'),
(72, 'Кузьмин Николай Сергеевич', '1989-11-11', '6547893210123456', 'г. Москва, ул. 50 лет Октября, д. 3', 'М'),
(73, 'Семенова Татьяна Алексеевна', '1991-05-15', '3217896540123456', 'г. Москва, ул. Ленина, д. 9', 'Ж'),
(74, 'Павлов Артем Владимирович', '1983-07-28', '1473692580123456', 'г. Москва, ул. Свободы, д. 11', 'М'),
(75, 'Кириллова Анастасия Сергеевна', '1990-12-01', '2581473690123456', 'г. Москва, ул. Сибирская, д. 4', 'Ж'),
(76, 'Сергеев Игорь Николаевич', '1982-09-19', '3691472580123456', 'г. Москва, ул. Капитана Воронина, д. 6', 'М'),
(77, 'Михайлова Валентина Петровна', '1985-01-25', '1592587530123456', 'г. Москва, ул. Ленина, д. 16', 'Ж'),
(78, 'Фролов Денис Александрович', '1988-06-30', '7532581590123456', 'г. Москва, ул. Черноморская, д. 2', 'М'),
(79, 'Костина Ольга Сергеевна', '1992-04-14', '9512584560123456', 'г. Москва, ул. Набережная, д. 5', 'Ж'),
(80, 'Савельев Алексей Викторович', '1980-08-22', '8521597530123456', 'г. Москва, ул. Мира, д. 19', 'М'),
(81, 'Ларина Марина Андреевна', '1989-10-10', '6541237890123456', 'г. Москва, ул. Дальневосточная, д. 8', 'Ж'),
(82, 'Громова Светлана Николаевна', '1984-02-17', '3214569870123456', 'г. Москва, ул. Светланская, д. 13', 'Ж'),
(83, 'Ковалев Сергей Юрьевич', '1991-03-29', '7891234560123456', 'г. Москва, ул. Ленина, д. 21', 'М'),
(84, 'Сидорова Анастасия Владимировна', '1986-11-05', '1472589630123456', 'г. Москва, ул. Комсомольская, д. 15', 'Ж'),
(85, 'Терентьев Илья Сергеевич', '1983-05-12', '2589631470123456', 'г. Москва, ул. Пушкина, д. 4', 'М'),
(86, 'Кузнецова Дарья Александровна', '1990-09-23', '3692587470123456', 'г. Москва, ул. Советская, д. 7', 'Ж'),
(87, 'Смирнов Роман Валерьевич', '1987-12-30', '1597532580123456', 'г. Москва, ул. Гагарина, д. 10', 'М'),
(88, 'Федотова Ольга Сергеевна', '1985-06-18', '7531598520123456', 'г. Москва, ул. Карла Маркса, д. 3', 'Ж'),
(89, 'Сёмина Мария Александровна', '2004-09-28', '1326457895623458', 'г. Москва, ул. Поддубная, д. 48', 'Ж'),
(90, 'Васечкин Игорь Петрович', '2002-05-02', '1235485125653323', 'г. Москва, ул. Мирная, д. 17', 'М');

-- --------------------------------------------------------

--
-- Структура таблицы `info_about_polyclinic`
--

CREATE TABLE `info_about_polyclinic` (
  `id_polyclinic` int(11) NOT NULL,
  `name_polyclinic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `info_about_polyclinic`
--

INSERT INTO `info_about_polyclinic` (`id_polyclinic`, `name_polyclinic`, `address`) VALUES
(1, 'Лечебное отделение поликлиники №1', 'г. Москва, ул. Ленина, д. 10, корп. 1'),
(2, 'Диагностическое отделение поликлиники №1', 'г. Москва, ул. Ленина, д.11'),
(3, 'Центр женского здоровья поликлиники №1', 'г. Москва, ул. Ленина д.10, корп. 2'),
(4, 'Стоматологический комплекс поликлиники №1', 'г. Москва, ул. Ленина, д.12'),
(5, 'Реабилитационный и неврологический центр поликлиники №1', 'г. Москва, ул. Ленина, д.13');

-- --------------------------------------------------------

--
-- Структура таблицы `medical_history`
--

CREATE TABLE `medical_history` (
  `id_history` int(11) NOT NULL,
  `complaints` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_disease` int(90) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `medical_history`
--

INSERT INTO `medical_history` (`id_history`, `complaints`, `id_disease`) VALUES
(1, 'Лихорадка, кашель', 1),
(2, 'Боль в правом нижнем квадранте живота', 2),
(3, 'Боль в зубах', 3),
(4, 'Боль внизу живота', 4),
(5, 'Размытость зрения', 5),
(6, 'Боль в суставах', 6),
(7, 'Чувство жажды', 7),
(8, 'Сильная головная боль', 8),
(9, 'Головные боли', 9),
(10, 'Усталость, увеличение веса', 10),
(11, 'Кашель, одышка', 12),
(12, 'Боль в суставах', 6),
(13, 'Боль в зубах', 3),
(14, 'Боль внизу живота', 4),
(15, 'Сильная головная боль', 8),
(16, 'Чувство жажды', 7),
(17, 'Головные боли', 9),
(18, 'Усталость, увеличение веса', 10),
(19, 'Лихорадка, кашель', 1),
(20, 'Боль в правом нижнем квадранте живота, лихорадка, тошнота и рвота, отсутсвие аппетита', 2),
(21, 'Лихорадка, кашель, насморк', 1),
(22, 'Cпецифические свистящие хрипы, покраснение и отёчность глаз, приступы сухого кашля', 32),
(25, 'Нарушение цикла, лишний вес, бесплодие', 22),
(26, 'Лихорадка, кашель', 1),
(27, 'Боль в правом нижнем квадранте живота, рвота', 2),
(28, 'Лихорадка, кашель, насморк', 1),
(30, 'Боли на голодный желудок, тошнота, рвота, частая изжога', 33),
(32, 'Боль в ухе, снижение слуха', 16),
(33, '', NULL),
(35, 'Одышка, отеки', 25),
(36, '', 35),
(37, '', 36),
(39, '', NULL),
(40, 'Боль в суставах рук', 6),
(42, 'Размытость зрения, трудности с ночным зрением', 5),
(44, 'Усталость, увеличение веса, медлительностью мышления и речи, зябкость, нарушение менструального цикла', 10),
(45, 'Чувство жажды, частое мочеиспускание', 7),
(46, 'Чувство жажды, частое мочеиспускание', 7),
(47, 'Чувство жажды, частое мочеиспускание', 7),
(53, '', NULL),
(54, '', NULL),
(55, '', NULL),
(56, 'Лихорадка, кашель, головная боль, насморк', 1),
(57, '', NULL),
(58, 'Сильная головная боль, тошнота', 8),
(59, '', NULL),
(60, 'Головные боли, головокружение, тошнота', 9);

-- --------------------------------------------------------

--
-- Структура таблицы `operating_ranges`
--

CREATE TABLE `operating_ranges` (
  `id_ranges` int(11) NOT NULL,
  `range_start` time NOT NULL,
  `range_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `operating_ranges`
--

INSERT INTO `operating_ranges` (`id_ranges`, `range_start`, `range_end`) VALUES
(1, '08:00:00', '08:15:00'),
(2, '08:15:00', '08:30:00'),
(3, '08:30:00', '08:45:00'),
(4, '08:45:00', '09:00:00'),
(5, '09:00:00', '09:15:00'),
(6, '09:15:00', '09:30:00'),
(7, '09:30:00', '09:45:00'),
(8, '09:45:00', '10:00:00'),
(9, '10:00:00', '10:15:00'),
(10, '10:15:00', '10:30:00'),
(11, '10:30:00', '10:45:00'),
(12, '10:45:00', '11:00:00'),
(13, '11:00:00', '11:15:00'),
(14, '11:15:00', '11:30:00'),
(15, '11:30:00', '11:45:00'),
(16, '11:45:00', '12:00:00'),
(17, '12:00:00', '12:15:00'),
(18, '12:15:00', '12:30:00'),
(19, '12:30:00', '12:45:00'),
(20, '12:45:00', '13:00:00'),
(21, '13:00:00', '13:15:00'),
(22, '13:15:00', '13:30:00'),
(23, '13:30:00', '13:45:00'),
(24, '13:45:00', '14:00:00'),
(25, '14:00:00', '14:15:00'),
(26, '14:15:00', '14:30:00'),
(27, '14:30:00', '14:45:00'),
(28, '14:45:00', '15:00:00'),
(29, '15:00:00', '15:15:00'),
(30, '15:15:00', '15:30:00'),
(31, '15:30:00', '15:45:00'),
(32, '15:45:00', '16:00:00'),
(33, '16:00:00', '16:15:00'),
(34, '16:15:00', '16:30:00'),
(35, '16:30:00', '16:45:00'),
(36, '16:45:00', '17:00:00'),
(37, '17:00:00', '17:15:00'),
(38, '17:15:00', '17:30:00'),
(39, '17:30:00', '17:45:00'),
(40, '17:45:00', '18:00:00'),
(41, '18:00:00', '18:15:00'),
(42, '18:15:00', '18:30:00'),
(43, '18:30:00', '18:45:00'),
(44, '18:45:00', '19:00:00'),
(45, '19:00:00', '19:15:00'),
(46, '19:15:00', '19:30:00'),
(47, '19:30:00', '19:45:00'),
(48, '19:45:00', '20:00:00'),
(49, '08:00:00', '09:00:00'),
(50, '09:00:00', '10:00:00'),
(51, '10:00:00', '11:00:00'),
(52, '11:00:00', '12:00:00'),
(53, '08:00:00', '08:30:00'),
(54, '08:30:00', '09:00:00'),
(55, '09:00:00', '09:30:00'),
(56, '09:30:00', '10:00:00'),
(57, '10:00:00', '10:30:00'),
(58, '10:30:00', '11:00:00'),
(59, '11:00:00', '11:30:00'),
(60, '11:30:00', '12:00:00'),
(61, '12:00:00', '12:30:00'),
(62, '12:30:00', '13:00:00'),
(63, '13:00:00', '13:30:00'),
(64, '13:30:00', '14:00:00'),
(65, '14:00:00', '14:30:00'),
(66, '14:30:00', '15:00:00'),
(67, '15:00:00', '15:30:00'),
(68, '15:30:00', '16:00:00'),
(69, '16:00:00', '16:30:00'),
(70, '16:30:00', '17:00:00'),
(71, '17:00:00', '17:30:00'),
(72, '17:30:00', '18:00:00'),
(73, '18:00:00', '18:30:00'),
(74, '18:30:00', '19:00:00'),
(75, '08:00:00', '08:45:00'),
(76, '08:45:00', '09:30:00'),
(77, '09:30:00', '10:15:00'),
(78, '10:15:00', '11:00:00'),
(79, '11:00:00', '11:45:00'),
(80, '12:00:00', '13:00:00'),
(81, '13:00:00', '14:00:00'),
(82, '14:00:00', '15:00:00'),
(83, '15:00:00', '16:00:00'),
(84, '12:00:00', '12:45:00'),
(85, '12:45:00', '13:30:00'),
(86, '13:30:00', '14:15:00'),
(87, '14:15:00', '15:00:00'),
(88, '15:00:00', '15:45:00');

-- --------------------------------------------------------

--
-- Структура таблицы `polyclinic_schedule`
--

CREATE TABLE `polyclinic_schedule` (
  `id_schedule` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '0-воскресенье, 1-понедельник и т.д',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_working` tinyint(1) NOT NULL,
  `polyclinic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `polyclinic_schedule`
--

INSERT INTO `polyclinic_schedule` (`id_schedule`, `day_of_week`, `start_time`, `end_time`, `is_working`, `polyclinic_id`) VALUES
(1, 1, '08:00:00', '20:00:00', 1, 1),
(2, 2, '08:00:00', '20:00:00', 1, 1),
(3, 3, '08:00:00', '20:00:00', 1, 1),
(4, 4, '08:00:00', '20:00:00', 1, 1),
(5, 5, '08:00:00', '20:00:00', 1, 1),
(6, 6, '09:00:00', '15:00:00', 1, 1),
(7, 0, '00:00:00', '00:00:00', 0, 1),
(8, 1, '08:00:00', '19:00:00', 1, 2),
(9, 2, '08:00:00', '19:00:00', 1, 2),
(10, 3, '08:00:00', '19:00:00', 1, 2),
(11, 4, '08:00:00', '19:00:00', 1, 2),
(12, 5, '08:00:00', '19:00:00', 1, 2),
(13, 6, '09:00:00', '14:00:00', 1, 2),
(14, 0, '00:00:00', '00:00:00', 0, 2),
(15, 1, '08:00:00', '18:00:00', 1, 3),
(16, 2, '08:00:00', '18:00:00', 1, 3),
(17, 3, '08:00:00', '18:00:00', 1, 3),
(18, 4, '08:00:00', '18:00:00', 1, 3),
(19, 5, '08:00:00', '18:00:00', 1, 3),
(20, 6, '09:00:00', '13:00:00', 1, 3),
(21, 0, '00:00:00', '00:00:00', 1, 3),
(22, 1, '08:00:00', '20:00:00', 1, 4),
(23, 2, '08:00:00', '19:00:00', 1, 4),
(24, 3, '08:00:00', '19:00:00', 1, 4),
(25, 4, '08:00:00', '19:00:00', 1, 4),
(26, 5, '08:00:00', '19:00:00', 1, 4),
(27, 6, '09:00:00', '16:00:00', 1, 4),
(28, 0, '00:00:00', '00:00:00', 1, 4),
(29, 1, '08:00:00', '20:00:00', 1, 5),
(30, 2, '08:00:00', '20:00:00', 1, 5),
(31, 3, '08:00:00', '20:00:00', 1, 5),
(32, 4, '08:00:00', '20:00:00', 1, 5),
(33, 5, '08:00:00', '20:00:00', 1, 5),
(34, 6, '09:00:00', '15:00:00', 1, 5),
(35, 0, '00:00:00', '00:00:00', 1, 5);

-- --------------------------------------------------------

--
-- Структура таблицы `qualification_improvement`
--

CREATE TABLE `qualification_improvement` (
  `id_qualif_improv` int(11) NOT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_of_organizator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `id_field` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `qualification_improvement`
--

INSERT INTO `qualification_improvement` (`id_qualif_improv`, `name`, `type`, `name_of_organizator`, `date`, `id_field`) VALUES
(2, 'Инновационные техники в хирургии', 'Семинар', 'Научный центр', '2023-02-20', 2),
(3, 'Современные методы диагностики и лечения в педиатрии', 'Курс', 'Медицинская ассоциация', '2023-03-10', 3),
(4, 'Эстетическая стоматология: новые горизонты', 'Семинар', 'Стоматологическая ассоциация', '2023-04-05', 4),
(5, 'Актуальные вопросы женского здоровья', 'Семинар', 'Медицинский университет', '2023-05-12', 5),
(6, 'Методы реабилитации и физиотерапии', 'Курс', 'Медицинская ассоциация', '2023-07-25', 7),
(7, 'Современные технологии лабораторной диагностики', 'Семинар', 'Научный центр', '2023-08-30', 8),
(8, 'Неврология: от теории к практике', 'Курс', 'Медицинский университет', '2023-09-15', 9),
(9, 'Современные подходы к лечению сердечно-сосудистых заболеваний', 'Семинар', 'Научный центр', '2023-10-20', 25),
(10, 'Актуальные вопросы эндокринологии', 'Курс', 'Медицинская ассоциация', '2023-11-05', 11),
(11, 'Современные методы реабилитации', 'Семинар', 'Медицинский университет', '2023-12-12', 12),
(12, 'Ультразвуковая диагностика: новые технологии', 'Курс', 'Научный центр', '2024-01-10', 13),
(13, 'Современные методы рентгенологической диагностики', 'Семинар', 'Медицинская ассоциация', '2024-02-15', 14),
(14, 'Современные подходы к диагностике и лечению аллергий', 'Курс', 'Медицинский университет', '2024-03-20', 15),
(15, 'Эстетическая дерматология: новые методы', 'Семинар', 'Научный центр', '2024-04-25', 16),
(16, 'Современные подходы в гастроэнтерологии', 'Курс', 'Медицинская ассоциация', '2024-05-30', 17),
(17, 'Актуальные вопросы отоларингологии', 'Семинар', 'Медицинский университет', '2024-06-10', 18),
(18, 'Современные методы психотерапии', 'Курс', 'Научный центр', '2024-07-15', 19),
(19, 'Современные подходы к лечению травм', 'Семинар', 'Медицинская ассоциация', '2024-08-20', 20),
(20, 'Инновации в онкологии', 'Курс', 'Медицинский университет', '2024-09-25', 21),
(21, 'Современные методы диагностики и лечения в урологии', 'Семинар', 'Научный центр', '2024-10-30', 22),
(22, 'Актуальные вопросы гематологии', 'Курс', 'Медицинская ассоциация', '2024-11-15', 23),
(23, 'Современные подходы к лечению инфекционных заболеваний', 'Семинар', 'Медицинский университет', '2024-12-20', 24),
(24, 'Инновационные технологии в кардиохирургии', 'Курс', 'Научный центр', '2025-01-10', 25),
(25, 'Современные методы нейрохирургии', 'Семинар', 'Медицинская ассоциация', '2025-02-15', 26),
(26, 'Современные методы диагностики и лечения глазных заболеваний у пожилых людей', 'Семинар', 'Научный центр', '2024-10-30', 6),
(27, 'Сердечно-сосудистая хирургия: повышение квалификации', 'Курс', 'Центр повышения квалификации при медицинском университете', '2020-09-01', 25),
(28, 'Урология: повышение квалификации', 'Курс', 'Центр дополнительного образования при медицинском университете', '2024-07-03', 22),
(29, 'Инфекционные заболевания', 'Курс', 'Отделение дополнительного образования при медицинском университет', '2022-02-02', 24),
(30, ' Острые и хронические заболевания уха, носа и его придаточных пазух, глотки и гортани.', 'Лекция', 'Цент дополнительного образования при Московском медицинском университете', '2024-12-02', 18),
(32, 'Лапароскопия в диагностике и лечении экстренных заболеваний органов брюшной полости', 'Курс', ' РНИМУ им. Н. И. Пирогова', '2024-03-03', 2),
(33, 'Современные подходы в терапии', 'Курс', 'Московский институт дополнительного образования', '2024-10-05', 1),
(34, 'Белые ночи гастроэнтерологии', 'Семинар', 'V Всероссийский научно-практический конгресс с международным участием', '2024-05-05', 17);

-- --------------------------------------------------------

--
-- Структура таблицы `referral`
--

CREATE TABLE `referral` (
  `id_referral` int(11) NOT NULL,
  `date_of_start` date NOT NULL,
  `duration` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `refrerral_doctor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `referral`
--

INSERT INTO `referral` (`id_referral`, `date_of_start`, `duration`, `id_patient`, `id_doctor`, `refrerral_doctor`) VALUES
(1, '2023-10-03', 30, 60, 1, 7),
(2, '2023-10-03', 30, 64, 3, 20),
(3, '2023-10-03', 30, 65, 4, 23),
(4, '2023-10-03', 30, 67, 29, 27),
(5, '2023-10-03', 30, 68, 32, 24),
(6, '2023-10-03', 30, 60, 6, 22),
(7, '2023-10-03', 30, 62, 13, 24),
(8, '2023-10-03', 30, 63, 4, 28),
(9, '2023-10-03', 30, 70, 2, 25),
(10, '2023-10-03', 30, 88, 30, 26),
(11, '2023-10-03', 30, 84, 32, 24),
(12, '2023-10-03', 30, 74, 6, 7),
(13, '2023-10-03', 30, 1, 1, 42),
(14, '2023-10-03', 30, 1, 1, 43),
(15, '2023-10-03', 30, 1, 1, 44),
(16, '2023-10-03', 30, 1, 1, 45);

-- --------------------------------------------------------

--
-- Структура таблицы `staff`
--

CREATE TABLE `staff` (
  `id_doctor` int(11) NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthday` date NOT NULL,
  `post` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_department` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `staff`
--

INSERT INTO `staff` (`id_doctor`, `full_name`, `birthday`, `post`, `status`, `address`, `phone_number`, `id_department`) VALUES
(1, 'Иванов Иван Иванович', '1985-05-15', 'Врач-терапевт', 1, 'г. Москва, ул. Ленина, д. 1', '+7 985 658-98-78', 1),
(2, 'Петров Петр Петрович', '1982-03-20', 'Врач-терапевт', 1, 'г. Москва, ул. Солнечная. д. 5', ' 7 974 985-87-87', 1),
(3, 'Сидорова Светлана Сергеевна', '1990-07-10', 'Врач-терапевт', 1, 'г. Москва, ул. Баумана, д. 23', '+7 911 111-11-11', 1),
(4, 'Кузнецов Алексей Викторович', '1988-11-25', 'Врач-терапевт', 1, 'г. Москва, ул. Малышева, д. 38', '+7 966-666-55-55', 1),
(5, 'Смирнова Анна Владимировна', '1985-02-14', 'Абдоминальный хирургг', 0, 'г. Москва, ул. Тверская, д. 22', '+7 900 323-05-67', 2),
(6, 'Федоров Сергей Николаевич', '1992-09-30', 'Хирург', 1, 'г. Москва, ул. Арбат, д. 35', '+7 900 234-56-78', 2),
(7, 'Морозова Ольга Александровна', '1980-12-05', 'Торакальный хирург', 0, 'г. Москва, ул. Пушкинская, д. 1', '+7 900 345-67-89', 2),
(8, 'Григорьев Игорь Валерьевич', '1983-04-18', 'Сердечно-сосудистый хирург', 0, 'г. Москва, ул. Краснопресненская, д. 12', '+7 900 456-78-90', 2),
(9, 'Коваленко Мария Петровна', '1987-06-22', 'Стоматолог-терапевт', 1, 'г. Москва, ул. Лубянка, д. 5', '+7 900 567-89-01', 3),
(10, 'Соловьев Дмитрий Андреевич', '1984-01-15', 'Стоматолог-хирург', 1, 'г. Москва, ул. Садовая, д. 16', '+7 900 678-89-01', 3),
(11, 'Тихонов Алексей Сергеевич', '1986-08-30', 'Стоматолог-пародонтолог', 1, 'г. Москва, ул. Кутузовский проспект, д. 14', '+7 955 678-90-12', 3),
(12, 'Лебедева Наталья Викторовна', '1991-10-12', 'Стоматолог-ортопед', 1, 'г. Москва, ул. Ленинградский проспект, д. 45', '+7 978 678-01-23', 3),
(13, 'Семенов Василий Петрович', '1989-03-05', 'Врач акушер-гинеколог', 1, 'г. Москва, ул. Чистопрудный бульвар, д. 4', ' 7 987 890-12-34', 4),
(14, 'Васильева Екатерина Сергеевна', '1990-11-02', 'Врач акушер-гинеколог', 1, 'г. Москва, ул. Сухаревская, д. 2', '+7 904 012-34-56', 4),
(15, 'Кузьмина Татьяна Викторовна', '1985-09-14', 'Врач акушер-гинеколог', 1, 'г. Москва, ул. Кузнецкий мост, д. 6', '+7 975 246-80-35', 4),
(16, 'Громова Юлия Владимировна', '1988-02-22', 'Врач акушер-гинеколог', 1, 'г. Москва, ул. Пресненская набережная, д. 8', '+7 933 680-54-79', 4),
(17, 'Николаев Андрей Владимирович', '1983-07-19', 'Врач-офтальмолог', 1, 'г. Москва, ул. Мясницкая, д. 15', '+7 932 901-23-45', 5),
(18, 'Романов Артем Александрович', '1987-05-25', 'Врач-офтальмолог', 1, 'г. Москва, ул. Таганская, д. 20', '+7 974 135-79-24', 5),
(19, 'Сафонов Денис Николаевич', '1992-12-01', 'Врач-офтальмолог', 1, 'г. Москва, ул. Сретенка, д. 10', '+7 987 357-91-46', 5),
(20, 'Баранов Илья Сергеевич', '1984-06-17', 'Физиотерапевт', 0, 'г. Москва, ул. Крымский вал, д. 9', '+7 906 354-02-57', 6),
(21, 'Фролов Константин Андреевич', '1991-04-09', 'Физиотерапевт', 0, 'г. Москва, ул. Воробьевы горы, д. 1', '+7 954 791-35-80', 6),
(22, 'Костина Дарья Александровна', '1986-08-11', 'Физиотерапевт', 0, 'г. Москва, ул. Парк Горького, д. 1', '+7 998 802-46-91', 6),
(23, 'Ларионов Сергей Викторович', '1983-10-30', 'Врач клинической лабораторной диагностики', 0, 'г. Москва, ул. Кировоградская, д. 12', '+7 974 913-57-02', 7),
(24, 'Шевченко Анастасия Петровна', '1990-01-28', 'Врач клинической лабораторной диагностики', 0, 'г. Москва, ул. Каширское шоссе, д. 45', '+7 900 024-68-13', 7),
(25, 'Ковалев Алексей Николаевич', '1987-03-15', 'Врач клинической лабораторной диагностики', 0, 'г. Москва, ул. Кленовый бульвар, д. 3', '+7 981 135-79-24', 7),
(26, 'Сидорова Ольга Сергеевна', '1985-07-05', 'Нейропсихолог', 0, 'г. Москва, ул. Лесная, д. 20', '+7 914 357-91-46', 8),
(27, 'Куликов Игорь Владимирович', '1992-11-20', 'Невролог', 0, 'г. Москва, ул. Станиславского, д. 4', '+7 921 467-02-57', 8),
(28, 'Михайлова Анна Андреевна', '1984-12-12', 'Невролог', 0, 'г. Москва, ул. Садовая-Самотечная, д. 1', '+7 987 579-13-68', 8),
(29, 'Соловьева Мария Викторовна', '1989-05-18', 'Кардиолог', 1, 'г. Москва, ул. Костякова, д. 7', '+7 975 680-24-79', 9),
(30, 'Григорьев Дмитрий Николаевич', '1986-09-22', 'Кардиолог', 1, 'г. Москва, ул. Кутузовский проспект, д. 30', '+7 971 791-35-80', 9),
(31, 'Костенко Татьяна Петровна', '1991-02-07', 'Кардиолог', 1, 'г. Москва, ул. Синяя, д. 8', '+7 905 802-46-91', 9),
(32, 'Федосова Светлана Сергеевна', '1983-08-14', 'Эндокринолог', 1, 'г. Москва, ул. Кольцевая дорога, д. 5', '+7 907 913-57-02', 10),
(33, 'Лебедев Илья Андреевич', '1987-10-25', 'Эндокринолог', 1, 'г. Москва, ул. Невский проспект, д. 10', '+7 901 024-68-13', 10),
(34, 'Сафонова Екатерина Владимировна', '1990-03-02', 'Реабилитолог', 1, 'г. Москва, ул. Арбат, д. 15', '+7 965 135-79-24', 11),
(35, 'Рябов Константин Викторович', '1985-06-19', 'Реабилитолог', 1, 'г. Москва, ул. Тверская, д. 18', '+7 978 246-80-35', 11),
(36, 'Кузнецова Анастасия Николаевна', '1992-01-11', 'Ультразвуковой диагност', 1, 'г. Москва, ул. Лубянка, д. 3', '+7 914 357-91-46', 12),
(37, 'Тихонов Сергей Петрович', '1984-04-27', 'Ультразвуковой диагност', 1, 'г. Москва, ул. Краснопресненская, д. 7', '+7 971 468-02-57', 12),
(38, 'Громов Алексей Владимирович', '1988-12-03', 'Врач-рентгенолог', 1, 'г. Москва, ул. Чистопрудный бульвар, д. 6', '+7 958 579-13-68', 13),
(39, 'Сидорова Дарья Андреевна', '1991-07-15', 'Врач-рентгенолог', 1, 'г. Москва, ул. Таганская, д. 25', '+7 978 680-24-79', 13),
(40, 'Ковалев Игорь Сергеевич', '1986-11-09', 'Аллерголог', 1, 'г. Москва, ул. Кузнецкий мост, д. 8', '+7 923 791-35-80', 14),
(41, 'Фролова Наталья Викторовна', '1983-05-21', 'Аллерголог', 1, 'г. Москва, ул. Сретенка, д. 12', '+7 940 802-46-91', 14),
(42, 'Ларионова Ольга Петровна', '1990-08-30', 'Дерматолог', 0, 'г. Москва, ул. Мясницкая, д. 18', '+7 932 913-57-02', 15),
(43, 'Семенова Анна Владимировна', '1987-02-18', 'Дерматолог', 0, 'г. Москва, ул. Крымский вал, д. 5', '+7 942 024-68-13', 15),
(44, 'Баранов Денис Николаевич', '1985-10-14', 'Гастроэнтеролог', 0, 'г. Москва, ул. Пресненская набережная, д. 10', '+7 935 135-79-24', 16),
(45, 'Григорьева Юлия Сергеевна', '1992-03-25', 'Гастроэнтеролог', 0, 'г. Москва, ул. Садовая-Черногрязская, д. 8', '+7 958 246-80-35', 16),
(46, 'Костина Татьяна Андреевна', '1984-09-12', 'Врач-отоларинголог', 1, 'г. Москва, ул. Кировоградская, д. 5', ' 7 919 357-91-46', 17),
(47, 'Задорнов Дмитрий Васильевич', '1995-12-02', 'Кардиохирург', 0, 'г. Москва, ул. Менделеева, д. 7', '+7 965 587-98-89', 24),
(48, 'Мария Егорова Александровна', '1998-05-14', 'Врач-уролог', 0, 'г. Москва, ул. Солнечная, д. 15', '+7 958 254-78-98', 21),
(49, 'Любавина Мария Александровна', '1992-02-02', 'Врач-уролог', 0, 'г. Москва, ул. Ягодная, д. 15', '+7 958 254-78-98', 21),
(50, 'Шик Анна Ивановна', '1981-08-02', 'Врач-гастроэнтеролог', 1, 'г. Москва, ул. Мирная, д. 13', '+7 965 247-85-95', 28);

--
-- Триггеры `staff`
--
DELIMITER $$
CREATE TRIGGER `after_delete_doctor` AFTER DELETE ON `staff` FOR EACH ROW BEGIN
  UPDATE appointment
  SET id_doctor = 0
  WHERE id_doctor = OLD.id_doctor;
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`id_appointment`),
  ADD UNIQUE KEY `id_referral` (`id_referral`),
  ADD KEY `id_ranges` (`id_ranges`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_cabinet` (`id_cabinet`),
  ADD KEY `id_medical_history` (`id_medical_history`);

--
-- Индексы таблицы `cabinet`
--
ALTER TABLE `cabinet`
  ADD PRIMARY KEY (`id_cabinet`),
  ADD KEY `id_department` (`id_department`);

--
-- Индексы таблицы `connection`
--
ALTER TABLE `connection`
  ADD PRIMARY KEY (`id_connection`),
  ADD KEY `id_department` (`id_department`),
  ADD KEY `id_polyclinic` (`id_polyclinic`);

--
-- Индексы таблицы `connection_education`
--
ALTER TABLE `connection_education`
  ADD PRIMARY KEY (`id_connection_education`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `id_education` (`id_education`);

--
-- Индексы таблицы `connection_qualif_improve`
--
ALTER TABLE `connection_qualif_improve`
  ADD PRIMARY KEY (`id_connection_qualif_improve`),
  ADD KEY `id_doctors` (`id_doctors`),
  ADD KEY `id_qualif_improve` (`id_qualif_improve`);

--
-- Индексы таблицы `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`id_department`);

--
-- Индексы таблицы `disease`
--
ALTER TABLE `disease`
  ADD PRIMARY KEY (`id_disease`),
  ADD KEY `id_field` (`id_field`);

--
-- Индексы таблицы `education`
--
ALTER TABLE `education`
  ADD PRIMARY KEY (`id_education`),
  ADD KEY `id_field` (`id_field`);

--
-- Индексы таблицы `field_of_medicine`
--
ALTER TABLE `field_of_medicine`
  ADD PRIMARY KEY (`id_field`);

--
-- Индексы таблицы `information_about_patient`
--
ALTER TABLE `information_about_patient`
  ADD PRIMARY KEY (`id_patient`),
  ADD UNIQUE KEY `policy_number` (`policy_number`);

--
-- Индексы таблицы `info_about_polyclinic`
--
ALTER TABLE `info_about_polyclinic`
  ADD PRIMARY KEY (`id_polyclinic`);

--
-- Индексы таблицы `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `id_disease` (`id_disease`);

--
-- Индексы таблицы `operating_ranges`
--
ALTER TABLE `operating_ranges`
  ADD PRIMARY KEY (`id_ranges`);

--
-- Индексы таблицы `polyclinic_schedule`
--
ALTER TABLE `polyclinic_schedule`
  ADD PRIMARY KEY (`id_schedule`),
  ADD KEY `polyclinic_id` (`polyclinic_id`);

--
-- Индексы таблицы `qualification_improvement`
--
ALTER TABLE `qualification_improvement`
  ADD PRIMARY KEY (`id_qualif_improv`),
  ADD KEY `id_field` (`id_field`);

--
-- Индексы таблицы `referral`
--
ALTER TABLE `referral`
  ADD PRIMARY KEY (`id_referral`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `refrerral_doctor` (`refrerral_doctor`);

--
-- Индексы таблицы `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id_doctor`),
  ADD KEY `id_department` (`id_department`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `appointment`
--
ALTER TABLE `appointment`
  MODIFY `id_appointment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=360;

--
-- AUTO_INCREMENT для таблицы `cabinet`
--
ALTER TABLE `cabinet`
  MODIFY `id_cabinet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT для таблицы `connection`
--
ALTER TABLE `connection`
  MODIFY `id_connection` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `connection_education`
--
ALTER TABLE `connection_education`
  MODIFY `id_connection_education` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT для таблицы `connection_qualif_improve`
--
ALTER TABLE `connection_qualif_improve`
  MODIFY `id_connection_qualif_improve` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT для таблицы `department`
--
ALTER TABLE `department`
  MODIFY `id_department` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `disease`
--
ALTER TABLE `disease`
  MODIFY `id_disease` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT для таблицы `education`
--
ALTER TABLE `education`
  MODIFY `id_education` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT для таблицы `field_of_medicine`
--
ALTER TABLE `field_of_medicine`
  MODIFY `id_field` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `information_about_patient`
--
ALTER TABLE `information_about_patient`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT для таблицы `info_about_polyclinic`
--
ALTER TABLE `info_about_polyclinic`
  MODIFY `id_polyclinic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT для таблицы `operating_ranges`
--
ALTER TABLE `operating_ranges`
  MODIFY `id_ranges` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT для таблицы `polyclinic_schedule`
--
ALTER TABLE `polyclinic_schedule`
  MODIFY `id_schedule` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT для таблицы `qualification_improvement`
--
ALTER TABLE `qualification_improvement`
  MODIFY `id_qualif_improv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT для таблицы `referral`
--
ALTER TABLE `referral`
  MODIFY `id_referral` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `staff`
--
ALTER TABLE `staff`
  MODIFY `id_doctor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`id_ranges`) REFERENCES `operating_ranges` (`id_ranges`),
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`id_doctor`) REFERENCES `staff` (`id_doctor`),
  ADD CONSTRAINT `appointment_ibfk_4` FOREIGN KEY (`id_patient`) REFERENCES `information_about_patient` (`id_patient`),
  ADD CONSTRAINT `appointment_ibfk_5` FOREIGN KEY (`id_referral`) REFERENCES `referral` (`id_referral`),
  ADD CONSTRAINT `appointment_ibfk_6` FOREIGN KEY (`id_cabinet`) REFERENCES `cabinet` (`id_cabinet`),
  ADD CONSTRAINT `appointment_ibfk_7` FOREIGN KEY (`id_medical_history`) REFERENCES `medical_history` (`id_history`);

--
-- Ограничения внешнего ключа таблицы `cabinet`
--
ALTER TABLE `cabinet`
  ADD CONSTRAINT `cabinet_ibfk_2` FOREIGN KEY (`id_department`) REFERENCES `department` (`id_department`);

--
-- Ограничения внешнего ключа таблицы `connection`
--
ALTER TABLE `connection`
  ADD CONSTRAINT `connection_ibfk_1` FOREIGN KEY (`id_department`) REFERENCES `department` (`id_department`),
  ADD CONSTRAINT `connection_ibfk_2` FOREIGN KEY (`id_polyclinic`) REFERENCES `info_about_polyclinic` (`id_polyclinic`);

--
-- Ограничения внешнего ключа таблицы `connection_education`
--
ALTER TABLE `connection_education`
  ADD CONSTRAINT `connection_education_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `staff` (`id_doctor`),
  ADD CONSTRAINT `connection_education_ibfk_2` FOREIGN KEY (`id_education`) REFERENCES `education` (`id_education`);

--
-- Ограничения внешнего ключа таблицы `connection_qualif_improve`
--
ALTER TABLE `connection_qualif_improve`
  ADD CONSTRAINT `connection_qualif_improve_ibfk_1` FOREIGN KEY (`id_doctors`) REFERENCES `staff` (`id_doctor`),
  ADD CONSTRAINT `connection_qualif_improve_ibfk_2` FOREIGN KEY (`id_qualif_improve`) REFERENCES `qualification_improvement` (`id_qualif_improv`);

--
-- Ограничения внешнего ключа таблицы `disease`
--
ALTER TABLE `disease`
  ADD CONSTRAINT `disease_ibfk_1` FOREIGN KEY (`id_field`) REFERENCES `field_of_medicine` (`id_field`);

--
-- Ограничения внешнего ключа таблицы `education`
--
ALTER TABLE `education`
  ADD CONSTRAINT `education_ibfk_1` FOREIGN KEY (`id_field`) REFERENCES `field_of_medicine` (`id_field`);

--
-- Ограничения внешнего ключа таблицы `medical_history`
--
ALTER TABLE `medical_history`
  ADD CONSTRAINT `medical_history_ibfk_2` FOREIGN KEY (`id_disease`) REFERENCES `disease` (`id_disease`);

--
-- Ограничения внешнего ключа таблицы `polyclinic_schedule`
--
ALTER TABLE `polyclinic_schedule`
  ADD CONSTRAINT `polyclinic_schedule_ibfk_1` FOREIGN KEY (`polyclinic_id`) REFERENCES `info_about_polyclinic` (`id_polyclinic`);

--
-- Ограничения внешнего ключа таблицы `qualification_improvement`
--
ALTER TABLE `qualification_improvement`
  ADD CONSTRAINT `qualification_improvement_ibfk_1` FOREIGN KEY (`id_field`) REFERENCES `field_of_medicine` (`id_field`);

--
-- Ограничения внешнего ключа таблицы `referral`
--
ALTER TABLE `referral`
  ADD CONSTRAINT `referral_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `staff` (`id_doctor`),
  ADD CONSTRAINT `referral_ibfk_2` FOREIGN KEY (`id_patient`) REFERENCES `information_about_patient` (`id_patient`),
  ADD CONSTRAINT `referral_ibfk_3` FOREIGN KEY (`refrerral_doctor`) REFERENCES `staff` (`id_doctor`);

--
-- Ограничения внешнего ключа таблицы `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`id_department`) REFERENCES `department` (`id_department`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
