// Déterminer la décision du semestre
if ($semesterAverage >= 10) {
    $semesterDecision = 'Validé';
    $decisionClass = 'text-success';
} else {
    $semesterDecision = 'Rattrapage';
    $decisionClass = 'text-danger';
}

echo "<div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h5 class='mb-0'>Résultat du Semestre: " . htmlspecialchars($semester['semester_name']) . "</h5>
        </div>
        <div class='card-body'>
            <div class='row'>
                <div class='col-md-6'>
                    <h4>Moyenne: <strong>" . number_format($semesterAverage, 2) . " / 20</strong></h4>
                </div>
                <div class='col-md-6'>
                    <h4>Décision: <span class='" . $decisionClass . "'><strong>" . $semesterDecision . "</strong></span></h4>
                </div>
            </div>
            <p>Crédits validés: " . $validatedCredits . " / " . $totalCredits . "</p>
        </div>
    </div>";