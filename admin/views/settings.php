<?php $social = site('social'); $sl = site('sportlyzer'); $csh = site('csh'); ?>
<form method="post" class="editor">
  <?= csrf_field() ?><input type="hidden" name="action" value="settings-save">

  <section class="card">
    <h2>Kontakt</h2>
    <div class="cols2">
      <?= a_text('email', site('email'), 'E-mail klubu', 'Zobrazuje se na webu a chodí na něj formuláře a rezervace.') ?>
      <?= a_text('mail_from', site('mail_from'), 'Odesílatel e-mailů z webu', 'Adresa na vaší doméně, např. web@lionshandball.cz.') ?>
    </div>
    <?= a_text('address', site('address'), 'Adresa (patička)') ?>
    <div class="cols3">
      <?= a_text('social_facebook', $social['facebook'] ?? '', 'Facebook') ?>
      <?= a_text('social_instagram', $social['instagram'] ?? '', 'Instagram') ?>
      <?= a_text('social_youtube', $social['youtube'] ?? '', 'YouTube') ?>
    </div>
  </section>

  <section class="card" id="smtp">
    <h2>Odesílání e-mailů (SMTP)</h2>
    <p class="muted">Doporučeno: vyplňte SMTP údaje e-mailové schránky (např. web@lionshandball.cz od vašeho hostingu). E-maily pak spolehlivě dorazí a nepadají do spamu.
      Když je server prázdný, použije se funkce mail() hostingu.</p>
    <?php $smtp = site('smtp') ?? []; ?>
    <div class="cols3">
      <?= a_text('smtp_host', $smtp['host'] ?? '', 'SMTP server', 'např. smtp.forpsi.com, smtp.gmail.com') ?>
      <?= a_text('smtp_port', (string) ($smtp['port'] ?? 587), 'Port', '587 (TLS) nebo 465 (SSL)') ?>
      <?= a_select('smtp_secure', $smtp['secure'] ?? 'tls', 'Zabezpečení', ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'žádné']) ?>
      <?= a_text('smtp_user', $smtp['user'] ?? '', 'Uživatel (e-mail)') ?>
      <label class="af"><span class="af__label">Heslo</span><input type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?= !empty($smtp['pass']) ? '•••••••• (uloženo – ponechte prázdné)' : '' ?>"></label>
      <?= a_text('smtp_from', $smtp['from'] ?? '', 'Odesílatel (From)', 'Většinou stejný jako uživatel.') ?>
    </div>
  </section>

  <section class="card">
    <h2>Facebook stránky (výpis příspěvků)</h2>
    <p class="muted">Když u stránky vložíte <b>klíč stránky (Page Access Token)</b>, web zobrazí její poslední příspěvky přímo ve svém designu – rychle a spolehlivě. Bez klíče se zobrazí jen karta s odkazem na Facebook. Postup, jak klíč získat, je v návodu.</p>
    <?php foreach (site('facebook_pages') as $k => $fp): ?>
      <div class="cols2">
        <?= a_text("fb[$k][name]", $fp['name'], 'Název (' . $k . ')') ?>
        <?= a_text("fb[$k][url]", $fp['url'], 'Adresa stránky na Facebooku') ?>
      </div>
      <label class="af"><span class="af__label">Klíč stránky (<?= e($k) ?>)</span>
        <input type="password" name="fb[<?= e($k) ?>][token]" autocomplete="off" placeholder="<?= !empty($fp['token']) ? '•••••••• (uloženo – ponechte prázdné)' : 'nevyplněno – zobrazí se jen odkaz' ?>">
        <?php if (!empty($fp['token'])): ?><small><label><input type="checkbox" name="fb[<?= e($k) ?>][token_clear]" value="1"> smazat uložený klíč</label></small><?php endif; ?>
      </label>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <h2>Cookies</h2>
    <label class="af"><span class="af__label">Lišta se souhlasem s cookies</span>
      <select name="cookie_banner">
        <option value="0"<?= site('cookie_banner') === true ? '' : ' selected' ?>>Vypnuto – vložený obsah (Sportlyzer, mapy) se načte hned</option>
        <option value="1"<?= site('cookie_banner') === true ? ' selected' : '' ?>>Zapnuto – obsah jiných služeb až po souhlasu návštěvníka</option>
      </select>
    </label>
  </section>

  <section class="card" id="uchovani">
    <h2>Uchování osobních údajů (GDPR)</h2>
    <p class="muted">Po uplynutí lhůty se záznamy automaticky smažou (kontrola probíhá jednou denně). Lhůty se zároveň propisují do textu Zásad ochrany osobních údajů.</p>
    <?php $ret = site('retention') ?? []; ?>
    <div class="cols2">
      <?= a_text('ret_forms', (string) ($ret['forms'] ?? 24), 'Přihlášky a formuláře – počet měsíců', 'Od odeslání formuláře.', ['inputmode' => 'numeric']) ?>
      <?= a_text('ret_gym', (string) ($ret['minigym'] ?? 12), 'Rezervace miniGYM – počet měsíců', 'Od termínu rezervace.', ['inputmode' => 'numeric']) ?>
    </div>
  </section>

  <section class="card">
    <h2>Napojení na systémy</h2>
    <div class="cols2">
      <?= a_text('csh_team', $csh['team_name'], 'Název týmu v IS Českého svazu házené', 'Podle toho web pozná utkání LIONS.') ?>
      <?= a_text('csh_venue', $csh['home_venue'], 'Domácí hala obsahuje text', 'Pro „Nejbližší domácí utkání“.') ?>
      <?= a_text('sl_seed', $sl['seed'], 'Sportlyzer – seed klubu') ?>
      <?= a_text('sl_club', $sl['club_url'], 'Sportlyzer – adresa klubu ve Finderu') ?>
    </div>
  </section>

  <section class="card" id="partneri">
    <h2>Partneři</h2>
    <div class="repeater" data-repeater>
      <div class="repeater__rows">
        <?php foreach (site('partners') as $i => [$href, $logo, $name]): ?>
          <div class="repeater__row repeater__row--block">
            <div class="cols3"><?= a_media("partners[$i][logo]", $logo, 'Logo') ?><div><?= a_text("partners[$i][name]", $name, 'Název') ?><?= a_text("partners[$i][url]", $href, 'Web') ?></div></div>
            <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <template>
        <div class="repeater__row repeater__row--block">
          <div class="cols3"><?= a_media('partners[__i__][logo]', '', 'Logo') ?><div><?= a_text('partners[__i__][name]', '', 'Název') ?><?= a_text('partners[__i__][url]', '', 'Web') ?></div></div>
          <button type="button" class="iconbtn iconbtn--del" data-row-del title="Odebrat">×</button>
        </div>
      </template>
      <button type="button" class="btn btn--sm" data-row-add>+ Přidat partnera</button>
    </div>
    <p class="muted">Partneři se zobrazují na stránce PARTNEŘI i v liště log v patičce všech stránek – ve stejném pořadí jako tady. Loga nejlépe PNG s průhledným pozadím.</p>
  </section>

  <section class="card" id="odkazy">
    <h2>Úvodní stránka – „ROZPISY A VÝSLEDKY“</h2>
    <div class="cols2">
    <?php $gi = 0; foreach (site('results_links') as $group => $links): ?>
      <div class="repeater" data-repeater>
        <?= a_text("links[g$gi][_name]", $group, 'Skupina') ?>
        <div class="repeater__rows">
          <?php foreach ($links as $i => [$label, $href]): ?>
            <div class="repeater__row"><?= a_text("links[g$gi][$i][label]", $label, 'Text') ?><?= a_text("links[g$gi][$i][url]", $href, 'Odkaz') ?><button type="button" class="iconbtn iconbtn--del" data-row-del>×</button></div>
          <?php endforeach; ?>
        </div>
        <template><div class="repeater__row"><?= a_text("links[g$gi][__i__][label]", '', 'Text') ?><?= a_text("links[g$gi][__i__][url]", '', 'Odkaz') ?><button type="button" class="iconbtn iconbtn--del" data-row-del>×</button></div></template>
        <button type="button" class="btn btn--sm" data-row-add>+ Přidat odkaz</button>
      </div>
    <?php $gi++; endforeach; ?>
    </div>
  </section>

  <div class="savebar"><button class="btn btn--red">Uložit nastavení</button></div>
</form>
<form method="post" class="danger-zone">
  <?= csrf_field() ?><input type="hidden" name="action" value="mail-test"><input type="hidden" name="_back" value="<?= e(admin_url('settings')) ?>#smtp">
  <button class="btn btn--sm">Odeslat testovací e-mail na <?= e(site('email')) ?></button> <small class="muted">(nejdřív uložte nastavení)</small>
</form>
