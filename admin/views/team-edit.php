<?php
$k = (string) ($_GET['t'] ?? '');
$t = $k !== '' ? ($GLOBALS['TEAMS'][$k] ?? null) : null;
$isNew = $t === null;
$t ??= ['title' => '', 'label' => '', 'home_tag' => '', 'photo' => null, 'heading' => null, 'info' => [], 'training_title' => 'TRÉNINK 2x týdně:', 'training' => [], 'notes' => [], 'cta' => true, 'register' => true, 'sportlyzer' => null, 'jersey' => null, 'partners' => [], 'csh' => [], 'widgets' => [], 'in_menu' => true, 'menu_label' => ''];
?>
<div class="toolbar">
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('teams')) ?>">← všechny týmy</a>
  <?php if (!$isNew): ?><a class="btn btn--ghost btn--sm" href="<?= e(url($k . '/')) ?>" target="_blank">Zobrazit stránku ↗</a><?php endif; ?>
</div>

<form method="post" class="editor">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="team-save">
  <input type="hidden" name="old_key" value="<?= e($k) ?>">

  <section class="card">
    <h2>Základní údaje</h2>
    <div class="cols2">
      <?= a_text('title', $t['title'], 'Název stránky', 'Zobrazí se v záhlaví stránky, např. „PŘÍPRAVKA Hostivice“.', ['required' => 'required']) ?>
      <?= a_text('key', $k, 'Adresa stránky', 'Např. „pripravka“ → web.cz/pripravka/. Změna rozbije staré odkazy.') ?>
      <?= a_text('menu_label', $t['menu_label'], 'Název v menu', 'Když je prázdné, použije se název stránky.') ?>
      <?= a_text('label', $t['label'], 'Štítek týmu', 'Malý nadpis nad sekcemi, např. „LIONS PŘÍPRAVKA“.') ?>
      <?= a_text('home_tag', $t['home_tag'], 'Štítek na úvodní stránce', 'Červený štítek u utkání, např. „ŽENY“, „DOROSTENCI MLADŠÍ“ (zkratky STD/MLD se vypíšou celé).') ?>
    </div>
    <?= a_check('in_menu', (bool) $t['in_menu'], 'Zobrazit v menu LIONS TÝMY') ?>
  </section>

  <section class="card">
    <h2>Úvod stránky</h2>
    <div class="cols2">
      <?= a_media('photo', $t['photo'], 'Fotka týmu / hráče') ?>
      <div>
        <?= a_text('heading', $t['heading'] ?? '', 'Nadpis', 'Když je prázdný, úvodní blok se nezobrazí.') ?>
        <?= a_textarea('info', implode("\n", $t['info']), 'Informace (každý řádek zvlášť)', 'Ročníky, trenér… **text** = tučně.', 3) ?>
      </div>
    </div>
    <div class="cols2">
      <div>
        <?= a_text('training_title', $t['training_title'], 'Nadpis tréninků') ?>
        <?= a_textarea('training', implode("\n", $t['training']), 'Tréninky (jeden na řádek)', 'Formát: DEN … čas … místo, např. „ÚTERÝ … 16:00h – 17:30h … LIONS Aréna Hostivice – Břve“', 5) ?>
      </div>
      <?= a_textarea('notes', implode("\n", $t['notes']), 'Doplňující informace (jedna na řádek)', 'Turnaje, členský příspěvek…', 5) ?>
    </div>
  </section>

  <section class="card">
    <h2>Přihláška a nábor</h2>
    <?= a_check('register', (bool) $t['register'], 'Zobrazit sekci PŘIHLÁŠKA s formulářem a registrací Sportlyzer') ?>
    <?= a_check('cta', (bool) $t['cta'], 'Zobrazit odkaz „napište nám“ na kontaktní formulář') ?>
    <p class="muted">Společné texty přihlášky upravíte v <a href="<?= e(admin_url('page-edit', ['g' => 'team'])) ?>">Stránky → Stránky týmů – společné</a>.</p>
  </section>

  <section class="card">
    <h2>Utkání, výsledky a tabulka – Český svaz házené</h2>
    <p class="muted">Vložte adresu soutěže z handball.cz (např. <code>https://www.handball.cz/souteze/zeny/liga-zeny-cechyyy</code>) nebo jen její konec. Rozpis, výsledky a tabulka se pak načítají automaticky. Soutěže LIONS najdete na <a href="<?= e(admin_url()) ?>">Přehledu</a>.</p>
    <div class="repeater" data-repeater>
      <div class="repeater__rows">
        <?php foreach ($t['csh'] ?: [] as $i => $c): ?>
          <div class="repeater__row">
            <?= a_text("csh[$i][slug]", $c['slug'], 'Soutěž (slug nebo adresa)') ?>
            <?= a_select("csh[$i][sex]", $c['sex'], 'Kategorie', ['zeny' => 'ženy (zeny)', 'muzi' => 'muži (muzi)']) ?>
            <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <template>
        <div class="repeater__row">
          <?= a_text('csh[__i__][slug]', '', 'Soutěž (slug nebo adresa)') ?>
          <?= a_select('csh[__i__][sex]', 'zeny', 'Kategorie', ['zeny' => 'ženy (zeny)', 'muzi' => 'muži (muzi)']) ?>
          <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
        </div>
      </template>
      <button type="button" class="btn btn--sm" data-row-add>+ Přidat soutěž</button>
    </div>
  </section>

  <section class="card">
    <h2>Sportlyzer a widgety</h2>
    <?= a_text('sportlyzer', (string) ($t['sportlyzer'] ?? ''), 'ID skupiny ve Sportlyzeru', 'Z adresy widgetu „…groupId=87854“. Podle něj se zobrazí TÝM a KALENDÁŘ. Prázdné = nezobrazovat.', ['inputmode' => 'numeric']) ?>
    <h3>Další widgety</h3>
    <p class="muted">Libovolný widget (Sportlyzer, YouTube, mapa…). Buď adresa pro vložení (iframe), nebo celý vložený kód.</p>
    <div class="repeater" data-repeater>
      <div class="repeater__rows">
        <?php foreach ($t['widgets'] ?: [] as $i => $w): ?>
          <div class="repeater__row repeater__row--block">
            <div class="cols3">
              <?= a_text("widgets[$i][title]", $w['title'] ?? '', 'Nadpis') ?>
              <?= a_text("widgets[$i][iframe]", $w['iframe'] ?? '', 'Adresa (iframe)') ?>
              <?= a_text("widgets[$i][height]", (string) ($w['height'] ?? 600), 'Výška (px)') ?>
            </div>
            <?= a_textarea("widgets[$i][html]", $w['html'] ?? '', 'nebo vložený kód (HTML)', '', 3) ?>
            <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <template>
        <div class="repeater__row repeater__row--block">
          <div class="cols3">
            <?= a_text('widgets[__i__][title]', '', 'Nadpis') ?>
            <?= a_text('widgets[__i__][iframe]', '', 'Adresa (iframe)') ?>
            <?= a_text('widgets[__i__][height]', '600', 'Výška (px)') ?>
          </div>
          <?= a_textarea('widgets[__i__][html]', '', 'nebo vložený kód (HTML)', '', 3) ?>
          <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
        </div>
      </template>
      <button type="button" class="btn btn--sm" data-row-add>+ Přidat widget</button>
    </div>
  </section>

  <section class="card">
    <h2>Partneři týmu</h2>
    <p class="muted">Loga se ukážou na stránce týmu v sekci PARTNEŘI TÝMU. Když tu nikdo není, sekce se vůbec nezobrazí. Nejlépe PNG s průhledným pozadím.</p>
    <div class="repeater" data-repeater>
      <div class="repeater__rows">
        <?php foreach ($t['partners'] ?? [] as $i => [$href, $logo, $name]): ?>
          <div class="repeater__row repeater__row--block">
            <div class="cols3"><?= a_media("tpartners[$i][logo]", $logo, 'Logo') ?><div><?= a_text("tpartners[$i][name]", $name, 'Název') ?><?= a_text("tpartners[$i][url]", $href, 'Web') ?></div></div>
            <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <template>
        <div class="repeater__row repeater__row--block">
          <div class="cols3"><?= a_media('tpartners[__i__][logo]', '', 'Logo') ?><div><?= a_text('tpartners[__i__][name]', '', 'Název') ?><?= a_text('tpartners[__i__][url]', '', 'Web') ?></div></div>
          <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
        </div>
      </template>
      <button type="button" class="btn btn--sm" data-row-add>+ Přidat partnera týmu</button>
    </div>
  </section>

  <div class="savebar"><button class="btn btn--red">Uložit tým</button></div>
</form>

<?php if (!$isNew): ?>
<form method="post" class="danger-zone" data-confirm="Opravdu odstranit tým „<?= e($t['title']) ?>“? Stránka přestane existovat.">
  <?= csrf_field() ?><input type="hidden" name="action" value="team-delete"><input type="hidden" name="t" value="<?= e($k) ?>">
  <button class="btn btn--danger btn--sm">Odstranit tým</button>
</form>
<?php endif; ?>
