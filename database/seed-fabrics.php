<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$categories = [
    [
        'name' => 'Silk Collection',
        'slug' => 'silk-collection',
        'description' => 'Pure and blended wholesale silks including Raw Silk, Charmeuse, Sheesha, and Thai Silk.',
    ],
    [
        'name' => 'Lawn & Cotton',
        'slug' => 'lawn-cotton',
        'description' => 'Finest combed cottons and high-thread-count Swiss & China lawn fabrics for summer collections.',
    ],
    [
        'name' => 'Chiffon & Organza',
        'slug' => 'chiffon-organza',
        'description' => 'Airy, sheer, and shimmering chiffon and organza fabrics for luxury dupattas and bridal pret.',
    ],
    [
        'name' => 'Linen Collection',
        'slug' => 'linen-collection',
        'description' => 'Authentic Irish and everyday blended linen fabrics offering superior breathability and texture.',
    ],
    [
        'name' => 'Net & Karandi',
        'slug' => 'net-karandi',
        'description' => 'Heritage cotton net, slub net, Khaadi net, and winter silk karandi fabrics.',
    ],
    [
        'name' => 'Velvet Collection',
        'slug' => 'velvet-collection',
        'description' => 'Plush Micro Velvet 9000 for winter bridal, formal jackets, and royal shawls.',
    ],
];

$catMap = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute([$cat['slug']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $up = $pdo->prepare('UPDATE categories SET name = ?, description = ?, is_active = 1 WHERE id = ?');
        $up->execute([$cat['name'], $cat['description'], $id]);
        $catMap[$cat['name']] = (int) $id;
    } else {
        $ins = $pdo->prepare('INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, 1)');
        $ins->execute([$cat['name'], $cat['slug'], $cat['description']]);
        $catMap[$cat['name']] = (int) $pdo->lastInsertId();
    }
}

// Deactivate or archive placeholder product
$pdo->exec("UPDATE products SET status = 'archived' WHERE product_code = 'TESTING'");

