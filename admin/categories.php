<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/header.php';

$message = '';

// Функция для создания slug с поддержкой кириллицы
function createSlug($string) {
    $translitTable = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya'
    ];
    
    // Приводим к нижнему регистру (с поддержкой UTF-8)
    $string = mb_strtolower($string, 'UTF-8');
    // Транслитерация кириллицы
    $string = strtr($string, $translitTable);
    // Замена запрещённых символов
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    // Удаляем дефисы по краям
    $string = trim($string, '-');
    
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $slug = createSlug($name); // Используем новую функцию
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $message = 'success';
        } catch (PDOException $e) {
            $message = 'error';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'deleted';
    } catch (PDOException $e) {
        $message = 'delete_error';
    }
}

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>Управление категориями</h1>
        <div class="admin-actions">
            <a href="articles.php" class="btn btn-outline">Управление статьями</a>
        </div>
    </div>
    
    <?php if ($message === 'success'): ?>
        <div class="admin-alert success">
            Категория успешно добавлена
        </div>
    <?php elseif ($message === 'error'): ?>
        <div class="admin-alert error">
            Ошибка: такая категория уже существует
        </div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="admin-alert success">
            Категория успешно удалена
        </div>
    <?php elseif ($message === 'delete_error'): ?>
        <div class="admin-alert error">
            Ошибка: сначала удалите статьи из этой категории
        </div>
    <?php endif; ?>
    
    <div class="admin-form-section">
        <h2>Добавить новую категорию</h2>
        <form method="post" class="admin-form">
            <div class="form-group">
                <label for="name">Название категории:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <button type="submit" name="add_category" class="btn">Добавить категорию</button>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Список категорий</h2>
        
        <?php if (!empty($categories)): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= $category['id'] ?></td>
                                <td><?= $category['name'] ?></td>
                                <td><?= $category['slug'] ?></td>
                                <td class="admin-actions-cell">
                                    <a href="?delete=<?= $category['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Вы уверены, что хотите удалить эту категорию?');">
                                        <i class="icon-delete"></i> Удалить
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty">
                <p>Нет ни одной категории. Добавьте первую категорию с помощью формы выше.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
