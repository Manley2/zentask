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
    ACR_CRED_ID = 'acr-zentask-admin'

    // Jenkins Secret Text credential IDs untuk Azure Service Principal
    // (buat di Jenkins: Manage Credentials -> Secret text)
    AZ_TENANT_ID_CRED = 'azure-tenant-id'
    AZ_CLIENT_ID_CRED = 'azure-client-id'
    AZ_CLIENT_SECRET_CRED = 'azure-client-secret'

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
          env.GIT_SHA = bat(returnStdout: true, script: "git rev-parse --short HEAD").trim()
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
          docker build -t %FULL_IMAGE_SHA% -t %FULL_IMAGE_LATEST% .
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
            %FULL_IMAGE_SHA%
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
            docker login %ACR_LOGIN_SERVER% -u %ACR_USER% -p %ACR_PASS%
          """
        }
      }
    }

    stage('Push to ACR') {
      steps {
        bat """
          docker push %FULL_IMAGE_SHA%
          docker push %FULL_IMAGE_LATEST%
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
            ./scripts/deploy-azure-webapp.ps1 `
              -SubscriptionId $env:AZ_SUBSCRIPTION_ID `
              -TenantId $env:AZ_TENANT_ID `
              -ClientId $env:AZ_CLIENT_ID `
              -ClientSecret $env:AZ_CLIENT_SECRET `
              -ResourceGroup $env:AZ_RESOURCE_GROUP `
              -WebAppName $env:AZ_WEBAPP_NAME `
              -ImageName $env:FULL_IMAGE_SHA `
              -RegistryServer $env:ACR_LOGIN_SERVER `
              -RegistryUser $env:ACR_USER `
              -RegistryPassword $env:ACR_PASS
          '''
        }
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
          ./scripts/cleanup.ps1 -ContainerName $env:TEST_CONTAINER -ImageSha $env:FULL_IMAGE_SHA -ImageLatest $env:FULL_IMAGE_LATEST
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