$fabrics = [
    [
        'code' => 'BNT-AC-01',
        'title' => 'Airen Pure Chiffon',
        'category' => 'Chiffon & Organza',
        'description' => 'Premium high-density airy chiffon fabric crafted with ultra-fine yarns. Offers delicate transparency, graceful draping, and soft-touch breathability suitable for dupattas, sarees, luxury shirts, and evening wear.',
        'composition' => '100% High-Twist Polyester Chiffon',
        'width_inch' => 44.0,
        'weight_gsm' => 65,
        'unit' => 'meter',
        'price_pkr' => 680.00,
        'image' => 'wcf-airen-chiffon.webp',
    ],
    [
        'code' => 'BNT-AS-02',
        'title' => 'Amur Silk Charmeuse',
        'category' => 'Silk Collection',
        'description' => 'Luxurious smooth-lustre Amur silk with liquid drape and subtle natural sheen. Ideal for bridal wear, luxury formal kurtas, shirts, and modern evening attire.',
        'composition' => '100% Silk Blend',
        'width_inch' => 44.0,
        'weight_gsm' => 95,
        'unit' => 'meter',
        'price_pkr' => 1450.00,
        'image' => 'wcf-amur-silk.webp',
    ],
    [
        'code' => 'BNT-BC-03',
        'title' => 'Bareeze Combed Cotton Lawn',
        'category' => 'Lawn & Cotton',
        'description' => 'Signature Bareeze-standard combed pure cotton fabric with supreme breathable weave, soft handfeel, and maximum colorfastness for designer pret and summer ensembles.',
        'composition' => '100% Combed Cotton',
        'width_inch' => 54.0,
        'weight_gsm' => 90,
        'unit' => 'meter',
        'price_pkr' => 850.00,
        'image' => 'wcf-bareeze-cotton.jpg',
    ],
    [
        'code' => 'BNT-CL-04',
        'title' => 'Fine China Lawn 80s',
        'category' => 'Lawn & Cotton',
        'description' => 'Ultra-lightweight 80/80 count fine China lawn fabric with silky smooth texture, smooth weave, and high breathability for hot climates and luxury digital printing.',
        'composition' => '100% Superfine Cotton',
        'width_inch' => 52.0,
        'weight_gsm' => 78,
        'unit' => 'meter',
        'price_pkr' => 720.00,
        'image' => 'wcf-china-lawn.webp',
    ],
    [
        'code' => 'BNT-SO-05',
        'title' => 'Semi-Pure Shimmer Organza',
        'category' => 'Chiffon & Organza',
        'description' => 'Crisp lightweight semi-pure organza featuring ethereal sheen and structured hold. Ideal for embroidery, luxury sleeves, flared hems, and festive dupattas.',
        'composition' => '60% Silk, 40% Viscose',
        'width_inch' => 44.0,
        'weight_gsm' => 48,
        'unit' => 'meter',
        'price_pkr' => 920.00,
        'image' => 'wcf-copy-of-semi-pure-organza-fabric.jpg',
    ],
    [
        'code' => 'BNT-CN-06',
        'title' => 'Textured Cotton Net Slub',
        'category' => 'Net & Karandi',
        'description' => 'Premium cotton net woven with distinctive slub yarns for tactile surface interest. Breathable, semi-sheer, and sturdy for festive threadwork and applique work.',
        'composition' => '85% Cotton, 15% Poly Slub',
        'width_inch' => 48.0,
        'weight_gsm' => 105,
        'unit' => 'meter',
        'price_pkr' => 780.00,
        'image' => 'wcf-cotton-net-slub.webp',
    ],
    [
        'code' => 'BNT-RS-07',
        'title' => 'Imported Katan Raw Silk',
        'category' => 'Silk Collection',
        'description' => 'Heavyweight imported raw silk with rich slub texture, authentic tactile slubs, and structural volume. Perfect for festive trousers, sherwanis, and formal jackets.',
        'composition' => '100% Raw Silk Blend',
        'width_inch' => 44.0,
        'weight_gsm' => 120,
        'unit' => 'meter',
        'price_pkr' => 1650.00,
        'image' => 'wcf-imported-raw-silk.webp',
    ],
    [
        'code' => 'BNT-IR-08',
        'title' => 'Indian Raw Silk 45"',
        'category' => 'Silk Collection',
        'description' => 'Traditional wide-width 45-inch Indian raw silk featuring rich depth, natural striations, and balanced stiffness for formal shalwar kameez and lehengas.',
        'composition' => '100% Spun Raw Silk',
        'width_inch' => 45.0,
        'weight_gsm' => 115,
        'unit' => 'meter',
        'price_pkr' => 1550.00,
        'image' => 'wcf-indian-raw-silk-width-45-inches.webp',
    ],
    [
        'code' => 'BNT-IL-09',
        'title' => 'Pure Irish Slub Linen',
        'category' => 'Linen Collection',
        'description' => 'Authentic Irish weave linen with moisture-wicking properties, crisp textured handfeel, and exceptional durability for summer kurtas and men tailored suits.',
        'composition' => '100% Pure Flax Linen',
        'width_inch' => 56.0,
        'weight_gsm' => 140,
        'unit' => 'meter',
        'price_pkr' => 1350.00,
        'image' => 'wcf-irish-linen.webp',
    ],
    [
        'code' => 'BNT-LF-10',
        'title' => 'Dyed Everyday Linen Blend',
        'category' => 'Linen Collection',
        'description' => 'Soft-washed everyday linen fabric balanced for minimal creasing and maximum daily comfort. Ideal for RTW collections, shirts, trousers, and co-ord sets.',
        'composition' => '70% Linen, 30% Cotton',
        'width_inch' => 54.0,
        'weight_gsm' => 130,
        'unit' => 'meter',
        'price_pkr' => 980.00,
        'image' => 'wcf-linen-fabric.webp',
    ],
    [
        'code' => 'BNT-LS-11',
        'title' => 'Loir Lawn Silk Weave',
        'category' => 'Silk Collection',
        'description' => 'Hybrid blend combining the breathability of fine lawn with the subtle gleam of filament silk. Silky soft against the skin, wrinkle-resistant, and vibrant in dye uptake.',
        'composition' => '60% Cotton Lawn, 40% Silk',
        'width_inch' => 46.0,
        'weight_gsm' => 85,
        'unit' => 'meter',
        'price_pkr' => 1150.00,
        'image' => 'wcf-loir-lawn-silk.webp',
    ],
    [
        'code' => 'BNT-LL-12',
        'title' => 'Swiss Luxury Lawn 90s',
        'category' => 'Lawn & Cotton',
        'description' => 'Elite 90s count Swiss-finish luxury lawn with glass-like smoothness and cooling airflow. The premier choice for designer summer unstitched collections.',
        'composition' => '100% Extra-Long Staple Cotton',
        'width_inch' => 54.0,
        'weight_gsm' => 82,
        'unit' => 'meter',
        'price_pkr' => 890.00,
        'image' => 'wcf-luxury-lawn.webp',
    ],
    [
        'code' => 'BNT-MV-13',
        'title' => 'Micro Velvet 9000 Dyed',
        'category' => 'Velvet Collection',
        'description' => 'Heavyweight Micro Velvet 9000 with rich dense plush pile, deep royal barn red shade, and sumptuous soft finish for winter formals, shawls, and bridal jackets.',
        'composition' => '100% Micro Polyester Velvet',
        'width_inch' => 44.0,
        'weight_gsm' => 260,
        'unit' => 'meter',
        'price_pkr' => 1850.00,
        'image' => 'wcf-micro-velvet-9000-dyed-barn-red.webp',
    ],
    [
        'code' => 'BNT-KN-14',
        'title' => 'Pakistani Khaadi Net',
        'category' => 'Net & Karandi',
        'description' => 'Handloom-inspired Khaadi net with open-mesh structure and traditional rustic charm. Perfectly suited for festive zari embroidery and bridal shirts.',
        'composition' => '70% Cotton, 30% Silk Net',
        'width_inch' => 48.0,
        'weight_gsm' => 95,
        'unit' => 'meter',
        'price_pkr' => 820.00,
        'image' => 'wcf-pk-khaadi-net.webp',
    ],
    [
        'code' => 'BNT-PC-15',
        'title' => 'Pure Cotton Net Premium',
        'category' => 'Net & Karandi',
        'description' => 'Natural unbleached-ready pure cotton net weave. Soft, non-irritating, easy to dye in bespoke Pantone shades, ideal for formal linings and breathable overlays.',
        'composition' => '100% Natural Cotton',
        'width_inch' => 48.0,
        'weight_gsm' => 90,
        'unit' => 'meter',
        'price_pkr' => 750.00,
        'image' => 'wcf-pure-cotton-net.jpg',
    ],
    [
        'code' => 'BNT-RS-16',
        'title' => 'Ramen Silk Georgette',
        'category' => 'Silk Collection',
        'description' => 'Fluid georgette-grain Ramen silk offering high durability, graceful movement, and rich color penetration for flowy frocks, maxis, and blouses.',
        'composition' => '100% Silk Viscose Blend',
        'width_inch' => 44.0,
        'weight_gsm' => 100,
        'unit' => 'meter',
        'price_pkr' => 1280.00,
        'image' => 'wcf-ramen-silk.webp',
    ],
    [
        'code' => 'BNT-RR-17',
        'title' => 'Romani Raw Silk Slub',
        'category' => 'Silk Collection',
        'description' => 'Artisanal Romani raw silk woven with horizontal slub texture and medium crisp body. Excellent for formal culottes, straight pants, and structured kurtas.',
        'composition' => '80% Raw Silk, 20% Rayon',
        'width_inch' => 45.0,
        'weight_gsm' => 125,
        'unit' => 'meter',
        'price_pkr' => 1580.00,
        'image' => 'wcf-romani-raw-silk.jpg',
    ],
    [
        'code' => 'BNT-SS-18',
        'title' => 'Sheesha Silk Lustre',
        'category' => 'Silk Collection',
        'description' => 'High-gloss mirror-finish Sheesha silk with luminous reflections and lightweight supple drape. Perfect for party wear shirts, sarees, and metallic embellishments.',
        'composition' => '100% Filament Silk',
        'width_inch' => 44.0,
        'weight_gsm' => 90,
        'unit' => 'meter',
        'price_pkr' => 1390.00,
        'image' => 'wcf-sheesha-silk-1.webp',
    ],
    [
        'code' => 'BNT-SK-19',
        'title' => 'Traditional Silk Karandi',
        'category' => 'Net & Karandi',
        'description' => 'Heritage winter fabric woven with cross-spun silk and wool-karandi yarns. Delivers cozy warmth, textured surface, and regal royal feel for winter formals.',
        'composition' => '50% Silk, 50% Karandi Cotton',
        'width_inch' => 54.0,
        'weight_gsm' => 165,
        'unit' => 'meter',
        'price_pkr' => 1750.00,
        'image' => 'wcf-silk-karandi.jpg',
    ],
    [
        'code' => 'BNT-TS-20',
        'title' => 'Thai Silk Jacquard',
        'category' => 'Silk Collection',
        'description' => 'Exotic Thai silk known for its crisp texture, two-tone iridescent lustre, and sculptural drape. The premier choice for wedding ensembles and evening capes.',
        'composition' => '100% Hand-Dyed Thai Silk',
        'width_inch' => 45.0,
        'weight_gsm' => 110,
        'unit' => 'meter',
        'price_pkr' => 1620.00,
        'image' => 'wcf-thai-silk.webp',
    ],
];

echo "Seeded " . count($fabrics) . " fabrics into the Bunat Fabrics catalog.\n";
