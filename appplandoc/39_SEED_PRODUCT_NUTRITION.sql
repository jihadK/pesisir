-- =====================================================================
-- PATCH 39 (SEED): Populate nutrition_info untuk produk seafood umum
-- DESC : Match by nama produk (ILIKE pattern). Aman di-rerun — UPDATE
--        based on pattern, kalau produk belum ada → skip otomatis.
-- DEPS : Patch 38 (kolom nutrition_info & badge) harus sudah dijalankan.
-- =====================================================================

BEGIN;

-- =====================================================================
-- IKAN PUTIH (Kakap, Baraccuda, Dorang, Gulama, Patin Laut, Sunglir)
-- Tinggi protein, omega-3 sedang, vitamin B12 & D
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"Mengandung sekitar 20-22g protein per 100g. Baik untuk pertumbuhan otot dan regenerasi sel tubuh."},
  {"label":"Omega-3","icon":"spa","detail":"Sumber asam lemak omega-3 (EPA & DHA) yang baik untuk kesehatan jantung dan otak."},
  {"label":"Vitamin D","icon":"wb_sunny","detail":"Membantu penyerapan kalsium dan menjaga kesehatan tulang. Mendukung sistem imun."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Penting untuk pembentukan sel darah merah dan fungsi saraf yang optimal."},
  {"label":"Selenium","icon":"shield","detail":"Mineral antioksidan yang melindungi sel dari kerusakan radikal bebas."}
]'::jsonb
WHERE deleted_date IS NULL
  AND (
       name ILIKE '%kakap%'
    OR name ILIKE '%baraccuda%' OR name ILIKE '%barakuda%'
    OR name ILIKE '%dorang%'
    OR name ILIKE '%gulama%'
    OR name ILIKE '%patin%'
    OR name ILIKE '%sunglir%'
  );

-- =====================================================================
-- CUMI / SOTONG (Baby Cumi, Bekutak, Cumi-cumi)
-- Tinggi protein, rendah lemak, kaya mineral tembaga & selenium
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"15-17g protein per 100g. Baik untuk diet tinggi protein rendah lemak."},
  {"label":"Rendah Kalori","icon":"monitor_weight","detail":"Hanya sekitar 92 kalori per 100g. Cocok untuk program diet sehat."},
  {"label":"Tembaga (Cu)","icon":"science","detail":"Sumber tembaga yang baik untuk produksi sel darah merah dan kesehatan saraf."},
  {"label":"Selenium","icon":"shield","detail":"Mineral antioksidan kuat yang mendukung fungsi kelenjar tiroid."},
  {"label":"Vitamin B2","icon":"local_pharmacy","detail":"Riboflavin yang membantu metabolisme energi dan kesehatan kulit."}
]'::jsonb
WHERE deleted_date IS NULL
  AND (
       name ILIKE '%cumi%'
    OR name ILIKE '%bekutak%'
    OR name ILIKE '%sotong%'
  );

-- =====================================================================
-- UDANG (Udang Laut, Udang Windu, Udang Vannamei)
-- Tinggi protein, astaxanthin, omega-3
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"24g protein per 100g — salah satu sumber protein hewani tertinggi."},
  {"label":"Astaxanthin","icon":"spa","detail":"Antioksidan kuat yang melawan radikal bebas, baik untuk kesehatan kulit dan mata."},
  {"label":"Omega-3","icon":"favorite","detail":"Asam lemak yang menyehatkan jantung dan menurunkan risiko penyakit kardiovaskular."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Esensial untuk fungsi otak dan pembentukan sel darah."},
  {"label":"Iodine","icon":"science","detail":"Penting untuk fungsi tiroid dan metabolisme yang sehat."},
  {"label":"Zinc","icon":"shield","detail":"Mendukung sistem imun dan penyembuhan luka."}
]'::jsonb
WHERE deleted_date IS NULL
  AND name ILIKE '%udang%';

-- =====================================================================
-- KERANG (Kerang Dara, Kerang Hijau)
-- Tinggi zat besi, B12, omega-3
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Zat Besi","icon":"bloodtype","detail":"Sumber zat besi heme yang mudah diserap tubuh — mencegah anemia."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Sangat tinggi B12 (lebih dari 10x kebutuhan harian per 100g) — vital untuk saraf & energi."},
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"14-18g protein per 100g dengan lemak sangat rendah."},
  {"label":"Omega-3","icon":"spa","detail":"DHA & EPA untuk kesehatan jantung dan fungsi otak."},
  {"label":"Mangan","icon":"science","detail":"Mineral untuk metabolisme tulang dan produksi kolagen."}
]'::jsonb
WHERE deleted_date IS NULL
  AND name ILIKE '%kerang%';

