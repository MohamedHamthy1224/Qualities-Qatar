# Return to the previous design

Use the appearance choice in the header profile menu to return to the previous style. This keeps the same content, records, and workflows; it does not require a rebuild.

## Undo the refined workspace only

The seven frontend files from immediately before the floating navy sidebar and refined dashboard are saved at `storage/app/design-backups/future-workspace-20261017/`. This snapshot restores the earlier compact reference style, including its 50% navy sidebar. It also restores both dashboard templates. The profile menu's **Previous** choice instead shows the original design.

For a source rollback to that compact reference style, run this from `D:\Projects\Qatar`. All seven hashes are verified before any source file is replaced. The added `future.css` remains on disk, but the restored `app.css` no longer imports it.

```powershell
$projectRoot = (Resolve-Path -LiteralPath '.').Path
$snapshotRoot = Join-Path $projectRoot 'storage\app\design-backups\future-workspace-20261017'
$manifest = Get-Content -LiteralPath (Join-Path $snapshotRoot 'manifest.json') -Raw | ConvertFrom-Json
$allowedPaths = @(
    'resources/css/app.css',
    'resources/css/clean.css',
    'resources/js/appearance.js',
    'resources/js/workspace-motion.js',
    'resources/views/layouts/app.blade.php',
    'resources/views/dashboard/index.blade.php',
    'resources/views/dashboard/partials/production-charts.blade.php'
)
if (@($manifest.files).Count -ne 7 -or @($manifest.files.path | Select-Object -Unique).Count -ne 7) {
    throw 'Expected seven distinct snapshot files.'
}
foreach ($entry in $manifest.files) {
    if ($entry.path -notin $allowedPaths) { throw "Unexpected snapshot path: $($entry.path)" }
    if ((Get-FileHash -LiteralPath (Join-Path $snapshotRoot $entry.path) -Algorithm SHA256).Hash -ne $entry.sha256) {
        throw "Snapshot integrity mismatch: $($entry.path)"
    }
}
foreach ($entry in $manifest.files) {
    Copy-Item -LiteralPath (Join-Path $snapshotRoot $entry.path) -Destination (Join-Path $projectRoot $entry.path) -Force
}
npm.cmd run build
& 'D:\Project\xampp\php\php.exe' artisan view:clear
```

## Original frontend source snapshot

The exact baseline from before the reference-video refresh is saved locally at:

`storage/app/design-backups/reference-refresh-20261017/`

`manifest.json` lists the 17 saved files, their lengths, and SHA256 hashes. The snapshot covers `resources/css/`, `resources/js/`, and `resources/views/layouts/app.blade.php`. It contains no environment files, database, vendor files, or Git changes. Keep this local snapshot if you clean generated storage files.

The later refined dashboard also changed two Blade templates that the original 17-file snapshot did not cover. A full original-design source rollback must restore those two templates from the pre-refined snapshot as well; otherwise the new dashboard intro and donut label would become visible after the new styles are unimported.

For that full source rollback, run the following in PowerShell from `D:\Projects\Qatar`. It verifies every saved file, including both dashboard templates, before restoring only the listed frontend files. Added refresh files remain on disk but the restored entry points no longer import them.

```powershell
$projectRoot = (Resolve-Path -LiteralPath '.').Path
$snapshotRoot = Join-Path $projectRoot 'storage\app\design-backups\reference-refresh-20261017'
$manifest = Get-Content -LiteralPath (Join-Path $snapshotRoot 'manifest.json') -Raw | ConvertFrom-Json
$dashboardSnapshotRoot = Join-Path $projectRoot 'storage\app\design-backups\future-workspace-20261017'
$dashboardManifest = Get-Content -LiteralPath (Join-Path $dashboardSnapshotRoot 'manifest.json') -Raw | ConvertFrom-Json
$dashboardPaths = @(
    'resources/views/dashboard/index.blade.php',
    'resources/views/dashboard/partials/production-charts.blade.php'
)
$dashboardEntries = @($dashboardManifest.files | Where-Object { $_.path -in $dashboardPaths })
if ($dashboardEntries.Count -ne 2 -or @($dashboardEntries.path | Select-Object -Unique).Count -ne 2) {
    throw 'Expected both distinct dashboard templates in the pre-refined snapshot.'
}

foreach ($entry in $manifest.files) {
    if ($entry.path -notmatch '^resources/(css/[^.][^\r\n]*|js/[^.][^\r\n]*|views/layouts/app\.blade\.php)$' -or $entry.path -match '(^|/)\.\.(/|$)') {
        throw "Unexpected snapshot path: $($entry.path)"
    }
    $savedFile = Join-Path $snapshotRoot $entry.path
    if ((Get-FileHash -LiteralPath $savedFile -Algorithm SHA256).Hash -ne $entry.sha256) {
        throw "Snapshot integrity mismatch: $($entry.path)"
    }
}
foreach ($entry in $dashboardEntries) {
    $savedFile = Join-Path $dashboardSnapshotRoot $entry.path
    if ((Get-FileHash -LiteralPath $savedFile -Algorithm SHA256).Hash -ne $entry.sha256) {
        throw "Dashboard snapshot integrity mismatch: $($entry.path)"
    }
}

foreach ($entry in $manifest.files) {
    Copy-Item -LiteralPath (Join-Path $snapshotRoot $entry.path) -Destination (Join-Path $projectRoot $entry.path) -Force
}
foreach ($entry in $dashboardEntries) {
    Copy-Item -LiteralPath (Join-Path $dashboardSnapshotRoot $entry.path) -Destination (Join-Path $projectRoot $entry.path) -Force
}

npm.cmd run build
& 'D:\Project\xampp\php\php.exe' artisan view:clear
```

Reload the page after the build. This source rollback replaces frontend edits made after the snapshot; use the profile menu for ordinary style comparisons.
