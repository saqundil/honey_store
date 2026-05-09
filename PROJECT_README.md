# مشروع Honey Store — Treasures Kyrgyzstan

## نظرة عامة
متجر إلكتروني لبيع العسل مبني بـ **Laravel 11 + Tailwind CSS + Vite + GSAP**.
يدعم لغتين (إنجليزي + عربي مع RTL) ويعتمد تصميم مستوحى من Figma.

## التقنيات
- **Backend**: PHP 8.2+, Laravel 11
- **Frontend**: Tailwind CSS (ألوان مخصصة باسم honey.*), Vite, GSAP (أنيميشن النحلة في Hero)
- **خطوط**: Open Sans, Open Sans Condensed, Cairo (للعربي)
- **قاعدة بيانات**: MySQL (جداول: users, products, orders, cache, jobs)

---

## هيكل المشروع

### Routes (routes/web.php)
| Method | URI                        | الوظيفة                           |
|--------|----------------------------|-----------------------------------|
| GET    | /                          | الصفحة الرئيسية (pages.home)      |
| GET    | /products/{slug}           | صفحة تفاصيل المنتج               |
| POST   | /products/{slug}/order     | إرسال طلب شراء                    |
| GET    | /locale/{locale}           | تبديل اللغة (en/ar)              |
| GET    | /products → redirect /#products | توجيه لقسم المنتجات          |
| GET    | /about                     | صفحة من نحن (PageController)      |
| GET    | /contact                   | صفحة تواصل معنا (PageController)  |
| GET    | /faq                       | صفحة الأسئلة الشائعة              |
| GET    | /shipping                  | صفحة الشحن والتوصيل               |
| GET    | /privacy                   | سياسة الخصوصية                    |
| GET    | /terms                     | الشروط والأحكام                   |

### Models

#### Product
- **الحقول**: id, slug, image, sku, price_value, currency, currency_position, price_decimals, sort_order, is_active, translations (JSON), timestamps
- **translations (JSON)**: يخزن ترجمات كل لغة بالشكل:
  ```json
  {
    "en": { "name": "...", "excerpt": "...", "description": "...", "badge": "...", "category": "...", "tags": [...] },
    "ar": { "name": "...", "excerpt": "...", "description": "...", "badge": "...", "category": "...", "tags": [...] }
  }
  ```
- **Scopes**: `scopeActive()` → المنتجات النشطة مرتبة حسب sort_order
- **Methods**: `translation()`, `localized($field)`, `formattedPrice()`, `toLocalizedArray()`

#### Order
- **الحقول**: id, product_id (FK), customer_name, email, phone, quantity, notes, locale, unit_price, total_price, currency, currency_position, price_decimals, status (default: pending), timestamps
- **العلاقة**: belongsTo Product

### Controllers

#### PageController
- `about()`: صفحة من نحن
- `contact()`: صفحة تواصل معنا
- `faq()`: الأسئلة الشائعة
- `shipping()`: الشحن والتوصيل
- `privacy()`: سياسة الخصوصية
- `terms()`: الشروط والأحكام
- جميع الدوال ترجع View مباشرة بدون بيانات ديناميكية

#### ProductController
- `show($slug)`: يعرض صفحة المنتج مع 4 منتجات ذات صلة
- `order($slug)`: يعالج نموذج الطلب مع Validation:
  - customer_name (required, max:120)
  - email (required, email, max:120)
  - phone (required, max:40)
  - quantity (required, integer, 1-100)
  - notes (nullable, max:500)
  - يحفظ لقطة من السعر والعملة وقت الطلب

### Middleware
- **SetLocale**: يقرأ اللغة من الـ session ويطبقها (en أو ar)

---

## Blade Views (resources/views/)

### التخطيط: layouts/app.blade.php
- يدعم RTL تلقائياً حسب اللغة
- يحمّل Google Fonts + Vite assets

