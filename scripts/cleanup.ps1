param(
  [string]$ContainerName = "zentask_test_container",
  [string]$ImageSha,
  [string]$ImageLatest,
  [switch]$IgnoreErrors
)

function TryRun([string]$cmd){
  try {
    iex $cmd | Out-Null

    # PENTING: Jenkins bisa gagal kalau $LASTEXITCODE != 0
    if ($LASTEXITCODE -ne 0) {
      if(-not $IgnoreErrors){
        Write-Host "[cleanup] WARN: cmd failed (exit=$LASTEXITCODE): $cmd"
      }
      $global:LASTEXITCODE = 0
    }
  } catch {
    if(-not $IgnoreErrors){
      Write-Host "[cleanup] WARN: exception on: $cmd | $($_.Exception.Message)"
    }
    $global:LASTEXITCODE = 0
  }
}

Write-Host "[cleanup] Stop & remove test container..."
TryRun "docker rm -f $ContainerName 2>$null"

if($ImageSha){
  Write-Host "[cleanup] Remove image (sha)..."
  TryRun "docker rmi -f $ImageSha 2>$null"
}
if($ImageLatest){
  Write-Host "[cleanup] Remove image (latest)..."
  TryRun "docker rmi -f $ImageLatest 2>$null"
}

Write-Host "[cleanup] Prune dangling..."
TryRun "docker image prune -f 2>$null"

Write-Host "[cleanup] Done."
$global:LASTEXITCODE = 0
exit 0
