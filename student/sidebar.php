<nav class="sidebar bg-dark text-white p-3">
    <h3 class="text-center mb-4"><?php echo SYSTEM_NAME; ?></h3>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Tableau de Bord</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'active' : ''; ?>" href="view_results.php"><i class="fas fa-calculator me-2"></i> Mes Résultats</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'view_course_materials.php' ? 'active' : ''; ?>" href="view_course_materials.php"><i class="fas fa-file-pdf me-2"></i> Supports de Cours</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'view_schedule.php' ? 'active' : ''; ?>" href="view_schedule.php"><i class="fas fa-clock me-2"></i> Emploi du Temps</a></li>
        <?php
        // Vérifier si l'étudiant est en doctorat
        $query = "SELECT s.*, f.id_filiere, f.filiere_name, l.level_name 
                  FROM students s 
                  JOIN filieres f ON s.id_filiere = f.id_filiere 
                  JOIN levels l ON f.id_level = l.id_level 
                  WHERE s.id_user = '{$_SESSION['user_id']}'";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
            if (isset($student['level_name']) && $student['level_name'] === 'Doctorat') {
                echo '<li class="nav-item"><a class="nav-link text-white ' . (basename($_SERVER['PHP_SELF']) == 'view_soutenance.php' ? 'active' : '') . '" href="view_soutenance.php"><i class="fas fa-gavel me-2"></i> Ma Soutenance</a></li>';
            }
        }
        ?>
        <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
    </ul>
</nav>
