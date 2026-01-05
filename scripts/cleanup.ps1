param(
  [string]$ContainerName = "zentask_test_container",
  [string]$ImageSha,
  [string]$ImageLatest,
  [switch]$IgnoreErrors
)

function TryRun($cmd){
  try {
    iex $cmd | Out-Null
  } catch {
    if(-not $IgnoreErrors){
      Write-Host "[cleanup] WARN: $cmd failed: $($_.Exception.Message)"
    }
  }
}

Write-Host "[cleanup] Stop & remove test container..."
TryRun "docker rm -f $ContainerName"

if($ImageSha){
  Write-Host "[cleanup] Remove image (sha)..."
  TryRun "docker rmi -f $ImageSha"
}
if($ImageLatest){
  Write-Host "[cleanup] Remove image (latest)..."
  TryRun "docker rmi -f $ImageLatest"
}

Write-Host "[cleanup] Prune dangling..."
TryRun "docker image prune -f"
Write-Host "[cleanup] Done."
