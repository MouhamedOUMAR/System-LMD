-- Creating database
CREATE DATABASE IF NOT EXISTS lmd_system;
USE lmd_system;

-- Table for levels (Licence, Master, Doctorat)
CREATE TABLE levels (
    id_level INT AUTO_INCREMENT PRIMARY KEY,
    level_name ENUM('Licence', 'Master', 'Doctorat') NOT NULL,
    INDEX idx_level_name (level_name)
);

-- Table for faculties
CREATE TABLE faculties (
    id_faculty INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(255) NOT NULL,
    INDEX idx_faculty_name (faculty_name)
);

-- Table for departments (linked to faculties)
CREATE TABLE departments (
    id_department INT AUTO_INCREMENT PRIMARY KEY,
    id_faculty INT NOT NULL,
    department_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_faculty) REFERENCES faculties(id_faculty) ON DELETE CASCADE,
    INDEX idx_department_name (department_name),
    INDEX idx_faculty_department (id_faculty, department_name)
);

-- Table for academic years
CREATE TABLE academic_years (
    id_academic_year INT AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(10) NOT NULL UNIQUE,
    start_date DATE,
    end_date DATE,
    INDEX idx_year_dates (start_date, end_date)
);

-- Table for users
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'teacher') NOT NULL,
    INDEX idx_username_role (username, role)
);

-- Table for filieres (linked to departments and levels)
CREATE TABLE filieres (
    id_filiere INT AUTO_INCREMENT PRIMARY KEY,
    id_department INT NOT NULL,
    id_level INT NOT NULL,
    filiere_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_department) REFERENCES departments(id_department) ON DELETE CASCADE,
    FOREIGN KEY (id_level) REFERENCES levels(id_level) ON DELETE CASCADE,
    INDEX idx_department_level (id_department, id_level),
    INDEX idx_filiere_name (filiere_name)
);

-- Table for students (includes level and sub-level)
CREATE TABLE students (
    id_student INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_filiere INT NOT NULL,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    email VARCHAR(255),
    sub_level ENUM('L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3') NOT NULL,
    lieu_naissance VARCHAR(100),
    nni VARCHAR(50),
    niveau VARCHAR(50),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id_filiere) ON DELETE CASCADE,
    INDEX idx_matricule (matricule),
    INDEX idx_nom_prenom (nom, prenom),
    INDEX idx_sub_level (sub_level)
);

-- Table for teachers
CREATE TABLE teachers (
    id_teacher INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_nom_prenom (nom, prenom)
);

-- Table for semesters (linked to filieres and academic years)
CREATE TABLE semesters (
    id_semester INT AUTO_INCREMENT PRIMARY KEY,
    semester_name VARCHAR(50) NOT NULL,
    id_filiere INT NOT NULL,
    id_academic_year INT NOT NULL,
    semester_order INT NOT NULL DEFAULT 1,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id_filiere) ON DELETE CASCADE,
    FOREIGN KEY (id_academic_year) REFERENCES academic_years(id_academic_year) ON DELETE CASCADE,
    INDEX idx_filiere_semester (id_filiere, semester_name)
);

-- Table for modules (linked to filieres and semesters)
CREATE TABLE modules (
    id_module INT AUTO_INCREMENT PRIMARY KEY,
    id_filiere INT NOT NULL,
    id_semester INT NOT NULL,
    module_name VARCHAR(255) NOT NULL,
    credits INT NOT NULL DEFAULT 6,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id_filiere) ON DELETE CASCADE,
    FOREIGN KEY (id_semester) REFERENCES semesters(id_semester) ON DELETE CASCADE,
    INDEX idx_module_name (module_name),
    INDEX idx_filiere_semester_module (id_filiere, id_semester)
);

