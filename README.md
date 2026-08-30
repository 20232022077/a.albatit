# المنصة الإسلامية

منصة محتوى إسلامي عربية (RTL بالكامل) مبنية على Laravel 12 — تعرض الكتب والمحاضرات والبرامج والتأملات القرآنية وقرآنيات ومركزية القرآن وحائط منشورات، مع لوحة تحكم إدارية كاملة تدير كل شيء دون الحاجة لمس الكود: المحتوى، المستخدمون والصلاحيات، الإعدادات، السيو، النسخ الاحتياطي، وسجل العمليات.

---

## 1. ما تم إنجازه

بُني المشروع عبر 30 مرحلة عمل متتالية، كل مرحلة رُوجعت وفُحصت فعليًا عبر HTTP قبل اعتمادها. ملخص ما هو جاهز الآن:

**المحتوى العام**
- 8 أنواع محتوى: الكتب، المحاضرات، البرامج وحلقاتها، التأملات القرآنية، قرآنيات، مركزية القرآن، منشورات الحائط، السيرة الذاتية.
- تصنيفات هرمية، وسوم قابلة لإعادة الاستخدام، مكتبة وسائط مركزية.
- محرك بحث داخلي (Full-Text) يغطي كل الأنواع، ونظام Slugs آمن مع أرشفة الروابط القديمة (301 عند تغيير الرابط).
- نظام تضمين يوتيوب آمن (Video ID فقط، لا iframe يدوي، نطاق youtube-nocookie.com حصرًا).
- عرض PDF عبر بث محمي (لا رابط مباشر للملف، ولا يظهر إلا إذا كان المحتوى منشورًا).

**لوحة التحكم**
- إدارة كاملة (CRUD + نشر/إلغاء نشر + حذف ناعم + استعادة) لكل نوع محتوى.
- نظام أدوار وصلاحيات (RBAC) مرن بالكامل من الواجهة، بلا أي صلاحية مكتوبة بشكل ثابت في الكود.
- إعدادات موقع مركزية (الاسم، الوصف، الشعار، Favicon، التواصل، وسائل التواصل الاجتماعي، السيو، عدد العناصر بالصفحة) — قابلة للتوسع بلا أي migration جديدة.
- سجل عمليات (Activity Log) يسجل كل عملية إدارية حساسة تلقائيًا.
- نظام نسخ احتياطي كامل (قاعدة بيانات + ملفات) من داخل اللوحة، مع سياسة احتفاظ تلقائية.

**السيو والأداء**
- Meta Title/Description لكل صفحة، Canonical، Open Graph، Twitter Cards.
- Structured Data (JSON-LD): WebSite، BreadcrumbList، Article، Book، Person.
- Sitemap.xml وrobots.txt ديناميكيان، منع فهرسة لوحة التحكم والصفحات الحساسة.
- تخزين مؤقت (Cache) للتصنيفات وبيانات الصفحة الرئيسية والإعدادات، فهارس قاعدة بيانات لكل مسارات البحث والفرز الشائعة، ضغط الصور تلقائيًا (WebP/AVIF).

**الأمان**
- مراجعة أمنية كاملة (انظر القسم 17) شملت CSRF، XSS، SQL Injection، IDOR، Mass Assignment، Broken Access Control، رفع الملفات، Path Traversal، Brute Force، وSession Security — وأُصلحت كل ثغرة حقيقية اكتُشفت أثناء المراجعة.
- 83 اختبارًا آليًا (Feature + Unit) تعمل ضد قاعدة بيانات اختبار معزولة تمامًا عن بيانات الإنتاج، تُشغَّل عبر `php artisan test`.

---

## 2. هيكل المشروع

