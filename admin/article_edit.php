<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$article = [
    'id' => null,
    'title' => '',
    'slug' => '',
    'content' => '',
    'category_id' => '',
    'image' => '',
    'meta_title' => '',
    'meta_description' => ''
];
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $title = $_POST['title'];
    $slug = $_POST['title'];
    $slug = transliterate($slug);
    $slug = preg_replace('/[^a-z0-9а-яё]+/iu', '-', $slug);
    $slug = preg_replace('/^-+|-+$/', '', $slug);
    $slug = mb_strtolower($slug, 'UTF-8');
    $content = $_POST['content']; // Сохраняем HTML как есть
    $category_id = (int)$_POST['category_id'];
    $image = $article['image'];
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/';
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            if ($image && file_exists($uploadDir . $image)) {
                unlink($uploadDir . $image);
            }
            $image = $fileName;
        }
    }
    
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    $tags = array_map('trim', $tags);
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE articles SET title = ?, slug = ?, content = ?, category_id = ?, image = ?, meta_title = ?, meta_description = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $category_id, $image, $meta_title, $meta_description, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO articles (title, slug, content, category_id, image, created_at, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([$title, $slug, $content, $category_id, $image, $meta_title, $meta_description]);
        $id = $pdo->lastInsertId();
    }
    
    update_article_tags($id, $tags);
    
    header("Location: articles.php");
    exit;
}

$article_tags = [];
if ($article['id']) {
    $tags = get_article_tags($article['id']);
    $article_tags = array_column($tags, 'name');
}

$all_tags = get_all_tags();
$tag_names = array_column($all_tags, 'name');
?>

<div class="admin-content">
    <h1><?= $article['id'] ? 'Редактирование' : 'Создание' ?> статьи</h1>
    
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="id" value="<?= $article['id'] ?>">
        
        <div class="form-group">
            <label for="title">Заголовок:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="meta_title">Meta Title (SEO заголовок):</label>
            <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($article['meta_title'] ?? '') ?>">
            <small>До 60 символов, рекомендуется включать ключевые слова</small>
        </div>
        
        <div class="form-group">
            <label for="meta_description">Meta Description (SEO описание):</label>
            <textarea id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($article['meta_description'] ?? '') ?></textarea>
            <small>До 160 символов, краткое описание статьи</small>
        </div>
        
        <div class="form-group">
            <label for="category_id">Категория:</label>
            <select id="category_id" name="category_id" required>
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $category['id'] == $article['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="image">Изображение:</label>
            <input type="file" id="image" name="image">
            <?php if ($article['image']): ?>
                <div class="current-image">
                    <img src="../images/<?= htmlspecialchars($article['image']) ?>" alt="Current" style="max-width: 200px;">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="tags">Теги (через запятую):</label>
            <input type="text" id="tags" name="tags" value="<?= htmlspecialchars(implode(', ', $article_tags)) ?>">
        </div>
        
        <div class="form-group">
            <label for="content">Содержание (можно использовать HTML-теги):</label>
            <textarea id="content" name="content"><?= htmlspecialchars($article['content']) ?></textarea>
        </div>
        
        <button type="submit" class="btn">Сохранить</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Настройка TinyMCE для работы с HTML
    tinymce.init({
        selector: '#content',
        plugins: 'advlist autolink lists link image charmap print preview anchor table code',
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright | bullist numlist | table | link image | code',
        height: 500,
        images_upload_url: '../upload.php',
        automatic_uploads: true,
        file_picker_types: 'image',
        image_title: true,
        image_caption: true,
        // Разрешить все HTML-теги
        valid_elements: '*[*]',
        // Разрешить все атрибуты
        valid_attributes: '*[*]',
        // Разрешить скрипты и стили (осторожно - может быть небезопасно!)
        extended_valid_elements: 'script[language|type|src],style[type]',
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                
                reader.onload = function() {
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    
                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };
            
            input.click();
        },
        // Добавляем кнопку для просмотра HTML-кода
        setup: function(editor) {
            editor.addButton('htmlcode', {
                text: 'HTML',
                icon: false,
                onclick: function() {
                    editor.execCommand('mceCodeEditor');
                }
            });
        }
    });
    
    const tagInput = document.getElementById('tags');
    const tagSuggestions = <?= json_encode($tag_names) ?>;
    
    new Awesomplete(tagInput, {
        list: tagSuggestions,
        filter: function(text, input) {
            return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
        },
        item: function(text, input) {
            return Awesomplete.ITEM(text, input.match(/[^,]*$/)[0]);
        },
        replace: function(text) {
            const before = this.input.value.match(/^.+,/)[0] || '';
            this.input.value = before + text + ', ';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