-- Table for subjects (linked to modules and teachers)
CREATE TABLE subjects (
    id_subject INT AUTO_INCREMENT PRIMARY KEY,
    id_module INT NOT NULL,
    id_teacher INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    coefficient DECIMAL(4,2) NOT NULL,
    credit DECIMAL(4,2) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    FOREIGN KEY (id_teacher) REFERENCES teachers(id_teacher) ON DELETE CASCADE,
    INDEX idx_subject_name (subject_name),
    INDEX idx_module_teacher (id_module, id_teacher)
);

-- Table for notes
CREATE TABLE notes (
    id_note INT AUTO_INCREMENT PRIMARY KEY,
    id_student INT NOT NULL,
    id_subject INT NOT NULL,
    note_devoir DECIMAL(4,2),
    note_examen DECIMAL(4,2),
    note_finale DECIMAL(4,2),
    validated BOOLEAN DEFAULT FALSE,
    status VARCHAR(50) DEFAULT "Validé",
    FOREIGN KEY (id_student) REFERENCES students(id_student) ON DELETE CASCADE,
    FOREIGN KEY (id_subject) REFERENCES subjects(id_subject) ON DELETE CASCADE,
    INDEX idx_student_subject (id_student, id_subject),
    INDEX idx_validation (validated)
);

-- Table for course_materials
CREATE TABLE course_materials (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    id_subject INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_subject) REFERENCES subjects(id_subject) ON DELETE CASCADE,
    INDEX idx_upload_date (uploaded_at)
);

-- Table for schedules
CREATE TABLE schedules (
    id_schedule INT AUTO_INCREMENT PRIMARY KEY,
    id_filiere INT NOT NULL,
    id_semester INT NOT NULL,
    day ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject_id INT,
    room VARCHAR(50),
    FOREIGN KEY (id_filiere) REFERENCES filieres(id_filiere) ON DELETE CASCADE,
    FOREIGN KEY (id_semester) REFERENCES semesters(id_semester) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id_subject) ON DELETE SET NULL,
    INDEX idx_schedule_time (day, start_time, end_time),
    INDEX idx_room (room)
);

-- Table for soutenances (specific to Doctorat)
CREATE TABLE soutenances (
    id_soutenance INT AUTO_INCREMENT PRIMARY KEY,
    id_student INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    defense_date DATE NOT NULL,
    defense_time TIME NOT NULL,
    room VARCHAR(50),
    jury_members TEXT,
    FOREIGN KEY (id_student) REFERENCES students(id_student) ON DELETE CASCADE,
    INDEX idx_defense_date (defense_date),
    INDEX idx_room (room)
);

-- Table pour les décisions de module
CREATE TABLE module_decisions (
    id_decision INT AUTO_INCREMENT PRIMARY KEY,
    id_student INT NOT NULL,
    id_module INT NOT NULL,
    decision VARCHAR(50) NOT NULL,
    average DECIMAL(4,2) DEFAULT NULL,
    FOREIGN KEY (id_student) REFERENCES students(id_student) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module) ON DELETE CASCADE,
    UNIQUE KEY unique_student_module (id_student, id_module)
);

-- Table pour les moyennes de semestre
CREATE TABLE semester_averages (
    id_average INT AUTO_INCREMENT PRIMARY KEY,
    id_student INT NOT NULL,
    id_semester INT NOT NULL,
    average DECIMAL(4,2) NOT NULL,
    total_credits INT NOT NULL,
    validated_credits INT NOT NULL,
    decision VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (id_student) REFERENCES students(id_student) ON DELETE CASCADE,
    FOREIGN KEY (id_semester) REFERENCES semesters(id_semester) ON DELETE CASCADE,
    UNIQUE KEY unique_student_semester (id_student, id_semester)
);

