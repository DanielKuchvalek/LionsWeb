<?php
/**
 * FORMULÁŘE – náhrada za WPForms.
 * Odeslaná data se posílají e-mailem na adresu z config/site.php ('email')
 * a zároveň se ukládají do storage/forms/<id>.csv (záloha, kdyby e-mail neprošel).
 *
 * Typy polí: name (jméno+příjmení), email (s potvrzením), phone (9 číslic za +420), rc (rodné číslo),
 *            year (rok narození), text, textarea,
 *            radio, checkbox, select, address, hidden
 * 'sensitive' => true  – citlivý údaj (např. rodné číslo): neposílá se e-mailem, v administraci je skrytý
 * 'guardian'  => true  – formulář vyplňuje zákonný zástupce za dítě (jiné znění potvrzení GDPR)
 * 'confirm'   => [...]  – potvrzovací e-mail pro odesílatele (předmět, nadpis, úvodní text)
 * 'team_field'          – pole s vybraným týmem → do potvrzení se vypíšou jeho tréninky
 * Každý formulář má automaticky povinné potvrzení o zpracování osobních údajů.
 */
return [

    // Krátká přihláška na stránce každého týmu (tým se doplní automaticky)
    'prihlaska' => [
        'title'         => null,
        'subject'       => 'LIONS NÁBOR – přihláška',
        'subject_field' => 'tým',
        'team_field'    => 'tym',
        'confirm' => [
            'subject' => 'Děkujeme za přihlášku do LIONS',
            'heading' => 'Děkujeme za přihlášku!',
            'intro'   => 'Přihlášku jsme přijali a brzy se vám ozveme. Nemusíte ale čekat – první tréninky jsou zdarma, stačí přijít na kterýkoliv trénink týmu.',
        ],
        'submit'        => 'Odeslat přihlášku',
        'fields' => [
            ['type' => 'hidden', 'name' => 'tym',     'label' => 'tým'],
            ['type' => 'name',   'name' => 'jmeno',   'label' => 'Jméno', 'required' => true],
            ['type' => 'email',  'name' => 'email',   'label' => 'E-mail', 'required' => true],
            ['type' => 'phone',  'name' => 'telefon', 'label' => 'Telefon', 'required' => true],
            ['type' => 'year',   'name' => 'rocnik',  'label' => 'Ročník narození'],
            ['type' => 'textarea', 'name' => 'zprava', 'label' => 'Poznámka', 'half' => true],
        ],
    ],

    'nabor' => [
        'title'       => 'Zájem o informace: LIONS NÁBOR 2026 léto',
        'team_field'  => 'tym',          // podle této volby se do potvrzení vypíšou tréninky týmu
        'option_teams' => [
            'Přípravka Chýně (narozeni 2018 a mladší)'                        => 'pripravka-chyne',
            'Žáci Mladší Hostivice - Břve (narozeni 2014 a 2015)'              => 'zaci-mladsi',
            'Přípravka Hostivice -Břve (narozeni 2018 a mladší)'               => 'pripravka',
            'Žákyně Mladší Hostivice - Břve + Chýně (narozeny 2014 a 2015)'    => 'zakyne-mladsi',
            'MINIžactvo Chýně (narozeni 2016 a 2017)'                          => 'minizactvo-chyne',
            'Žáci Starší Hostivice - Břve (narozeni 2012 a 2013'               => 'zaci-starsi',
            'MINIžactvo Hostivice - Břve (narozeni 2016 a 2017)'               => 'minizactvo',
            'Žákyně Starší Hostivice - Břve (narozeny 2012 a 2013)'            => 'zakyne-starsi',
        ],
        'confirm' => [
            'subject' => 'Děkujeme za zájem o LIONS',
            'heading' => 'Děkujeme za zájem o LIONS!',
            'intro'   => 'Váš zájem o házenou jsme přijali a brzy se vám ozveme. Nemusíte ale čekat – první tréninky jsou zdarma, stačí přijít na kterýkoliv trénink vybraného týmu.',
        ],
        'description' => 'budeme Vás kontaktovat zpět ... prosíme o kontrolu složek spamu i hromadných',
        'subject'     => 'LIONS NÁBOR – zájem o informace',
        'submit'      => 'Odeslat',
        'fields' => [
            ['type' => 'name',  'name' => 'jmeno',   'label' => 'Jméno', 'required' => true],
            ['type' => 'email', 'name' => 'email',   'label' => 'E-mail', 'required' => true, 'confirm' => true],
            ['type' => 'phone', 'name' => 'telefon', 'label' => 'Telefon', 'required' => true],
            ['type' => 'radio', 'name' => 'tym',     'label' => 'tým dle věku', 'required' => true, 'options' => [
                'Přípravka Chýně (narozeni 2018 a mladší)',
                'Žáci Mladší Hostivice - Břve (narozeni 2014 a 2015)',
                'Přípravka Hostivice -Břve (narozeni 2018 a mladší)',
                'Žákyně Mladší Hostivice - Břve + Chýně (narozeny 2014 a 2015)',
                'MINIžactvo Chýně (narozeni 2016 a 2017)',
                'Žáci Starší Hostivice - Břve (narozeni 2012 a 2013',
                'MINIžactvo Hostivice - Břve (narozeni 2016 a 2017)',
                'Žákyně Starší Hostivice - Břve (narozeny 2012 a 2013)',
            ], 'columns' => 2],
        ],
    ],

    'kontakt' => [
        'title'   => null,
        'subject' => 'Dotaz z webu LIONS',
        'confirm' => [
            'subject' => 'Děkujeme za zprávu – LIONS Handball',
            'heading' => 'Děkujeme za zprávu',
            'intro'   => 'Vaši zprávu jsme přijali a odpovíme co nejdříve. Odpověď může někdy zapadnout do složky spam nebo hromadné – zkontrolujte ji prosím.',
        ],
        'submit'  => 'Odeslat',
        'fields' => [
            ['type' => 'name',  'name' => 'jmeno', 'label' => 'Jméno', 'required' => true],
            ['type' => 'email', 'name' => 'email', 'label' => 'E-mail', 'required' => true, 'confirm' => true,
             'help' => 'Prosíme zkontrolujte správnost Vámi zadané email adresy.'],
            ['type' => 'select', 'name' => 'tema', 'label' => 'Čeho se Váš dotaz asi týká?', 'required' => true, 'options' => [
                'LIONS obecně',
                'LION Přípravka (ročníky 2018 a mladší)',
                'LIONS MINIžactvo (ročníky 2016 až 2017)',
                'LIONS dívky žákyně (ročníky 2012 až 2015)',
                'LIONS chlapci žáci (ročníky 2012 až 2015)',
                'LIONS dívky dorost (ročníky 2010 až 2011)',
                'LIONS chlapci dorost (ročníky 2008 až 2011)',
                'LVÍČATA kroužky',
            ]],
            ['type' => 'textarea', 'name' => 'zprava', 'label' => 'Detail dotazu či důvodu kontaktu:',
             'help' => 'Prosíme pro odpověď kontrolujte i Vaše email složky spam a hromadné, kam někdy naše odpověď může zapadnout.'],
        ],
    ],

    'lvicata-hostivice' => [
        'title'   => null,
        'subject' => 'LVÍČATA ZŠ Hostivice – přihláška',
        'confirm' => [
            'subject' => 'Přihláška do kroužku LVÍČATA přijata',
            'heading' => 'Přihláška přijata',
            'intro'   => 'Děkujeme, přihlášku do kroužku míčových her LVÍČATA jsme přijali. Další informace vám pošleme e-mailem.',
        ],
        'guardian' => true,
        'greet_field' => 'zastupce',   // v potvrzení oslovit zákonného zástupce, ne dítě
        'submit'  => 'ZDE POTVRZUJETE PŘIHLÁŠKU',
        'fields' => [
            ['type' => 'name',    'name' => 'ucastnik', 'label' => 'jméno účastníka', 'required' => true],
            ['type' => 'address', 'name' => 'adresa',   'label' => 'adresa účastníka'],
            // poloviční pole jsou v párech: rodné číslo + třída, telefon + družina
            ['type' => 'rc',      'name' => 'rodne_cislo', 'label' => 'rodné číslo účastníka', 'required' => true, 'sensitive' => true],
            ['type' => 'text',    'name' => 'trida',    'label' => 'třída ZŠ Hostivice', 'required' => true],
            ['type' => 'name',    'name' => 'zastupce', 'label' => 'jméno zákonného zástupce', 'required' => true],
            ['type' => 'email',   'name' => 'email',    'label' => 'email zákonného zástupce', 'required' => true, 'confirm' => true],
            ['type' => 'phone',   'name' => 'telefon',  'label' => 'telefonní číslo zákonného zástupce', 'required' => true],
            ['type' => 'radio',   'name' => 'druzina',  'label' => 'vyzvedávat a vracet do družiny', 'required' => true, 'options' => ['ANO', 'NE'], 'inline' => true],
            ['type' => 'text',    'name' => 'vzkaz',    'label' => 'vzkaz pro organizátora', 'full' => true],
        ],
    ],

    'lvicata-vida' => [
        'title'   => null,
        'subject' => 'LVÍČATA ZŠ Chýně VIDA – přihláška',
        'confirm' => [
            'subject' => 'Přihláška do kroužku LVÍČATA přijata',
            'heading' => 'Přihláška přijata',
            'intro'   => 'Děkujeme, přihlášku do kroužku míčových her LVÍČATA jsme přijali. Další informace vám pošleme e-mailem.',
        ],
        'guardian' => true,
        'greet_field' => 'zastupce',   // v potvrzení oslovit zákonného zástupce, ne dítě
        'submit'  => 'ZDE POTVRZUJETE PŘIHLÁŠKU',
        'fields' => [
            ['type' => 'name',    'name' => 'ucastnik', 'label' => 'jméno účastníka', 'required' => true],
            ['type' => 'address', 'name' => 'adresa',   'label' => 'adresa účastníka'],
            // poloviční pole jsou v párech: rodné číslo + třída, telefon + družina
            ['type' => 'rc',      'name' => 'rodne_cislo', 'label' => 'rodné číslo účastníka', 'required' => true, 'sensitive' => true],
            ['type' => 'text',    'name' => 'trida',    'label' => 'třída ZŠ Chýně VIDA', 'required' => true],
            ['type' => 'name',    'name' => 'zastupce', 'label' => 'jméno zákonného zástupce', 'required' => true],
            ['type' => 'email',   'name' => 'email',    'label' => 'email zákonného zástupce', 'required' => true, 'confirm' => true],
            ['type' => 'phone',   'name' => 'telefon',  'label' => 'telefonní číslo zákonného zástupce', 'required' => true],
            ['type' => 'radio',   'name' => 'druzina',  'label' => 'vyzvedávat a vracet do družiny', 'required' => true, 'options' => ['ANO', 'NE'], 'inline' => true],
            ['type' => 'text',    'name' => 'vzkaz',    'label' => 'vzkaz pro organizátora', 'full' => true],
        ],
    ],

    'skolni-liga' => [
        'title'   => 'PŘEDBĚŽNÝ ZÁJEM ... ŠKOLNÍ LIGA MINIHÁZENÉ a ŠKOLSKÝ POHÁR HÁZENÉ 25/26',
        'subject' => 'ŠKOLNÍ LIGA MINIHÁZENÉ – předběžný zájem',
        'confirm' => [
            'subject' => 'Děkujeme za zájem – Školní liga minihází',
            'heading' => 'Děkujeme za projevený zájem',
            'intro'   => 'Váš předběžný zájem o Školní ligu minihází a Školský pohár házené jsme přijali. Brzy se vám ozveme s dalšími informacemi.',
        ],
        'submit'  => 'ODESLAT',
        'fields' => [
            ['type' => 'text',  'name' => 'skola',   'label' => 'název základní školy', 'required' => true],
            ['type' => 'name',  'name' => 'kontakt', 'label' => 'jméno a příjmení kontaktní osoby pro komunikaci', 'required' => true],
            ['type' => 'email', 'name' => 'email',   'label' => 'email kontaktní osoby pro komunikaci', 'required' => true, 'confirm' => true],
            ['type' => 'phone', 'name' => 'telefon', 'label' => 'telefon kontaktní osoby pro komunikaci', 'required' => true],
            ['type' => 'checkbox', 'name' => 'kategorie', 'label' => 'věkové kategorie školních týmů',
             'help' => '... možnost označení všech týmů nebo výběr dle preferencí', 'options' => [
                '1.-3. ročníky ... CHLAPCI ...  MINIházená ... Školní Liga LVÍČATA ZÁPAD',
                '4.-5. ročníky ... CHLAPCI ...  MINIházená ... Školní Liga LVÍČATA ZÁPAD',
                '5.-7. ročníky ... CHLAPCI ...  HÁZENÁ ... Školský Pohár okresní kolo Praha Západ',
                '1.-3. ročníky ... DĚVČATA ...  MINIházená ... Školní Liga LVÍČATA ZÁPAD',
                '4.-5. ročníky ... DĚVČATA ...  MINIházená ... Školní Liga LVÍČATA ZÁPAD',
                '5.-7. ročníky ... DĚVČATA ...  HÁZENÁ ... Školský Pohár okresní kolo Praha Západ',
            ]],
            ['type' => 'checkbox', 'name' => 'kola', 'label' => 'jednotlivá kola soutěží',
             'help' => '... možnost označení všech termínů nebo výběr dle preferencí', 'options' => [
                'CHLAPCI ... HÁZENÁ okresní kolo ... 14.01.2026',
                'CHLAPCI ... MINIházená 1. kolo ... 22.01.2026',
                'CHLAPCI ... MINIházená 2. kolo ... 19.03.2026',
                'CHLAPCI ... MINIházená 3. kolo ... 21.05.2026',
                'DĚVČATA ... HÁZENÁ okresní kolo ... 15.01.2026',
                'DĚVČATA ... MINIházená 1. kolo ... 29.01.2026',
                'DĚVČATA ...  MINIházená 2. kolo ... 26.03.2026',
                'DĚVČATA ... MINIházená 3. kolo ... 28.05.2026',
            ]],
            ['type' => 'radio', 'name' => 'ukazka', 'label' => 'předběžný zájem o ukázkové hodiny', 'options' => ['ANO', 'NE'], 'inline' => true],
            ['type' => 'textarea', 'name' => 'dotaz', 'label' => 'případný rychlý dotaz k zodpovězení od nás'],
        ],
    ],
];
