pipeline {
    agent any
    stages {
        stage('Clone') {
            steps {
                echo 'Cloning repository...'
                git branch: 'main', url: 'https://github.com/YOUR_USERNAME/student-management-system.git'
            }
        }
        stage('Build') {
            steps {
                echo 'Building...'
            }
        }
        stage('Test') {
            steps {
                echo 'Testing...'
            }
        }
        stage('Deploy') {
            steps {
                echo 'Deploying...'
            }
        }
    }
    post {
        success {
            echo '✅ Success!'
        }
        failure {
            echo '❌ Failed!'
        }
    }
}
