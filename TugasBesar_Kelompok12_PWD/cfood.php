<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$user_id = currentUserId();
require_once 'includes/header.php';

// cek  edit pesanan
$editOrderId = isset($_GET['edit_order']) ? (int)($_GET['edit_order']) : 0;
$editOrder   = null;
$isEditing   = false;

if ($editOrderId > 0) {
    $stmt = $conn->prepare("
        SELECT id, items_json, status
        FROM cfood_orders
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $editOrderId, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editOrder = $res->fetch_assoc();
    $stmt->close();


    if ($editOrder) {
        $isEditing = true;
    } else {
        $editOrder = null;
    }
}

function getSnackImageUrl($image)
{
    if (empty($image)) {
        return 'public/img/default-snack.png'; 
    }

    if (strpos($image, 'http') === 0) {
        return $image;
    }

    return 'public/img/' . ltrim($image, '/');
}

$itemsResult = $conn->query("
    SELECT id, name, description, price, category, image, is_available
    FROM cfood_items
    ORDER BY category ASC, name ASC
");

$products = [];
$categories = [];

if ($itemsResult && $itemsResult->num_rows > 0) {
    while ($row = $itemsResult->fetch_assoc()) {
        $cat = $row['category'] ?: 'Lainnya';

        if (!in_array($cat, $categories, true)) {
            $categories[] = $cat;
        }

        $row['image_url'] = getSnackImageUrl($row['image'] ?? '');
        $products[] = $row;
    }
} else {

    $categories = ['Promo', 'Combo', 'Popcorn', 'Drink', 'Fritters'];
}
?>
<section class="cfood-page">
    <div class="cfood-header-row">
        <div class="cfood-back-circle" onclick="history.back()">
            <span>&larr;</span>
        </div>
        <div class="cfood-search-wrapper">
            <span class="cfood-search-icon">&#128269;</span>
            <input id="cfood-search" type="text" placeholder="Pengen nyemil apa hari ini?">
        </div>
    </div>

    <div class="cfood-main">
        <!-- KIRI: LIST PRODUK -->
        <div class="cfood-left">
            <div class="cfood-tabs">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $index => $cat): ?>
                        <button
                            class="cfood-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-category="<?php echo htmlspecialchars($cat); ?>"
                        >
                            <?php echo htmlspecialchars($cat); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="cfood-section-title">
                <?php
                if ($isEditing) {
                    echo 'Edit Pesanan C.Food';
                } elseif (!empty($categories)) {
                    echo htmlspecialchars($categories[0]);
                } else {
                    echo 'C.Food';
                }
                ?>
            </h2>

            <div class="cfood-product-grid" id="cfood-product-list">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                            $cat   = $p['category'] ?: 'Lainnya';
                            $name  = $p['name'] ?? '';
                            $desc  = $p['description'] ?? '';
                            $price = (int)($p['price'] ?? 0);
                            $img   = $p['image_url'];
                            $stock = (int)($p['is_available'] ?? 0) === 1;
                        ?>
                        <article
                            class="cfood-card"
                            data-category="<?php echo htmlspecialchars($cat); ?>"
                            data-name="<?php echo htmlspecialchars($name); ?>"
                            data-desc="<?php echo htmlspecialchars($desc); ?>"
                        >
                            <div class="cfood-card-left">
                                <h3 class="cfood-card-title"><?php echo htmlspecialchars($name); ?></h3>
                                <p class="cfood-card-desc"><?php echo htmlspecialchars($desc); ?></p>
                                <p class="cfood-card-price">
                                    Rp <?php echo number_format($price, 0, ',', '.'); ?>
                                </p>
                                <p class="cfood-card-stock <?php echo $stock ? 'in' : 'out'; ?>">
                                    <?php echo $stock ? 'Tersedia' : 'Out of stock'; ?>
                                </p>
                            </div>
                            <div class="cfood-card-right">
                                <div class="cfood-card-thumb">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="">
                                </div>
                                <button
                                    class="cfood-add-btn"
                                    data-id="<?php echo (int)$p['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($name); ?>"
                                    data-price="<?php echo $price; ?>"
                                    <?php echo !$stock ? 'disabled' : ''; ?>
                                >
                                    Tambah
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada data snack C.Food. Silakan tambahkan dulu di tabel <strong>cfood_items</strong>.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- KERANJANG SAYA (C.Food) -->
        <div class="cfood-cart">
            <!-- form ini yang akan dikirim ke PHP -->
            <form id="cfood-checkout-form" method="post" action="cfood_checkout.php">
                <div class="cfood-cart-header">
                    <span>🛒 Keranjang Saya</span>
                </div>

                <div id="cfood-cart-empty">
                    Tambah cemilan yang mau dipesan, nanti akan muncul di sini.
                </div>

                <div id="cfood-cart-items" style="display:none; flex-direction:column; gap:6px;"></div>

                <div class="cfood-cart-footer">
                    <div id="cfood-cart-count">0 item dipilih</div>
                    <div id="cfood-cart-total">Rp 0</div>
                </div>

                <!-- HIDDEN FIELD: diisi JS dengan JSON item -->
                <input type="hidden" name="items_json" id="cfood-items-json">
                <input type="hidden" name="edit_order_id" id="cfood-edit-order-id"
                       value="<?php echo $editOrder ? (int)$editOrder['id'] : ''; ?>">

                <button type="submit" id="cfood-cart-submit" class="cfood-cart-submit" disabled>
                    <?php echo $isEditing ? 'Perbarui Pesanan' : 'Lanjut'; ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php if ($editOrder): ?>
<script>
    // Data lama untuk diprefill ke keranjang oleh main.js
    window.CFOOD_EDIT_ITEMS = <?php
        echo json_encode(json_decode($editOrder['items_json'], true));
    ?>;
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>