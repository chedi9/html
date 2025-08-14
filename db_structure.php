<?php
/**
 * Database Structure Explorer
 * - Displays database name, tables, engines, collations, row counts, sizes
 * - For each table: columns, indexes, foreign keys, and CREATE TABLE
 * - Simple search and collapsible UI for usability
 */

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$error = '';
$pdo = null;
try {
    require_once __DIR__ . '/db.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        // ok
    } else {
        $error = 'PDO connection not available from db.php';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$dbName = '';
$tables = [];
if ($error === '') {
    try {
        $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        $statusStmt = $pdo->query('SHOW TABLE STATUS');
        while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
            $tables[] = $row; // Name, Engine, Rows, Collation, Data_length, Index_length, Comment, etc.
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Preload metadata per table
$meta = [];
if ($error === '') {
    foreach ($tables as $t) {
        $tname = $t['Name'];
        $info = [
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'create_sql' => ''
        ];
        try {
            // Columns
            $cols = $pdo->query('SHOW FULL COLUMNS FROM `'.str_replace('`','``',$tname).'`')->fetchAll(PDO::FETCH_ASSOC);
            $info['columns'] = $cols ?: [];

            // Indexes (group by Key_name)
            $idxRows = $pdo->query('SHOW INDEX FROM `'.str_replace('`','``',$tname).'`')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $byKey = [];
            foreach ($idxRows as $ix) {
                $k = $ix['Key_name'];
                if (!isset($byKey[$k])) $byKey[$k] = [];
                $byKey[$k][] = $ix;
            }
            $info['indexes'] = $byKey;

            // CREATE TABLE and FKs
            $crt = $pdo->query('SHOW CREATE TABLE `'.str_replace('`','``',$tname).'`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $crt ? (isset($crt['Create Table']) ? $crt['Create Table'] : (isset($crt['Create View'])?$crt['Create View']:'')) : '';
            $info['create_sql'] = (string)$createSql;
            $fks = [];
            if ($createSql) {
                // Parse basic FK lines
                if (preg_match_all('/CONSTRAINT\s+[`\"]?([^`\"]+)[`\"]?\s+FOREIGN KEY\s*\(([^\)]+)\)\s*REFERENCES\s+[`\"]?([^`\"]+)[`\"]?\s*\(([^\)]+)\)([^,]*)/i', $createSql, $m, PREG_SET_ORDER)) {
                    foreach ($m as $mm) {
                        $fkName = $mm[1];
                        $cols = trim($mm[2]);
                        $refTable = $mm[3];
                        $refCols = trim($mm[4]);
                        $opts = trim($mm[5]);
                        $fks[] = [
                            'name' => $fkName,
                            'columns' => preg_replace('/[`\s]/','', $cols),
                            'ref_table' => $refTable,
                            'ref_columns' => preg_replace('/[`\s]/','', $refCols),
                            'options' => preg_replace('/\s+/', ' ', $opts)
                        ];
                    }
                }
            }
            $info['foreign_keys'] = $fks;
        } catch (Throwable $e) {
            $info['error'] = $e->getMessage();
        }
        $meta[$tname] = $info;
    }
}

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Database Structure Explorer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root{--bg:#f7f9fc;--card:#fff;--muted:#556;--accent:#0b67ff;--ok:#1b7a3d;--warn:#ff9f1c;--bad:#b12020}
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:18px;color:#111;background:var(--bg)}
        .container{max-width:1200px;margin:0 auto}
        .card{background:var(--card);border-radius:10px;padding:18px;margin-bottom:12px;box-shadow:0 6px 18px rgba(20,30,40,0.06)}
        h1{margin:0 0 8px;font-size:22px}
        h2{margin:0 0 8px;font-size:18px}
        .small{color:var(--muted);font-size:13px}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:8px;text-align:left;border-bottom:1px solid #eee;font-size:13px;vertical-align:top}
        .btn{display:inline-block;padding:8px 12px;border-radius:8px;background:var(--accent);color:#fff;text-decoration:none}
        .muted{color:var(--muted)}
        .search{padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;width:280px}
        details{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff}
        details+details{margin-top:8px}
        summary{cursor:pointer;font-weight:600}
        pre{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto}
        code.inline{background:#f1f5f9;padding:2px 6px;border-radius:6px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px}
        .pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:12px}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Database Structure Explorer</h1>
        <?php if ($error !== ''): ?>
            <div class="small" style="color:var(--bad)">Error: <?php echo h($error); ?></div>
        <?php else: ?>
            <div class="small">Database: <strong><?php echo h($dbName); ?></strong> · Tables: <strong><?php echo count($tables); ?></strong></div>
            <div style="margin-top:8px">
                <input class="search" id="search" type="search" placeholder="Filter tables by name…" oninput="filterTables()">
            </div>
        <?php endif; ?>
    </div>

    <?php if ($error === ''): ?>
    <div class="card">
        <h2>Overview</h2>
        <div class="grid">
            <?php foreach ($tables as $t): $name=$t['Name']; $rows=(int)$t['Rows']; $engine=$t['Engine']; $coll=$t['Collation']; $data=(int)$t['Data_length']; $idx=(int)$t['Index_length']; ?>
                <div class="small">
                    <strong><a href="#t-<?php echo h($name); ?>"><?php echo h($name); ?></a></strong>
                    <div class="muted">Rows: <?php echo $rows; ?> · Engine: <?php echo h($engine); ?> · Collation: <?php echo h($coll); ?></div>
                    <div class="muted">Size: <?php echo number_format(($data+$idx)/1024,0); ?> KB (data <?php echo number_format($data/1024,0); ?> KB, index <?php echo number_format($idx/1024,0); ?> KB)</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($tables as $t): $name=$t['Name']; $info=$meta[$name]; ?>
        <div class="card table-card" data-name="<?php echo h(strtolower($name)); ?>" id="t-<?php echo h($name); ?>">
            <h2><?php echo h($name); ?></h2>
            <div class="small">Engine: <span class="pill"><?php echo h($t['Engine']); ?></span>
                · Collation: <span class="pill"><?php echo h($t['Collation']); ?></span>
                · Rows: <span class="pill"><?php echo (int)$t['Rows']; ?></span>
                <?php if (!empty($t['Comment'])): ?> · Comment: <span class="pill"><?php echo h($t['Comment']); ?></span><?php endif; ?>
            </div>

            <details open>
                <summary>Columns (<?php echo count($info['columns']); ?>)</summary>
                <table class="table">
                    <tr><th>Name</th><th>Type</th><th>Null</th><th>Default</th><th>Key</th><th>Extra</th><th>Comment</th></tr>
                    <?php foreach ($info['columns'] as $c): ?>
                        <tr>
                            <td><code class="inline"><?php echo h($c['Field']); ?></code></td>
                            <td><?php echo h($c['Type']); ?></td>
                            <td><?php echo h($c['Null']); ?></td>
                            <td><?php echo h($c['Default']); ?></td>
                            <td><?php echo h($c['Key']); ?></td>
                            <td><?php echo h($c['Extra']); ?></td>
                            <td class="small"><?php echo h($c['Comment']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </details>

            <details>
                <summary>Indexes (<?php echo count($info['indexes']); ?>)</summary>
                <?php if (empty($info['indexes'])): ?>
                    <div class="small muted">None</div>
                <?php else: ?>
                    <?php foreach ($info['indexes'] as $keyName => $rows): ?>
                        <div class="small"><strong><?php echo h($keyName); ?></strong> — <?php echo $rows[0]['Non_unique']? 'NON-UNIQUE':'UNIQUE'; ?></div>
                        <table class="table">
                            <tr><th>Seq</th><th>Column</th><th>Collation</th><th>Cardinality</th><th>Sub_part</th><th>Null</th><th>Index_type</th></tr>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo (int)$r['Seq_in_index']; ?></td>
                                    <td><code class="inline"><?php echo h($r['Column_name']); ?></code></td>
                                    <td><?php echo h($r['Collation']); ?></td>
                                    <td><?php echo h($r['Cardinality']); ?></td>
                                    <td><?php echo h($r['Sub_part']); ?></td>
                                    <td><?php echo h($r['Null']); ?></td>
                                    <td><?php echo h($r['Index_type']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>
            </details>

            <details>
                <summary>Foreign keys (<?php echo count($info['foreign_keys']); ?>)</summary>
                <?php if (empty($info['foreign_keys'])): ?>
                    <div class="small muted">None</div>
                <?php else: ?>
                    <table class="table">
                        <tr><th>Name</th><th>Columns</th><th>References</th><th>Options</th></tr>
                        <?php foreach ($info['foreign_keys'] as $fk): ?>
                            <tr>
                                <td><code class="inline"><?php echo h($fk['name']); ?></code></td>
                                <td><?php echo h($fk['columns']); ?></td>
                                <td><?php echo h($fk['ref_table']); ?>(<?php echo h($fk['ref_columns']); ?>)</td>
                                <td class="small">&nbsp;<?php echo h($fk['options']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </details>

            <details>
                <summary>CREATE TABLE</summary>
                <pre><?php echo h($info['create_sql']); ?></pre>
            </details>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function filterTables(){
  const q = (document.getElementById('search').value || '').trim().toLowerCase();
  const cards = document.querySelectorAll('.table-card');
  cards.forEach(card => {
    const name = card.getAttribute('data-name') || '';
    card.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
  });
}
</script>
</body>
</html>

