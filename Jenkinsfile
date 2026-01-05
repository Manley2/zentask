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
    string(name: 'AZ_WEBAPP_NAME',   defaultValue: 'Zentask-web',        description: 'Nama Azure Web App (App Service)')
    string(name: 'AZ_SUBSCRIPTION_ID', defaultValue: '738fb47b-f5e3-4518-b6ab-6b5865958218', description: 'Azure Subscription ID')
  }

  environment {
    // === Jenkins Credential IDs ===
    // ACR (Username with password)
    ACR_CRED_ID = 'acr-admin-zentask'

    // Azure Service Principal (Secret Text)
    AZ_TENANT_ID_CRED     = 'azure-tenant-id'
    AZ_CLIENT_ID_CRED     = 'azure-client-id'
    AZ_CLIENT_SECRET_CRED = 'azure-client-secret'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        script {
          env.GIT_COMMIT_SHORT = powershell(returnStdout: true, script: '(git rev-parse --short HEAD).Trim()').trim()

          env.ACR_LOGIN_SERVER = params.ACR_LOGIN_SERVER
          env.IMAGE_NAME       = params.IMAGE_NAME

          env.FULL_IMAGE_SHA    = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:${env.GIT_COMMIT_SHORT}"
          env.FULL_IMAGE_LATEST = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:latest"

          echo "Building commit: ${env.GIT_COMMIT_SHORT}"
          echo "Image SHA: ${env.FULL_IMAGE_SHA}"
          echo "Image latest: ${env.FULL_IMAGE_LATEST}"
        }
      }
    }

    stage('Prepare Environment') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"
          if (!(Test-Path ".env")) {
            if (Test-Path ".env.example") {
              Copy-Item ".env.example" ".env"
              Write-Host "Created .env from .env.example"
            } else {
              Write-Host "No .env or .env.example found (OK if image handles it)"
            }
          } else {
            Write-Host ".env already exists"
          }
        '''
      }
    }

    stage('Build Docker Image') {
      steps {
        bat """
          docker build -t "%FULL_IMAGE_SHA%" -t "%FULL_IMAGE_LATEST%" .
        """
      }
    }

    stage('Test Image') {
      steps {
        bat """
          docker run --rm --entrypoint php "%FULL_IMAGE_SHA%" -v
        """
      }
    }

    stage('Login to ACR') {
      steps {
        withCredentials([
          usernamePassword(
            credentialsId: 'acr-admin-zentask',
            usernameVariable: 'ACR_USER',
            passwordVariable: 'ACR_PASS'
          )
        ]) {
          powershell '''
            $ErrorActionPreference = "Stop"
            Write-Host "Logging in to ACR: $env:ACR_LOGIN_SERVER"
            docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
            docker login $env:ACR_LOGIN_SERVER -u $env:ACR_USER -p $env:ACR_PASS
          '''
        }
      }
    }

    stage('Push to ACR') {
      steps {
        script {
          def pushWithRetry = { String imageRef ->
            retry(3) {
              try {
                powershell """
                  \$ErrorActionPreference = "Stop"
                  Write-Host "Pushing ${imageRef}"
                  docker push "${imageRef}"
                """
              } catch (Exception e) {
                echo "Push failed, retrying in 10 seconds..."
                sleep(time: 10, unit: 'SECONDS')
                throw e
              }
            }
          }

          pushWithRetry(env.FULL_IMAGE_SHA)
          pushWithRetry(env.FULL_IMAGE_LATEST)

          echo "✅ Successfully pushed images:"
          echo "   - ${env.FULL_IMAGE_SHA}"
          echo "   - ${env.FULL_IMAGE_LATEST}"
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
          powershell(label: 'Deploy WebApp (Azure CLI)', script: """
            \$ErrorActionPreference = "Stop"
            \$ProgressPreference    = "SilentlyContinue"

            function Run-Az([string] \$args) {
              Write-Host ">>> az \$args"
              \$out = & az \$args 2>&1
              \$code = \$LASTEXITCODE
              if (\$out) { \$out | ForEach-Object { Write-Host \$_ } }
              if (\$code -ne 0) { throw "AZ failed (exit=\$code): az \$args" }
            }

            # Hard fail kalau parameter kosong (biar jelas errornya)
            if ([string]::IsNullOrWhiteSpace("${params.AZ_SUBSCRIPTION_ID}")) { throw "AZ_SUBSCRIPTION_ID parameter is empty" }
            if ([string]::IsNullOrWhiteSpace("${params.AZ_RESOURCE_GROUP}"))  { throw "AZ_RESOURCE_GROUP parameter is empty" }
            if ([string]::IsNullOrWhiteSpace("${params.AZ_WEBAPP_NAME}"))     { throw "AZ_WEBAPP_NAME parameter is empty" }
            if ([string]::IsNullOrWhiteSpace("\$env:FULL_IMAGE_SHA"))          { throw "FULL_IMAGE_SHA env is empty" }

            Write-Host "DEPLOY TARGET:"
            Write-Host " - Subscription : ${params.AZ_SUBSCRIPTION_ID}"
            Write-Host " - ResourceGroup: ${params.AZ_RESOURCE_GROUP}"
            Write-Host " - WebApp       : ${params.AZ_WEBAPP_NAME}"
            Write-Host " - Image        : \$env:FULL_IMAGE_SHA"
            Write-Host " - ACR          : \$env:ACR_LOGIN_SERVER"

            Run-Az "--version"

            # Login SP
            Run-Az "login --service-principal -u \$env:AZ_CLIENT_ID -p \$env:AZ_CLIENT_SECRET --tenant \$env:AZ_TENANT_ID"
            Run-Az "account set --subscription ${params.AZ_SUBSCRIPTION_ID}"

            # Pastikan webapp ada (kalau salah nama, error kebaca di sini)
            Run-Az "webapp show --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} -o table"

            # Apply container config
            Run-Az "webapp config container set --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} --docker-custom-image-name \$env:FULL_IMAGE_SHA --docker-registry-server-url https://\$env:ACR_LOGIN_SERVER --docker-registry-server-user \$env:ACR_USER --docker-registry-server-password \$env:ACR_PASS"

            # wajib untuk linux custom container
            Run-Az "webapp config appsettings set --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} --settings WEBSITES_PORT=80"

            # enable container logging
            Run-Az "webapp log config --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} --docker-container-logging filesystem --level information"

            # restart webapp
            Run-Az "webapp restart --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP}"

            # verify config (tampil di Jenkins console)
            Write-Host "VERIFY CONTAINER CONFIG:"
            Run-Az "webapp config container show --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} -o json"

            Write-Host "DEPLOY OK"
          """)
        }
      }
    }

    stage('Health Check') {
      steps {
        powershell """
          \$ErrorActionPreference = "Stop"
          \$ProgressPreference    = "SilentlyContinue"

          Start-Sleep -Seconds 30
          \$url = "https://${params.AZ_WEBAPP_NAME}.azurewebsites.net/"
          Write-Host "Checking: \$url"

          try {
            \$resp = Invoke-WebRequest -Uri \$url -UseBasicParsing -TimeoutSec 30
            Write-Host ("HTTP " + \$resp.StatusCode)
            Write-Host "Health OK"
          } catch {
            Write-Host "Health FAILED: \$($_.Exception.Message)"
            Write-Host "=== DEBUG: last container logs (tail) ==="
            try {
              az webapp log tail --name ${params.AZ_WEBAPP_NAME} --resource-group ${params.AZ_RESOURCE_GROUP} 2>&1 | Select-Object -First 120 | ForEach-Object { Write-Host \$_ }
            } catch {
              Write-Host "Cannot tail logs (az not logged in / permission issue)."
            }
            throw
          }
        """
      }
    }

    stage('Cleanup') {
      steps {
        bat """
          docker rmi "%FULL_IMAGE_SHA%" 1>NUL 2>NUL
          docker rmi "%FULL_IMAGE_LATEST%" 1>NUL 2>NUL
          docker image prune -f 1>NUL 2>NUL
          echo Cleanup done
        """
      }
    }
  }

  post {
    always {
      // logout ACR (tidak bikin fail)
      powershell '''
        $ErrorActionPreference = "Continue"
        docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
      '''
    }
  }
}