```
app/
  Console/Commands/       أوامر Artisan (make:admin, backup:run)
  Enums/                  ContentStatus (draft/published/unpublished)
  Http/Controllers/       متحكمات الموقع العام + Admin/ للوحة التحكم
  Http/Middleware/        CSP، هيدرات الأمان، فحص الحساب المفعّل
  Http/Requests/          Form Requests (تحقق + Authorization) لكل نموذج إدخال
  Models/                 نماذج Eloquent
  Policies/                سياسات التفويض (UserPolicy، RolePolicy)
  Search/                 محرك البحث (SearchEngine interface + EloquentSearchEngine)
  Support/                منطق مساعد: SafeFileUpload، SafeYoutube، ActivityLogger،
                           BackupService، SiteSettings، ContentUrl
database/
  migrations/              29 ملف ترحيل مرقّمة تسلسليًا (0001 → 0020 مع ملحقات)
  seeders/                 AccessControlSeeder (الصلاحيات + دور super-admin)، CategorySeeder
resources/
  views/                   Blade — layouts/ (app, public, admin, error)، وملفات كل قسم
  views/admin/             واجهات لوحة التحكم
  js/app.js                منطق JS غير المتداخل بالكامل (بلا أي onclick= داخل HTML)
  css/app.css               Tailwind v4
routes/
  web.php                  كل مسارات الموقع العام ولوحة التحكم
  console.php              الجدولة (Schedule) وأوامر Artisan المخصصة
tests/
  Feature/, Unit/           83 اختبارًا آليًا
config/backup.php           إعدادات النسخ الاحتياطي (سياسة الاحتفاظ، مسارات mysqldump)
config/cors.php              إعداد CORS مُحكم صراحة
```

**البنية المعمارية للمحتوى**: كل أنواع المحتوى (عدا الحائط والسيرة الذاتية إلى حد ما) تُبنى فوق جدول مضلع الأشكال (polymorphic) واحد هو `content_items` يحمل الحقول المشتركة (العنوان، الرابط، الحالة، تاريخ النشر، meta JSON)، وجدول فرعي مخصص لكل نوع (`books`، `lectures`، ...) مفتاحه الأساسي هو نفسه `content_item_id`. هذا يعني أن كل منطق النشر/الحذف الناعم/السيو/الوسوم/التصنيفات مكتوب **مرة واحدة** (في `App\Http\Controllers\Admin\Concerns\ManagesContentItems` وscope‑ات النموذج) ويُعاد استخدامه لكل الأنواع الثمانية.

---

## 3. الجداول

| الجدول | الغرض |
|---|---|
| `users` | المستخدمون (حذف ناعم، `is_active` لتعطيل الحساب فورًا) |
| `roles` / `permissions` / `permission_role` / `role_user` | نظام الأدوار والصلاحيات (RBAC) |
| `content_items` | الجدول المضلع المركزي لكل المحتوى (النوع، العنوان، الرابط، الحالة، meta) |
| `content_item_slugs` | أرشيف الروابط القديمة لإعادة التوجيه 301 |
| `books`, `lectures`, `programs`, `program_episodes`, `reflections`, `wall_posts`, `biographies` | بيانات خاصة بكل نوع محتوى، مرتبطة بـ `content_items` بنفس المفتاح الأساسي |
| `biography_sections` | عناصر السيرة الذاتية (مؤهلات/خبرات/إنجازات...) |
| `categories` | تصنيفات هرمية (شجرة عبر `parent_id`)، تشمل `quran-centrality` و`quraniyat` كتصنيفين خاصين يُستخدمان لتحديد نطاق تلك الأقسام |
| `tags` | وسوم قابلة لإعادة الاستخدام |
| `content_category`, `content_tag`, `content_media` | جداول ربط (pivot) |
| `media` | مكتبة الوسائط المركزية (صور/PDF/فيديو) مع متغيرات WebP/AVIF في `metadata` |
| `settings` | مخزن عام Key-Value لإعدادات الموقع — إضافة إعداد جديد لا تحتاج migration |
| `backups` | سجل النسخ الاحتياطية (الاسم، الحجم، الحالة، من أنشأها) |
| `activity_logs` | سجل العمليات الإدارية (polymorphic subject) |
| `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens` | جداول Laravel القياسية |

