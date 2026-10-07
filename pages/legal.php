<?php
/** @var array $page */
$g = $page['group'];
$retention = site('retention') ?? [];
$vars = [
    '{spolek}'  => block('kontakt.org_name'),
    '{adresa}'  => block('kontakt.org_address'),
    '{ico}'     => block('kontakt.org_ico'),
    '{email}'   => (string) site('email'),
    '{retence_formulare}' => (string) ($retention['forms'] ?? 24),
    '{retence_minigym}'   => (string) ($retention['minigym'] ?? 12),
];
?>
<section class="section section--compact">
  <div class="container legal">
    <?php if ($g === 'privacy' && block('privacy.updated')): ?><p class="updated">Platné od <?= e(block('privacy.updated')) ?></p><?php endif; ?>
    <?= text_html(strtr(block($g . '.text'), $vars)) ?>

    <?php if ($g === 'cookies'): ?>
      <?php $ask = consent_required(); ?>
      <table>
        <thead><tr><th>Služba</th><th>K čemu slouží</th><?php if ($ask): ?><th>Stav</th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach (consent_services() as $key => $s): ?>
            <tr>
              <td><b><?= e($s['name']) ?></b><?php if ($s['policy']): ?><br><a href="<?= e($s['policy']) ?>" target="_blank" rel="noopener">zásady služby</a><?php endif; ?></td>
              <td><?= e($s['desc']) ?></td>
              <?php if ($ask): ?><td data-consent-status="<?= e($key) ?>">—</td><?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($ask): ?><p><button type="button" class="btn btn--navy" data-consent-open>Změnit nastavení cookies</button></p><?php endif; ?>
    <?php endif; ?>
  </div>
</section>
