pipeline {
  agent any

  options {
    timestamps()
    disableConcurrentBuilds()
    skipDefaultCheckout(true)
  }

  parameters {
    string(name: 'ACR_LOGIN_SERVER', defaultValue: 'zentask.azurecr.io', description: 'ACR login server (contoh: zentask.azurecr.io)')
    string(name: 'IMAGE_NAME',       defaultValue: 'zentask',            description: 'Nama image di registry')
    string(name: 'AZ_RESOURCE_GROUP',defaultValue: 'tubes-zentask',       description: 'Resource Group Web App')
    string(name: 'AZ_WEBAPP_NAME',   defaultValue: 'zentask-web',         description: 'Nama Azure Web App (App Service)')
    string(name: 'AZ_SUBSCRIPTION_ID', defaultValue: '738fb47b-f5e3-4518-b6ab-6b5865958218', description: 'Azure Subscription ID')
  }

  environment {
    // Jenkins Credential IDs
    ACR_CRED_ID           = 'acr-admin-zentask'     // Username+Password (ACR Access Keys)
    AZ_TENANT_ID_CRED     = 'azure-tenant-id'       // Secret text
    AZ_CLIENT_ID_CRED     = 'azure-client-id'       // Secret text
    AZ_CLIENT_SECRET_CRED = 'azure-client-secret'   // Secret text
  }

  stages {

    stage('Checkout') {
      steps {
        checkout scm
        script {
          env.GIT_COMMIT_SHORT = powershell(returnStdout: true, script: '(git rev-parse --short HEAD).Trim()').trim()

          env.ACR_LOGIN_SERVER   = params.ACR_LOGIN_SERVER
          env.IMAGE_NAME         = params.IMAGE_NAME
          env.AZ_RESOURCE_GROUP  = params.AZ_RESOURCE_GROUP
          env.AZ_WEBAPP_NAME     = params.AZ_WEBAPP_NAME
          env.AZ_SUBSCRIPTION_ID = params.AZ_SUBSCRIPTION_ID

          env.FULL_IMAGE_SHA    = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:${env.GIT_COMMIT_SHORT}"
          env.FULL_IMAGE_LATEST = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:latest"

          echo "Commit: ${env.GIT_COMMIT_SHORT}"
          echo "Image SHA: ${env.FULL_IMAGE_SHA}"
          echo "Image Latest: ${env.FULL_IMAGE_LATEST}"
        }
      }
    }

    stage('Preflight (DNS & Tools)') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"
          $ProgressPreference    = "SilentlyContinue"

          Write-Host "=== Preflight: tools ==="
          docker version
          az --version

          Write-Host "=== Preflight: DNS check for ACR ==="
          try {
            Resolve-DnsName -Name $env:ACR_LOGIN_SERVER -ErrorAction Stop | Out-Host
          } catch {
            Write-Host "DNS FAILED for $env:ACR_LOGIN_SERVER"
            Write-Host "This usually means Jenkins agent cannot resolve the hostname (proxy/DNS/network)."
            throw
          }
        '''
      }
    }

    stage('Build Docker Image') {
      steps {
        bat 'docker build -t "%FULL_IMAGE_SHA%" -t "%FULL_IMAGE_LATEST%" .'
      }
    }

    stage('Test Image') {
      steps {
        bat 'docker run --rm --entrypoint php "%FULL_IMAGE_SHA%" -v'
      }
    }

    stage('Login to ACR') {
      steps {
        withCredentials([usernamePassword(credentialsId: env.ACR_CRED_ID, usernameVariable: 'ACR_USER', passwordVariable: 'ACR_PASS')]) {
          powershell '''
            $ErrorActionPreference = "Stop"
            $ProgressPreference    = "SilentlyContinue"

            Write-Host "Logging in to ACR: $env:ACR_LOGIN_SERVER as $env:ACR_USER"
            docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null

            # use stdin to avoid masking issues
            $env:ACR_PASS | docker login $env:ACR_LOGIN_SERVER -u $env:ACR_USER --password-stdin
          '''
        }
      }
    }

    stage('Push to ACR') {
      steps {
        script {
          def pushWithRetry = { String imageRef ->
            retry(3) {
              powershell """
                \$ErrorActionPreference = "Stop"
                \$ProgressPreference    = "SilentlyContinue"

                Write-Host "Pushing: ${imageRef}"
                docker push "${imageRef}"
              """
            }
          }

          pushWithRetry(env.FULL_IMAGE_SHA)
          pushWithRetry(env.FULL_IMAGE_LATEST)

          echo "✅ Pushed:"
          echo " - ${env.FULL_IMAGE_SHA}"
          echo " - ${env.FULL_IMAGE_LATEST}"
        }
      }
    }

    stage('Deploy to Azure Web App') {
      steps {
        withCredentials([
          usernamePassword(credentialsId: env.ACR_CRED_ID, usernameVariable: 'ACR_USER', passwordVariable: 'ACR_PASS'),
          string(credentialsId: env.AZ_TENANT_ID_CRED, variable: 'AZ_TENANT_ID'),
          string(credentialsId: env.AZ_CLIENT_ID_CRED, variable: 'AZ_CLIENT_ID'),
          string(credentialsId: env.AZ_CLIENT_SECRET_CRED, variable: 'AZ_CLIENT_SECRET')
        ]) {
          powershell '''
            $ErrorActionPreference = "Stop"
            $ProgressPreference    = "SilentlyContinue"

            function Run-Az([string]$args) {
              Write-Host ">>> az $args"
              $out = & az $args 2>&1
              $code = $LASTEXITCODE
              if ($out) { $out | ForEach-Object { Write-Host $_ } }
              if ($code -ne 0) { throw "AZ failed (exit=$code): az $args" }
            }

            Write-Host "Login Azure (service principal)..."
            Run-Az "login --service-principal -u $env:AZ_CLIENT_ID -p $env:AZ_CLIENT_SECRET --tenant $env:AZ_TENANT_ID"

            Write-Host "Set subscription: $env:AZ_SUBSCRIPTION_ID"
            Run-Az "account set --subscription $env:AZ_SUBSCRIPTION_ID"

            Write-Host "Deploy target:"
            Write-Host " - RG     : $env:AZ_RESOURCE_GROUP"
            Write-Host " - WebApp : $env:AZ_WEBAPP_NAME"
            Write-Host " - Image  : $env:FULL_IMAGE_SHA"
            Write-Host " - ACR    : $env:ACR_LOGIN_SERVER"

            # (Optional) Pastikan webapp Linux container (kadang salah OS bikin error aneh)
            Run-Az "webapp show --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP -o table"

            # Set container image + credentials
            Run-Az "webapp config container set --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP --docker-custom-image-name $env:FULL_IMAGE_SHA --docker-registry-server-url https://$env:ACR_LOGIN_SERVER --docker-registry-server-user $env:ACR_USER --docker-registry-server-password $env:ACR_PASS"

            # WAJIB untuk custom container
            Run-Az "webapp config appsettings set --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP --settings WEBSITES_PORT=80"

            # Enable container logging (biar lognya gerak)
            Run-Az "webapp log config --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP --docker-container-logging filesystem --level information"

            # Restart
            Run-Az "webapp restart --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP"

            Write-Host "Verify container config:"
            Run-Az "webapp config container show --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP -o json"

            Write-Host "Deploy OK"
          '''
        }
      }
    }

    stage('Health Check') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"
          $ProgressPreference    = "SilentlyContinue"

          function Try-Debug() {
            Write-Host "=== DEBUG: webapp status ==="
            az webapp show --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP -o table 2>&1 | ForEach-Object { Write-Host $_ }

            Write-Host "=== DEBUG: container config ==="
            az webapp config container show --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP -o json 2>&1 | ForEach-Object { Write-Host $_ }

            Write-Host "=== DEBUG: appsettings (filtered) ==="
            az webapp config appsettings list --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP `
              --query "[?name=='WEBSITES_PORT' || starts_with(name,'DB_') || name=='APP_ENV' || name=='APP_DEBUG' || name=='APP_URL' || name=='SESSION_DRIVER' || name=='CACHE_DRIVER'].{name:name,value:value}" `
              -o table 2>&1 | ForEach-Object { Write-Host $_ }

            Write-Host "=== DEBUG: container logs (short tail) ==="
            # kadang tail bisa streaming; kita potong output agar Jenkins ga nge-hang
            try {
              az webapp log tail --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP 2>&1 |
                Select-Object -First 120 | ForEach-Object { Write-Host $_ }
            } catch {
              Write-Host "log tail failed: $($_.Exception.Message)"
            }

            Write-Host "=== DEBUG: download logs zip ==="
            try {
              az webapp log download --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP --log-file webapp-logs.zip 2>&1 |
                ForEach-Object { Write-Host $_ }
            } catch {
              Write-Host "log download failed: $($_.Exception.Message)"
            }
          }

          Start-Sleep -Seconds 35

          $url = "https://{0}.azurewebsites.net/" -f $env:AZ_WEBAPP_NAME
          Write-Host "Checking: $url"

          try {
            $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
            Write-Host ("HTTP " + $resp.StatusCode)
            Write-Host "Health OK"
          } catch {
            Write-Host "Health FAILED: $($_.Exception.Message)"
            Try-Debug
            throw
          }
        '''
      }
    }
  }

  post {
    always {
      // archive log zip kalau ada
      archiveArtifacts artifacts: 'webapp-logs.zip', allowEmptyArchive: true

      // jangan sampai cleanup bikin build gagal
      powershell '''
        $ErrorActionPreference="Continue"
        docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
      '''
    }
  }
}