> **ملاحظة صراحة**: جداول `site_settings` و`user_profiles` و`quran_collections` و`quran_items` موجودة في مخطط قاعدة البيانات (من التصميم الأولي للمشروع) لكن **لا يستخدمها أي كود حاليًا** — استُبدلت بـ `settings` (للإعدادات) وبنمط `content_items` + تصنيف `quran-centrality`/`quraniyat` (للمحتوى القرآني). أُبقيت كما هي احترامًا لقاعدة "لا تحذف أي وظيفة"، لكنها مرشّحة لتنظيف مستقبلي إن رغبتم. راجع القسم 18.

---

## 4. العلاقات الأساسية بين النماذج

- `ContentItem` (polymorphic) → `hasOne` لكل من `Book`, `Lecture`, `Program`, `ProgramEpisode`, `Reflection`, `WallPost`, `Biography` (حسب `type`).
- `ContentItem` `belongsToMany` `Category` و`Tag` (عبر `content_category`/`content_tag`)، و`belongsToMany` `Media` (عبر `content_media`, بحقل `collection` يميز الغلاف عن المرفق).
- `Program` `hasMany` `ProgramEpisode` (كلاهما فوق `content_items`، الربط عبر `program_id`).
- `User` `belongsToMany` `Role`، و`Role` `belongsToMany` `Permission`.
- `User::hasPermission(string $permission)` و`User::hasRole(string $role)` — نقطة الفحص الوحيدة المستخدمة في كل الـ Policies وGates.
- `Backup belongsTo User` (`created_by`, قابل للـ null للنسخ التي ينشئها الجدول الزمني).
- `ActivityLog belongsTo User` و`morphTo subject` (يشير لأي نموذج تم تعديله).

---

## 5. أقسام الموقع

**عام (بلا تسجيل دخول):** الرئيسية، السيرة الذاتية، الكتب، المحاضرات، البرامج، التأملات، قرآنيات، مركزية القرآن، الحائط، البحث، `sitemap.xml`، `robots.txt`.

**لوحة التحكم (`/admin/*`, تتطلب دخولًا + صلاحية):** إدارة المحتوى (لكل نوع من الثمانية)، التصنيفات، الوسوم، الوسائط، المستخدمون، الأدوار والصلاحيات، إعدادات الموقع، سجل العمليات، النسخ الاحتياطية.

---

## 6. المستخدمون والأدوار

النظام أدوار متعددة وليس صلاحيات ثابتة على المستخدم مباشرة: **مستخدم ← دور واحد أو أكثر ← كل دور يحمل مجموعة صلاحيات**. الدور الوحيد المزروع افتراضيًا هو `super-admin` (كل الصلاحيات دون استثناء، عبر `Gate::before` — يتجاوز كل فحص). أي دور آخر يُنشأ من لوحة التحكم (`/admin/roles`) ويُمنح مجموعة صلاحيات مختارة.

**حماية مدمجة ضد تصعيد الصلاحيات** (مهم لفهم النظام قبل تعديل الأدوار):
- مستخدم لا يستطيع تعديل أو حذف حسابه الخاص.
- الدور `super-admin` نفسه لا يمكن تعديله أو حذفه من أي واجهة.
- من يدير المستخدمين (`users.update`) دون أن يكون super-admin **لا يستطيع** منح أحدهم دور `super-admin`.
- من يدير الأدوار (`roles.update`) دون أن يكون super-admin **لا يستطيع** منح صلاحية لا يملكها هو نفسه — لا يمكن "اختراع" دور أقوى مما يملك.

---

## 7. الصلاحيات

| الصلاحية | الوصف |
|---|---|
| `users.view` / `users.create` / `users.update` / `users.delete` | إدارة المستخدمين |
| `roles.view` / `roles.create` / `roles.update` / `roles.delete` | إدارة الأدوار والصلاحيات |
| `content.view` / `content.create` / `content.update` / `content.delete` | إدارة كل أنواع المحتوى (مشتركة بين الثمانية) |
| `content.publish` / `content.unpublish` | النشر وإلغاء النشر |
| `settings.manage` | إعدادات الموقع |
| `backups.manage` | إنشاء/عرض/تحميل/استعادة/حذف النسخ الاحتياطية |
| `activity_logs.view` | مشاهدة سجل العمليات |

