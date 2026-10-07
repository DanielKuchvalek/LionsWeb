<?php
declare(strict_types=1);

/**
 * Odesílání e-mailů.
 * Když je v administraci (Nastavení → E-maily) vyplněný SMTP server, posílá se přes něj
 * (spolehlivé doručení, nekončí ve spamu). Jinak se použije PHP mail() hostingu.
 *
 * $html – volitelná HTML verze; e-mail se pak pošle jako text + HTML (multipart/alternative),
 *         takže se správně zobrazí v každém e-mailovém programu.
 *
 * @return array{ok:bool, error:?string}
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null, ?string $html = null, string $fromName = 'LIONS Handball'): array
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Neplatná adresa příjemce.'];
    }
    $subject = preg_replace('/[\r\n]+/', ' ', $subject) ?? '';
    $smtp = site('smtp') ?? [];
    $from = (string) (($smtp['from'] ?? '') ?: site('mail_from'));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'web@' . (preg_replace('/^www\./', '', (string) (site('hosts')[0] ?? 'localhost')));
    }
    $fromName = preg_replace('/[\r\n"<>]+/', ' ', $fromName) ?? 'LIONS';

    $headers = [
        'Date: ' . date('r'),
        'From: ' . mime_header($fromName) . " <$from>",
        'To: <' . $to . '>',
        'Subject: ' . mime_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . (explode('@', $from)[1] ?? 'localhost') . '>',
        'MIME-Version: 1.0',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }

    if ($html === null) {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $encodedBody = chunk_split(base64_encode($body));
    } else {
        $boundary = 'lions_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $encodedBody = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($body))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";
    }

    if (!empty($smtp['host'])) {
        return smtp_send($smtp, $from, $to, implode("\r\n", $headers) . "\r\n\r\n" . $encodedBody);
    }

    // mail(): To a Subject předává PHP zvlášť
    $extra = array_values(array_filter($headers, fn($h) => !preg_match('/^(To|Subject):/i', $h)));
    $err = null;
    set_error_handler(function ($no, $str) use (&$err) { $err = $str; return true; });
    $ok = mail($to, mime_header($subject), $encodedBody, implode("\r\n", $extra), '-f' . $from);
    restore_error_handler();
    return ['ok' => (bool) $ok, 'error' => $ok ? null : ($err ?: 'Funkce mail() na serveru e-mail neodeslala.')];
}

function mime_header(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/** Minimální SMTP klient (SSL/TLS nebo STARTTLS, AUTH LOGIN) */
function smtp_send(array $cfg, string $from, string $to, string $data): array
{
    $host = (string) $cfg['host'];
    $port = (int) ($cfg['port'] ?? 587) ?: 587;
    $secure = (string) ($cfg['secure'] ?? 'tls'); // ssl | tls | none
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return ['ok' => false, 'error' => "SMTP: nelze se připojit k $host:$port ($errstr)"];
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (string $c, array $expect) use ($fp, $read): string {
        if ($c !== '') fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!in_array((int) substr($r, 0, 3), $expect, true)) {
            throw new RuntimeException('SMTP: ' . trim($r ?: 'bez odpovědi') . ($c !== '' && !str_starts_with($c, 'AUTH') && !preg_match('/^[A-Za-z0-9+\/=]+$/', $c) ? " (příkaz $c)" : ''));
        }
        return $r;
    };

    try {
        $cmd('', [220]);
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP: nepodařilo se zapnout šifrování STARTTLS');
            }
            $cmd($ehlo, [250]);
        }
        if (!empty($cfg['user'])) {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $cfg['user']), [334]);
            $cmd(base64_encode((string) ($cfg['pass'] ?? '')), [235]);
        }
        $cmd("MAIL FROM:<$from>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        // tečka na začátku řádku se musí zdvojit
        $data = preg_replace('/^\./m', '..', $data);
        $cmd($data . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        @fclose($fp);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Grafická šablona e-mailu v barvách klubu (tabulkové rozložení a inline styly,
 * aby vypadal dobře i v Gmailu, Outlooku a na mobilu).
 * $rows = [['Popisek', 'hodnota (HTML)'], …]
 */
function email_template(string $heading, string $introHtml, array $rows = [], ?array $button = null, string $footerHtml = ''): string
{
    $logo = abs_url(media(site('logo')));
    $site = abs_url(url());
    $table = '';
    foreach ($rows as [$label, $value]) {
        $table .= '<tr><td style="padding:10px 14px;border-bottom:1px solid #e6eaf2;color:#64708a;font-size:14px;width:38%;vertical-align:top">' . e($label) . '</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e6eaf2;color:#101828;font-size:15px;font-weight:600;vertical-align:top">' . $value . '</td></tr>';
    }
    $btn = $button ? '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 8px"><tr><td style="border-radius:999px;background:' . ($button[2] ?? '#be1e3e') . '">'
        . '<a href="' . e($button[1]) . '" style="display:inline-block;padding:13px 26px;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:.03em">' . e($button[0]) . '</a></td></tr></table>' : '';

    return '<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($heading) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#eef2f8;font-family:Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f8"><tr><td align="center" style="padding:24px 12px">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden">'
        // hlavička – červeno-modrá jako erb
        . '<tr><td style="padding:0"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>'
        . '<td width="50%" style="background:#be1e3e;height:6px;font-size:0;line-height:0">&nbsp;</td><td width="50%" style="background:#3a5896;height:6px;font-size:0;line-height:0">&nbsp;</td></tr></table></td></tr>'
        . '<tr><td align="center" style="background:#11244a;padding:24px 20px">'
        . '<img src="' . e($logo) . '" width="64" alt="LIONS" style="display:block;margin:0 auto 10px;border:0">'
        . '<div style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:.12em">LIONS</div>'
        . '<div style="color:#b9c6e4;font-size:11px;letter-spacing:.3em;text-transform:uppercase">Handball</div></td></tr>'
        // obsah
        . '<tr><td style="padding:28px 28px 8px">'
        . '<h1 style="margin:0 0 12px;color:#101828;font-size:24px;line-height:1.2">' . e($heading) . '</h1>'
        . '<div style="color:#25324a;font-size:15px;line-height:1.6">' . $introHtml . '</div>'
        . ($table ? '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;border:1px solid #e6eaf2;border-radius:10px;border-collapse:separate;overflow:hidden">' . $table . '</table>' : '')
        . $btn
        . ($footerHtml ? '<div style="color:#64708a;font-size:13px;line-height:1.55;margin-top:18px">' . $footerHtml . '</div>' : '')
        . '</td></tr>'
        // patička
        . '<tr><td style="padding:20px 28px 26px;border-top:1px solid #e6eaf2;color:#64708a;font-size:12px;line-height:1.6">'
        . 'LIONS Handball · ' . e((string) site('address')) . '<br>'
        . '<a href="mailto:' . e((string) site('email')) . '" style="color:#be1e3e">' . e((string) site('email')) . '</a> · '
        . '<a href="' . e($site) . '" style="color:#be1e3e">' . e(preg_replace('~^https?://~', '', rtrim($site, '/'))) . '</a>'
        . '</td></tr></table></td></tr></table></body></html>';
}
