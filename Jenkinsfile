pipeline {
  agent any

  options {
    timestamps()
    disableConcurrentBuilds()
    skipDefaultCheckout(true)
  }

  parameters {
    string(name: 'ACR_LOGIN_SERVER', defaultValue: 'zentask.azurecr.io', description: 'ACR login server (contoh: zentask.azurecr.io)')
    string(name: 'IMAGE_NAME', defaultValue: 'zentask', description: 'Nama image di registry')
    string(name: 'AZ_RESOURCE_GROUP', defaultValue: 'tubes-zentask', description: 'Resource Group Web App')
    string(name: 'AZ_WEBAPP_NAME', defaultValue: 'Zentask-web', description: 'Nama Azure Web App (App Service)')
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

    // Optional: Laravel APP KEY for local image test (Secret Text)
    // (Kalau kamu tidak punya/ga perlu, boleh hapus stage Test Image ENV injection)
    LARAVEL_APP_KEY_CRED = 'laravel-app-key'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        script {
          // Ambil short SHA (mirip versi macOS)
          env.GIT_COMMIT_SHORT = powershell(
            returnStdout: true,
            script: '(git rev-parse --short HEAD).Trim()'
          ).trim()

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
        // Sama ide seperti macOS: buat .env kalau belum ada (buat build context)
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
        // Kalau butuh platform linux/amd64 di Windows docker desktop, kamu bisa tambahkan:
        // docker build --platform linux/amd64 ...
        bat """
          docker build -t "%FULL_IMAGE_SHA%" -t "%FULL_IMAGE_LATEST%" .
        """
      }
    }

    stage('Test Image') {
      steps {
        // Test ringan seperti versi macOS: pastikan php jalan di image
        // (Tidak perlu run full web server)
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
                Write-Host "Using user: $env:ACR_USER"

                docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null

                docker login `
                $env:ACR_LOGIN_SERVER `
                -u $env:ACR_USER `
                -p $env:ACR_PASS
            '''
            }
        }
    }


    stage('Push to ACR') {
      steps {
        script {
          // retry push 3x seperti versi macOS
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
          // Paling aman: pakai script repo kamu (deploy-azure-webapp.ps1)
          // Agar konsisten dengan Jenkinsfile kamu sebelumnya.
          powershell(label: 'Deploy WebApp', script: '''
            $ErrorActionPreference = "Stop"
            $ProgressPreference    = "SilentlyContinue"

            Write-Host "DEPLOY: subscription=$env:AZ_SUBSCRIPTION_ID rg=$env:AZ_RESOURCE_GROUP app=$env:AZ_WEBAPP_NAME"
            Write-Host "DEPLOY: image=$env:FULL_IMAGE_SHA"
            Write-Host "DEPLOY: acr=$env:ACR_LOGIN_SERVER user=$env:ACR_USER"

            $scriptPath = Join-Path $env:WORKSPACE "scripts\\deploy-azure-webapp.ps1"
            if (!(Test-Path $scriptPath)) { throw "Deploy script not found: $scriptPath" }

            $logPath = Join-Path $env:WORKSPACE ("deploy-azure-webapp-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")
            Write-Host "DEPLOY LOG: $logPath"

            & $scriptPath `
              -SubscriptionId   $env:AZ_SUBSCRIPTION_ID `
              -TenantId         $env:AZ_TENANT_ID `
              -ClientId         $env:AZ_CLIENT_ID `
              -ClientSecret     $env:AZ_CLIENT_SECRET `
              -ResourceGroup    $env:AZ_RESOURCE_GROUP `
              -WebAppName       $env:AZ_WEBAPP_NAME `
              -ImageName        $env:FULL_IMAGE_SHA `
              -RegistryServer   $env:ACR_LOGIN_SERVER `
              -RegistryUser     $env:ACR_USER `
              -RegistryPassword $env:ACR_PASS `
              *>&1 | Tee-Object -FilePath $logPath

            if ($LASTEXITCODE -ne 0) {
              throw "Deploy script failed with exit code $LASTEXITCODE"
            }

            Write-Host "DEPLOY OK"
          ''')
        }
      }
    }

    stage('Health Check') {
      steps {
        // mirip macOS: tunggu sebentar lalu curl/healthcheck
        powershell '''
          $ErrorActionPreference = "Continue"
          Start-Sleep -Seconds 30

          $url = ("https://{0}.azurewebsites.net/" -f $env:AZ_WEBAPP_NAME)
          Write-Host "Checking application health: $url"

          try {
            # kalau kamu punya scripts/healthcheck.ps1, pakai itu (lebih rapi)
            if (Test-Path ".\\scripts\\healthcheck.ps1") {
              ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 240
            } else {
              # fallback basic
              Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30 | Out-Null
              Write-Host "Health check OK"
            }
          } catch {
            Write-Host "Health check failed, check logs"
            exit 1
          }
        '''
      }
    }

    stage('Cleanup') {
      steps {
        // bersihin local images seperti versi macOS
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
    success {
      script {
        def duration = currentBuild.durationString.replace(' and counting', '')
        echo """
╔═══════════════════════════════════════════════════════════╗
║           🎉 DEPLOYMENT SUCCESSFUL 🎉                      ║
╠═══════════════════════════════════════════════════════════╣
║ Build: #${env.BUILD_NUMBER}                               ║
║ Commit: ${env.GIT_COMMIT_SHORT}                           ║
║ Duration: ${duration}                                     ║
║                                                           ║
║ 🌐 Application URL:                                       ║
║    https://${params.AZ_WEBAPP_NAME}.azurewebsites.net      ║
║                                                           ║
║ 🐳 Docker Images:                                         ║
║    ${env.FULL_IMAGE_SHA}                                  ║
║    ${env.FULL_IMAGE_LATEST}                               ║
╚═══════════════════════════════════════════════════════════╝
        """
      }
    }

    failure {
      script {
        echo """
╔═══════════════════════════════════════════════════════════╗
║           ❌ DEPLOYMENT FAILED ❌                          ║
╠═══════════════════════════════════════════════════════════╣
║ Build: #${env.BUILD_NUMBER}                               ║
║ Commit: ${env.GIT_COMMIT_SHORT}                           ║
║                                                           ║
║ Please check the console output for error details.        ║
╚═══════════════════════════════════════════════════════════╝
        """
      }
    }

    always {
      archiveArtifacts artifacts: 'deploy-azure-webapp-*.log', allowEmptyArchive: true
      // logout ACR (tidak bikin fail)
      powershell '''
        $ErrorActionPreference = "Continue"
        docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
      '''
    }
  }
}
