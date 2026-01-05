param(
  [Parameter(Mandatory=$true)][string]$SubscriptionId,
  [Parameter(Mandatory=$true)][string]$TenantId,
  [Parameter(Mandatory=$true)][string]$ClientId,
  [Parameter(Mandatory=$true)][string]$ClientSecret,
  [Parameter(Mandatory=$true)][string]$ResourceGroup,
  [Parameter(Mandatory=$true)][string]$WebAppName,
  [Parameter(Mandatory=$true)][string]$ImageName,        # full: zentask.azurecr.io/zentask:<sha>
  [Parameter(Mandatory=$true)][string]$RegistryServer,   # zentask.azurecr.io
  [Parameter(Mandatory=$true)][string]$RegistryUser,
  [Parameter(Mandatory=$true)][string]$RegistryPassword
)

Write-Host "[deploy] az login (service principal)..."
az login --service-principal -u $ClientId -p $ClientSecret --tenant $TenantId | Out-Null
az account set --subscription $SubscriptionId

Write-Host "[deploy] Set container image: $ImageName"
# pakai registry creds (pasti jalan). Kalau nanti kamu mau pakai Managed Identity AcrPull,
# bagian user/pass bisa dihapus setelah MI beres.
az webapp config container set `
  --name $WebAppName `
  --resource-group $ResourceGroup `
  --docker-custom-image-name $ImageName `
  --docker-registry-server-url ("https://{0}" -f $RegistryServer) `
  --docker-registry-server-user $RegistryUser `
  --docker-registry-server-password $RegistryPassword | Out-Null

Write-Host "[deploy] Restart web app..."
az webapp restart --name $WebAppName --resource-group $ResourceGroup | Out-Null

Write-Host "[deploy] Done."
