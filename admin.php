<?php
session_start();

// Conexión a la base de datos SQLite
$db = new PDO('sqlite:orchid.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Crear tablas si no existen
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    );
");

$db->exec("
    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        images TEXT NOT NULL,
        video_url TEXT,
        project_url TEXT,
        github_url TEXT,
        live_url TEXT,
        category_id INTEGER,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    );
");

$db->exec("
    CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE
    );
");

$db->exec("
    CREATE TABLE IF NOT EXISTS site_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        photo TEXT NOT NULL,
        description TEXT NOT NULL
    );
");

$db->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        parent_id INTEGER,
        FOREIGN KEY (parent_id) REFERENCES categories(id)
    );
");

// Insertar usuario por defecto si no existe
$stmt = $db->prepare("SELECT * FROM users WHERE username = 'jocarsa'");
$stmt->execute();
$user = $stmt->fetch();
if (!$user) {
    $db->exec("INSERT INTO users (username, password, email) VALUES ('jocarsa', 'jocarsa', 'jocarsa@example.com')");
}

// Insertar datos del sitio por defecto si no existen
$stmt = $db->prepare("SELECT * FROM site_data");
$stmt->execute();
$site_data = $stmt->fetch();
if (!$site_data) {
    $db->exec("INSERT INTO site_data (name, photo, description) VALUES ('Mi Nombre', 'tu-foto.jpg', 'Una pequeña frase o descripción sobre mí.')");
}

// Funciones de ayuda
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function login($username, $password) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
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

