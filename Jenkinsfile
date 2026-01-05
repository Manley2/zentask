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
    // Jenkins Credential ID untuk ACR (Username with password)
    ACR_CRED_ID = 'acr-admin-zentask'

    // Jenkins Secret Text credential IDs untuk Azure Service Principal
    AZ_TENANT_ID_CRED = 'acr-admin-zentask'
    AZ_CLIENT_ID_CRED = 'acr-admin-zentask'
    AZ_CLIENT_SECRET_CRED = 'acr-admin-zentask'

    // nama container sementara untuk test
    TEST_CONTAINER = 'zentask_test_container'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    stage('Prepare Environment') {
      steps {
        script {
          // 1) paling aman: pakai commit dari Jenkins
          def sha = (env.GIT_COMMIT ?: "").trim()
          if (sha.length() >= 7) {
            env.GIT_SHA = sha.substring(0, 7)
          } else {
            // 2) fallback: ambil dari git via PowerShell (lebih bersih dari bat di Windows)
            env.GIT_SHA = powershell(returnStdout: true, script: '(git rev-parse --short HEAD).Trim()').trim()
          }

          env.IMAGE_TAG = env.GIT_SHA
          env.FULL_IMAGE_SHA = "${params.ACR_LOGIN_SERVER}/${params.IMAGE_NAME}:${env.IMAGE_TAG}"
          env.FULL_IMAGE_LATEST = "${params.ACR_LOGIN_SERVER}/${params.IMAGE_NAME}:latest"
        }

        bat """
          echo Commit SHA: %GIT_SHA%
          echo Image (sha): %FULL_IMAGE_SHA%
          echo Image (latest): %FULL_IMAGE_LATEST%
          docker version
        """
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
        // Run container locally and check /health
        bat """
          docker rm -f %TEST_CONTAINER% 2>nul || exit /b 0
        """

        bat """
          docker run -d --name %TEST_CONTAINER% -p %APP_PORT_LOCAL%:80 ^
            -e APP_ENV=production ^
            -e APP_DEBUG=false ^
            "%FULL_IMAGE_SHA%"
        """

        powershell '''
          ./scripts/healthcheck.ps1 -Url ("http://localhost:{0}/health" -f $env:APP_PORT_LOCAL) -TimeoutSeconds 120
        '''
      }
    }

    stage('Login to ACR') {
      steps {
        withCredentials([usernamePassword(credentialsId: env.ACR_CRED_ID, usernameVariable: 'ACR_USER', passwordVariable: 'ACR_PASS')]) {
          bat """
            docker login ${params.ACR_LOGIN_SERVER} -u %ACR_USER% -p %ACR_PASS%
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
            echo "DEPLOY: stage entered"

            withCredentials([
            usernamePassword(credentialsId: env.ACR_CRED_ID, usernameVariable: 'ACR_USER', passwordVariable: 'ACR_PASS'),
            string(credentialsId: env.AZ_TENANT_ID_CRED, variable: 'AZ_TENANT_ID'),
            string(credentialsId: env.AZ_CLIENT_ID_CRED, variable: 'AZ_CLIENT_ID'),
            string(credentialsId: env.AZ_CLIENT_SECRET_CRED, variable: 'AZ_CLIENT_SECRET')
            ]) {
            echo "DEPLOY: credentials bound, running script..."

            powershell(label: 'Deploy WebApp', script: '''
                $ErrorActionPreference = "Stop"
                $ProgressPreference    = "SilentlyContinue"
                $VerbosePreference     = "Continue"
                $InformationPreference = "Continue"

                $scriptPath = Join-Path $env:WORKSPACE "scripts\\deploy-azure-webapp.ps1"
                if (!(Test-Path $scriptPath)) { throw "Deploy script not found: $scriptPath" }

                $logPath = Join-Path $env:WORKSPACE ("deploy-azure-webapp-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")
                Write-Host "Deploy log: $logPath"

                try {
                & $scriptPath `
                    -SubscriptionId $env:AZ_SUBSCRIPTION_ID `
                    -TenantId $env:AZ_TENANT_ID `
                    -ClientId $env:AZ_CLIENT_ID `
                    -ClientSecret $env:AZ_CLIENT_SECRET `
                    -ResourceGroup $env:AZ_RESOURCE_GROUP `
                    -WebAppName $env:AZ_WEBAPP_NAME `
                    -ImageName $env:FULL_IMAGE_SHA `
                    -RegistryServer $env:ACR_LOGIN_SERVER `
                    -RegistryUser $env:ACR_USER `
                    -RegistryPassword $env:ACR_PASS `
                    *>&1 | Tee-Object -FilePath $logPath

                if ($LASTEXITCODE -ne 0) { throw "Deploy script failed with exit code $LASTEXITCODE" }
                Write-Host "DEPLOY OK"
                } catch {
                Write-Host "DEPLOY FAILED: $($_.Exception.Message)"
                if (Test-Path $logPath) { Get-Content $logPath -Tail 200 }
                throw
                }
            ''')
            }

            echo "DEPLOY: stage finished"
        }
    }


    stage('Health Check') {
      steps {
        powershell '''
          $url = "https://{0}.azurewebsites.net/health" -f $env:AZ_WEBAPP_NAME
          ./scripts/healthcheck.ps1 -Url $url -TimeoutSeconds 180
        '''
      }
    }

    stage('Cleanup') {
      steps {
        powershell '''
          ./scripts/cleanup.ps1 -ContainerName $env:TEST_CONTAINER -ImageSha $env:FULL_IMAGE_SHA -ImageLatest $env:FULL_IMAGE_LATEST -IgnoreErrors
        '''
      }
    }
  }

  post {
    always {
      echo "Post Actions: ensure cleanup"
      powershell '''
        ./scripts/cleanup.ps1 -ContainerName $env:TEST_CONTAINER -ImageSha $env:FULL_IMAGE_SHA -ImageLatest $env:FULL_IMAGE_LATEST -IgnoreErrors
      '''
    }
  }
}