-- Table pour les décisions annuelles
CREATE TABLE annual_decisions (
    id_decision INT AUTO_INCREMENT PRIMARY KEY,
    id_student INT NOT NULL,
    id_academic_year INT NOT NULL,
    study_year INT NOT NULL,
    average DECIMAL(4,2) DEFAULT NULL,
    total_credits INT DEFAULT 0,
    validated_credits INT DEFAULT 0,
    decision VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (id_student) REFERENCES students(id_student) ON DELETE CASCADE,
    FOREIGN KEY (id_academic_year) REFERENCES academic_years(id_academic_year) ON DELETE CASCADE,
    UNIQUE KEY unique_student_year (id_student, id_academic_year, study_year)
);

-- Création de la table results
CREATE TABLE IF NOT EXISTS `results` (
  `id_result` int(11) NOT NULL AUTO_INCREMENT,
  `id_student` int(11) NOT NULL,
  `id_semester` int(11) NOT NULL,
  `moyenne_semestre` decimal(5,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_result`),
  KEY `id_student` (`id_student`),
  KEY `id_semester` (`id_semester`),
  CONSTRAINT `results_ibfk_1` FOREIGN KEY (`id_student`) REFERENCES `students` (`id_student`),
  CONSTRAINT `results_ibfk_2` FOREIGN KEY (`id_semester`) REFERENCES `semesters` (`id_semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data
INSERT INTO levels (level_name) VALUES ('Licence'), ('Master'), ('Doctorat');
INSERT INTO faculties (faculty_name) VALUES ('Faculté des Sciences');
INSERT INTO departments (id_faculty, department_name) VALUES (1, 'Département Informatique');
INSERT INTO filieres (id_department, id_level, filiere_name) VALUES (1, 1, 'Informatique Générale - Licence'), (1, 2, 'Informatique Avancée - Master'), (1, 3, 'Recherche en Informatique - Doctorat');
INSERT INTO academic_years (year_name, start_date, end_date) VALUES ('2023-2024', '2023-09-01', '2024-06-30');
INSERT INTO users (username, password, role) VALUES ('admin', 'admin123', 'admin'), ('student_doc', 'student123', 'student');
INSERT INTO students (id_user, id_filiere, matricule, nom, prenom, sub_level) VALUES (2, 3, 'DOC001', 'Doe', 'John', 'D1');

-- Ajout des semestres par défaut
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 1", id_filiere, 1, 1 FROM filieres WHERE id_level = 1 LIMIT 1;
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 2", id_filiere, 1, 2 FROM filieres WHERE id_level = 1 LIMIT 1;
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 3", id_filiere, 1, 3 FROM filieres WHERE id_level = 1 LIMIT 1;
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 4", id_filiere, 1, 4 FROM filieres WHERE id_level = 1 LIMIT 1;
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 5", id_filiere, 1, 5 FROM filieres WHERE id_level = 2 LIMIT 1;
INSERT INTO semesters (semester_name, id_filiere, id_academic_year, semester_order) 
SELECT "Semestre 6", id_filiere, 1, 6 FROM filieres WHERE id_level = 2 LIMIT 1;

-- Mise à jour des dates des semestres
UPDATE semesters s
JOIN academic_years a ON s.id_academic_year = a.id_academic_year
SET 
    s.start_date = CASE 
        WHEN s.semester_order % 2 = 1 THEN a.start_date
        ELSE DATE_ADD(a.start_date, INTERVAL 6 MONTH)
    END,
    s.end_date = CASE 
        WHEN s.semester_order % 2 = 1 THEN DATE_ADD(a.start_date, INTERVAL 5 MONTH)
        ELSE a.end_date
    END;

-- Mise à jour des statuts des notes
UPDATE notes SET status = 
CASE 
    WHEN note_finale >= 10 THEN 'Validé'
    WHEN note_finale >= 7 AND note_finale < 10 THEN 'Rattrapage'
    ELSE 'Non validé'
END
WHERE status IS NULL OR status = '';

-- Mise à jour de la colonne niveau des étudiants
UPDATE students s
JOIN filieres f ON s.id_filiere = f.id_filiere
JOIN levels l ON f.id_level = l.id_level
SET s.niveau = l.level_name
WHERE s.niveau IS NULL;







