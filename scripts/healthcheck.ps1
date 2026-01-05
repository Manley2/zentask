param(
  [Parameter(Mandatory=$true)][string]$Url,
  [int]$TimeoutSeconds = 120
)

$deadline = (Get-Date).AddSeconds($TimeoutSeconds)
Write-Host "[healthcheck] Checking $Url (timeout ${TimeoutSeconds}s)"

while((Get-Date) -lt $deadline){
  try {
    $resp = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 10
    if ($resp.StatusCode -eq 200) {
      Write-Host "[healthcheck] OK (200)"
      exit 0
    } else {
      Write-Host "[healthcheck] Status: $($resp.StatusCode)"
    }
  } catch {
    Write-Host "[healthcheck] Not ready yet: $($_.Exception.Message)"
  }
  Start-Sleep -Seconds 5
}

Write-Error "[healthcheck] FAILED: timeout waiting for 200 OK"
exit 1
