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

          env.ACR_LOGIN_SERVER = params.ACR_LOGIN_SERVER
          env.IMAGE_NAME       = params.IMAGE_NAME
          env.AZ_RESOURCE_GROUP  = params.AZ_RESOURCE_GROUP
          env.AZ_WEBAPP_NAME     = params.AZ_WEBAPP_NAME
          env.AZ_SUBSCRIPTION_ID = params.AZ_SUBSCRIPTION_ID

          env.FULL_IMAGE_SHA    = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:${env.GIT_COMMIT_SHORT}"
          env.FULL_IMAGE_LATEST = "${env.ACR_LOGIN_SERVER}/${env.IMAGE_NAME}:latest"

          echo "Commit: ${env.GIT_COMMIT_SHORT}"
          echo "Image SHA: ${env.FULL_IMAGE_SHA}"
        }
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
            docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
            docker login $env:ACR_LOGIN_SERVER -u $env:ACR_USER -p $env:ACR_PASS
          '''
        }
      }
    }

    stage('Push to ACR') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"
          Write-Host "Pushing $env:FULL_IMAGE_SHA"
          docker push "$env:FULL_IMAGE_SHA"
          Write-Host "Pushing $env:FULL_IMAGE_LATEST"
          docker push "$env:FULL_IMAGE_LATEST"
        '''
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

            Write-Host "AZ CLI version:"
            az --version

            Write-Host "Login Azure (service principal)..."
            az login --service-principal `
              -u $env:AZ_CLIENT_ID `
              -p $env:AZ_CLIENT_SECRET `
              --tenant $env:AZ_TENANT_ID | Out-Null

            Write-Host "Set subscription: $env:AZ_SUBSCRIPTION_ID"
            az account set --subscription $env:AZ_SUBSCRIPTION_ID

            Write-Host "Set container config..."
            Write-Host " - webapp=$env:AZ_WEBAPP_NAME rg=$env:AZ_RESOURCE_GROUP"
            Write-Host " - image=$env:FULL_IMAGE_SHA"
            Write-Host " - registry=https://$env:ACR_LOGIN_SERVER"

            az webapp config container set `
              --name $env:AZ_WEBAPP_NAME `
              --resource-group $env:AZ_RESOURCE_GROUP `
              --docker-custom-image-name $env:FULL_IMAGE_SHA `
              --docker-registry-server-url ("https://" + $env:ACR_LOGIN_SERVER) `
              --docker-registry-server-user $env:ACR_USER `
              --docker-registry-server-password $env:ACR_PASS | Out-Null

            # WAJIB: set port 80 agar reverse-proxy Azure benar
            az webapp config appsettings set `
              --name $env:AZ_WEBAPP_NAME `
              --resource-group $env:AZ_RESOURCE_GROUP `
              --settings WEBSITES_PORT=80 | Out-Null

            Write-Host "Restart webapp..."
            az webapp restart --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP | Out-Null

            Write-Host "Show container config (verify):"
            az webapp config container show `
              --name $env:AZ_WEBAPP_NAME `
              --resource-group $env:AZ_RESOURCE_GROUP `
              --query "{image:dockerCustomImageName, server:dockerRegistryServerUrl}" -o json

            Write-Host "Deploy OK"
          '''
        }
      }
    }

    stage('Health Check') {
      steps {
        powershell '''
          $ErrorActionPreference = "Stop"
          Start-Sleep -Seconds 30

          $url = "https://{0}.azurewebsites.net/" -f $env:AZ_WEBAPP_NAME
          Write-Host "Checking: $url"

          try {
            Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30 | Out-Null
            Write-Host "Health OK"
          } catch {
            Write-Host "Health FAILED. Tailing Azure logs..."
            try {
              az webapp log tail --name $env:AZ_WEBAPP_NAME --resource-group $env:AZ_RESOURCE_GROUP
            } catch {
              Write-Host "Cannot tail logs (az not logged in or permission issue)."
            }
            throw
          }
        '''
      }
    }
  }

  post {
    always {
      // jangan sampai cleanup bikin build gagal
      powershell '''
        $ErrorActionPreference="Continue"
        docker logout $env:ACR_LOGIN_SERVER 2>$null | Out-Null
      '''
    }
  }
}