function add_project($title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id) {
    global $db;
    $stmt = $db->prepare("INSERT INTO projects (title, description, images, video_url, project_url, github_url, live_url, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id]);
}

function update_project($id, $title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id) {
    global $db;
    $stmt = $db->prepare("UPDATE projects SET title = ?, description = ?, images = ?, video_url = ?, project_url = ?, github_url = ?, live_url = ?, category_id = ? WHERE id = ?");
    return $stmt->execute([$title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id, $id]);
}

function delete_project($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    return $stmt->execute([$id]);
}

function get_pages() {
    global $db;
    $stmt = $db->query("SELECT * FROM pages");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_page($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_page($title, $content, $slug) {
    global $db;
    $stmt = $db->prepare("INSERT INTO pages (title, content, slug) VALUES (?, ?, ?)");
    return $stmt->execute([$title, $content, $slug]);
}

function update_page($id, $title, $content, $slug) {
    global $db;
    $stmt = $db->prepare("UPDATE pages SET title = ?, content = ?, slug = ? WHERE id = ?");
    return $stmt->execute([$title, $content, $slug, $id]);
}

function delete_page($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
    return $stmt->execute([$id]);
}

function get_site_data() {
    global $db;
    $stmt = $db->query("SELECT * FROM site_data LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_site_data($name, $photo, $description) {
    global $db;
    $stmt = $db->prepare("UPDATE site_data SET name = ?, photo = ?, description = ? WHERE id = 1");
    return $stmt->execute([$name, $photo, $description]);
}

function get_categories() {
    global $db;
    $stmt = $db->query("SELECT * FROM categories ORDER BY parent_id, name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_category($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_category($name, $slug, $parent_id = null) {
    global $db;
    $stmt = $db->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $slug, $parent_id]);
}

function update_category($id, $name, $slug, $parent_id = null) {
    global $db;
    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, parent_id = ? WHERE id = ?");
    return $stmt->execute([$name, $slug, $parent_id, $id]);
}

function delete_category($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    return $stmt->execute([$id]);
}

// Manejo de acciones
$action = $_GET['action'] ?? 'login';

if ($action === 'logout') {
    logout();
    header('Location: admin.php');
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (login($username, $password)) {
        header('Location: admin.php?action=dashboard');
        exit;
    } else {
        $error = 'Nombre de usuario o contraseña incorrectos.';
    }
}

if ($action === 'dashboard' && !is_logged_in()) {
    header('Location: admin.php');
    exit;
}

if ($action === 'add_project' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $images = implode(',', $_FILES['images']['name']);
    $video_url = $_POST['video_url'];
    $project_url = $_POST['project_url'];
    $github_url = $_POST['github_url'];
    $live_url = $_POST['live_url'];
    $category_id = $_POST['category_id'] ?? null;

    // Guardar imágenes
    $target_dir = 'uploads/';
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $target_file = $target_dir . basename($_FILES['images']['name'][$key]);
        move_uploaded_file($tmp_name, $target_file);
    }

    add_project($title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'edit_project' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $images = implode(',', $_FILES['images']['name']);
    $video_url = $_POST['video_url'];
    $project_url = $_POST['project_url'];
    $github_url = $_POST['github_url'];
    $live_url = $_POST['live_url'];
    $category_id = $_POST['category_id'] ?? null;

    // Guardar imágenes
    $target_dir = 'uploads/';
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $target_file = $target_dir . basename($_FILES['images']['name'][$key]);
        move_uploaded_file($tmp_name, $target_file);
    }

    update_project($id, $title, $description, $images, $video_url, $project_url, $github_url, $live_url, $category_id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'delete_project' && isset($_GET['id'])) {
    $id = $_GET['id'];
    delete_project($id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'add_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $slug = $_POST['slug'];
    add_page($title, $content, $slug);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'edit_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $slug = $_POST['slug'];
    update_page($id, $title, $content, $slug);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'delete_page' && isset($_GET['id'])) {
    $id = $_GET['id'];
    delete_page($id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'edit_site' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $photo = $_FILES['photo']['name'];
    $description = $_POST['description'];

    if (!empty($photo)) {
        $target_dir = 'images/';
        $target_file = $target_dir . basename($photo);
        move_uploaded_file($_FILES['photo']['tmp_name'], $target_file);
    } else {
        $site_data = get_site_data();
        $photo = $site_data['photo'];
    }

    update_site_data($name, $photo, $description);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'add_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $slug = $_POST['slug'];
    $parent_id = $_POST['parent_id'] ?? null;
    add_category($name, $slug, $parent_id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'edit_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $slug = $_POST['slug'];
    $parent_id = $_POST['parent_id'] ?? null;
    update_category($id, $name, $slug, $parent_id);
    header('Location: admin.php?action=dashboard');
    exit;
}

if ($action === 'delete_category' && isset($_GET['id'])) {
    $id = $_GET['id'];
    delete_category($id);
    header('Location: admin.php?action=dashboard');
    exit;
}

$projects = get_projects();
$pages = get_pages();
$site_data = get_site_data();
$categories = get_categories();
$edit_project = null;
$edit_page = null;
$edit_category = null;

if (isset($_GET['edit_project'])) {
    $id = $_GET['edit_project'];
    $edit_project = get_project($id);
}

if (isset($_GET['edit_page'])) {
    $id = $_GET['edit_page'];
    $edit_page = get_page($id);
}

if (isset($_GET['edit_category'])) {
    $id = $_GET['edit_category'];
    $edit_category = get_category($id);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'login' ? 'Iniciar Sesión' : 'Admin - Mi Portafolio'; ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <h1>Admin - Mi Portafolio</h1>
        <nav>
            <?php if (is_logged_in()): ?>
                <a href="admin.php?action=dashboard">Dashboard</a>
                <a href="admin.php?action=add_project">Añadir Proyecto</a>
                <a href="admin.php?action=add_page">Añadir Página</a>
                <a href="admin.php?action=add_category">Añadir Categoría</a>
                <a href="admin.php?action=edit_site">Editar Sitio</a>
                <a href="admin.php?action=logout">Cerrar Sesión</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="main-content">
        <?php if ($action === 'login'): ?>
            <header>
                <h2>Iniciar Sesión</h2>
            </header>
            <form action="admin.php?action=login" method="POST">
                <?php if (isset($error)): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endif; ?>
                <label for="username">Nombre de Usuario</label>
                <input type="text" name="username" id="username" required>
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
                <button type="submit">Iniciar Sesión</button>
            </form>
        <?php elseif ($action === 'dashboard'): ?>
            <header>
                <h2>Dashboard</h2>
            </header>
            <div class="projects-list">
                <h2>Lista de Proyectos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                <td>
                                    <a href="admin.php?action=edit_project&id=<?php echo $project['id']; ?>">Editar</a> |
                                    <a href="admin.php?action=delete_project&id=<?php echo $project['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este proyecto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pages-list">
                <h2>Lista de Páginas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                <td>
                                    <a href="admin.php?action=edit_page&id=<?php echo $page['id']; ?>">Editar</a> |
                                    <a href="admin.php?action=delete_page&id=<?php echo $page['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta página?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="categories-list">
                <h2>Lista de Categorías</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Categoría Padre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td><?php echo htmlspecialchars($category['parent_id'] ? get_category($category['parent_id'])['name'] : 'Ninguna'); ?></td>
                                <td>
                                    <a href="admin.php?action=edit_category&id=<?php echo $category['id']; ?>">Editar</a> |
                                    <a href="admin.php?action=delete_category&id=<?php echo $category['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($action === 'add_project' || $action === 'edit_project'): ?>
            <header>
                <h2><?php echo $edit_project ? 'Editar Proyecto' : 'Añadir Proyecto'; ?></h2>
            </header>
            <form action="admin.php?action=<?php echo $edit_project ? 'edit_project' : 'add_project'; ?>" method="POST" enctype="multipart/form-data">
                <?php if ($edit_project): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_project['id']; ?>">
                <?php endif; ?>
                <label for="title">Título</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($edit_project['title'] ?? ''); ?>" required>
                <label for="description">Descripción</label>
                <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($edit_project['description'] ?? ''); ?></textarea>
                <label for="category_id">Categoría</label>
                <select name="category_id" id="category_id">
                    <option value="">Ninguna</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($edit_project['category_id'] ?? null) == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="images">Imágenes</label>
                <div class="image-upload">
                    <input type="file" name="images[]" id="images" multiple accept="image/*">
                    <label for="images">Subir Imágenes</label>
                </div>
                <div class="image-preview">
                    <?php if ($edit_project): ?>
                        <?php $images = explode(',', $edit_project['images']); ?>
                        <?php foreach ($images as $image): ?>
                            <div style="position: relative;">
                                <img src="uploads/<?php echo htmlspecialchars(trim($image)); ?>" alt="Imagen del proyecto">
                                <button type="button" class="remove-image" data-image="<?php echo htmlspecialchars(trim($image)); ?>">X</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <label for="video_url">URL del Video</label>
                <input type="text" name="video_url" id="video_url" value="<?php echo htmlspecialchars($edit_project['video_url'] ?? ''); ?>">
                <label for="project_url">URL del Proyecto</label>
                <input type="text" name="project_url" id="project_url" value="<?php echo htmlspecialchars($edit_project['project_url'] ?? ''); ?>">
                <label for="github_url">URL de GitHub</label>
                <input type="text" name="github_url" id="github_url" value="<?php echo htmlspecialchars($edit_project['github_url'] ?? ''); ?>">
                <label for="live_url">URL en Vivo</label>
                <input type="text" name="live_url" id="live_url" value="<?php echo htmlspecialchars($edit_project['live_url'] ?? ''); ?>">
                <button type="submit"><?php echo $edit_project ? 'Actualizar Proyecto' : 'Añadir Proyecto'; ?></button>
            </form>
        <?php elseif ($action === 'add_page' || $action === 'edit_page'): ?>
            <header>
                <h2><?php echo $edit_page ? 'Editar Página' : 'Añadir Página'; ?></h2>
            </header>
            <form action="admin.php?action=<?php echo $edit_page ? 'edit_page' : 'add_page'; ?>" method="POST">
                <?php if ($edit_page): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_page['id']; ?>">
                <?php endif; ?>
                <label for="title">Título</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($edit_page['title'] ?? ''); ?>" required>
                <label for="content">Contenido</label>
                <textarea name="content" id="content" rows="4" required><?php echo htmlspecialchars($edit_page['content'] ?? ''); ?></textarea>
                <label for="slug">Slug</label>
                <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($edit_page['slug'] ?? ''); ?>" required>
                <button type="submit"><?php echo $edit_page ? 'Actualizar Página' : 'Añadir Página'; ?></button>
            </form>
        <?php elseif ($action === 'add_category' || $action === 'edit_category'): ?>
            <header>
                <h2><?php echo $edit_category ? 'Editar Categoría' : 'Añadir Categoría'; ?></h2>
            </header>
            <form action="admin.php?action=<?php echo $edit_category ? 'edit_category' : 'add_category'; ?>" method="POST">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>
                <label for="name">Nombre</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
                <label for="slug">Slug</label>
                <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($edit_category['slug'] ?? ''); ?>" required>
                <label for="parent_id">Categoría Padre</label>
                <select name="parent_id" id="parent_id">
                    <option value="">Ninguna</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($edit_category['parent_id'] ?? null) == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><?php echo $edit_category ? 'Actualizar Categoría' : 'Añadir Categoría'; ?></button>
            </form>
        <?php elseif ($action === 'edit_site'): ?>
            <header>
                <h2>Editar Sitio</h2>
            </header>
            <form action="admin.php?action=edit_site" method="POST" enctype="multipart/form-data">
                <label for="name">Nombre</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($site_data['name']); ?>" required>
                <label for="photo">Foto</label>
                <input type="file" name="photo" id="photo" accept="image/*">
                <label for="description">Descripción</label>
                <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($site_data['description']); ?></textarea>
                <button type="submit">Guardar Cambios</button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageUpload = document.querySelector('.image-upload input[type="file"]');
            const imagePreview = document.querySelector('.image-preview');

            imageUpload.addEventListener('change', function(event) {
                const files = event.target.files;
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '5px';
                        img.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                        img.style.marginRight = '10px';
                        imagePreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });

            imagePreview.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-image')) {
                    const imageName = event.target.getAttribute('data-image');
                    const formData = new FormData();
                    formData.append('image', imageName);

                    fetch('admin.php?action=remove_image', {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            event.target.parentElement.remove();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

