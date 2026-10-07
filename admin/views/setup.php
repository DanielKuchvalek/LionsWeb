<form method="post" class="card auth__card">
  <h1>Vítejte v administraci</h1>
  <p class="muted">Web zatím nemá žádného správce. Vytvořte si první účet – další správce přidáte později v sekci „Účet a správci“.</p>
  <?= csrf_field() ?>
  <?= a_text('user', '', 'Uživatelské jméno', '', ['autocomplete' => 'username', 'required' => 'required']) ?>
  <label class="af"><span class="af__label">Heslo (min. 8 znaků)</span><input type="password" name="pass" minlength="8" autocomplete="new-password" required></label>
  <label class="af"><span class="af__label">Heslo znovu</span><input type="password" name="pass2" minlength="8" autocomplete="new-password" required></label>
  <button class="btn btn--red btn--block" type="submit">Vytvořit účet</button>
</form>
