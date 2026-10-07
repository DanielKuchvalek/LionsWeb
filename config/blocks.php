<?php
/**
 * Upravitelné bloky obsahu (texty, obrázky, videa) – zobrazují se v administraci v sekci "Stránky".
 * 'default' = původní obsah z lionshandball.cz. Změny z administrace se ukládají do storage/content/blocks.json.
 *
 * Typy: text (jeden řádek), textarea (odstavce oddělené prázdným řádkem, **tučně**),
 *       lines (jedna položka na řádek), image, images (obrázky – jeden na řádek), video, url
 */
return [

    'home' => ['label' => 'Úvodní stránka', 'url' => '', 'fields' => [
        'hero_banner'       => ['type' => 'image', 'label' => 'Náborový banner', 'default' => '2026/05/ChatGPT-Image-May-7-2026-05_49_38-PM.png'],
        'hero_link'         => ['type' => 'url',   'label' => 'Kam vede banner', 'default' => 'lions-nabor/'],
        'video1'            => ['type' => 'video', 'label' => 'Video 1', 'default' => '2019/04/LIONS-NABOR-CHLAPCI-2026.mp4'],
        'video1_label'      => ['type' => 'text',  'label' => 'Popisek videa 1', 'default' => 'LIONS NÁBOR chlapci'],
        'video2'            => ['type' => 'video', 'label' => 'Video 2', 'default' => '2019/04/LIONS-NABOR-DEVCATA-2026.mp4'],
        'video2_label'      => ['type' => 'text',  'label' => 'Popisek videa 2', 'default' => 'LIONS NÁBOR děvčata'],
        'matches_title'     => ['type' => 'text',  'label' => 'Nadpis – domácí utkání', 'default' => 'NEJBLIŽŠÍ DOMÁCÍ UTKÁNÍ'],
        'matches_subtitle'  => ['type' => 'text',  'label' => 'Podnadpis – domácí utkání', 'default' => 'V LIONS ARÉNĚ HANDBALL CENTRA HOSTIVICE'],
        'matches_count'     => ['type' => 'text',  'label' => 'Počet zobrazených domácích utkání', 'default' => '6'],
        'links_note'        => ['type' => 'textarea', 'label' => 'Poznámka pod odkazy na soutěže', 'default' => 'PŘÍPRAVKA A MINIŽACTVO HRAJÍ TURNAJE BEZ VÝSLEDKŮ. TURNAJE A UTKÁNÍ VŠECH VĚKOVÝCH KATEGORIÍ LZE TAKÉ NALÉZT V KALENDÁŘÍCH JEDNOTLIVÝCH TÝMŮ.'],
        'about_title'       => ['type' => 'text',  'label' => 'O klubu – nadpis', 'default' => 'LIONS Handball'],
        'about_text'        => ['type' => 'textarea', 'label' => 'O klubu – text', 'default' => "Cílem klubu LIONS Handball Hostivice je provozování házenkářského oddílu všech mládežnických věkových kategorií, tedy od přípravky (děti od 6 let věku) přes minižactvo, mladší a starší žákovské kategorie (mládež do 15 let věku), až po dorostenecké kategorie (mládež do 18 let věku) se zaměřením nejen vychovávat talenty pro ligová oddíly, ale také široké skupině dětí a mládeže umožnit sportovat a trávit volný čas mezi vrstevníky.\n\nLIONS tým žen hraje od sezóny 2020/2021 1. ligu házené žen, nejvyšší republikovou soutěž.\n\nLIONS tým dorostu mladšího vstoupil do nejvyšší soutěže, do 1. ligy házené dorostu mladšího v sezóně 2024/2025, následovaný týmem LIONS dorostu staršího, který vstoupil do nejvyšší soutěže, do 1. ligy házené dorostu staršího v sezóně 2025/2026."],
        'about_image'       => ['type' => 'image', 'label' => 'O klubu – obrázek', 'default' => '2024/06/LIONS-web-home-01-1.png'],
        'calendar_title'    => ['type' => 'text',  'label' => 'Nadpis kalendáře', 'default' => 'LIONS Handball KALENDÁŘ'],
    ]],

    'team' => ['label' => 'Stránky týmů – společné', 'url' => 'pripravka/', 'fields' => [
        'join_title'   => ['type' => 'text', 'label' => 'Přihláška – nadpis', 'default' => 'VYZKOUŠEJTE TRÉNINK HÁZENÉ'],
        'join_text'    => ['type' => 'textarea', 'label' => 'Přihláška – text', 'default' => "RÁDI UMOŽŇUJEME NOVÝM ZÁJEMCŮM A ZÁJEMKYNÍM SI S NÁMI V LIONS VYZKOUŠET HÁZENOU A NĚKOLIK HÁZENKÁŘSKÝCH TRÉNINKŮ ZDARMA.\n\nNejlépe když Vášeho zájemce / zájemkyni přivedete rovnou v sálovém obutí a sportovním oblečení, ať se může zapojit do tréninku týmu."],
        'join_steps'   => ['type' => 'lines', 'label' => 'Přihláška – kroky (jeden na řádek)', 'default' => "Vyplňte krátký formulář\nOzveme se Vám zpět\nPřijďte na trénink – první tréninky zdarma"],
        'join_form_title' => ['type' => 'text', 'label' => 'Přihláška – nadpis formuláře', 'default' => 'Zájem o informace: LIONS NÁBOR'],
        'register_title'  => ['type' => 'text', 'label' => 'Registrace Sportlyzer – nadpis', 'default' => 'Registrace'],
        'register_text'   => ['type' => 'text', 'label' => 'Registrace Sportlyzer – text', 'default' => 'Členská přihláška přes systém SportLyzer'],
        'cta'          => ['type' => 'text', 'label' => 'Tlačítko na kontakt', 'default' => 'VYZKOUŠEJTE TRÉNINK HÁZENÉ - KLIKNĚTE A NAPIŠTE NÁM'],
    ]],

    'nabor' => ['label' => 'LIONS NÁBOR', 'url' => 'lions-nabor/', 'fields' => [
        'banner'      => ['type' => 'image', 'label' => 'Banner', 'default' => '2026/05/ChatGPT-Image-May-7-2026-05_49_38-PM.png'],
        'title'       => ['type' => 'textarea', 'label' => 'Hlavní nadpis', 'default' => 'RÁDI UMOŽŇUJEME NOVÝM ZÁJEMCŮM A ZÁJEMKYNÍM SI S NÁMI V LIONS VYZKOUŠET HÁZENOU A NĚKOLIK HÁZENKÁŘSKÝCH TRÉNINKŮ ZDARMA.'],
        'big'         => ['type' => 'textarea', 'label' => 'Zvýrazněný text', 'default' => "JE TOMU TAK I BĚHEM TÉTO SEZÓNY.\n**PŘIJĎTE, BUDETE VÍTÁNI.**"],
        'text'        => ['type' => 'textarea', 'label' => 'Text', 'default' => "Stačí si najít ten správný tým dle data narození a přijít na kterýkoliv z uvedených tréninků tohoto týmu.\n\nNejlépe když Vášeho zájemce / zájemkyni přivedete rovnou v sálovém obutí a sportovním oblečení, ať se může zapojit do tréninku týmu."],
        'text2'       => ['type' => 'textarea', 'label' => 'Text pod čarou', 'default' => 'Rodiče mohou sledovat tréninky z tribuny, posečkat v restauraci či se pro své dítko vrátit na konci tréninku. Těšíme se na Vás, LIONS.'],
        'leo'         => ['type' => 'images', 'label' => 'Obrázky maskota (střídají se)', 'default' => "2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_22_09-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_18_28-PM-e1753906181689.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_11_36-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_04_34-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_01_05-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-09_58_07-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-09_52_49-PM.png\n2019/04/LEO_ChatGPT-Image-Jun-13-2025-10_31_13-PM.png"],
        'ages_title'  => ['type' => 'text', 'label' => 'Nadpis výběru týmu', 'default' => 'VYBERTE LIONS TÝM DLE VĚKU'],
        'ages'        => ['type' => 'lines', 'label' => 'Věkové skupiny (ROČNÍKY | NÁZEV=adresa; NÁZEV=adresa)', 'default' => "NAROZENI 2018 A MLADŠÍ | PŘÍPRAVKA=pripravka/\nNAROZENI 2016 A 2017 | MINIŽACTVO=minizactvo/\nNAROZENI 2014 A 2015 | ŽÁKYNĚ MLADŠÍ=zakyne-mladsi/; ŽÁCI MLADŠÍ=zaci-mladsi/\nNAROZENI 2012 A 2013 | ŽÁKYNĚ STARŠÍ=zakyne-starsi/; ŽÁCI STARŠÍ=zaci-starsi/"],
    ]],

    'kontakt' => ['label' => 'KONTAKTY', 'url' => 'kontakt-new/', 'fields' => [
        'form_title' => ['type' => 'text', 'label' => 'Nadpis formuláře', 'default' => 'NAPIŠTE NÁM'],
        'org_kicker' => ['type' => 'text', 'label' => 'Spolek – štítek', 'default' => 'NAŠE INICIÁLY'],
        'org_name'   => ['type' => 'text', 'label' => 'Spolek – název', 'default' => 'TJ Sokol Hostivice'],
        'org_address'=> ['type' => 'text', 'label' => 'Spolek – adresa', 'default' => 'Husovo náměstí 13, 253 01, Hostivice'],
        'org_ds'     => ['type' => 'text', 'label' => 'Datová schránka ID', 'default' => '23dhdv4'],
        'org_bank'   => ['type' => 'text', 'label' => 'Bankovní účet u Komerční banky', 'default' => '115 – 1256140247 / 0100'],
        'org_ico'    => ['type' => 'text', 'label' => 'IČO', 'default' => '75092271'],
        'address'    => ['type' => 'text', 'label' => 'Adresa haly', 'default' => 'Handball Centrum Hostivice, Hájecká 1866, 253 01 Hostivice – Břve'],
        'map_title'  => ['type' => 'text', 'label' => 'Nadpis mapy', 'default' => 'PŘIJĎTE K NÁM'],
        'map_url'    => ['type' => 'url',  'label' => 'Google Maps – adresa pro vložení (embed)', 'default' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20487.541652935517!2d14.236376545604678!3d50.06863461478301!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x470bbebfbaa4c9a7%3A0xaf3959e4c1ca5696!2sSport%20Centrum%20Hostivice!5e0!3m2!1sen!2scz!4v1722897459125!5m2!1sen!2scz'],
    ]],

    'partneri' => ['label' => 'PARTNEŘI', 'url' => 'sponsors/', 'fields' => [
        'title' => ['type' => 'text', 'label' => 'Nadpis', 'default' => 'LIONS Handball partneři'],
    ]],

    'patron' => ['label' => 'BÝT PATRON', 'url' => 'kup-si-tym/', 'fields' => [
        'hero_title'  => ['type' => 'text', 'label' => 'Nadpis', 'default' => 'BUĎTE DŮLEŽITOU SOUČÁSTÍ'],
        'hero_title2' => ['type' => 'text', 'label' => 'Nadpis – 2. řádek', 'default' => 'LVÍ SMEČKY'],
        'hero_sub'    => ['type' => 'textarea', 'label' => 'Podnadpis', 'default' => "PROPAGUJTE SVOU ZNAČKU\nPODPOŘTE SVŮJ LIONS TÝM"],
        'jerseys'     => ['type' => 'images', 'label' => 'Dresy (střídají se)', 'default' => "2024/08/LIONS-JERSEY-NEW-2023_ALL_ZENY-1024x433.png\n2024/08/LIONS-JERSEY-NEW-2023_ALL_DOROSTENCI-1024x433.png\n2024/08/LIONS-JERSEY-NEW-2023_ALL_ZACI-1024x433.png\n2024/08/LIONS-JERSEY-NEW-2023_ALL_ZACKY-1024x433.png\n2024/08/LIONS-JERSEY-NEW-2023_ALL_MINIZACTVO-1024x433.png"],
        'lead'        => ['type' => 'textarea', 'label' => 'Úvodní text', 'default' => 'LIONS HANDBALL jako sportovní klub POTŘEBUJE pro svou finanční stabilitu dlouhodobou SPOLUPRÁCI S PARTNERY. Proto nejen tento program pro partnery / naše podporovatele.'],
        'steps'       => ['type' => 'lines', 'label' => 'Body nabídky (NADPIS | text)', 'default' => "CO NABÍZÍME? | Nejdůležitějším a nejviditelnějším místem pro logo je přední část dresu, kde pod logy hlavních partnerů je volné místo pro logo právě vaší firmy.\nCO JEŠTĚ ZÍSKÁTE? | Vybraný tým dětí a mládeže nebo tým žen, který bude propagovat Vaši firmu, který bude reprezentovat Vaši společnost v rámci sportovních a společenských akcí.\nALE JEŠTĚ … | Reklamní banner a muší křídlo v interiéru sportovní haly, ale také mnoho dalších příležitostí k propagaci vaší značky, dle individuální dohody při uzavření smlouvy o reklamě.\nJAK TEDY NA TO? | Napište na náš email lions@lionshandball.cz."],
        'photos'      => ['type' => 'images', 'label' => 'Fotky 1', 'default' => "2024/06/DSC_8759-scaled-1-1024x617.jpg\n2024/06/DSC_8580-scaled-1-1024x687.jpg\n2024/06/DSC_8672-1024x773.jpg\n2024/06/DSC_8656-1024x696.jpg"],
        'mid_title'   => ['type' => 'text', 'label' => 'Prostřední nadpis', 'default' => 'PROPAGUJTE svou ZNAČKU'],
        'mid_title2'  => ['type' => 'text', 'label' => 'Prostřední nadpis – 2. řádek', 'default' => 'SKRZE svůj VYBRANÝ LIONS TEAM'],
        'claims'      => ['type' => 'lines', 'label' => 'Hesla', 'default' => "S NÁMI BUDETE VIDĚT\nBUDEME VÁS REPREZENTOVAT\nA PŘISPĚJETE K NAŠIM ÚSPĚCHŮM"],
        'signature'   => ['type' => 'textarea', 'label' => 'Podpis', 'default' => "**Těšíme se na spolupráci,**\nLIONS Handball"],
        'photos2'     => ['type' => 'images', 'label' => 'Fotky 2', 'default' => "2024/08/A7403437-1024x683.jpg\n2024/08/A7400160-1024x683.jpg"],
        'facts'       => ['type' => 'lines', 'label' => 'Fakta o klubu (NADPIS | text, nebo jen text)', 'default' => "LIONS HANDBALL je účastníkem nejvyšší republikové soutěže, 1. LIGY házené v kategoriích: | ŽEN a DOROSTENCŮ Mladších\nLIONS týmy dětí a mládeže hrají dlouhodobé soutěže a jednotlivé turnaje po celé republice, včetně několika akcí v zahraničí.\nLIONS ORGANIZUJE celorepublikové dlouhodobé soutěže pro kategorie: | ŽAKYŇ Mladších a ŽÁKŮ Mladších\nLIONS ORGANIZUJE několik samostatných turnajů všech věkových kategorií.\nLIONS ORGANIZUJE celorepublikové házenkářské kempy www.czechhandballcamp.cz\nLIONS je GARANTEM sportovních tříd na ZŠ Hostivice\nLIONS ORGANIZUJE kroužky míčových her na školách a o letních prázdninách příměstské sportovní tábory."],
        'contact_title' => ['type' => 'text', 'label' => 'Závěrečný nadpis', 'default' => 'NAPIŠTĚ NÁM NA'],
    ]],

    'minigym' => ['label' => 'LIONS miniGYM', 'url' => 'mini-gym/', 'fields' => [
        'title'   => ['type' => 'text', 'label' => 'Nadpis', 'default' => 'LIONS miniGYM BOOKING'],
        'service' => ['type' => 'text', 'label' => 'Text služby', 'default' => 'LIONS miniGYM booking !!! REZERVACÍ SOUHLASÍŠ S PRAVIDLY LIONS miniGYM !!!'],
        'rules'   => ['type' => 'image', 'label' => 'Pravidla (obrázek)', 'default' => '2024/08/FITKO-pravidla.png'],
        'open'    => ['type' => 'text', 'label' => 'Nejdřívější začátek rezervace (hodina)', 'default' => '11'],
        'close'   => ['type' => 'text', 'label' => 'Zavírá se v (hodina) – poslední rezervace musí skončit nejpozději v tuto hodinu', 'default' => '22'],
        'capacity'=> ['type' => 'text', 'label' => 'Kolik lidí může cvičit současně', 'default' => '1'],
        'duration'=> ['type' => 'text', 'label' => 'Délka rezervace (minuty)', 'default' => '60'],
        'step'    => ['type' => 'text', 'label' => 'Rezervace může začít každých … minut', 'default' => '30'],
    ]],

    'privacy' => ['label' => 'Ochrana osobních údajů (GDPR)', 'url' => 'ochrana-osobnich-udaju/', 'fields' => [
        'title'   => ['type' => 'text', 'label' => 'Nadpis', 'default' => 'Ochrana osobních údajů'],
        'updated' => ['type' => 'text', 'label' => 'Platné od', 'default' => '1. 10. 2026'],
        'text'    => ['type' => 'textarea', 'label' => 'Text zásad (## nadpis, - odrážka, **tučně**, [odkaz](adresa); {spolek}, {adresa}, {ico}, {email}, {retence_formulare}, {retence_minigym} se doplní automaticky)', 'default' => '## Kdo vaše údaje zpracovává
Správcem osobních údajů je **{spolek}**, {adresa}, IČO {ico} (dále „klub“ nebo „LIONS Handball“). V otázkách ochrany osobních údajů nás kontaktujte na e-mailu [{email}](mailto:{email}).

## Jaké údaje zpracováváme a proč
Zpracováváme jen údaje, které nám sami pošlete přes formuláře na tomto webu, a jen k účelu, pro který jste je poslali.

- **Nábor a přihlášky do týmů** (jméno, e-mail, telefon, ročník narození, zvolený tým) – abychom vás kontaktovali ohledně tréninků a členství v klubu. Právní základ: jednání o uzavření smlouvy (čl. 6 odst. 1 písm. b) GDPR). Uchováváme {retence_formulare} měsíců od odeslání.
- **Kontaktní formulář** (jméno, e-mail, text dotazu) – abychom odpověděli na váš dotaz. Právní základ: oprávněný zájem odpovědět na dotaz (čl. 6 odst. 1 písm. f) GDPR). Uchováváme {retence_formulare} měsíců.
- **Přihlášky do kroužků LVÍČATA** (údaje účastníka včetně rodného čísla, adresa, údaje zákonného zástupce) – k evidenci účastníků kroužku a komunikaci se zákonným zástupcem. Rodné číslo slouží k jednoznačné identifikaci účastníka. Právní základ: plnění smlouvy (čl. 6 odst. 1 písm. b) GDPR). Uchováváme {retence_formulare} měsíců od odeslání přihlášky. Rodné číslo neposíláme e-mailem, je dostupné jen oprávněným osobám v administraci webu.
- **Školní liga minihází** (kontaktní údaje školy a kontaktní osoby) – k organizaci soutěže. Právní základ: oprávněný zájem (čl. 6 odst. 1 písm. f) GDPR). Uchováváme {retence_formulare} měsíců.
- **Rezervace LIONS miniGYM** (jméno, e-mail, telefon, termín) – ke správě rezervací posilovny. Právní základ: plnění smlouvy (čl. 6 odst. 1 písm. b) GDPR). Uchováváme {retence_minigym} měsíců od termínu rezervace.
- **Technické údaje** (IP adresa při odeslání formuláře) – k ochraně webu proti spamu a zneužití. Právní základ: oprávněný zájem (čl. 6 odst. 1 písm. f) GDPR). IP adresu u formulářů mažeme po 30 dnech, záznamy pro ochranu proti zahlcení webu po 24 hodinách.
- **Anonymní statistika návštěvnosti** – počítáme, které stránky se zobrazují a na které odkazy a tlačítka se kliká (např. „menu → MUŽI“), z jakého webu návštěvníci přišli a zda jde o mobil nebo počítač. Ukládají se jen souhrnné denní počty, bez cookies, bez IP adresy a bez jakéhokoli identifikátoru – z dat nelze zjistit, kdo web navštívil. Slouží ke zlepšování webu. Uchováváme 13 měsíců.

