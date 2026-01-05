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
    string(name: 'APP_PORT_LOCAL', defaultValue: '8085', description: 'Port lokal untuk test container')
  }

  environment {
    // Jenkins Credential IDs
    ACR_CRED_ID = 'acr-admin-zentask'
    AZ_TENANT_ID_CRED     = 'azure-tenant-id'
    AZ_CLIENT_ID_CRED     = 'azure-client-id'
    AZ_CLIENT_SECRET_CRED = 'azure-client-secret'

    // gunakan nama unik per build supaya tidak bentrok
    TEST_CONTAINER = "zentask_test_${BUILD_NUMBER}"
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
            // 1) Cleanup container lama
            bat '''
            @echo on
            docker rm -f %TEST_CONTAINER% >NUL 2>NUL
            echo [test] cleaned old container (if any): %TEST_CONTAINER%
            '''

            // 2) Run container dengan credentials
            script {
                withCredentials([string(credentialsId: 'laravel-app-key', variable: 'APP_KEY')]) {
                    // Gunakan PowerShell untuk handling environment variable yang lebih baik
                    powershell """
                    \$ErrorActionPreference = "Stop"

                    Write-Host "[test] Starting container: \$env:TEST_CONTAINER on port \$env:APP_PORT_LOCAL"

                    docker run -d ``
                    --name \$env:TEST_CONTAINER ``
                    -p "\$env:APP_PORT_LOCAL`:80" ``
                    -e APP_ENV=production ``
                    -e APP_DEBUG=false ``
                    -e APP_KEY=\$env:APP_KEY ``
                    -e APP_URL=http://localhost:\$env:APP_PORT_LOCAL ``
                    -e LOG_CHANNEL=stderr ``
                    \$env:FULL_IMAGE_SHA

                    if (\$LASTEXITCODE -ne 0) {
                        throw "Docker run failed with exit code \$LASTEXITCODE"
                    }

                    Write-Host "[test] Container started successfully"

                    # Wait a bit for container to initialize
                    Start-Sleep -Seconds 5

                    # Check if container is still running
                    \$running = docker ps --filter "name=\$env:TEST_CONTAINER" --format "{{.Names}}"
                    if (-not \$running) {
                        Write-Host "[test] ERROR: Container stopped unexpectedly"
                        docker logs \$env:TEST_CONTAINER
                        throw "Container failed to stay running"
                    }
                    """
                }
            }

            // 3) Healthcheck
            powershell '''
            $ErrorActionPreference = "Continue"

            $url = ("http://localhost:{0}/" -f $env:APP_PORT_LOCAL)
            Write-Host "[healthcheck] Checking $url"

            try {
                ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 180
                Write-Host "[healthcheck] OK"
            }
            catch {
                Write-Host "[healthcheck] FAILED: $($_.Exception.Message)"
                Write-Host "---- docker ps ----"
                docker ps --filter "name=$env:TEST_CONTAINER"
                Write-Host "---- docker logs (tail 200) ----"
                docker logs --tail 200 $env:TEST_CONTAINER
                Write-Host "---- laravel logs (tail 200) ----"
                docker exec $env:TEST_CONTAINER sh -lc "tail -n 200 /var/www/storage/logs/laravel*.log 2>/dev/null || true"
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
          docker push "%FULL_IMAGE_SHA%"
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
        powershell '''
          $ErrorActionPreference = "Stop"
          $url = "https://{0}.azurewebsites.net/" -f $env:AZ_WEBAPP_NAME
          Write-Host "[healthcheck] Checking $url"
          ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 240
        '''
      }
    }
  }

  post {
    always {
      echo 'Post Actions: cleanup test container + archive deploy logs'
      archiveArtifacts artifacts: 'deploy-azure-webapp-*.log', allowEmptyArchive: true

      // tampilkan logs test container kalau stage test gagal (membantu debug 500)
      powershell '''
        $ErrorActionPreference = "Continue"
        if ($env:TEST_CONTAINER) {
          Write-Host "[post] docker logs (tail 200) for $env:TEST_CONTAINER"
          docker logs --tail 200 $env:TEST_CONTAINER 2>$null
        }
      '''

      // cleanup container test
      bat """
        docker rm -f %TEST_CONTAINER% >NUL 2>NUL
        echo [post] cleaned test container: %TEST_CONTAINER%
      """
    }
  }
}
