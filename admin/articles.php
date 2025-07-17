<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT image FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if ($article && $article['image'] && file_exists("../images/" . $article['image'])) {
        unlink("../images/" . $article['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: articles.php");
    exit;
}

$stmt = $pdo->query("SELECT articles.*, categories.name AS category_name 
                    FROM articles 
                    LEFT JOIN categories ON articles.category_id = categories.id 
                    ORDER BY articles.created_at DESC");
$articles = $stmt->fetchAll();

foreach ($articles as &$article) {
    $article['tags'] = get_article_tags($article['id']);
}
unset($article);
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>Управление статьями</h1>
        <div class="admin-actions">
            <a href="categories.php" class="btn btn-outline">Управление категориями</a>
            <a href="article_edit.php" class="btn">Добавить статью</a>
        </div>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="admin-alert success">
            Статья успешно удалена
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['saved'])): ?>
        <div class="admin-alert success">
            Статья успешно сохранена
        </div>
    <?php endif; ?>
    
    <div class="admin-list">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заголовок</th>
                        <th>Категория</th>
                        <th>Теги</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?= $article['id'] ?></td>
                            <td>
                                <a href="/article/<?= $article['slug'] ?>" target="_blank" class="text-link">
                                    <?= $article['title'] ?>
                                </a>
                            </td>
                            <td><?= $article['category_name'] ?></td>
                            <td>
                                <div class="tags-container">
                                    <?php foreach ($article['tags'] as $tag): ?>
                                        <span class="tag-badge"><?= $tag['name'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?= date('d.m.Y', strtotime($article['created_at'])) ?></td>
                            <td class="admin-actions-cell">
                                <a href="article_edit.php?id=<?= $article['id'] ?>" class="btn btn-sm">
                                    <i class="icon-edit"></i> Редакт.
                                </a>
                                <a href="?delete=<?= $article['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Вы уверены, что хотите удалить эту статью?');">
                                    <i class="icon-delete"></i> Удалить
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($articles)): ?>
            <div class="admin-empty">
                <p>Нет ни одной статьи. <a href="article_edit.php">Создайте первую статью</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
