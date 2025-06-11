// Déterminer la décision du semestre
if ($semesterAverage >= 10) {
    $semesterDecision = 'Validé';
} else {
    $semesterDecision = 'Rattrapage';
}

// Insérer ou mettre à jour le résultat du semestre
$query = "INSERT INTO results (id_student, id_semester, average, credits_validated, total_credits, decision) 
          VALUES (?, ?, ?, ?, ?, ?) 
          ON DUPLICATE KEY UPDATE 
          average = VALUES(average), 
          credits_validated = VALUES(credits_validated), 
          total_credits = VALUES(total_credits), 
          decision = VALUES(decision)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiddis", $id_student, $id_semester, $semesterAverage, $validatedCredits, $totalCredits, $semesterDecision);
mysqli_stmt_execute($stmt);