---

## 8. Routes الرئيسية

**عام:**
`GET /`, `/books`, `/books/{slug}`, `/lectures`, `/lectures/{slug}`, `/programs`, `/programs/{slug}`, `/reflections`, `/reflections/{slug}`, `/quraniyat`, `/quraniyat/{slug}`, `/quran-centrality`, `/quran-centrality/{slug}`, `/wall`, `/biography`, `/search`, `/pdf/{media}`, `/sitemap.xml`, `/robots.txt`, `/login`.

**لوحة التحكم** (كل واحد منها له `index/create/store/edit/update/destroy/restore` + `publish/unpublish` لأنواع المحتوى):
`/admin/books`, `/admin/lectures`, `/admin/programs` (+ `/admin/programs/{program}/episodes`), `/admin/reflections`, `/admin/quraniyat`, `/admin/quran-centrality`, `/admin/wall-posts`, `/admin/categories`, `/admin/tags`, `/admin/media`, `/admin/users`, `/admin/roles`, `/admin/biography`, `/admin/settings`, `/admin/activity-logs`, `/admin/backups` (+ `/download`, `/restore`).

القائمة الكاملة والدقيقة (142 مسارًا) متاحة عبر: `php artisan route:list`

---

## 9. تثبيت المشروع

```bash
git clone <repo-url>
cd ahmed-albatit
composer install
npm install
cp .env.example .env
php artisan key:generate
```

---

## 10. إعداد قاعدة البيانات

1. أنشئ قاعدة بيانات MySQL/MariaDB فارغة (يفضَّل `utf8mb4` / `utf8mb4_unicode_ci` لدعم العربية كاملًا).
2. عدّل بيانات الاتصال في `.env` (القسم التالي).
3. شغّل الترحيل والبذور الأساسية:

```bash
php artisan migrate --force
php artisan db:seed --force
```

`php artisan db:seed` يشغّل `DatabaseSeeder` الذي يستدعي تلقائيًا `AccessControlSeeder` (كل الصلاحيات + دور `super-admin`) ثم `CategorySeeder` (تصنيفا "مركزية القرآن" و"قرآنيات" الأساسيان). **`AccessControlSeeder` ضروري** — بدونه لا يوجد أي صلاحيات ولا دور `super-admin`، ولن يعمل `php artisan make:admin` (القسم 13).

---

## 11. إعداد `.env`

انسخ `.env.example` وعدّل القيم التالية قبل الإطلاق الفعلي (كل سطر منها موثّق داخل الملف نفسه):

| المتغير | ماذا يجب فعله في Production |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | **`false` دائمًا** — القيمة الافتراضية في الكود نفسه `false` حتى لو نُسي هذا السطر |
| `APP_URL` | الدومين الحقيقي بـ `https://` |
| `APP_KEY` | يُولَّد تلقائيًا بـ `php artisan key:generate` — **لا تشاركه أبدًا ولا تنسخه من بيئة أخرى** |
| `DB_*` | بيانات اتصال قاعدة الإنتاج |
| `SESSION_SECURE_COOKIE` | `true` (يتطلب أن يكون الموقع فعليًا على HTTPS) |
| `SESSION_ENCRYPT` | `true` |
| `LOG_STACK` | `daily` (تدوير تلقائي للسجلات بدل ملف واحد ينمو للأبد) |
| `BACKUP_MYSQLDUMP_PATH` / `BACKUP_MYSQL_PATH` | فقط إن لم يكن `mysqldump`/`mysql` على PATH الخادم |
| `MAIL_*` | حاليًا `log` (لا يُرسل بريد فعليًا) — عدّلها فقط إذا احتجتم إشعارات بريدية مستقبلًا؛ لا شيء في المشروع الحالي يعتمد على البريد |

`.env` نفسه **مستبعد من Git بالكامل** (مؤكَّد عبر `.gitignore` وعبر فحص مباشر أن المستودع لا يحتوي عليه) — لا كلمات مرور ولا مفاتيح API ولا Secrets داخل GitHub في أي وقت.

