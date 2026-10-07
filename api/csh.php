<?php
declare(strict_types=1);

/**
 * Živá data pro prohlížeč – stránka si je během probíhajícího utkání
 * sama pravidelně stahuje a přepisuje skóre bez obnovení stránky.
 *
 *   api/csh.php?scope=home         – utkání z homepage
 *   api/csh.php?scope=team&team=…  – utkání jednoho týmu
 */
require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');

$scope = $_GET['scope'] ?? 'home';
if ($scope === 'team' && ($t = team((string) ($_GET['team'] ?? '')))) {
    $matches = [];
    foreach (csh_team_competitions($t) as $comp) {
        array_push($matches, ...csh_lions_matches($comp));
    }
} else {
    $matches = csh_all_lions_matches();
}

$out = [];
foreach ($matches as $m) {
    $out[] = [
        'id'    => $m['id'],
        'state' => $m['state'],
        'home'  => $m['home_score'] === null ? null : (int) $m['home_score'],
        'away'  => $m['away_score'] === null ? null : (int) $m['away_score'],
        'start' => $m['start']?->format(DATE_ATOM),
    ];
}
echo json_encode(['live' => csh_any_live($matches), 'matches' => $out]);
