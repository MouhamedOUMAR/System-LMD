<nav class="sidebar bg-dark text-white p-3">
    <h3 class="text-center mb-4"><?php echo SYSTEM_NAME; ?></h3>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_academic_years.php' ? 'active' : ''; ?>" href="manage_academic_years.php"><i class="fas fa-calendar-alt"></i> Années Académiques</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_semesters.php' ? 'active' : ''; ?>" href="manage_semesters.php"><i class="fas fa-calendar"></i> Semestres</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_faculties.php' ? 'active' : ''; ?>" href="manage_faculties.php"><i class="fas fa-university"></i> Facultés</a></li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_departments.php">
                <i class="fas fa-building me-2"></i> Départements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_filieres.php">
                <i class="fas fa-graduation-cap me-2"></i> Filières
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_modules.php">
                <i class="fas fa-book me-2"></i> Modules
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_subjects.php">
                <i class="fas fa-book-open me-2"></i> Matières
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_teachers.php">
                <i class="fas fa-chalkboard-teacher me-2"></i> Enseignants
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_students.php">
                <i class="fas fa-user-graduate me-2"></i> Étudiants
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="manage_notes.php">
                <i class="fas fa-clipboard-list me-2"></i> Notes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </li>
    </ul>
</nav>


