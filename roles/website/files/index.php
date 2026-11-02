<?php
// Simpele PHP-website van Konrad Sawicki
// Laat zien op welke server hij draait en telt bezoeken in de database
require 'config.php';

$db_status = '';
$bezoeken = 0;

try {
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $db->query('CREATE TABLE IF NOT EXISTS bezoeken (id INT AUTO_INCREMENT PRIMARY KEY, tijd DATETIME)');
    $db->query('INSERT INTO bezoeken (tijd) VALUES (NOW())');
    $bezoeken = $db->query('SELECT COUNT(*) FROM bezoeken')->fetch_row()[0];
    $db_status = 'Verbonden met MariaDB op ' . $db_server;
} catch (Exception $e) {
    $db_status = 'Geen verbinding met de database';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Website van Konrad</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="kaart">
        <h1>Hallo! Dit is de website van Konrad Sawicki</h1>
        <p>Deze website is automatisch neergezet met een Ansible-playbook.</p>
        <table>
            <tr><td>Webserver</td><td><?= gethostname() ?></td></tr>
            <tr><td>PHP-versie</td><td><?= phpversion() ?></td></tr>
            <tr><td>Database</td><td><?= $db_status ?></td></tr>
            <tr><td>Aantal bezoeken</td><td><?= $bezoeken ?></td></tr>
            <tr><td>Tijd</td><td><?= date('d-m-Y H:i:s') ?></td></tr>
        </table>
    </div>
</body>
</html>
