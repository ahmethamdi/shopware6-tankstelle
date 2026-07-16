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
- **REDESIGN (modern B2B/kurumsal, Vapor'dan ayrı) — 19 commit ("Redesign N/N"), main'de:**
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
- **Ağ paylaşımı hazır**: LAN IP `192.168.1.25:18600` sales_channel_domain'e eklendi, `0.0.0.0`'da dinletince ağdakiler görür (bkz. yukarı "Ağ paylaşımı").

### Önceki oturum (Redesign 16-19, main'de push'lu)
- **16:** Mobil footer-top fix. **Pill şeridi manuel** (8 slot `vapor-pill-1..8-*`). LAN IP `.37`→`.25`.
- **17:** **"Nach Kategorie einkaufen" (catslider) MANUEL** (6 slot `vapor-catcard-1..6-img/-mini1..3/-title/-link`). Title doluysa manuel; yoksa canlı kategori fallback.
- **18:** Deals des Tages koyu V4 panel (`.vapor-deals-panel`, glow).
- **19:** Admin edit paneli anasayfa akışına göre tab'landı. ⚠️ **Admin tab'ları field'ların FİZİKSEL sırasına göre diziyor** (theme.json `fields` dict sırası önemli).

### 🆕 Bu oturumda yapılanlar (Redesign 20-24 + mobil + varyant, hepsi main'de push'lu, `af04880`)
- **20:** **Bestseller bölümü gizlendi** (`vapor-sec-bestseller-show=false`, veri durur) → yerine **Information** bölümü (`.vapor-info`: editorial statement + turuncu rail + 5 madde checklist, panelden `vapor-info-*`, Home: Bottom "12b"). **Top-Marken:** circular `vapor-brandnav` (brands) yerine dönen marquee slider (`vapor-brandslider`/topmarken, 38s, hover-pause) aktif edildi.
- **21:** **Pill şeridi kendi bloğuna** (`vaporPills`, Home: Top "2)", Hero altı; 8 slot `vaporBento`'dan taşındı). 8 pill kategori isim+SEO-link ile dolduruldu. Bento→"3)", Promoduo→"4)", Vorteile→"5)". `brands-show` tekrar true.
- **22:** **Sidebar yeniden düzenlendi** — duplicate USP listesi (`vapor-usp-1..4`, theme.json'dan da silindi) kaldırıldı. Yeni sıra: Kategoriler → **IHRE VORTEILE** (kategori altına) → Promo1 → **Bestseller mini** (yeni) → Neuheiten mini → Promo2(SALE) → B2B CTA → **Newsletter** (yeni, `vapor-sidebar-nl-*`) → B2B-Support. İki görsel de korundu.
- **23-24:** Catslider görsel deneme (placeholder) yapılıp **REVERT edildi** (kullanıcı istemedi). Sonra: catslider **mini hücrelere lacivert border** (`.vapor-catcard__minicell`).
- **Mobil (app tarzı):** Bento mobilde vitrin(big) tam genişlik + diğer 4 kart **2×2** (min-height 170px). Catslider mobilde kart 86vw + ana görsel `contain`+`grid-template-rows:1fr` (KESİLMİYOR, foot'a taşmıyor) + scroll-snap. **Header mobilde:** "Jetzt registrieren" CTA gizli (`display:none !important`, d-none override bug'ı), account action .btn/.dropdown 40px'e indi, ikonlar taşmasız dizili.
- **Varyant seçici akıllı mod:** `configurator.html.twig` override — grup seçenek sayısı **eşiği aşarsa dropdown** (select), az ise buton. Eşik `vapor-variant-dropdown-threshold` (General tab, **default 4** → 5+ dropdown, ≤4 buton). displayType=select zaten dropdown. Varyant ürün ekleme: DAL script (kernel boot, `product.repository`+`property_group.repository`; parent+children+configuratorSettings; ⚠️ productNumber suffix `substr($id,-6)` — Shopware UUID'leri timestamp-sıralı, ilk 6 char çakışır).
- **USP metinleri:** Header infobar 1-4 (`vapor-infobar-1..4`, 5=18+ korundu) + ürün detay buy-widget USP (hardcoded 4) → "Kostenlose Lieferung für Händler / Über 4.000 zufriedene Partner / Bundesweites Vertriebsnetz / Persönlicher Kundenservice".
- **Test ürünleri (DB'de, git'e girmez):** TP24 Test-Liquid (3 varyant→buton), TP24 Test-Aroma (5→dropdown), TP24 Test-Pods (11→dropdown). Hepsi Liquids kategorisinde.

## ⏭️ Sıradaki işler (kaldığımız yer)

- [ ] **Cart/checkout sayfası kontrol edilmedi** — mobilde bakılacaktı, sıradaki.
- [ ] **Kullanıcı admin'den dolduracak:** catslider kartları (görsel+mini+link) + bento/hero/promoduo-bg görselleri. ⚠️ **Media-field upload persist sorunu:** catcard görsel yükleme DB'ye kaydolmuyordu (`img=None`, sadece title kaydoldu) — kök neden araştırılmadı; kod tarafı OK (hero/promo aynı pattern çalışıyor).
- [ ] Test varyant ürünleri (3) işi bitince silinebilir.
- [ ] **Footer USP** (title+sub, `vapor-footer-usp-1..4`) hâlâ eski metin (Versand/Bezahlung/Lieferung/Support) — istenirse 4 yeni USP metnine çevrilir.
- [ ] "ALLE KATEGORIEN" sidebar başlığı turuncu — istenirse laciverte çekilebilir.
- [ ] Gerekirse VioRepresentativeLogin (B2B temsilci girişi).

### Ekran görüntüsü alma (age gate'i geç)
Age gate: `localStorage vapor_age_verified='1'` VEYA sayfadaki "Ja, ich bin über 18" butonuna tıkla + cookie "Only technically required".
Wrapper: `scratchpad/pw.sh` (npx playwright-cli sarmalayıcı, session=tp24mob). Örn: `bash scratchpad/pw.sh goto <url>` / `resize W H` / `eval "..."` / `screenshot` / `snapshot` (ref al) / `fill <ref> <val>` / `click <ref>`.
- **Age gate reload'da tekrar çıkabilir** → goto SONRASI localStorage set + butona tıkla.
- **`--full-page` `file://`'de çalışmaz**; statik HTML mockup'ı `public/`'e kopyalayıp HTTP'den servis et (dev-router statik html verir), iş bitince sil.
- **Admin login (playwright):** `snapshot` → `fill <userRef> admin` / `fill <passRef> shopware` / `click <loginRef>`. Reactive Vue value set için `eval` YETMEZ, gerçek `fill`/`click` kullan.
- **Admin SPA cache'li:** theme.json değişince admin panel eski tab'ları gösterebilir → `reload` + tam `goto` ile hard-refresh şart. Theme config API ile doğrula: `GET /api/_action/theme/<id>/configuration` (Bearer token: `POST /api/oauth/token` client_id=administration).

## 📝 Vapor referansı
Vapor projesi hâlâ duruyor: `/Applications/XAMPP/xamppfiles/htdocs/Shopware6` (tema `custom/plugins/VaporTheme`).
Kod/yapı karşılaştırması için oraya bakabilirsin. İki proje ayrı DB + ayrı port, karışmaz.
