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

### Ağ paylaşımı (LAN — aynı Wi-Fi'daki cihazlar görsün)
LAN IP: **192.168.1.25** (DHCP → değişebilir, `ipconfig getifaddr en0` ile kontrol et). `0.0.0.0`'da dinlet + APP_URL LAN IP:
```bash
nohup env PHP_CLI_SERVER_WORKERS=16 \
  DATABASE_URL="mysql://root@127.0.0.1:3307/shopware_tp24" \
  APP_URL="http://192.168.1.25:18600" APP_ENV=prod \
  php -d memory_limit=1G -S 0.0.0.0:18600 -t public public/dev-router.php \
  > /tmp/tp24-server-lan.log 2>&1 &
```
> LAN IP Shopware sales_channel_domain'e eklendi (`http://192.168.1.25:18600` + eski `.37`) → yoksa HTTP 400.
> **IP değişirse 3 adım:** (1) yeni IP'yi `sales_channel_domain`'e ekle, (2) `.env.local`'da `APP_URL`'i yeni IP yap, (3) **`theme:compile`** (core `shopware.js` asset URL'i derlemeye gömülü — sadece cache:clear yetmez, dış cihazda o JS 127.0.0.1'e gider). macOS firewall kapalı, ekstra izin gerekmez.
> `127.0.0.1:18600` domain'i de duruyor. Erişim: **http://192.168.1.37:18600** (18+ yaş kapısı çıkar).

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
- **REDESIGN (modern B2B/kurumsal, Vapor'dan ayrı) — 15 commit ("Redesign N/N"), main'de:**
  - Tasarım sistemi: `overrides.scss`'te TP24 token'ları (`$tp-*`: renk/spacing/gölge/radius). base.scss `:root`'ta CSS değişkenleri.
  - **Header** (`layout/header/header.html.twig` + `.tp-header`): **TAM GENİŞLİK** — arka planlar (USP şeridi + navbar) edge-to-edge, İÇERİK container'da (`$tp-container-max`, sayfa gövdesiyle hizalı). Turuncu bant kaldırıldı → beyaz header. `.header-main > .container` full-width, iç container'lar container-width. Logo 60px, header 104px.
  - **Hero** (`.vapor-hero`): lacivert gradient zemin + turuncu vurgu/CTA + eyebrow rozeti.
  - **Sidebar** (korundu + UZATILDI): kategori mega menü + promo + CTA + USP + **NEU EINGETROFFEN** mini ürün listesi (canlı) + **B2B-Support** kartı (tel/saat) + **IHRE VORTEILE** alt alta 3 kompakt kart (`.vapor-sidebar__vt-card`).
  - Kartlar/deals/promo/**footer** hep token diline çekildi (footer açık-gri Vapor → kurumsal lacivert; newsletter lime→turuncu).
  - **Bölümler** (`index.html.twig` `vaporSecDefs` sıralama motoru, hepsi panel-driven):
    - pills(5), bento(15) [marka kısayol şeridi KALDIRILDI], catslider(18)
    - **promoduo(19)**: sol lacivert promo kart + sağda 2'li Neuheiten slider (`.vapor-promoduo`)
    - **Deals des Tages(50)**: GERÇEK ürün kartları + günlük rotasyon (day-of-year 'z' ofset) + gece yarısına sayan ortak sayaç (`data-vapor-daily-countdown`, 00:00'da JS reload) + yatay scroll + turuncu "Deal des Tages" ribbon.
    - neu(70), bestseller(90), b2b(110). **Section boşluk** sıkılaştırıldı ($tp-space-5).
  - **Slider okları**: `sw_icon 'chevron-*'` (public/theme'de yok, boş görünüyordu) → inline SVG chevron. Genel `data-vapor-slide` prev/next JS handler (main.js).
  - **KALDIRILAN** (kullanıcı isteği): Top-Marken marka şeridi + kampanya banner ('POWERED BY ELFBAR') + Angebote slider + alt Top-Marken logo slider (hepsi `-show=false` veya Twig'den çıkarıldı).
- **Kategori ağacı KURULDU** (9 ana + 39 alt, vapor-handel.de yapısı) → sidebar/pill/menü/catslider dolu.
- **14 demo ürün** eklendi (ELFBAR/LOST MARY/AL FAKHER vb.) → Neuheiten/Bestseller/Deals dolu.
- Katalog kurma scriptleri: `scratchpad/build-cats.php`, `build-products.php` (idempotent, DB'ye yazar).
- **Ağ paylaşımı hazır**: LAN IP `192.168.1.37:18600` sales_channel_domain'e eklendi, `0.0.0.0`'da dinletince ağdakiler görür (bkz. yukarı "Ağ paylaşımı").

## ⏭️ Sıradaki işler (kaldığımız yer)

- [ ] **Mobil uyumluluk** tam tur kontrolü (Bölüm 4 + footer test edildi; kalan bölümler responsive yazıldı ama tek tek gözden geçirilmeli).
- [ ] **Görseller:** bento/catslider/hero/ürün/promoduo-bg/footer-trust placeholder — admin'den yüklenince dolacak.
- [ ] "ALLE KATEGORIEN" sidebar başlığı turuncu görünüyor (base.scss'te 2 `.vapor-sidebar__head` tanımı var, 2.si -orange- kazanıyor) — istenirse laciverte çekilebilir.
- [ ] Gerekirse VioRepresentativeLogin (B2B temsilci girişi).

### Ekran görüntüsü alma (age gate'i geç)
Age gate localStorage anahtarı `vapor_age_verified`. playwright-cli ile:
`npx --yes --package @playwright/cli@latest playwright-cli open <url>` → `eval "localStorage.setItem('vapor_age_verified','1')"` → `goto <url>` → `screenshot`.

## 📝 Vapor referansı
Vapor projesi hâlâ duruyor: `/Applications/XAMPP/xamppfiles/htdocs/Shopware6` (tema `custom/plugins/VaporTheme`).
Kod/yapı karşılaştırması için oraya bakabilirsin. İki proje ayrı DB + ayrı port, karışmaz.