### الصفحات:
- **pages/home.blade.php**: الصفحة الرئيسية — تجمع كل الأقسام عبر components
- **pages/product-show.blade.php**: صفحة تفاصيل المنتج — معرض صور (5 thumbnails)، 3 tabs (وصف، معلومات إضافية، مراجعات)، stepper للكمية مع حساب سعر ديناميكي، نموذج طلب، زر واتساب، منتجات مشابهة
- **pages/about.blade.php**: صفحة من نحن — قصة الشركة وفريق العمل
- **pages/contact.blade.php**: صفحة تواصل معنا — نموذج تواصل ومعلومات الاتصال
- **pages/faq.blade.php**: الأسئلة الشائعة — accordion بالأسئلة والأجوبة
- **pages/shipping.blade.php**: سياسة الشحن والتوصيل
- **pages/privacy.blade.php**: سياسة الخصوصية
- **pages/terms.blade.php**: الشروط والأحكام

### الـ Components:
| Component            | الوظيفة                                       |
|----------------------|------------------------------------------------|
| navbar               | هيدر ثابت + قائمة موبايل + تبديل لغة          |
| hero                 | بانر رئيسي مع أنيميشن نحل (GSAP + SVG paths)  |
| mission              | فيديو + نص المهمة + توقيع                      |
| news                 | آخر الأخبار (3 مقالات من ملف اللغة)            |
| products             | شبكة منتجات (1/2/3 أعمدة حسب الشاشة)          |
| testimonial          | شهادة عميل                                      |
| gallery              | معرض 6 صور                                      |
| newsletter           | نموذج اشتراك بالنشرة البريدية                   |
| honey-types          | 4 أنواع عسل (بطاقات)                            |
| bees-section         | قسم ديكوري مع 8 نحلات متحركة                    |
| page-hero            | بانر علوي للصفحات الداخلية (عنوان + breadcrumb)  |
| footer               | 4 أعمدة: معلومات، منتجات، أخبار، روابط         |

---

## ملفات اللغة (lang/)
- **lang/en/home.php** و **lang/ar/home.php**
- تحتوي على كل النصوص: brand, nav, hero, mission, news, products, product_page, testimonial, gallery, newsletter, types, footer
- المنتجات تُعرَّف أولاً في ملفات اللغة ثم يُزامنها ProductSeeder إلى قاعدة البيانات

## Seeder
- **ProductSeeder**: يقرأ المنتجات من ملفات اللغة (en + ar)، يدمج الترجمات في حقل translations JSON، يحذف المنتجات غير الموجودة في الملفات، ويُنشئ/يُحدّث الباقي داخل DB transaction.

---

## التنسيقات (CSS)
- **resources/css/app.css**: Tailwind مع طبقة @layer components تحتوي على:
  - أنماط بطاقات المنتجات (.product-shell, .product-panel, .product-chip...)
  - نظام تصميم Figma (.figma-product-title, .figma-gallery-*, .figma-tab-*, .figma-form-*, .figma-quantity-box, .figma-cart-button, .figma-submit-button, .figma-whatsapp-button, .figma-related-card...)
  - أنيميشن النحل (hero-bee-float-fast/medium/large)
  - دعم reduced-motion
  - قواعد RTL للعربي

## ألوان Tailwind المخصصة (tailwind.config.js)
```
honey.dark: #131313, honey.grey: #383838, honey.muted: #5B5858
honey.nav: #3C3C3C, honey.orange: #C74817, honey.gold: #D3A863
honey.cream: #F7F3F0, honey.card: #FCFAF9
```

## JavaScript (resources/js/)
- **app.js**: أنيميشن GSAP للنحلة (MotionPathPlugin + SVG paths)، يتجاوب مع الشاشة، يحترم prefers-reduced-motion
- **bootstrap.js**: إعداد Axios

---

## ملاحظات مهمة
- قسم المنتجات في الصفحة الرئيسية يحتوي على `id="products"` مع scroll-margin لتفادي الاختفاء تحت الـ navbar الثابت
- صفحة المنتج تحتوي على JS مباشر (vanilla) للتعامل مع: تبديل صور المعرض، تحديث الكمية والسعر، توليد رابط واتساب، تبديل التابات
- رقم واتساب: 962775392581
- الأصول في public/images/ و public/videos/ و public/build/
