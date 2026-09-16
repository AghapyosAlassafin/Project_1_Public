<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n" . implode("\n", $tables) . "\n\n";
if (in_array('users', $tables)) {
  echo "users columns:\n";
  $cols = $db->query("PRAGMA table_info('users')")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($cols as $c) {
    echo $c['cid'] . ": " . $c['name'] . " (" . $c['type'] . ")" . ($c['pk'] ? ' pk' : '') . "\n";
  }
}
if (in_array('migrations', $tables)) {
  echo "\nmigrations table rows:\n";
  $rows = $db->query("SELECT * FROM migrations")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    echo $r['id'] . ' - ' . $r['migration'] . ' - ' . $r['batch'] . "\n";
  }
}
