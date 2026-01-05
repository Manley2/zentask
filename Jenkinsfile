pipeline {
  agent any

  options {
    timestamps()
    disableConcurrentBuilds()
    skipDefaultCheckout(true)
  }

  parameters {
    string(name: 'ACR_LOGIN_SERVER', defaultValue: 'zentask.azurecr.io', description: 'ACR login server')
    string(name: 'IMAGE_NAME', defaultValue: 'zentask', description: 'Nama image di registry')
    string(name: 'AZ_RESOURCE_GROUP', defaultValue: 'tubes-zentask', description: 'Resource Group Web App')
    string(name: 'AZ_WEBAPP_NAME', defaultValue: 'Zentask-web', description: 'Nama Azure Web App')
    string(name: 'AZ_SUBSCRIPTION_ID', defaultValue: '738fb47b-f5e3-4518-b6ab-6b5865958218', description: 'Azure Subscription ID')
    string(name: 'APP_PORT_LOCAL', defaultValue: '8085', description: 'Port lokal untuk test container')
  }

  environment {
    ACR_CRED_ID           = 'acr-admin-zentask'
    AZ_TENANT_ID_CRED     = 'azure-tenant-id'
    AZ_CLIENT_ID_CRED     = 'azure-client-id'
    AZ_CLIENT_SECRET_CRED = 'azure-client-secret'
    TEST_CONTAINER        = "zentask_test_${BUILD_NUMBER}"
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        bat 'git --version'
        bat 'git rev-parse --short HEAD'
      }
    }

    stage('Prepare Environment') {
      steps {
        script {
          def sha = (env.GIT_COMMIT ?: "").trim()
          if (sha.length() >= 7) {
            env.GIT_SHA = sha.substring(0, 7)
          } else {
            env.GIT_SHA = powershell(returnStdout: true, script: '(git rev-parse --short HEAD).Trim()').trim()
          }

          env.IMAGE_TAG          = env.GIT_SHA
          env.ACR_LOGIN_SERVER   = params.ACR_LOGIN_SERVER
          env.IMAGE_NAME         = params.IMAGE_NAME
          env.AZ_RESOURCE_GROUP  = params.AZ_RESOURCE_GROUP
          env.AZ_WEBAPP_NAME     = params.AZ_WEBAPP_NAME
          env.AZ_SUBSCRIPTION_ID = params.AZ_SUBSCRIPTION_ID
          env.APP_PORT_LOCAL     = params.APP_PORT_LOCAL
          env.FULL_IMAGE_SHA     = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:${env.IMAGE_TAG}"
          env.FULL_IMAGE_LATEST  = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:latest"
        }

        bat """
          echo Commit SHA: %GIT_SHA%
          echo ACR Login Server: %ACR_LOGIN_SERVER%
          echo Image (sha): %FULL_IMAGE_SHA%
          echo Image (latest): %FULL_IMAGE_LATEST%
          docker version
        """
      }
    }

    stage('Build Docker Image') {
      steps {
        bat 'docker build -t "%FULL_IMAGE_SHA%" -t "%FULL_IMAGE_LATEST%" .'
      }
    }

    stage('Test Image') {
      steps {
        bat '''
          @echo on
          docker rm -f %TEST_CONTAINER% >NUL 2>NUL
          echo [test] Cleaned old container (if any): %TEST_CONTAINER%
        '''

        withCredentials([string(credentialsId: 'laravel-app-key', variable: 'APP_KEY')]) {
          powershell '''
            $ErrorActionPreference = "Stop"

            Write-Host "[test] Starting container: $env:TEST_CONTAINER on port $env:APP_PORT_LOCAL"
            Write-Host "[test] Image: $env:FULL_IMAGE_SHA"

            $dockerArgs = @(
              "run", "-d",
              "--name", $env:TEST_CONTAINER,
              "-p", "$($env:APP_PORT_LOCAL):80",
              "-e", "APP_ENV=production",
              "-e", "APP_DEBUG=false",
              "-e", "APP_KEY=$env:APP_KEY",
              "-e", "APP_URL=http://localhost:$($env:APP_PORT_LOCAL)",
              "-e", "LOG_CHANNEL=stderr",
              $env:FULL_IMAGE_SHA
            )

            & docker $dockerArgs

            if ($LASTEXITCODE -ne 0) {
              throw "Docker run failed with exit code $LASTEXITCODE"
            }

            Write-Host "[test] Container started successfully, waiting 10 seconds for initialization..."
            Start-Sleep -Seconds 10

            # Check if container is still running
            $containerStatus = docker ps --filter "name=$env:TEST_CONTAINER" --format "{{.Status}}"
            if (-not $containerStatus) {
              Write-Host "[test] ERROR: Container stopped unexpectedly"
              Write-Host "---- Container logs ----"
              docker logs $env:TEST_CONTAINER
              throw "Container failed to stay running"
            }

            Write-Host "[test] Container status: $containerStatus"
            docker ps --filter "name=$env:TEST_CONTAINER"
          '''
        }

        powershell '''
          $ErrorActionPreference = "Continue"

          $url = "http://localhost:$($env:APP_PORT_LOCAL)/"
          Write-Host "[healthcheck] Checking $url"

          try {
            if (Test-Path "./scripts/healthcheck.ps1") {
              & ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 180
              Write-Host "[healthcheck] OK"
            } else {
              Write-Host "[healthcheck] Script not found, doing basic check..."

              $maxAttempts = 30
              $attempt = 0
              $success = $false

              while ($attempt -lt $maxAttempts -and -not $success) {
                $attempt++
                Write-Host "[healthcheck] Attempt $attempt/$maxAttempts..."

                try {
                  $response = Invoke-WebRequest -Uri $url -TimeoutSec 5 -UseBasicParsing
                  if ($response.StatusCode -eq 200) {
                    $success = $true
                    Write-Host "[healthcheck] Success! Status code: $($response.StatusCode)"
                  }
                } catch {
                  Write-Host "[healthcheck] Failed: $($_.Exception.Message)"
                  Start-Sleep -Seconds 6
                }
              }

              if (-not $success) {
                throw "Health check failed after $maxAttempts attempts"
              }
            }
          }
          catch {
            Write-Host "[healthcheck] FAILED: $($_.Exception.Message)"
            Write-Host ""
            Write-Host "==== DIAGNOSTIC INFO ===="
            Write-Host ""
            Write-Host "---- Docker PS ----"
            docker ps --filter "name=$env:TEST_CONTAINER"
            Write-Host ""
            Write-Host "---- Docker Inspect (Health/State) ----"
            docker inspect $env:TEST_CONTAINER --format='{{json .State}}' | ConvertFrom-Json | ConvertTo-Json -Depth 5
            Write-Host ""
            Write-Host "---- Container Logs (last 200 lines) ----"
            docker logs --tail 200 $env:TEST_CONTAINER
            Write-Host ""
            Write-Host "---- Laravel Logs ----"
            docker exec $env:TEST_CONTAINER sh -c "ls -lh /var/www/storage/logs/ 2>/dev/null || echo 'No logs directory'"
            docker exec $env:TEST_CONTAINER sh -c "tail -n 200 /var/www/storage/logs/laravel*.log 2>/dev/null || echo 'No Laravel logs found'"
            Write-Host ""
            Write-Host "---- Nginx Error Log ----"
            docker exec $env:TEST_CONTAINER sh -c "tail -n 50 /var/log/nginx/error.log 2>/dev/null || echo 'No nginx error log'"
            Write-Host ""
            Write-Host "---- PHP-FPM Status ----"
            docker exec $env:TEST_CONTAINER sh -c "ps aux | grep php-fpm || echo 'php-fpm not running'"
            Write-Host ""
            Write-Host "---- Test Connection from Inside Container ----"
            docker exec $env:TEST_CONTAINER sh -c "curl -I http://localhost:80 2>&1 || echo 'curl failed'"

            exit 1
          }
        '''
      }
    }

    stage('Login to ACR') {
      steps {
        withCredentials([
          usernamePassword(credentialsId: env.ACR_CRED_ID, usernameVariable: 'ACR_USER', passwordVariable: 'ACR_PASS')
        ]) {
          bat """
            echo Logging in to ACR: %ACR_LOGIN_SERVER%
            docker login %ACR_LOGIN_SERVER% -u %ACR_USER% -p %ACR_PASS%
          """
        }
      }
    }

    stage('Push to ACR') {
      steps {
        bat """
          echo Pushing image with SHA tag...
          docker push "%FULL_IMAGE_SHA%"
          echo Pushing image with latest tag...
          docker push "%FULL_IMAGE_LATEST%"
        """
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

            Write-Host "==== DEPLOY CONFIGURATION ===="
            Write-Host "Subscription: $env:AZ_SUBSCRIPTION_ID"
            Write-Host "Resource Group: $env:AZ_RESOURCE_GROUP"
            Write-Host "Web App: $env:AZ_WEBAPP_NAME"
            Write-Host "Image: $env:FULL_IMAGE_SHA"
            Write-Host "Registry: $env:ACR_LOGIN_SERVER"
            Write-Host "Registry User: $env:ACR_USER"
            Write-Host ""

            $scriptPath = Join-Path $env:WORKSPACE "scripts/deploy-azure-webapp.ps1"
            if (!(Test-Path $scriptPath)) {
              throw "Deploy script not found: $scriptPath"
            }

            $logPath = Join-Path $env:WORKSPACE ("deploy-azure-webapp-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")
            Write-Host "Deploy log will be saved to: $logPath"
            Write-Host ""

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

            Write-Host ""
            Write-Host "==== DEPLOY SUCCESSFUL ===="
          '''
        }
      }
    }

    stage('Health Check Production') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"

          $url = "https://$($env:AZ_WEBAPP_NAME).azurewebsites.net/"
          Write-Host "[healthcheck] Checking production: $url"

          if (Test-Path "./scripts/healthcheck.ps1") {
            & ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 240
            Write-Host "[healthcheck] Production is healthy"
          } else {
            Write-Host "[healthcheck] Script not found, doing basic check..."

            $maxAttempts = 40
            $attempt = 0
            $success = $false

            while ($attempt -lt $maxAttempts -and -not $success) {
              $attempt++
              Write-Host "[healthcheck] Attempt $attempt/$maxAttempts..."

              try {
                $response = Invoke-WebRequest -Uri $url -TimeoutSec 10 -UseBasicParsing
                if ($response.StatusCode -eq 200) {
                  $success = $true
                  Write-Host "[healthcheck] Success! Status code: $($response.StatusCode)"
                }
              } catch {
                Write-Host "[healthcheck] Failed: $($_.Exception.Message)"
                Start-Sleep -Seconds 6
              }
            }

            if (-not $success) {
              throw "Production health check failed after $maxAttempts attempts"
            }
          }
        '''
      }
    }
  }

  post {
    always {
      echo 'Post Actions: cleanup test container + archive deploy logs'

      archiveArtifacts artifacts: 'deploy-azure-webapp-*.log', allowEmptyArchive: true

      powershell '''
        $ErrorActionPreference = "Continue"

        if ($env:TEST_CONTAINER) {
          Write-Host ""
          Write-Host "==== Final Container Logs (last 300 lines) ===="
          docker logs --tail 300 $env:TEST_CONTAINER 2>$null
        }
      '''

      bat """
        docker rm -f %TEST_CONTAINER% >NUL 2>NUL
        echo [post] Cleaned test container: %TEST_CONTAINER%
      """

      echo 'Pipeline completed'
    }

    success {
      echo '✓ Build and deployment successful!'
    }

    failure {
      echo '✗ Build or deployment failed. Check logs above for details.'
    }
  }
}