-- =====================================================================
-- TELUR IKAN (Caviar, Roe)
-- Sangat tinggi omega-3, B12, vitamin D
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Sangat Tinggi Omega-3","icon":"spa","detail":"Salah satu sumber omega-3 alami paling padat — sekitar 6-7g per 100g."},
  {"label":"Vitamin D","icon":"wb_sunny","detail":"Mendukung kesehatan tulang dan sistem imun. Penting di iklim tropis dalam ruangan."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Sumber B12 sangat tinggi untuk energi dan fungsi saraf."},
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"24-28g protein berkualitas tinggi per 100g."},
  {"label":"Choline","icon":"psychology","detail":"Nutrisi penting untuk perkembangan otak, baik untuk ibu hamil & menyusui."}
]'::jsonb
WHERE deleted_date IS NULL
  AND (name ILIKE '%telur ikan%' OR name ILIKE '%caviar%' OR name ILIKE '%roe%');

-- =====================================================================
-- IKAN BERMINYAK (Salmon, Tuna, Tongkol, Cakalang, Tenggiri, Kembung)
-- Sangat tinggi omega-3, vitamin D, B12
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Sangat Tinggi Omega-3","icon":"spa","detail":"2-3g omega-3 per 100g — salah satu ikan paling sehat untuk jantung."},
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"22-26g protein per 100g, lengkap dengan asam amino esensial."},
  {"label":"Vitamin D","icon":"wb_sunny","detail":"Memenuhi sebagian besar kebutuhan harian vitamin D dalam 1 porsi."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Sumber B12 yang sangat baik untuk produksi energi dan sel darah."},
  {"label":"Selenium","icon":"shield","detail":"Antioksidan yang mendukung fungsi tiroid dan sistem imun."},
  {"label":"DHA untuk Otak","icon":"psychology","detail":"Mendukung perkembangan & kesehatan otak — penting untuk anak & lansia."}
]'::jsonb
WHERE deleted_date IS NULL
  AND (
       name ILIKE '%salmon%'
    OR name ILIKE '%tuna%'
    OR name ILIKE '%tongkol%'
    OR name ILIKE '%cakalang%'
    OR name ILIKE '%tenggiri%'
    OR name ILIKE '%kembung%'
    OR name ILIKE '%makarel%'
  );

-- =====================================================================
-- KEPITING / RAJUNGAN
-- Tinggi protein, omega-3, zinc, selenium
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"18-20g protein per 100g, low fat dan low carb."},
  {"label":"Omega-3","icon":"spa","detail":"Asam lemak sehat untuk kesehatan jantung dan otak."},
  {"label":"Zinc","icon":"shield","detail":"Tinggi zinc untuk sistem imun, penyembuhan luka, dan kesehatan kulit."},
  {"label":"Vitamin B12","icon":"local_pharmacy","detail":"Penting untuk produksi sel darah merah dan fungsi saraf."},
  {"label":"Selenium","icon":"science","detail":"Antioksidan kuat yang mendukung fungsi tiroid."}
]'::jsonb
WHERE deleted_date IS NULL
  AND (name ILIKE '%kepiting%' OR name ILIKE '%rajungan%' OR name ILIKE '%crab%');

-- =====================================================================
-- DEFAULT FALLBACK: Produk seafood lain yang belum ke-cover
-- Gunakan nutrition info ikan laut generik
-- =====================================================================

UPDATE tbm_products SET nutrition_info = '[
  {"label":"Tinggi Protein","icon":"fitness_center","detail":"Sumber protein hewani berkualitas tinggi untuk pertumbuhan dan pemeliharaan otot."},
  {"label":"Omega-3","icon":"spa","detail":"Asam lemak esensial untuk kesehatan jantung dan otak."},
  {"label":"Vitamin & Mineral","icon":"local_pharmacy","detail":"Mengandung berbagai vitamin (B12, D) dan mineral (selenium, iodine) yang penting bagi tubuh."}
]'::jsonb
WHERE deleted_date IS NULL
  AND nutrition_info IS NULL;

-- =====================================================================
-- Verifikasi
-- =====================================================================

SELECT
    name,
    badge,
    jsonb_array_length(nutrition_info) AS nutrition_count
  FROM tbm_products
 WHERE deleted_date IS NULL
 ORDER BY name;

COMMIT;

SELECT 'Patch 39 applied: nutrition_info populated for all products.' AS status;
