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

## ✅ Şu an hazır olan

- Shopware 6.7 kurulu + çalışıyor (252 tablo, admin/shopware).
- TP24 teması **kurulu + aktif + Storefront'a atanmış + compile'lı**.
- Anasayfa + admin **HTTP 200**, turuncu renkler doğrulandı (CSS'te #E1523D 311 kez, mavi 0).
- Git repo bağlı, push'lu (`main`).
- **Font:** Montserrat (Gotham geçici alternatifi). 3 katmanda: overrides.scss + theme.json `vapor-font-*` + core `sw-font-family-base/headline`. Gerçek Gotham için base.scss'te hazır @font-face yorum bloğu var.
- **Logo + favicon:** marka renklerinde SVG placeholder (damla + pompa). `bundles/tankstellenpartner24theme/logo/` → header (`layout/header/logo.html.twig`) + favicon (`layout/meta.html.twig`). Admin'e gerçek logo yüklenince otomatik ona düşer.
- **İçerik TP24 akaryakıt B2B'ye uyarlandı:** 18+ yaş kapısı tamamen kaldırıldı (age gate + ürün kartı rozetleri + footer). ~50 theme.json value çevrildi (Kraftstoffe & AdBlue, Schmierstoffe, Autopflege, Technik & Zubehör, Merchandise & POS). VaporShop/ELFBAR/LOST MARY/Pods/Aromen → nötr akaryakıt kategorileri. E-posta → info@tankstellenpartner24.de.

## ⏭️ Sıradaki işler (yarın devam)

- [ ] **Kategori tekrarlarını çeşitlendir** (kozmetik): birkaç theme.json alanı aynı jenerik isme düştü (2× "Shop & Convenience", 2× "Autopflege", 2× "Merchandise & POS"). Demo daha çeşitli görünsün istersen farklılaştır.
- [ ] **Ana navigasyon menüsü + kategori sayfaları:** hâlâ eski demo katalog kategorilerinden geliyor (tema değil, **admin → Katalog** işi). Gerçek TP24 kategori ağacını kur.
- [ ] **Gerçek logo & Gotham fontu** gelince bağla (placeholder'lar yerine).
- [ ] Gerekirse VioRepresentativeLogin kur (B2B temsilci girişi istenirse).

## 📝 Vapor referansı
Vapor projesi hâlâ duruyor: `/Applications/XAMPP/xamppfiles/htdocs/Shopware6` (tema `custom/plugins/VaporTheme`).
Kod/yapı karşılaştırması için oraya bakabilirsin. İki proje ayrı DB + ayrı port, karışmaz.
