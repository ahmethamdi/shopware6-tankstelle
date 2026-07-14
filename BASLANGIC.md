# Tankstellenpartner 24 — Shopware 6 Projesi (Başlangıç)

Bu proje **Vapor Handel** temasının birebir kopyası olarak kuruldu; sadece **isim + renkler** değişti.
Vapor'daki çalışma stilimizle (Claude Code ile) buradan devam edeceğiz.

> Site: https://www.tankstellenpartner24.de/ · Repo: `git@github.com:ahmethamdi/shopware6-tankstelle.git`

---

## 🚦 Ortam (KRİTİK — her yeni sohbette bunları ver)

| Ne | Değer |
|---|---|
| **Proje kökü** | `/Applications/XAMPP/xamppfiles/htdocs/shopware6-tankstelle` |
| **Tema** | `custom/plugins/Tankstellenpartner24Theme` |
| **DB** | `shopware_tp24` @ `127.0.0.1:3307` (MySQL 8, **şifresiz** root) |
| **Server portu** | **18600** (Vapor 18443/18500, başka proje 8619 → çakışma yok) |
| **APP_URL** | `http://127.0.0.1:18600` |
| **Admin** | `http://127.0.0.1:18600/admin` → **admin / shopware** |
| **Namespace** | `Tankstellenpartner24Theme\` |

### Server başlatma (Vapor ile aynı mantık — 16 worker + dev-router şart)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/shopware6-tankstelle
nohup env PHP_CLI_SERVER_WORKERS=16 \
  DATABASE_URL="mysql://root@127.0.0.1:3307/shopware_tp24" \
  APP_URL="http://127.0.0.1:18600" APP_ENV=prod \
  php -d memory_limit=1G -S 127.0.0.1:18600 -t public public/dev-router.php \
  > /tmp/tp24-server.log 2>&1 &
```
> `public/dev-router.php` statik CSS/JS servis eder (yoksa /theme/*.css 404 → çıplak HTML).
> Bu dosya `.gitignore`'da (dev-only), repoda yok — Vapor'dan kopyalandı, dursun.

### Her tema/config değişikliğinden SONRA (Vapor'daki gibi ŞART)
```bash
export DATABASE_URL="mysql://root@127.0.0.1:3307/shopware_tp24" APP_URL="http://127.0.0.1:18600" APP_ENV=prod
php bin/console theme:compile     # scss/theme.json değiştiyse
php bin/console theme:refresh     # theme.json'a YENİ ALAN eklendiyse ŞART
php bin/console cache:clear
```
Build (JS+SCSS birlikte): `bash bin/build-storefront.sh` (Vapor'daki gibi).

---

## 🎨 Vapor'dan farklar (ne değişti)

1. **İsim/namespace:** `VaporTheme` → `Tankstellenpartner24Theme` (composer, PHP sınıfı, theme.json, twig `@` namespace, JS klasörü `tankstellenpartner24-theme`).
2. **Renkler** (branding'e göre):
   - Ana (turuncu-kırmızı): `#538AD0` → **`#E1523D`**
   - İkincil (lacivert): **`#1B3A5C`**
   - Metin (siyah): **`#0D0D0D`** · Sale: **`#D93A3A`**
   - Tüm eski mavi tonları (`#4F88D9`, `#5E96DB` vb.) turuncu/kırmızı ailesine map edildi.
   - Font branding'de **Gotham** (Medium+Bold) — henüz yüklenmedi, sen ekleyeceksin.
3. **VioRepresentativeLogin bağımlılığı nötrleştirildi:** Vapor'da agent/temsilci girişi için `sidebar.html.twig` ve `account-menu.html.twig` `@VioRepresentativeLogin/...` extend ediyordu. Bu plugin burada YOK → ikisi de artık `@Storefront/...` extend ediyor. **Vio'yu kurarsan** o iki dosyadaki not'a göre satırı geri çevir, agent desteği döner. (`context.extension('vioAgent')` kontrolleri zararsız, Vio yoksa `customer`'a düşer.)
4. **Bestseller fallback `parent()` kaldırıldı:** Vapor'un section-sıralama refactorü sonrası `parent()` çağrısı `{% set %}` capture içinde kaldığından hata veriyordu; ürün yoksa boş bırakılacak şekilde düzeltildi.

---

## ⚠️ ÖNEMLİ KEŞİF (2026-07-14)
**TP24 AKARYAKIT DEĞİL — Vapor ile AYNI iş: e-sigara/vape B2B toptancısı!**
Gerçek tankstellenpartner24.de bir e-sigara/vape B2B sitesi (isim yanıltıcı). Menüler
vapor-handel.de'nin AYNISI. 13 Temmuz'daki "akaryakıt" içerik çevirisi YANLIŞTI, geri alındı.
Doğru yön: e-sigara içeriği + TP24 markası (turuncu) + **Vapor'dan görsel olarak farklı özgün tasarım**.

## ✅ Şu an hazır olan

- Shopware 6.7 kurulu + çalışıyor. Anasayfa + admin **HTTP 200**. Git push'lu (`main`).
- **Font:** Montserrat (Gotham geçici alternatifi). overrides.scss + theme.json `vapor-font-*` + core `sw-font-family-base/headline`.
- **Logo:** Kullanıcı gerçek logoyu admin `sw-logo-desktop`'a yükledi (damla+pompa+TANKSTELLEN PARTNER 24). Yedek: SVG placeholder `bundles/tankstellenpartner24theme/logo/tp24-logo.svg` (gerçeğe sadık). Renkler: turuncu `#E1523D` + lacivert `#13365A` + mavi `#005984`.
- **REDESIGN (modern B2B/kurumsal, Vapor'dan ayrı) — 8 commit push'lu:**
  - Tasarım sistemi: `overrides.scss`'te TP24 token'ları (`$tp-*`: renk/spacing/gölge/radius). base.scss `:root`'ta CSS değişkenleri.
  - **Header** (`layout/header/header.html.twig` + `.tp-header`): lacivert USP üst şeridi + logo + geniş açık arama + temiz aksiyon ikonları + turuncu "Jetzt registrieren" CTA. Navbar sade kısayol (mega menü KALDIRILDI, kategoriler sidebar'da).
  - **Hero** (`.vapor-hero`): lacivert gradient zemin + turuncu vurgu/CTA + eyebrow rozeti.
  - **Sidebar** korundu (çok menü için, kullanıcı isteği): modern lacivert başlık + kategori mega menü.
  - Kartlar/deals/promo hep token diline çekildi (deals siyah→lacivert, promo mor→lacivert).
  - **3 YENİ panel-driven bölüm** (sıralama motoruna eklendi — `index.html.twig` `vaporSecDefs`):
    1. **pills** (order 5): yuvarlak-ikonlu kategori kısayol şeridi (`.vapor-pills`)
    2. **bento** (order 15): 1 büyük+4 kart bento grid + 6 kısayol kartı (`.vapor-bento`/`.vapor-shortcuts`, theme.json `vapor-bento-*` demo dolu)
    3. **catslider** (order 18): "Nach Kategorie einkaufen" büyük+3 mozaik görselli kayan kategori kartları (`.vapor-catslider`/`.vapor-catcard`)
- **Kategori ağacı KURULDU** (9 ana + 39 alt, vapor-handel.de yapısı) → sidebar/pill/menü/catslider dolu.
- **14 demo ürün** eklendi (ELFBAR/LOST MARY/AL FAKHER vb.) → Neuheiten/Bestseller/Deals dolu.
- Katalog kurma scriptleri: `scratchpad/build-cats.php`, `build-products.php` (idempotent, DB'ye yazar).

## ⏭️ Sıradaki işler (kaldığımız yer)

- [ ] **YENİ BÖLÜM 4:** sol büyük promo kart + sağda 2 ürün slider'ı (referans görselleri kullanıcıda). Ürün verisi artık var.
- [ ] **Footer redesign** (kurumsal, temiz — token diline çek).
- [ ] **Mobil uyumluluk** kontrolü (tüm yeni bölümler responsive yazıldı ama test edilmeli).
- [ ] **Görseller:** bento/catslider/hero/ürün kartları placeholder — admin'den görsel yüklenince dolacak.
- [ ] Gerekirse VioRepresentativeLogin (B2B temsilci girişi).

### Ekran görüntüsü alma (age gate'i geç)
Age gate localStorage anahtarı `vapor_age_verified`. playwright-cli ile:
`npx --yes --package @playwright/cli@latest playwright-cli open <url>` → `eval "localStorage.setItem('vapor_age_verified','1')"` → `goto <url>` → `screenshot`.

## 📝 Vapor referansı
Vapor projesi hâlâ duruyor: `/Applications/XAMPP/xamppfiles/htdocs/Shopware6` (tema `custom/plugins/VaporTheme`).
Kod/yapı karşılaştırması için oraya bakabilirsin. İki proje ayrı DB + ayrı port, karışmaz.
