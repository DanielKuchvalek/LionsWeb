# LIONS Handball Team Hostivice – web klubu

Web házenkářského klubu LIONS Hostivice s vlastní administrací, napojením na výsledky Českého svazu házené a rezervačním systémem.

🌐 **Živý web:** [lionshandball.cz](https://lionshandball.cz)

## Co web umí
- **Automatické výsledky a rozpisy** – utkání, tabulky a program týmů se stahují z handball.cz (IS ČSH) s cache; při zápase živé skóre bez obnovení stránky
- **Rezervace miniGYM** – týdenní kalendář, kapacita a otevírací doba, ochrana proti dvojí rezervaci přímo v databázi
- **Online přihlášky a formuláře** – nábor, Lvíčata, tábory; ochrana proti spamu (honeypot, podepsaný token, rate limit)
- **Administrace** – správa týmů, textů stránek, médií, partnerů, přijatých formulářů a více správců
- **Vlastní anonymní měření návštěvnosti** – bez cookies a bez Google Analytics
- **GDPR** – souhlasy u formulářů, automatické mazání starých údajů, export a výmaz osobních údajů
- **SEO** – canonical URL, popisy stránek, sitemap, robots.txt, strukturovaná data (JSON-LD)
- Napojení na widgety Sportlyzer (kalendáře, soupisky) a Facebook

## Technologie
- **PHP 8.1+** bez frameworku a bez WordPressu (vlastní routing, šablony a administrace)
- **SQLite** (PDO, režim WAL) + JSON soubory pro obsah
- **Vanilla JavaScript**, vlastní CSS
- Apache s `mod_rewrite` (nebo Nginx)
- Zabezpečení: bcrypt hesla, CSRF ochrana, blokace po chybných pokusech, automatické denní zálohy

---

# Dokumentace

# LIONS Handball – web

Lehký PHP web bez WordPressu a bez databáze. Potřebuje jen PHP 8.0+ s rozšířením `curl` a Apache s `mod_rewrite` (běžný webhosting).

## Co se aktualizuje samo

| Co | Odkud | Jak často |
|---|---|---|
| Nejbližší domácí utkání, odpočet | handball.cz (IS ČSH) | cache 15 min, během zápasu 1 min |
| Poslední výsledky, program týmů | handball.cz | dtto |
| Rozpis, výsledky a tabulka každého týmu | handball.cz | dtto |
| Živé skóre (bez obnovení stránky) | `api/csh.php` | každou minutu, když se hraje |
| Kalendáře, soupisky, registrace | Sportlyzer (widgety) | živě |
| Příspěvky | Facebook Page Plugin | živě |

Když handball.cz nejde, web ukáže poslední uložená data (`storage/cache`).

## Administrace – `/admin/`

Při prvním otevření `/admin/` si vytvoříte účet správce (další správce přidáte v sekci *Účet a správci*).

| Sekce | Co umí |
|---|---|
| Přehled | nejbližší utkání, ruční obnovení dat z handball.cz, **vyhledání soutěží LIONS pro novou sezónu** |
| miniGYM rezervace | týdenní přehled kdo má kdy rezervováno, seznam, přidat / upravit / smazat, export CSV, otevírací doba a kapacita |
| Týmy | texty, fotka, tréninky, soutěže ČSH, ID Sportlyzeru, vlastní widgety, **partneři týmu** (libovolný počet log; bez partnerů se sekce nezobrazí), zobrazení v menu a pořadí, přidání / smazání týmu |
| Návštěvnost | zobrazení stránek, na co a kde se kliká, odchody na jiné weby, odkud lidé přišli, mobil / počítač (viz níže) |
| Stránky a texty | texty a obrázky všech stránek (výchozí = původní obsah, tlačítko „Vrátit původní“) |
| Obrázky a videa | nahrávání přetažením (velké fotky se samy zmenší), hledání, mazání |
| Přijaté formuláře | všechny odeslané přihlášky a dotazy, stažení pro Excel |
| Nastavení webu | e-mail, sociální sítě, Facebook stránky, partneři (stránka PARTNEŘI + lišta log v patičce), odkazy „Rozpisy a výsledky“ |

Změny z administrace se ukládají do `storage/content/*.json` a mají přednost před soubory v `config/`.
Zabezpečení: hashovaná hesla, ochrana proti CSRF, zablokování po 5 chybných pokusech na 10 minut, odhlášení po 8 h nečinnosti.

## Návštěvnost a prokliky (administrace → Návštěvnost)

Vlastní anonymní měření bez cookies a bez cizích služeb (žádný Google Analytics), proto není potřeba cookie lišta.

- **Jak to funguje:** `assets/js/app.js` po načtení stránky a při každém kliknutí pošle krátkou zprávu na `api/track.php`
  (`navigator.sendBeacon`, návštěvníka nezdrží). Server ji přičte do tabulky `stats` v `storage/lions.sqlite`
  – **jen denní součty**: den · druh (zobrazení / klik) · stránka · cíl odkazu · text odkazu · část stránky · počet.
- **Co se ukládá:** stránka; u zobrazení doména, ze které návštěvník přišel, a mobil / tablet / počítač;
  u kliknutí text odkazu nebo tlačítka, kam vede a část stránky (Menu, Podmenu týmu, Patička, Lišta partnerů,
  jinak nadpis sekce). Kliknutí do vloženého widgetu (Sportlyzer, mapa) se pozná, dění uvnitř widgetu ne.
- **Co se neukládá:** IP adresa, cookies, otisk prohlížeče ani nic, podle čeho by šlo poznat jednoho návštěvníka.
  Hodnoty z formulářů se neměří (počítá se jen kliknutí na „Odeslat“).
- **Nepočítá se:** roboti (podle User-Agent) a správci – po přihlášení do administrace si prohlížeč uloží značku `lionsAdmin`.
- **Ochrana:** max. 600 záznamů / 10 min z jedné IP (IP jen v tabulce `hits`, maže se po 24 h), max. 20 000 různých řádků za den.
- **Uchování:** 13 měsíců, pak se automaticky mažou (denní údržba). Popsáno v Zásadách ochrany osobních údajů i cookies.
- **Vlastní pojmenování:** atribut `data-track="Název"` na odkazu/tlačítku změní text v přehledu,
  `data-track-section="Název"` na obalu sekce změní název části stránky.

## Co upravit na novou sezónu

Nejjednodušeji v administraci: **Přehled → Vyhledat soutěže LIONS**, pak u každého týmu vložit soutěž a nové ID skupiny Sportlyzeru.
Ručně to jde i v souboru **`config/teams.php`** (platí jen dokud se týmy neuloží z administrace):

- **`csh`**: soutěže týmu na handball.cz. `slug` je konec adresy soutěže, např.
  `handball.cz/souteze/zeny/liga-zeny-cechyyy` → `'slug' => 'liga-zeny-cechyyy', 'sex' => 'zeny'`.
  Soutěž, která zatím nemá rozpis, se na stránce automaticky nezobrazí.
- **`sportlyzer`**: ID skupiny ve Sportlyzeru (TÝM + KALENDÁŘ widget).
- **`widgets`**: libovolný další widget, např.
  `['title' => 'STATISTIKY', 'iframe' => 'https://…', 'height' => 600]` nebo `['html' => '<script …></script>']`.
- texty (trenér, tréninky, příspěvek…)

Menu, kontakty, partneři a odkazy „ROZPISY A VÝSLEDKY“ jsou v `config/site.php`, formuláře v `config/forms.php`.

## Kde jsou data a jak je to jištěné

| Data | Kde | Jištění |
|---|---|---|
| Přihlášky, dotazy, rezervace miniGYM | `storage/lions.sqlite` (SQLite, režim WAL) | transakce; dvojí rezervaci termínu znemožňuje přímo databáze (UNIQUE index) |
| Texty, týmy, nastavení z administrace | `storage/content/*.json` | atomický zápis (nejdřív dočasný soubor, pak přejmenování) |
| Správci | `storage/admin/users.json` | hesla hashovaná (bcrypt) |
| Výsledky z handball.cz | `storage/cache/` | při výpadku svazu se zobrazí poslední uložená data |
| Zálohy | `storage/backups/` | každý den automaticky, 30 posledních; ruční stažení v *Stav systému* |

- Každý formulář se **nejdřív uloží do databáze, až pak se posílá e-mail**. Když e-mail neprojde, záznam nezmizí:
  v administraci je označený „e-mail neodešel“ a jde poslat znovu.
- Ochrana proti spamu: skryté pole (prohlížeče ho automaticky nevyplňují), podepsaný token s platností 14 dní
  (po vypršení si ho stránka sama obnoví), limit 10 odeslání / 10 min z jedné IP.
- Otestováno: 120 současných pokusů o 12 termínů → přesně 12 rezervací; 200 současných formulářů → vše uloženo;
  400 požadavků / 40 současně na stránky bez chyby.

## GDPR a cookies

- **Zásady ochrany osobních údajů** (`/ochrana-osobnich-udaju/`) a **Zásady cookies** (`/zasady-cookies/`) – texty upravíte v *Stránky a texty*.
  Údaje o spolku a lhůty uchování se do textu doplňují automaticky.
- Každý formulář má povinné potvrzení o seznámení se zásadami (u Lvíčat ve znění pro zákonného zástupce); čas potvrzení se ukládá k záznamu.
- **Rodné číslo** se neposílá e-mailem a v administraci je skryté (zobrazí se až kliknutím).
- **Automatické mazání**: formuláře po 24 měsících, rezervace po 12 měsících, IP adresy po 30 dnech (změna v *Nastavení → Uchování osobních údajů*).
- **Žádosti o přístup / výmaz**: administrace → *Osobní údaje* – vyhledání osoby, stažení kopie údajů, smazání.
- **Cookies**: web sám nepoužívá sledovací cookies; návštěvnost se měří anonymně bez cookies (viz výše). Cookie lišta je
  standardně vypnutá a obsah Facebooku, Sportlyzeru a Google Map se načítá rovnou; zapnout ji jde v *Nastavení → Cookies*.
  Písma a loga týmů jsou na vlastním serveru.
- Přidáte-li nový widget cizí služby, automaticky se zařadí pod souhlas („Ostatní vložený obsah“, u Sportlyzer/Google/Facebook do jejich kategorie).

## Nasazení – kontrolní seznam

1. Nahrát vše kromě `_old-prototype/` (a `_media-originals/` není nutné).
2. Práva k zápisu pro PHP: `storage/` a `assets/media/` (vč. podsložek).
3. PHP 8.1+ s rozšířeními pdo_sqlite, curl, mbstring, fileinfo (gd a zip doporučeno). Apache s `mod_rewrite` a `AllowOverride All`.
4. Otevřít `/admin/`, vytvořit správce, v **Nastavení → Odesílání e-mailů** vyplnit SMTP a poslat testovací e-mail.
5. Otevřít **Stav systému** – vše musí být zelené (ověří i, že databáze není veřejně stažitelná).
6. Zapnout HTTPS (Let's Encrypt u hostingu).

**Nginx místo Apache?** `.htaccess` se tam neuplatní – do konfigurace přidejte:
```
location ~ ^/(storage|config|lib|templates|pages|admin/views|_media-originals|_old-prototype)/ { deny all; }
location ~ ^/assets/media/.*\.php$ { deny all; }
location / { try_files $uri $uri/ /index.php?$query_string; }
```

## Obrázky

Fotky a videa z původního webu jsou v `assets/media/` (stejná struktura jako `wp-content/uploads`, staré odkazy se přesměrují).
Velké obrázky jsou zmenšené pro web, originály leží v `_media-originals/` (není veřejně přístupné).
Nové obrázky nahrávejte v administraci (Obrázky a videa) – uloží se do `assets/media/uploads/`.
