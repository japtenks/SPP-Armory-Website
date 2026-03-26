<?php
builddiv_start(1, 'Downloads', 0);

$siteRoot = realpath(__DIR__ . '/../../../');
$downloadsRoot = $siteRoot . DIRECTORY_SEPARATOR . 'downloads';
$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$downloadsRealmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$sections = array(
    'addons' => array(
        'title' => 'Addon Packs',
        'description' => 'Local copies of addon zips and folders. Start by copying files here.',
        'web' => '/downloads/addons',
        'path' => $downloadsRoot . DIRECTORY_SEPARATOR . 'addons',
    ),
    'tools' => array(
        'title' => 'Tools & Utilities',
        'description' => 'Optional helper tools, launchers, docs, or patches for players on the Realms.',
        'web' => '/downloads/tools',
        'path' => $downloadsRoot . DIRECTORY_SEPARATOR . 'tools',
    ),
);

if (!function_exists('downloads_format_size')) {
    function downloads_format_size($bytes)
    {
        $bytes = (float)$bytes;
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return ($i === 0 ? (string)(int)$bytes : number_format($bytes, 1)) . ' ' . $units[$i];
    }
}

if (!function_exists('downloads_collect_files')) {
    function downloads_collect_files($diskPath, $webBase)
    {
        $items = array();
        if (!is_dir($diskPath)) {
            return $items;
        }

        $allowedExt = array('zip', 'rar', '7z', 'exe', 'msi', 'txt', 'pdf', 'mpq');
        $entries = scandir($diskPath);
        if (!is_array($entries)) {
            return $items;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $diskPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if ($ext !== '' && !in_array($ext, $allowedExt, true)) {
                continue;
            }

            $items[] = array(
                'name' => $entry,
                'href' => rtrim($webBase, '/') . '/' . rawurlencode($entry),
                'size' => downloads_format_size(filesize($fullPath)),
                'modified' => @date('Y-m-d H:i', filemtime($fullPath)),
                'ext' => $ext !== '' ? strtoupper($ext) : 'FILE',
            );
        }

        usort($items, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }
}
?>
<style>
.downloads-shell {
  display: grid;
  gap: 20px;
}
.downloads-intro,
.downloads-card {
  background: rgba(2, 6, 15, 0.82);
  border: 1px solid rgba(255, 193, 7, 0.25);
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.28);
}
.downloads-intro {
  display: grid;
  gap: 12px;
}
.downloads-intro h2,
.downloads-card h3 {
  margin: 0;
  color: #ffcc66;
}
.downloads-intro p,
.downloads-card p,
.downloads-empty,
.downloads-note {
  margin: 0;
  color: #d4c7a1;
  line-height: 1.6;
}
.downloads-grid {
  display: grid;
  grid-template-columns: 1.45fr 1fr;
  gap: 20px;
}
.downloads-file-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}
.download-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  gap: 12px;
  align-items: center;
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 193, 7, 0.12);
}
.download-item a {
  color: #7fd6ff;
  font-weight: 700;
  text-decoration: none;
  word-break: break-word;
}
.download-item a:hover {
  color: #ffcc66;
}
.download-badge,
.download-meta {
  color: #d4c7a1;
  font-size: 0.92rem;
  white-space: nowrap;
}
.download-badge {
  min-width: 48px;
  text-align: center;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(255, 193, 7, 0.12);
  border: 1px solid rgba(255, 193, 7, 0.22);
}
.downloads-steps {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}
.downloads-steps div {
  color: #e5ddc7;
}
.downloads-link {
  color: #7fd6ff;
  font-weight: 700;
  text-decoration: none;
}
.downloads-link:hover {
  color: #ffcc66;
}
.downloads-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}
.downloads-action {
  display: inline-flex;
  align-items: center;
  min-height: 40px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 193, 7, 0.24);
  background: rgba(255, 193, 7, 0.08);
  color: #ffe39a;
  font-weight: 700;
  text-decoration: none;
}
.downloads-action:hover {
  background: rgba(255, 193, 7, 0.16);
  color: #fff0bf;
}
@media (max-width: 980px) {
  .downloads-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<div class="downloads-shell">
  <div class="downloads-intro">
    <p>This page is for hosted client files. The main target is addon packs from the <a class="downloads-link" href="https://github.com/celguar/spp-classics-cmangos/tree/master/Addons" target="_blank" rel="noopener noreferrer">spp-classics-cmangos</a> repo, but you can also drop in launchers, patches, PDFs, or other curated files.</p>
    <p class="downloads-note">Suggested Linux host folders: <code>/var/www/html/downloads/addons</code> and <code>/var/www/html/downloads/tools</code>.</p>
	<p class="downloads-note">Suggested windows host folders: <code>/website/downloads/addons</code> and <code>/website/downloads/tools</code>.</p>
  </div>

  <div class="downloads-grid">
    <?php foreach ($sections as $key => $section): ?>
      <?php $files = downloads_collect_files($section['path'], $section['web']); ?>
      <div class="downloads-card">
        <h3><?php echo htmlspecialchars($section['title']); ?></h3>
        <p><?php echo htmlspecialchars($section['description']); ?></p>

        <?php if ($key === 'tools'): ?>
          <div class="downloads-actions">
            <a class="downloads-action" href="<?php echo htmlspecialchars('download-realmlist.php?realm=' . (int)$downloadsRealmId); ?>">Download realmlist.wtf</a>
          </div>
        <?php endif; ?>

        <?php if ($files): ?>
          <div class="downloads-file-list">
            <?php foreach ($files as $file): ?>
              <div class="download-item">
                <a href="<?php echo htmlspecialchars($file['href']); ?>"><?php echo htmlspecialchars($file['name']); ?></a>
                <span class="download-badge"><?php echo htmlspecialchars($file['ext']); ?></span>
                <span class="download-meta"><?php echo htmlspecialchars($file['size']); ?><?php if (!empty($file['modified'])) echo ' | ' . htmlspecialchars($file['modified']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="downloads-empty">No files are hosted in <code><?php echo htmlspecialchars($section['web']); ?></code> yet.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>


</div>
<?php builddiv_end(); ?>
