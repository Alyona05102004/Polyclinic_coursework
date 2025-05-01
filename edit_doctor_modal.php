<?php include 'bd.php'; 

if (isset($_POST['doctor_id'])) {
    $doctorId = intval($_POST['doctor_id']);
    $sql = "SELECT * FROM staff WHERE id_doctor = $doctorId";
    $result = $conn->query($sql);
    $doctor = $result->fetch_assoc();
}
?>

?>
<div class="modal fade" id="staff_editModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staff_editModal_Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-2 text-uppercase" id="staff_editModal_Label">Информация о пользователе</h5>
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