---

## 12. تشغيل المشروع

**تطوير محلي:**
```bash
php artisan serve
npm run dev      # في نافذة طرفية أخرى، للـ Hot Reload
```

**إنتاج (بعد كل نشر/تحديث كود):**
```bash
composer install --no-dev --optimize-autoloader
npm run build                 # يبني resources/ إلى public/build (يجب أن يُخدَّم عبر الويب)
php artisan migrate --force   # آمن بذاته: كل migration جديد فقط يُضيف/يعدّل، ولا شيء يحذف بيانات موجودة
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

يحتاج الخادم أيضًا **Cron** واحد قياسي (لتفعيل الجدولة، أي النسخ الاحتياطي اليومي التلقائي):
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

⚠️ **على استضافات LiteSpeed/cPanel تحديدًا**: تأكد أن أمر `php` في الـ Cron يشغّل PHP بوضع **CLI** فعليًا، لا وضع CGI/FastCGI — بعض الحسابات يكون فيها `php` الموجود ضمن PATH إصدار CGI (يطبع ترويسات HTTP بدل تنفيذ الأمر). إن حدث هذا، استخدم مسار `lsphp` الصريح بدلًا من `php`:
```
* * * * * cd /path-to-project && /usr/local/bin/lsphp artisan schedule:run >> /dev/null 2>&1
```
كذلك، بعض خطط الاستضافة المشتركة تعيد توزيع توقيت الـ Cron تلقائيًا لتوزيع الحمل (فتحوّل مثلًا `* * * * *` إلى `8-59/15 * * * *`) — لهذا السبب بالتحديد تعتمد جدولة النسخة الاحتياطية في `routes/console.php` على `everyMinute()` مع شرط `when()` بدل `daily()->at('03:00')` الحساسة لدقيقة محددة.

---

## 13. إنشاء Administrator بأمان

**لا تُنشئ حساب المسؤول عبر إدخال مباشر في قاعدة البيانات أو Tinker** — هذا يتجاوز التحقق من صحة البيانات وقد يخزّن كلمة مرور بدون تشفير بالخطأ. الطريقة المعتمدة الوحيدة:

```bash
php artisan make:admin
```

سيطلب منك الاسم، البريد، وكلمة المرور (12 حرفًا على الأقل، تُدخَل بشكل مخفي ولا تظهر في الشاشة ولا في سجل الأوامر). يُنشئ الأمر حسابًا مفعّلًا (`is_active = true`) ويمنحه دور `super-admin` تلقائيًا. يمكن إعادة تشغيله لاحقًا لإنشاء مسؤولين إضافيين.

---

## 14. تشغيل النسخة الاحتياطية

**من لوحة التحكم**: `/admin/backups` ← زر "إنشاء نسخة احتياطية" (يتطلب صلاحية `backups.manage`). تُنشئ أرشيف `.zip` واحدًا يضم تفريغ SQL كاملًا لقاعدة البيانات + كل الملفات المرفوعة (الوسائط العامة وملفات PDF الخاصة)، ويُخزَّن في `storage/app/backups` — **مسار غير قابل للوصول عبر أي رابط عام إطلاقًا** (لا `url()` معرّف لهذا القرص، ولا رابط تنزيل إلا عبر Controller يتحقق من الصلاحية).

**تلقائيًا**: نسخة يومية الساعة 3:00 صباحًا (`routes/console.php`)، تتطلب Cron مفعّلًا (القسم 12).

**من الطرفية**:
```bash
php artisan backup:run
```

**سياسة الاحتفاظ**: تلقائيًا، بعد كل نسخة ناجحة، يُحتفظ فقط بآخر `BACKUP_KEEP_COUNT` نسخة (افتراضيًا 10، معدَّل عبر `.env`) وتُحذف الأقدم منها (ملفًا وسجلًا). يمكن أيضًا حذف أي نسخة يدويًا من اللوحة في أي وقت.

---

## 15. الاستعادة من نسخة احتياطية

من `/admin/backups`، زر "استعادة" أمام النسخة المطلوبة. **هذا إجراء تدميري**: يستبدل قاعدة البيانات الحالية بالكامل بمحتوى النسخة (تفريغ SQL يُعاد استيراده حرفيًا)، ثم يستبدل مجلدات الملفات المرفوعة بمحتوى النسخة كذلك. لذلك:

- يظهر تحذير صريح ويتطلب تأكيدًا قبل التنفيذ.
- يتطلب صلاحية `backups.manage` حصرًا.
- **يُنصح بشدة بإنشاء نسخة احتياطية جديدة قبل الاستعادة من نسخة قديمة**، حتى تكون الحالة الحالية قابلة للرجوع إليها إن احتجتم ذلك.
- كل عملية استعادة (نجحت أو فشلت) تُسجَّل في سجل العمليات.

---

## 16. متطلبات Production

- PHP 8.4+ مع الإضافات: `pdo_mysql`, `zip`, `gd` أو `imagick` (لتوليد صور WebP/AVIF).
- MySQL 8+ أو MariaDB 10.4+ (يُستخدم فهرس FULLTEXT للبحث — تأكدوا أن المحرك InnoDB).
- `mysqldump` و`mysql` (سطر أوامر) على مسار الخادم أو محدَّدَين في `.env` — مطلوبان للنسخ الاحتياطي.
- Node.js 18+ (لبناء `npm run build` وقت النشر فقط — غير مطلوب وقت التشغيل).
- شهادة HTTPS فعّالة (Let's Encrypt أو غيرها) — إلزامية قبل تفعيل `SESSION_SECURE_COOKIE`/`HSTS`، وإلا سيفشل تسجيل الدخول (الكوكيز الآمنة لا تُرسَل عبر HTTP).
- Cron قياسي لتشغيل الجدولة (النسخ الاحتياطي اليومي).
- مساحة تخزين كافية لـ `storage/app/public` و`storage/app/private` و`storage/app/backups` (الأخيرة تنمو ببطء بفضل سياسة الاحتفاظ).

---

## 17. إجراءات الأمان المهمة

ملخص ما رُوجع واختُبر فعليًا (المراجعة الكاملة في مرحلتي الأمان رقم 27 و29):

- **Debug**: مغلق افتراضيًا في الكود (`config('app.debug')` يرجع `false` ما لم يُفعَّل صراحة)، ولا صفحة خطأ تُظهر Stack Trace أو مسارات نظام لأي زائر — صفحات 404/403/419/500 مخصصة.
- **CSRF**: مفعَّل على كل مسارات `web` (تحقق مباشر عبر HTTP فعلي: طلب POST بلا رمز يرجع 419).
- **XSS**: كل مخرجات Blade مُهرَّبة تلقائيًا (`{{ }}`)، وكل استثناء (`{!! !!}`) إما `e()` قبل `nl2br()` أو JSON-LD محمي بـ `JSON_HEX_TAG`.
- **SQL Injection**: Eloquent/Query Builder في كل مكان، الاستعلام الخام الوحيد (`whereRaw` للبحث النصي) مُعامَل بالكامل (parameter binding).
- **IDOR**: كل متحكم محتوى يتحقق أن نوع العنصر يطابق المسار (مثال: `/admin/books/{id}` يرفض أي `id` ليس كتابًا فعليًا)، وملفات PDF لا تُخدَّم إلا إذا كانت مرتبطة بمحتوى منشور فعليًا.
- **Mass Assignment**: كل نموذج يستخدم `$fillable` صراحة (لا `$guarded = []` في أي مكان)، وكل إدخال يمر عبر Form Request مع `validated()`.
- **رفع الملفات**: `SafeFileUpload` يتحقق من الامتداد + الحجم + MIME الحقيقي (وليس المُرسَل من المتصفح) + محتوى الصورة الفعلي + رأس PDF السحري (`%PDF-`) + خلوّه من إجراءات JavaScript/Launch التلقائية داخل PDF + رفض أسماء الملفات المزدوجة الامتداد.
- **Broken Access Control / تصعيد الصلاحيات**: راجع القسم 6 — محميّ ومختبَر عبر اختبارات انحدار دائمة.
- **Brute Force**: تسجيل الدخول محدود بـ 5 محاولات/دقيقة لكل (بريد + IP)، مع رسالة عامة لا تكشف أي حقل كان الخطأ فيه.
- **Session**: إعادة توليد معرّف الجلسة عند تسجيل الدخول (منع Session Fixation)، إبطال كامل + إعادة توليد رمز CSRF عند تسجيل الخروج، كوكيز `HttpOnly` + `SameSite=Lax` دائمًا، و`Secure` في الإنتاج.
- **حماية لوحة التحكم**: مصادقة + صلاحية مطلوبتان على كل مسار إداري، وحساب يُعطَّل أثناء الجلسة يُطرَد فورًا (لا ينتظر انتهاء الجلسة).
- **Security Headers**: `X-Frame-Options: DENY`، `X-Content-Type-Options: nosniff`، `Referrer-Policy: strict-origin-when-cross-origin`، `Content-Security-Policy` صارمة (`script-src 'self'` بلا `unsafe-inline`)، `Strict-Transport-Security` تلقائيًا في الإنتاج عبر HTTPS، وإخفاء بصمة PHP (`X-Powered-By`).
- **CORS**: مُقفَل صراحة (`config/cors.php`) — لا يوجد API عام حاليًا.
- **النسخ الاحتياطية**: غير متاحة عبر أي رابط عام (راجع القسم 14).

---

## 18. ملاحظات قبل الإطلاق

1. **فعّلوا HTTPS فعليًا** عند النشر (شهادة SSL + إعادة توجيه HTTP → HTTPS على مستوى الخادم/الـ reverse proxy) ثم اضبطوا `APP_ENV=production` و`SESSION_SECURE_COOKIE=true` — الكود يفرض `https://` في كل الروابط المُولَّدة تلقائيًا بمجرد `APP_ENV=production`، لكن لا يستبدل شهادة SSL فعلية على الخادم.
2. **أنشئوا حساب المسؤول الأول فورًا بعد أول Deploy** عبر `php artisan make:admin` (القسم 13) قبل فتح الموقع للعامة.
3. **البريد الإلكتروني غير مفعَّل فعليًا** (`MAIL_MAILER=log`) — لا مشكلة حاليًا لأن لا شيء في المشروع يعتمد على إرسال بريد، لكن إن أُضيف مستقبلًا استرجاع كلمة مرور أو إشعارات، يجب ضبط SMTP حقيقي أولًا.
4. **جداول غير مستخدمة في المخطط** (`site_settings`, `user_profiles`, `quran_collections`, `quran_items`) — موثّقة في القسم 3، أُبقيت لعدم حذف أي بنية قائمة، ومرشّحة لتنظيف لاحق إن رغبتم.
5. **تحقق سريع بعد كل Deploy**: `php artisan test` (يتطلب قاعدة بيانات اختبار منفصلة — راجع `.env.testing`)، ثم زيارة يدوية لصفحة رئيسية + تسجيل دخول + إنشاء نسخة احتياطية تجريبية واحدة للتأكد أن `mysqldump` يعمل على بيئة الخادم الفعلية.
6. **النسخ الاحتياطي يعتمد على وجود `mysqldump`/`mysql` على الخادم** — على بعض الاستضافات المشتركة قد لا تتوفر صلاحية تشغيل عمليات خارجية (`proc_open`)؛ تأكدوا من ذلك قبل الاعتماد الكامل على النسخ التلقائي.
7. المشروع **متجاوب بالكامل** (Mobile First) و**RTL أصيل** من طبقة CSS الأساسية وليس ترقيعًا لاحقًا، وكل الإعدادات القابلة للتغيير (الاسم، الشعار، التواصل، السيو، عدد العناصر بالصفحة...) **تُدار من لوحة التحكم فقط بلا لمس كود**.
