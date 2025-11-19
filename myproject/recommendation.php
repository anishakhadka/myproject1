<?php
session_start();
require_once 'db_connect.php';

/**
 * Helper: safe html escape
 */
function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

/**
 * 1) Redirect if total sold across products > 30
 */
$totalSold = 0;
$resTotal = $conn->query("SELECT SUM(sold) AS total_sold FROM recommendation_clothes");
if ($resTotal !== false) {
    $rowTotal = $resTotal->fetch_assoc();
    $totalSold = (int)($rowTotal['total_sold'] ?? 0);
} else {
    error_log("recommendation.php: SUM(sold) query failed: " . $conn->error);
}

if ($totalSold > 30) {
    header("Location: index.php");
    exit;
}

/**
 * 2) Session initialization & restart logic
 */
if (!isset($_SESSION['step'])) $_SESSION['step'] = 1;
if (!isset($_SESSION['recommendation'])) $_SESSION['recommendation'] = [];

if (isset($_GET['restart']) && $_GET['restart'] == 1) {
    $_SESSION['step'] = 1;
    $_SESSION['recommendation'] = [];
    header("Location: recommendation.php");
    exit;
}

/**
 * 3) Handle POST submissions (PRG pattern)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $k => $v) {
        if (in_array($k, ['skin_tone','color','body_type','gender','category'])) {
            $_SESSION['recommendation'][$k] = trim($v);
        }
    }
    $_SESSION['step'] = ($_SESSION['step'] ?? 1) + 1;
    header("Location: recommendation.php");
    exit;
}

$step = $_SESSION['step'] ?? 1;
$rec = $_SESSION['recommendation'] ?? [];

/**
 * 4) Fetch recommendations at final step (step 6)
 */
$recommendedClothes = [];

if ($step === 6) {
    $skin_tone = $rec['skin_tone'] ?? '';
    $color = $rec['color'] ?? '';
    $body_type = $rec['body_type'] ?? '';
    $gender = $rec['gender'] ?? '';
    $category = $rec['category'] ?? 'all';

    if ($category === 'all') {
        $sql = "SELECT * FROM recommendation_clothes WHERE skin_tone=? AND color=? AND body_type=? AND gender=?";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $stmt->bind_param("ssss", $skin_tone, $color, $body_type, $gender);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendedClothes[] = $row;
                }
                $res->free();
            }
            $stmt->close();
        }
    } else {
        $sql = "SELECT * FROM recommendation_clothes WHERE skin_tone=? AND color=? AND body_type=? AND gender=? AND category=?";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $stmt->bind_param("sssss", $skin_tone, $color, $body_type, $gender, $category);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendedClothes[] = $row;
                }
                $res->free();
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Style Your Dream Style</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #f5f5f5;
        margin: 0
    }

    nav {
        background: #333;
        padding: 8px
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 12px;
        align-items: center;
        margin: 0;
        padding: 0;
        justify-content: center
    }

    nav a {
        color: #fff;
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 4px
    }

    nav a.active,
    nav a:hover {
        background: #555
    }

    .container {
        max-width: 1000px;
        margin: 24px auto;
        background: #fff;
        padding: 22px;
        border-radius: 8px
    }

    h2 {
        text-align: center;
        margin-top: 0
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center
    }

    .cards {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center
    }

    .card {
        width: 200px;
        background: #fff;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        text-align: center
    }

    .card img {
        width: 100%;
        height: 140px;
        object-fit: cover;
        border-radius: 6px
    }

    select,
    button {
        padding: 10px;
        font-size: 16px;
        width: 60%
    }

    button {
        background: #007bff;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer
    }

    button:hover {
        background: #0056b3
    }

    .product-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        justify-content: center;
        margin-top: 18px
    }

    .product {
        width: 220px;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06)
    }

    .product img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 6px
    }

    .add-cart {
        margin-top: 8px;
        padding: 8px 12px;
        background: #28a745;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer
    }

    .add-cart:hover {
        background: #218838
    }

    .small {
        font-size: 0.9rem;
        color: #666
    }

    .center {
        text-align: center
    }

    @media (max-width:600px) {

        select,
        button {
            width: 92%
        }
    }
    </style>
</head>