Po uplynutí uvedených lhůt se údaje z webu automaticky smažou. Zálohy databáze se uchovávají nejvýše 30 dní.

## Kdo k údajům má přístup
K údajům mají přístup pouze pověření členové vedení a trenéři klubu. Údaje nepředáváme dalším osobám ani je neprodáváme. Technicky je zpracovává poskytovatel webhostingu a e-mailu klubu (jako zpracovatel na základě smlouvy). Údaje nepředáváme mimo Evropskou unii.

Pokud se registrujete přes systém **Sportlyzer** (členská přihláška a klubová aplikace), zpracovává údaje také provozovatel této služby podle svých podmínek.

## Vaše práva
Máte právo:

- požadovat přístup ke svým údajům a jejich kopii,
- požadovat opravu nepřesných údajů,
- požadovat výmaz údajů nebo omezení jejich zpracování,
- vznést námitku proti zpracování na základě oprávněného zájmu,
- na přenositelnost údajů,
- podat stížnost u Úřadu pro ochranu osobních údajů, Pplk. Sochora 27, 170 00 Praha 7, [www.uoou.cz](https://www.uoou.cz).

Svá práva uplatníte e-mailem na [{email}](mailto:{email}). Odpovíme nejpozději do jednoho měsíce.

## Cookies
Tento web nepoužívá žádné analytické ani reklamní cookies. Návštěvnost měříme anonymně bez cookies (viz výše). Některé stránky zobrazují obsah jiných služeb (Sportlyzer, Google Mapy, Facebook), které mohou ukládat vlastní cookies. Podrobnosti najdete v [zásadách cookies](/zasady-cookies/).'],
    ]],

    'cookies' => ['label' => 'Zásady cookies', 'url' => 'zasady-cookies/', 'fields' => [
        'title'   => ['type' => 'text', 'label' => 'Nadpis', 'default' => 'Zásady používání cookies'],
        'text'    => ['type' => 'textarea', 'label' => 'Text zásad', 'default' => '## Co jsou cookies
Cookies jsou malé soubory, které si webová stránka ukládá do vašeho prohlížeče.

## Cookies tohoto webu
Web LIONS Handball **nepoužívá žádné analytické, marketingové ani reklamní cookies**. Návštěvnost a prokliky počítáme anonymně bez cookies – ukládají se jen souhrnné denní počty, bez IP adresy a bez možnosti rozpoznat jednotlivé návštěvníky. Používáme jen nezbytné cookies:

- **lions_admin** – přihlášení správců do administrace webu (do zavření prohlížeče; návštěvníkům se nenastavuje).
- **lionsAdmin** (úložiště prohlížeče) – jen u správců: označí prohlížeč správce, aby se jeho návštěvy nezapočítávaly do statistiky.

Písma i loga týmů načítáme z vlastního serveru, bez kontaktování cizích služeb.

## Obsah jiných služeb
Některé části webu zobrazují obsah jiných služeb (kalendáře a soupisky ze Sportlyzeru, mapy Google), které mohou ukládat vlastní cookies. Příspěvky z Facebooku se načtou až po kliknutí na „Zobrazit příspěvky zde“. Přehled služeb najdete níže.

## Jak cookies smazat nebo zablokovat
Cookies můžete kdykoli smazat nebo zablokovat v nastavení svého prohlížeče.'],
    ]],

    'lvicata-zs-hostivice' => ['label' => 'LVÍČATA ZŠ Hostivice', 'url' => 'lvicata-zs-hostivice/', 'fields' => [
        'flyer' => ['type' => 'image', 'label' => 'Leták', 'default' => '2026/08/LVICATA-ZS-HOSTIVICE_2026_A5_150.png'],
    ]],
    'lvicata-krouzek-micovek-zs-chyne-vida' => ['label' => 'LVÍČATA ZŠ Chýně VIDA', 'url' => 'lvicata-krouzek-micovek-zs-chyne-vida/', 'fields' => [
        'flyer' => ['type' => 'image', 'label' => 'Leták', 'default' => '2026/08/LVICATA-ZS-VIDA_2026_A5_150.png'],
    ]],
    'skolni-liga-minihazene-lvicata-zapad' => ['label' => 'ŠKOLNÍ LIGA MINIHÁZENÉ', 'url' => 'skolni-liga-minihazene-lvicata-zapad/', 'fields' => [
        'title' => ['type' => 'text',  'label' => 'Název stránky', 'default' => 'PRAHA ZÁPAD … ŠKOLNÍ LIGA MINIHÁZENÉ a ŠKOLSKÝ POHÁR HÁZENÉ 25/26'],
        'flyer' => ['type' => 'image', 'label' => 'Leták', 'default' => '2025/11/Skolni-Liga-MINIhazene-LVICATA-ZAPAD_flyer_25-1.png'],
    ]],
    'czech-handball-camps' => ['label' => 'CZECH HANDBALL CAMPs', 'url' => 'czech-handball-camps/', 'fields' => [
        'flyer' => ['type' => 'image', 'label' => 'Plakát', 'default' => '2026/08/ChatGPT-Image-Jul-23-2026-10_45_02-PM.png'],
        'link'  => ['type' => 'url',   'label' => 'Odkaz', 'default' => 'https://www.czechhandballcamp.cz'],
    ]],
    'championship-zactva-mladsiho' => ['label' => 'CHAMPIONSHIP ŽÁKŮ Mladších', 'url' => 'championship-zactva-mladsiho/', 'fields' => [
        'flyer' => ['type' => 'image', 'label' => 'Propozice', 'default' => '2026/08/KOFOLA-championship-hazene-zaku-mladsich_20262027_propozice_design.png'],
        'link'  => ['type' => 'url',   'label' => 'Odkaz (nepovinné)', 'default' => ''],
    ]],
    'sport-tridy-zs-hostivice' => ['label' => 'SPORT TŘÍDY ZŠ Hostivice', 'url' => 'sport-tridy-zs-hostivice/', 'fields' => [
        'flyer' => ['type' => 'image', 'label' => 'Leták', 'default' => '2024/08/SPORTS-CLASS-2024.png'],
        'link'  => ['type' => 'url',   'label' => 'Odkaz (nepovinné)', 'default' => ''],
    ]],
    'letni-sport-primestske-tabory' => ['label' => 'Letní TÁBORY', 'url' => 'letni-sport-primestske-tabory/', 'fields' => [
        'flyer'   => ['type' => 'image', 'label' => 'Leták', 'default' => '2026/01/SCH-TABOR-letni-2026.png'],
        'title'   => ['type' => 'text',  'label' => 'Nadpis', 'default' => 'Aktuálně je možno se přihlašovat na letní'],
        'title2'  => ['type' => 'text',  'label' => 'Nadpis – 2. řádek', 'default' => 'SPORT klasické a příměstské TÁBORY 2025'],
        'link'    => ['type' => 'url',   'label' => 'Odkaz na přihlášky', 'default' => 'https://sportcentrumhostivice.cz/tabory/'],
        'gallery' => ['type' => 'images','label' => 'Galerie', 'default' => "2024/08/NK3_7517-scaled-e1723755294786-1024x511.jpg\n2024/06/DSC_6490-e1723755513349-1024x512.jpg\n2024/06/DSC_0025-scaled-1-e1723755631836-1024x511.jpg\n2024/06/DSC_1086-e1723755565226-1024x512.jpg\n2024/08/NK3_7657-scaled-e1723755254247-1024x511.jpg\n2024/08/NK3_7715-1-scaled-e1723755344210-1024x511.jpg"],
    ]],
];
