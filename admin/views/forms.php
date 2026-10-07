<?php
$labels = ['prihlaska' => 'Přihlášky ze stránek týmů', 'nabor' => 'LIONS NÁBOR – zájem o informace', 'kontakt' => 'Kontaktní formulář',
           'lvicata-hostivice' => 'LVÍČATA ZŠ Hostivice – přihlášky', 'lvicata-vida' => 'LVÍČATA ZŠ Chýně VIDA – přihlášky', 'skolni-liga' => 'Školní liga minihází – zájem'];
$stats = [];
foreach (db()->query("SELECT form, COUNT(*) n, MAX(created) last, SUM(mail_status <> 'sent') failed FROM submissions GROUP BY form") as $r) $stats[$r['form']] = $r;
?>
<p class="muted">Všechny odeslané formuláře z webu jsou bezpečně uložené v databázi. Kopie každého formuláře zároveň chodí e-mailem – kam, nastavíte níže.</p>
<div class="page-list">
  <?php foreach ($labels as $id => $label): $st = $stats[$id] ?? null; ?>
    <a class="card page-item" href="<?= e(admin_url('form-view', ['f' => $id])) ?>">
      <b><?= e($label) ?></b>
      <span class="big-num"><?= (int) ($st['n'] ?? 0) ?></span>
      <small class="muted"><?= $st ? 'poslední ' . e(date('j. n. Y H:i', strtotime($st['last']))) : 'zatím nic' ?></small>
      <?php if (!empty($st['failed'])): ?><small class="mail-bad">⚠ <?= (int) $st['failed'] ?>× e-mail neodešel</small><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="post" class="editor">
  <?= csrf_field() ?><input type="hidden" name="action" value="form-recipients-save">
  <section class="card">
    <h2>Kam chodí upozornění na vyplněný formulář</h2>
    <p class="muted">Ke každému formuláři můžete zadat jednu nebo více adres (oddělte čárkou). Když pole necháte prázdné, upozornění chodí na e-mail klubu <b><?= e(site('email')) ?></b> (mění se v Nastavení). Vyplňujícímu vždy zároveň přijde jeho vlastní potvrzení.</p>
    <?php $rec = site('form_recipients') ?? []; ?>
    <?php foreach ($labels + ['minigym' => 'LIONS miniGYM – rezervace a zrušení'] as $id => $label): ?>
      <?= a_text("rec[$id]", $rec[$id] ?? '', $label, '', ['placeholder' => (string) site('email'), 'inputmode' => 'email']) ?>
    <?php endforeach; ?>
    <button type="submit" class="btn btn--red">Uložit adresy</button>
  </section>
</form>
