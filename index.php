<?php
session_start();

// Conexión a la base de datos SQLite
$db = new PDO('sqlite:orchid.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Funciones de ayuda
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_projects() {
    global $db;
    $stmt = $db->query("SELECT * FROM projects");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_project($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_pages() {
    global $db;
    $stmt = $db->query("SELECT * FROM pages");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_page($slug) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_site_data() {
    global $db;
    $stmt = $db->query("SELECT * FROM site_data LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_categories() {
    global $db;
    $stmt = $db->query("SELECT * FROM categories ORDER BY parent_id, name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_category_by_slug($slug) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_subcategories($parent_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ?");
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_projects_by_category($category_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM projects WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$action = $_GET['action'] ?? 'list';
$projects = get_projects();
$pages = get_pages();
$site_data = get_site_data();
$categories = get_categories();

if ($action === 'detail' && isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $project = get_project($project_id);
}

if ($action === 'page' && isset($_GET['slug'])) {
    $page_slug = $_GET['slug'];
    $page = get_page($page_slug);
}

if ($action === 'category' && isset($_GET['slug'])) {
    $category_slug = $_GET['slug'];
    $category = get_category_by_slug($category_slug);
    $projects = get_projects_by_category($category['id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Portafolio</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="profile">
            <img src="images/<?php echo htmlspecialchars($site_data['photo']); ?>" alt="Mi Foto">
            <h1><?php echo htmlspecialchars($site_data['name']); ?></h1>
            <p><?php echo htmlspecialchars($site_data['description']); ?></p>
        </div>
        <nav>
            <ul class="menu">
                <li><a href="index.php">Inicio</a></li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="index.php?action=category&slug=<?php echo htmlspecialchars($category['slug']); ?>"><?php echo htmlspecialchars($category['name']); ?></a>
                        <?php if (get_subcategories($category['id'])): ?>
                            <ul class="submenu">
                                <?php foreach (get_subcategories($category['id']) as $subcategory): ?>
                                    <li><a href="index.php?action=category&slug=<?php echo htmlspecialchars($subcategory['slug']); ?>"><?php echo htmlspecialchars($subcategory['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($pages as $page): ?>
                    <li><a href="index.php?action=page&slug=<?php echo htmlspecialchars($page['slug']); ?>"><?php echo htmlspecialchars($page['title']); ?></a></li>
                <?php endforeach; ?>
                <?php if (is_logged_in()): ?>
                    <li><a href="admin.php">Admin</a></li>
                    <li><a href="admin.php?action=logout">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="admin.php">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <?php if ($action === 'list'): ?>
            <section id="proyectos">
                <h2>Listado de Proyectos</h2>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="project">
                            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p><?php echo htmlspecialchars($project['description']); ?></p>
                            <a href="index.php?action=detail&id=<?php echo $project['id']; ?>">Ver más</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($action === 'detail' && isset($project)): ?>
            <section id="project-detail">
                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                <p><?php echo htmlspecialchars($project['description']); ?></p>
                <div class="image-gallery">
                    <?php $images = explode(',', $project['images']); ?>
                    <?php foreach ($images as $image): ?>
                        <img src="uploads/<?php echo htmlspecialchars(trim($image)); ?>" alt="Imagen del proyecto">
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($project['video_url'])): ?>
                    <div class="video-container">
                        <iframe width="560" height="315" src="<?php echo htmlspecialchars($project['video_url']); ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
                <div class="project-link">
                    <?php if (!empty($project['github_url'])): ?>
                        <a href="<?php echo htmlspecialchars($project['github_url']); ?>" target="_blank">Ver en GitHub</a>
                    <?php endif; ?>
                    <?php if (!empty($project['live_url'])): ?>
                        <a href="<?php echo htmlspecialchars($project['live_url']); ?>" target="_blank">Ver en Vivo</a>
                    <?php endif; ?>
                    <?php if (!empty($project['project_url'])): ?>
                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank">Ver Proyecto</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif ($action === 'page' && isset($page)): ?>
            <section id="page-detail">
                <h2><?php echo htmlspecialchars($page['title']); ?></h2>
                <div><?php echo htmlspecialchars_decode($page['content']); ?></div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

