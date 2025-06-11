📘 Description du projet — Gestion LMD
Titre du projet : Création et mise à jour de la base de données des canevas d’une formation LMD

🎯 Objectif du projet
Ce projet a pour objectif de concevoir et développer une application web complète permettant la gestion pédagogique d’un établissement d’enseignement supérieur suivant le système LMD (Licence - Master - Doctorat).
Il centralise la gestion des départements, filières, semestres, modules, matières, étudiants, enseignants, notes, emplois du temps, rattrapages, et décisions de passage.

🧠 Fonctionnalités principales
👨‍💼 Administrateur :
Gérer les départements, filières, niveaux, années académiques

Créer et mettre à jour les modules (UE) et matières (EC)

Saisir et modifier les notes (devoirs, examens)

Calcul automatique des résultats et décisions (Admis, Ajourné, etc.)

Générer et exporter les bulletins de notes en PDF

Gérer les emplois du temps par filière

Uploader les supports de cours pour chaque matière

👨‍🎓 Étudiant :
Consulter ses notes et moyennes

Voir ses décisions pédagogiques (module/année)

Télécharger ses bulletins PDF

Accéder aux supports de cours

Voir son emploi du temps (lundi–dimanche)

👨‍🏫 Enseignant (à compléter) :
Visualiser ses matières

Saisir les notes des étudiants

Ajouter des documents/supports pédagogiques

⚙ Technologies utilisées
Frontend : HTML5, CSS3, JavaScript

Backend : PHP (procédural)

Base de données : MySQL

Serveur local : XAMPP

🧮 Logique pédagogique (Système LMD)
Note finale d'une matière = (devoir × 0.4) + (examen × 0.6)

Moyenne d’un module = moyenne des matières

Module validé si moyenne ≥ 10

Année validée si :

Moyenne annuelle ≥ 10

Crédits semestriels cumulés ≥ 39 sur 60

Si une matière ≤ 5.4, rattrapage obligatoire

Résultats et décisions générés automatiquement

📦 Structure du projet
bash
Copy
Edit
/admin
/student
/enseignant
/assets
/config
/db
/includes
🚧 État du projet
✅ Système fonctionnel et testé localement

🔧 Quelques fonctionnalités à optimiser (rattrapage, gestion enseignant)

🎯 Prochaines étapes : amélioration UI/UX, passage à MVC ou Laravel

📄 Licence
Ce projet est open source — vous pouvez le modifier ou l’utiliser à des fins pédagogiques.

✨ Contributeurs
💡 Direction : Abdellahi et Mohamed

📚 Université : [ISCAE]

📆 Année académique : 2024–2025
