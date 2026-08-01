param(
    [string] $Database = $env:DB_DATABASE,
    [string] $User = $env:DB_USERNAME,
    [string] $Password = $env:DB_PASSWORD,
    [string] $HostName = $env:DB_HOST,
    [string] $BackupDir = ".\storage\app\backups"
)

if (-not $Database) { throw "DB_DATABASE est requis." }
if (-not $User) { throw "DB_USERNAME est requis." }
if (-not $HostName) { $HostName = "127.0.0.1" }

New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$target = Join-Path $BackupDir "$Database-$stamp.sql"

if ($Password) {
    mysqldump --host=$HostName --user=$User --password=$Password $Database | Out-File -FilePath $target -Encoding utf8
} else {
    mysqldump --host=$HostName --user=$User $Database | Out-File -FilePath $target -Encoding utf8
}

Write-Host "Sauvegarde creee: $target"
