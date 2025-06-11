<?php
require '../config.php';

$query = "SELECT id_level, level_name FROM levels ORDER BY level_name";
$result = mysqli_query($conn, $query);

$levels = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $levels[] = [
            'id_level' => $row['id_level'],
            'level_name' => $row['level_name']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($levels); 