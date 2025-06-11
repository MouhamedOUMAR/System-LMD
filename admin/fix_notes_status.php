<?php
require '../config.php';
if (!isAdmin()) {
    die('Accès refusé.');
}

// Récupérer tous les étudiants
$students = mysqli_query($conn, "SELECT id_student FROM students");
while ($student = mysqli_fetch_assoc($students)) {
    $id_student = $student['id_student'];
    // Récupérer tous les modules suivis par l'étudiant
    $modules = mysqli_query($conn, "SELECT DISTINCT s.id_module FROM subjects s JOIN notes n ON n.id_subject = s.id_subject WHERE n.id_student = $id_student");
    while ($module = mysqli_fetch_assoc($modules)) {
        $id_module = $module['id_module'];
        // Récupérer toutes les notes et coefficients du module
        $notes = mysqli_query($conn, "SELECT n.id_note, n.note_finale, s.coefficient FROM notes n JOIN subjects s ON n.id_subject = s.id_subject WHERE n.id_student = $id_student AND s.id_module = $id_module");
        $moduleNotes = [];
        $modulePoints = 0;
        $moduleCoefficients = 0;
        while ($note = mysqli_fetch_assoc($notes)) {
            $moduleNotes[] = $note;
            $modulePoints += $note['note_finale'] * $note['coefficient'];
            $moduleCoefficients += $note['coefficient'];
        }
        $moduleAverage = ($moduleCoefficients > 0) ? $modulePoints / $moduleCoefficients : 0;
        $moduleValidated = false;
        if ($moduleAverage >= 10 || $moduleAverage > 9) {
            $moduleValidated = true;
        }
        // Refaire la requête pour mettre à jour chaque matière
        $notes = mysqli_query($conn, "SELECT n.id_note, n.note_finale FROM notes n JOIN subjects s ON n.id_subject = s.id_subject WHERE n.id_student = $id_student AND s.id_module = $id_module");
        while ($note = mysqli_fetch_assoc($notes)) {
            $status = '';
            $validated = 0;
            if ($moduleValidated) {
                if ($note['note_finale'] >= 10) {
                    $status = 'Validé';
                } else {
                    $status = 'Validé par compensation';
                }
                $validated = 1;
            } else {
                if ($note['note_finale'] >= 10) {
                    $status = 'Validé';
                    $validated = 1;
                } else {
                    $status = 'Rattrapage';
                    $validated = 0;
                }
            }
            $id_note = $note['id_note'];
            mysqli_query($conn, "UPDATE notes SET status = '$status', validated = $validated WHERE id_note = $id_note");
        }
    }
}
echo "Correction terminée. Tous les statuts ont été recalculés."; 