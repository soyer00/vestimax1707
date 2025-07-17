<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../includes/header.php';
?>

<div class="admin-dashboard">
    <h1>Административная панель</h1>
    <div class="admin-menu">
        <a href="categories.php" class="admin-menu__item">Управление категориями</a>
        <a href="articles.php" class="admin-menu__item">Управление статьями</a>
        <a href="logout.php" class="admin-menu__item">Выход</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