<body>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a class="active" href="recommendation.php">Recommend</a></li>
            <li><a href="men.php">Men</a></li>
            <li><a href="women.php">Women</a></li>
            <li><a href="kids.php">Kids</a></li>
            <li><a href="sale.php">Sale</a></li>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Style Your Dream Style</h2>

        <?php if ($step === 1): ?>
        <form method="POST">
            <label class="small">Select your skin tone:</label>
            <div class="cards">
                <?php
                $tones = [
                    'fair' => ['label'=>'Fair','img'=>'uploads/skin_fair.jpg'],
                    'medium' => ['label'=>'Medium','img'=>'uploads/skin_medium.jpg'],
                    'olive' => ['label'=>'Olive','img'=>'uploads/skin_olive.jpg'],
                    'dark' => ['label'=>'Dark','img'=>'uploads/skin_dark.jpg'],
                ];
                foreach ($tones as $key => $info):
                ?>
                <label class="card">
                    <img src="<?php echo h($info['img']); ?>" alt="<?php echo h($info['label']); ?>">
                    <div><?php echo h($info['label']); ?></div>
                    <input type="radio" name="skin_tone" value="<?php echo h($key); ?>" required
                        <?php if(isset($rec['skin_tone']) && $rec['skin_tone']==$key) echo 'checked'; ?>>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Next</button>
        </form>

        <?php elseif ($step === 2): ?>
        <form method="POST">
            <label class="small">Choose a color that suits your skin tone:</label>
            <?php
            $colorsByTone = [
                'fair' => [
                    ['value'=>'White','label'=>'White','img'=>'uploads/color_white.jpg'],
                    ['value'=>'Pastel Pink','label'=>'Pastel Pink','img'=>'uploads/color_pastel_pink.jpg'],
                    ['value'=>'Light Blue','label'=>'Light Blue','img'=>'uploads/color_light_blue.jpg'],
                    ['value'=>'Beige','label'=>'Beige','img'=>'uploads/color_beige.jpg']
                ],
                'medium' => [
                    ['value'=>'Coral','label'=>'Coral','img'=>'uploads/color_coral.jpg'],
                    ['value'=>'Olive','label'=>'Olive','img'=>'uploads/color_olive.jpg'],
                    ['value'=>'Navy','label'=>'Navy','img'=>'uploads/color_navy.jpg'],
                    ['value'=>'Turquoise','label'=>'Turquoise','img'=>'uploads/color_turquoise.jpg']
                ],
                'olive' => [
                    ['value'=>'Orange','label'=>'Orange','img'=>'uploads/color_orange.jpg'],
                    ['value'=>'Warm Brown','label'=>'Warm Brown','img'=>'uploads/color_warm_brown.jpg'],
                    ['value'=>'Khaki','label'=>'Khaki','img'=>'uploads/color_khaki.jpg'],
                    ['value'=>'Mustard','label'=>'Mustard','img'=>'uploads/color_mustard.jpg']
                ],
                'dark' => [
                    ['value'=>'Bright Yellow','label'=>'Bright Yellow','img'=>'uploads/color_bright_yellow.jpg'],
                    ['value'=>'Royal Blue','label'=>'Royal Blue','img'=>'uploads/color_royal_blue.jpg'],
                    ['value'=>'Hot Pink','label'=>'Hot Pink','img'=>'uploads/color_hot_pink.jpg'],
                    ['value'=>'White','label'=>'White','img'=>'uploads/color_white.jpg']
                ]
            ];
            $chosenTone = $rec['skin_tone'] ?? '';
            $options = $colorsByTone[$chosenTone] ?? [];
            ?>
            <div class="cards">
                <?php foreach ($options as $opt): ?>
                <label class="card">
                    <?php if (!empty($opt['img'])): ?>
                    <img src="<?php echo h($opt['img']); ?>" alt="<?php echo h($opt['label']); ?>">
                    <?php endif; ?>
                    <div><?php echo h($opt['label']); ?></div>
                    <input type="radio" name="color" value="<?php echo h($opt['value']); ?>" required
                        <?php if(isset($rec['color']) && $rec['color']==$opt['value']) echo 'checked'; ?>>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Next</button>
        </form>

        <?php elseif ($step === 3): ?>
        <form method="POST">
            <label class="small">Select your body type:</label>
            <div class="cards">
                <?php
                $bodies = [
                    'rectangle' => ['label'=>'Rectangle','img'=>'uploads/body_rectangle.jpg'],
                    'inverted_triangle' => ['label'=>'Inverted Triangle','img'=>'uploads/body_inverted_triangle.jpg'],
                    'hourglass' => ['label'=>'Hourglass','img'=>'uploads/body_hourglass.jpg'],
                    'pear' => ['label'=>'Pear','img'=>'uploads/body_pear.jpg'],
                    'apple' => ['label'=>'Apple','img'=>'uploads/body_apple.jpg'],
                ];
                foreach ($bodies as $k=>$v):
                ?>
                <label class="card">
                    <img src="<?php echo h($v['img']); ?>" alt="<?php echo h($v['label']); ?>">
                    <div><?php echo h($v['label']); ?></div>
                    <input type="radio" name="body_type" value="<?php echo h($k); ?>" required
                        <?php if(isset($rec['body_type']) && $rec['body_type']==$k) echo 'checked'; ?>>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Next</button>
        </form>

        <?php elseif ($step === 4): ?>
        <form method="POST">
            <label class="small">Select your gender:</label>
            <div class="cards">
                <?php
                $genders = [
                    'male' => ['label'=>'Male','img'=>'uploads/gender_male.jpg'],
                    'female' => ['label'=>'Female','img'=>'uploads/gender_female.jpg'],
                    'kids' => ['label'=>'Kids','img'=>'uploads/gender_kids.jpg'],
                ];
                foreach ($genders as $k=>$v):
                ?>
                <label class="card">
                    <img src="<?php echo h($v['img']); ?>" alt="<?php echo h($v['label']); ?>">
                    <div><?php echo h($v['label']); ?></div>
                    <input type="radio" name="gender" value="<?php echo h($k); ?>" required
                        <?php if(isset($rec['gender']) && $rec['gender']==$k) echo 'checked'; ?>>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Next</button>
        </form>

        <?php elseif ($step === 5): ?>
        <form method="POST">
            <label class="small">Select category:</label>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <label class="card" style="width:150px;">
                    <img src="uploads/top_sample.jpg" alt="Top" style="height:110px">
                    <div>Top</div>
                    <input type="radio" name="category" value="top" required
                        <?php if(isset($rec['category']) && $rec['category']=='top') echo 'checked'; ?>>
                </label>

                <label class="card" style="width:150px;">
                    <img src="uploads/bottom_sample.jpg" alt="Bottom" style="height:110px">
                    <div>Bottom</div>
                    <input type="radio" name="category" value="bottom" required
                        <?php if(isset($rec['category']) && $rec['category']=='bottom') echo 'checked'; ?>>
                </label>

                <label class="card" style="width:150px;">
                    <img src="uploads/footwear_sample.jpg" alt="Footwear" style="height:110px">
                    <div>Footwear</div>
                    <input type="radio" name="category" value="footwear" required
                        <?php if(isset($rec['category']) && $rec['category']=='footwear') echo 'checked'; ?>>
                </label>

                <label class="card" style="width:150px;">
                    <img src="uploads/all_sample.jpg" alt="All" style="height:110px">
                    <div>All</div>
                    <input type="radio" name="category" value="all" required
                        <?php if(isset($rec['category']) && $rec['category']=='all') echo 'checked'; ?>>
                </label>
            </div>
            <button type="submit">See Recommendations</button>
        </form>
        <?php elseif ($step === 6): ?>
        <h3 class="center">Recommended Clothes for You</h3>
        <div class="product-grid">
            <?php if (!empty($recommendedClothes)): ?>
            <?php foreach ($recommendedClothes as $row):
                    $product_name = $row['product_name'] ?? $row['name'] ?? 'No Name';
                    $img = !empty($row['image']) ? 'uploads/' . $row['image'] : 'uploads/all_sample.jpg';
                    $price = $row['price'] ?? 0;
                    $rating = $row['rating'] ?? 0;
                    $category = $row['category'] ?? 'N/A';
                    $product_id = $row['id'] ?? $row['product_id'] ?? 0;
                    $table_name = 'recommendation_clothes';
                ?>
            <div class="product">
                <img src="<?php echo h($img); ?>" alt="<?php echo h($product_name); ?>">
                <h3><?php echo h($product_name); ?></h3>
                <p>Rs. <?php echo number_format($price,2); ?></p>
                <p class="small">Category: <?php echo h(ucfirst($category)); ?></p>
                <p class="small">
                    <?php for($i=1;$i<=5;$i++): ?><?php echo ($i <= $rating) ? "⭐" : "☆"; ?><?php endfor; ?></p>
                <form method="POST" action="add_to_cart.php">
                    <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
                    <input type="hidden" name="table" value="<?php echo h($table_name); ?>">
                    <input type="hidden" name="redirect_back" value="cart.php">
                    <button type="submit" class="add-cart">Add to Cart</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="center">No recommendations found for your selections.</p>
            <?php endif; ?>
        </div>

        <p class="center" style="margin-top:16px;">
            <a href="recommendation.php?restart=1"><button>Take Quiz Again</button></a>
        </p>
        <?php endif; ?>
    </div>
</body>

</html>