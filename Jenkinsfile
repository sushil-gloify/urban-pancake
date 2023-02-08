pipeline{

agent any

stages
{
    
// clearing memory of workspace 

stage('Clear Workspace'){
steps{
sh "rm -rf *"
    }
}

// Github code pull

stage('CheckOUtCode')
{
steps{
    
git branch: 'to_qa_first_update_07_04_22', credentialsId: '71d6587e-42f3-407d-b6dc-050714994a00', poll: false, url: 'git@github.com:gloinvent/carterporter-hyd-carterx.git'
     }
}

//deploying the code in server

stage('CodeDeployment')
{
    
steps{
sshagent(['77724be1-ca89-4abc-afb7-6c01d27ad9db']) 
{
    sh "scp -r -o StrictHostKeyChecking=no /var/lib/jenkins/workspace/carterporter-qa/basic/* root@13.126.56.232:/var/www/test/"
      
    
     }
    }
   }
 }
 
}
