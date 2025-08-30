<?php
// Database configuration
define('DB_HOST', '127.0.0.1:3308');
define('DB_NAME', 'link_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        // Create tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            color VARCHAR(7) DEFAULT '#3B82F6',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            description TEXT,
            category_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )");
        
        // Insert some default categories if none exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        if ($stmt->fetchColumn() == 0) {
            $defaultCategories = [
                ['Jobs', '#EF4444'],
                ['Shopping', '#10B981'],
                ['News', '#8B5CF6'],
                ['Social', '#3B82F6'],
                ['Education', '#F59E0B']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, color) VALUES (?, ?)");
            foreach ($defaultCategories as $category) {
                $stmt->execute([$category[0], $category[1]]);
            }
        }
        
        return $pdo;
    } catch(PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    if (isset($_POST['add_link'])) {
        $title = trim($_POST['title']);
        $url = trim($_POST['url']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $error = "Please enter a valid URL";
        } else {
            // Check if category exists, if not create it
            $stmt = $db->prepare("SELECT id, color FROM categories WHERE name = ?");
            $stmt->execute([$category]);
            $category_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_id = $category_data['id'] ?? null;
            
            if (!$category_id) {
                // Create a new category with a random color from our palette
                $colors = ['#EF4444', '#10B981', '#8B5CF6', '#3B82F6', '#F59E0B', '#EC4899', '#6366F1'];
                $random_color = $colors[array_rand($colors)];
                
                $stmt = $db->prepare("INSERT INTO categories (name, color) VALUES (?, ?)");
                $stmt->execute([$category, $random_color]);
                $category_id = $db->lastInsertId();
            }
            
            // Insert the link
            $stmt = $db->prepare("INSERT INTO links (title, url, description, category_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $url, $description, $category_id]);
            
            $success = "Link added successfully!";
        }
    }
    
    if (isset($_POST['delete_link'])) {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM links WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Link deleted successfully!";
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build query for getting links
$db = getDB();
$query = "
    SELECT l.*, c.name as category_name, c.color as category_color 
    FROM links l 
    LEFT JOIN categories c ON l.category_id = c.id 
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $query .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $query .= " AND c.name = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY l.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#10B981',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    }
                }
            }
        }
    </script>
    <style>
        .category-badge {
            transition: all 0.2s ease;
        }
        .category-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .link-card {
            transition: all 0.3s ease;
        }
        .link-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .btn {
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <header class="mb-12 text-center">
            <h1 class="text-5xl font-bold text-indigo-600 mb-4">Link Manager</h1>
            <p class="text-gray-600 text-lg">Organize and access your links with ease</p>
        </header>

        <!-- Stats Bar -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-indigo-600 mb-2"><?= count($links) ?></div>
                <div class="text-gray-600">Total Links</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-indigo-600 mb-2"><?= count($categories) ?></div>
                <div class="text-gray-600">Categories</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-indigo-600 mb-2">1</div>
                <div class="text-gray-600">Active User (You!)</div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Search & Filter</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 mb-2" for="search">Search by Title or Description</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= htmlspecialchars($search) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter keywords..."
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="category">Filter by Category</label>
                    <select 
                        id="category" 
                        name="category" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['name'] ?>" <?= $category_filter === $cat['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button 
                        type="submit" 
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center justify-center btn"
                    >
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                    <a 
                        href="?" 
                        class="ml-2 bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition duration-200 flex items-center justify-center btn"
                    >
                        <i class="fas fa-times mr-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Link Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Add New Link</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="title">Title</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="url">URL</label>
                            <input 
                                type="url" 
                                id="url" 
                                name="url" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="https://example.com"
                            >
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="category">Category</label>
                            <input 
                                type="text" 
                                id="category" 
                                name="category" 
                                list="categories"
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="e.g., Jobs, Shopping, News"
                            >
                            <datalist id="categories">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2" for="description">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                        </div>
                        
                        <button 
                            type="submit" 
                            name="add_link" 
                            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center justify-center btn"
                        >
                            <i class="fas fa-plus-circle mr-2"></i> Add Link
                        </button>
                    </form>
                </div>
                
                <!-- Categories Section -->
                <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Categories</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $cat): ?>
                            <span 
                                class="px-3 py-1 rounded-full text-white text-sm font-medium category-badge" 
                                style="background-color: <?= $cat['color'] ?>"
                            >
                                <?= htmlspecialchars($cat['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Links List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">Your Links</h2>
                        <span class="text-sm text-gray-500"><?= count($links) ?> item(s)</span>
                    </div>
                    
                    <?php if (count($links) > 0): ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($links as $link): ?>
                                <div class="p-6 hover:bg-gray-50 transition duration-150 link-card">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <span 
                                                    class="px-2.5 py-0.5 rounded-full text-white text-xs font-medium mr-2 category-badge" 
                                                    style="background-color: <?= $link['category_color'] ?>"
                                                >
                                                    <?= htmlspecialchars($link['category_name'] ?? 'Uncategorized') ?>
                                                </span>
                                                <span class="text-sm text-gray-500"><?= date('M j, Y', strtotime($link['created_at'])) ?></span>
                                            </div>
                                            
                                            <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="hover:text-indigo-600 transition duration-150">
                                                    <?= htmlspecialchars($link['title']) ?>
                                                    <i class="fas fa-external-link-alt text-xs ml-1 text-gray-400"></i>
                                                </a>
                                            </h3>
                                            
                                            <?php if (!empty($link['description'])): ?>
                                                <p class="text-gray-600 mb-4"><?= htmlspecialchars($link['description']) ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center text-sm text-gray-500">
                                                <span class="truncate">
                                                    <?= parse_url($link['url'], PHP_URL_HOST) ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <form method="POST" class="ml-4">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button 
                                                type="submit" 
                                                name="delete_link" 
                                                class="text-red-500 hover:text-red-700 transition duration-150 p-2 rounded-full hover:bg-red-50"
                                                onclick="return confirm('Are you sure you want to delete this link?')"
                                                title="Delete link"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-indigo-400 text-5xl mb-4">
                                <i class="fas fa-link"></i>
                            </div>
                            <h3 class="text-xl font-medium text-gray-600 mb-2">No links found</h3>
                            <p class="text-gray-500">
                                <?= (!empty($search) || !empty($category_filter)) ? 
                                    'Try adjusting your search filters' : 
                                    'Add your first link using the form on the left' 
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <footer class="mt-12 text-center text-gray-500 text-sm">
            <p>Link Manager &copy; <?= date('Y') ?> - Made for you!</p>
        </footer>
    </div>
    
    <script>
        // Simple animation for page elements
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.link-card, .bg-white');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>