<?php
// Set page title
$pageTitle = "BookVibe - Discover Your Next Great Read";

// Include database config and header
require '../config/db.php';
include 'includes/header.php';

try {
    // Get featured books (random selection for now)
    $featuredBooks = $pdo->query("
        SELECT b.book_id, b.title, b.author, b.cover_image, b.price, b.sale_price,
               b.avg_rating, b.review_count, b.description, g.genre_name
        FROM books b
        LEFT JOIN genres g ON b.genre_id = g.genre_id
        ORDER BY RAND() 
        LIMIT 6
    ")->fetchAll();
    
    // Get trending books (highest rated with good review count)
    $trendingBooks = $pdo->query("
        SELECT b.book_id, b.title, b.author, b.cover_image, b.avg_rating, b.review_count
        FROM books b
        WHERE b.review_count >= 2
        ORDER BY (b.avg_rating * 0.7) + (LOG(b.review_count + 1) * 0.3) DESC
        LIMIT 4
    ")->fetchAll();
    
    // Get recent reviews
    $recentReviews = $pdo->query("
        SELECT r.rating, r.review_title, r.review_text, r.created_at,
               u.full_name, b.title as book_title, b.book_id
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        JOIN books b ON r.book_id = b.book_id
        WHERE r.is_public = TRUE
        ORDER BY r.created_at DESC
        LIMIT 3
    ")->fetchAll();
    
    // Get genre stats
    $genreStats = $pdo->query("
        SELECT g.genre_name, g.genre_icon, COUNT(b.book_id) as book_count
        FROM genres g
        LEFT JOIN books b ON g.genre_id = b.genre_id
        GROUP BY g.genre_id, g.genre_name, g.genre_icon
        ORDER BY book_count DESC
        LIMIT 8
    ")->fetchAll();
    
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">
            <i class="fas fa-book-open me-3"></i>
            Discover Your Next Great Read
        </h1>
        <p class="hero-subtitle">
            Join thousands of book lovers sharing reviews, discovering new authors, and building their reading journey
        </p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form class="d-flex search-bar" role="search">
                    <div class="input-group input-group-lg">
                        <input type="search" class="form-control form-control-custom" 
                               placeholder="Search for books, authors, genres..." aria-label="Search">
                        <button class="btn btn-primary-custom" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<main class="container mt-5">
    
    <!-- Featured Books Section -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary-custom">
                <i class="fas fa-star me-2"></i>Featured Books
            </h2>
            <a href="browse.php" class="btn btn-outline-primary">
                View All Books <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row">
            <?php foreach ($featuredBooks as $book): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="position-relative">
                            <img src="assets/images/<?= $book['cover_image'] ?: 'placeholder.jpg' ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>"
                                 style="height: 300px; object-fit: cover;">
                            
                            <?php if ($book['sale_price'] && $book['sale_price'] < $book['price']): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                    Sale!
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="card-text text-muted mb-2">
                                by <?= htmlspecialchars($book['author']) ?>
                            </p>
                            
                            <?php if ($book['genre_name']): ?>
                                <span class="badge bg-light text-dark mb-2"><?= $book['genre_name'] ?></span>
                            <?php endif; ?>
                            
                            <?php if ($book['avg_rating'] > 0): ?>
                                <div class="mb-2">
                                    <?php
                                    $rating = $book['avg_rating'];
                                    $fullStars = floor($rating);
                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                    ?>
                                    <span class="star-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $fullStars): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </span>
                                    <small class="text-muted ms-1">
                                        <?= number_format($rating, 1) ?> (<?= $book['review_count'] ?> reviews)
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <p class="card-text flex-grow-1">
                                <?= strlen($book['description']) > 100 ? 
                                    substr(htmlspecialchars($book['description']), 0, 100) . '...' : 
                                    htmlspecialchars($book['description']) ?>
                            </p>
                            
                            <div class="mt-auto">
                                <?php if ($book['price']): ?>
                                    <div class="mb-2">
                                        <?php if ($book['sale_price'] && $book['sale_price'] < $book['price']): ?>
                                            <span class="text-decoration-line-through text-muted">
                                                $<?= number_format($book['price'], 2) ?>
                                            </span>
                                            <span class="text-danger fw-bold ms-1">
                                                $<?= number_format($book['sale_price'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="fw-bold">$<?= number_format($book['price'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="book.php?id=<?= $book['book_id'] ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Stats & Trending Section -->
    <div class="row mb-5">
        <div class="col-lg-8">
            <!-- Trending Books -->
            <h3 class="text-primary-custom mb-3">
                <i class="fas fa-fire me-2"></i>Trending Now
            </h3>
            <div class="row">
                <?php foreach ($trendingBooks as $book): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="assets/images/<?= $book['cover_image'] ?: 'placeholder.jpg' ?>" 
                                         class="img-fluid rounded-start" alt="<?= htmlspecialchars($book['title']) ?>"
                                         style="height: 120px; object-fit: cover;">
                                </div>
                                <div class="col-8">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($book['title']) ?></h6>
                                        <p class="card-text small text-muted mb-2">
                                            by <?= htmlspecialchars($book['author']) ?>
                                        </p>
                                        <div class="d-flex align-items-center">
                                            <span class="star-rating me-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= floor($book['avg_rating']) ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </span>
                                            <small class="text-muted"><?= $book['review_count'] ?> reviews</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Browse by Genre -->
            <h3 class="text-primary-custom mb-3">
                <i class="fas fa-tags me-2"></i>Browse Genres
            </h3>
            <div class="row">
                <?php foreach ($genreStats as $genre): ?>
                    <div class="col-6 mb-2">
                        <a href="browse.php?genre=<?= urlencode(strtolower($genre['genre_name'])) ?>" 
                           class="btn btn-outline-secondary btn-sm w-100 text-start">
                            <i class="fas <?= $genre['genre_icon'] ?: 'fa-book' ?> me-2"></i>
                            <?= $genre['genre_name'] ?>
                            <span class="badge bg-secondary ms-auto"><?= $genre['book_count'] ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Reviews Section -->
    <?php if (!empty($recentReviews)): ?>
    <section class="mb-5">
        <h3 class="text-primary-custom mb-4">
            <i class="fas fa-comments me-2"></i>Recent Reviews
        </h3>
        <div class="row">
            <?php foreach ($recentReviews as $review): ?>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($review['review_title']) ?></h6>
                                <span class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-2">
                                for <a href="book.php?id=<?= $review['book_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($review['book_title']) ?>
                                </a>
                            </p>
                            <p class="card-text">
                                "<?= strlen($review['review_text']) > 80 ? 
                                    substr(htmlspecialchars($review['review_text']), 0, 80) . '...' : 
                                    htmlspecialchars($review['review_text']) ?>"
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    by <?= htmlspecialchars($review['full_name']) ?>
                                </small>
                                <small class="text-muted">
                                    <?= date('M j', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
</main>

<?php include 'includes/footer.php'; ?>