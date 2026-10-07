<div class="grid2">
  <form method="post" class="card">
    <h2>Změna hesla</h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <label class="af"><span class="af__label">Současné heslo</span><input type="password" name="old" autocomplete="current-password" required></label>
    <label class="af"><span class="af__label">Nové heslo (min. 8 znaků)</span><input type="password" name="new" minlength="8" autocomplete="new-password" required></label>
    <label class="af"><span class="af__label">Nové heslo znovu</span><input type="password" name="new2" minlength="8" autocomplete="new-password" required></label>
    <button class="btn btn--red">Změnit heslo</button>
  </form>

  <section class="card">
    <h2>Správci webu</h2>
    <table class="tbl">
      <?php foreach (admin_users() as $u): ?>
        <?php $self = $u['user'] === admin_user(); ?>
        <tr><td><b><?= e($u['user']) ?></b><?= admin_is_owner($u['user']) ? ' <span class="tag">hlavní správce</span>' : '' ?><?= $self ? ' <span class="tag">vy</span>' : '' ?></td>
          <td class="muted"><?= e(isset($u['created']) ? date('j. n. Y', strtotime($u['created'])) : '') ?></td>
          <td class="actions"><?php if (admin_can_delete($u['user'])): ?>
            <form method="post" data-confirm="<?= $self ? 'Opravdu odebrat svůj vlastní účet? Budete odhlášen(a) a už se nepřihlásíte.' : 'Odebrat správce ' . e($u['user']) . '?' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="user-delete"><input type="hidden" name="user" value="<?= e($u['user']) ?>"><button class="btn btn--sm btn--danger"><?= $self ? 'Odebrat můj účet' : 'Odebrat' ?></button></form>
          <?php endif; ?></td></tr>
      <?php endforeach; ?>
    </table>
    <p class="muted">Ostatní správce může odebrat jen hlavní správce webu. Hlavního správce nelze odebrat.</p>
    <form method="post" class="row">
      <?= csrf_field() ?><input type="hidden" name="action" value="user-add">
      <input type="text" name="user" placeholder="jméno" required>
      <input type="password" name="pass" placeholder="heslo (min. 8 znaků)" minlength="8" autocomplete="new-password" required>
      <button class="btn btn--sm">Přidat správce</button>
    </form>
  </section>
</div>
