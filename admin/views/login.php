<form method="post" class="card auth__card">
  <h1>Administrace LIONS</h1>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="login">
  <?= a_text('user', '', 'Uživatelské jméno', '', ['autocomplete' => 'username', 'required' => 'required', 'autofocus' => 'autofocus']) ?>
  <label class="af"><span class="af__label">Heslo</span><input type="password" name="pass" autocomplete="current-password" required></label>
  <button class="btn btn--red btn--block" type="submit">Přihlásit</button>
</form>
