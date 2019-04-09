<?php

    class DbOperations{

        private $con;

        function __construct(){
            require_once dirname(__FILE__) . '/DbConnect.php';
            $db = new DbConnect;
            $this->con = $db->connect();
        }

        public function createuser($Password, $Nama, $Jabatan, $Email){
            if(!$this->isemailExist($Email)){
            $stmt = $this->con->prepare("INSERT INTO tb_pengajar (Password, Nama, Jabatan, Email ) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $Password, $Nama, $Jabatan, $Email);

            if($stmt->execute()){

              return USER_CREATED;

            }else{

              return USER_FAILURE;

            }

          }
          return USER_EXIST;
        }

        public function userLogin($ID, $Password){

          if ($this->isemailExist($ID)){

              $hashed_password = $this->getUserPasswordByEmail($ID);

              if(password_verify($Password, $hashed_password)){

                  return USER_AUTHENTICATED;

                }else{

                  return USER_PASSWORD_DO_NOT_MATCH;

                }

          }else {

            return USER_NOT_FOUND;

          }

      }

        private function getUserPasswordByEmail($id){
          $stmt = $this->con->prepare("SELECT password FROM tb_pengajar WHERE ID = ?");
          $stmt ->bind_param("s", $id);
          $stmt ->execute();
          $stmt ->bind_result($password);
          $stmt ->fetch();
          return $password;

        }

        public function getAllusers(){
          $stmt = $this->con->prepare("SELECT id,email, name,school FROM tb_user;");
          $stmt ->execute();
          $stmt ->bind_result($id, $email, $name, $school);
          $users = array();
          while ($stmt ->fetch()){
            $user = array();
            $user['id'] = $id;
            $user['email']= $email;
            $user['name'] = $name;
            $user['school'] = $school;
            array_push($users, $user);

        }
        return $users;
    }

          public function getUserByEmail($id){
          $stmt = $this->con->prepare("SELECT ID, Nama, status, Email FROM tb_pengajar WHERE ID = ?");
          $stmt ->bind_param("s",$id);
          $stmt ->execute();
          $stmt ->bind_result($id, $Nama, $status, $email);
          $stmt ->fetch();
          $user = array();
          $user['id'] = $id;
          $user['nama']= $Nama;
          $user['status'] = $status;
          $user['email'] = $email;
          return $user;


        }


          private function isemailExist($id){
              $stmt = $this->con->prepare("SELECT * FROM tb_pengajar WHERE status='Pengajar' AND ID = ?");
              $stmt->bind_param("s", $id);
              $stmt->execute();
              $stmt->store_result();
              return $stmt->num_rows > 0 ;
        }

    //     private function isemailExist($id){
    //       $stmt = $this->con->prepare("SELECT * FROM tb_pengajar WHERE ID = ?");
    //       $stmt->bind_param("s", $id);
    //       $stmt->execute();
    //       $stmt->store_result();
    //       return $stmt->num_rows > 0 ;
    // }

        public function insertabsen($ID_Karyawan,$status){
            $stmt = $this->con->prepare("INSERT INTO tb_absenharian (ID_Karyawan,status) VALUES (?,?)");
            $stmt->bind_param("ss", $ID_Karyawan,$status);

            if($stmt->execute()){

              return ABSEN_BERHASIL;

            }else{

              return ABSEN_GAGAL;

            }
        }

        public function cekabsen($id){
            $stmt = $this->con->prepare("SELECT * FROM tb_absenharian WHERE ID_Karyawan=
            ? AND date(jam) = CURRENT_DATE()");
            $stmt->bind_param('s',$id);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows > 0;
        }

        public function tampilAbsenById($id,$tgl){
          $stmt = $this->con->prepare("SELECT jam,status FROM tb_absenharian WHERE ID_Karyawan=? AND jam LIKE ('$tgl%')");
          $stmt ->bind_param("s",$id);
          $stmt ->execute();
          $stmt ->bind_result($jam, $status);
          $stmt ->fetch();
          $user = array();
          $user['jam'] = $jam;
          $user['status']= $status;
          return $user;


        }

        public function update_profile($id,$nama,$email){
      
          $stmt = $this->con->prepare("UPDATE tb_pengajar SET Nama=?,Email=? WHERE ID=?");
          $stmt ->bind_param("sss",$nama,$email,$id);
          if($stmt->execute()){
            return 301;
          }else{
            return 302;
          }

        }

        public function updatePassword($current_password, $new_password, $id){

          $hashed_password = $this->getUsersPasswordByUsername($id);
          if(password_verify($current_password,$hashed_password)){
    
          $hash_password = password_hash($new_password, PASSWORD_DEFAULT);
          $stmt = $this->con->prepare("UPDATE tb_pengajar SET Password = ? WHERE ID = ?");
          $stmt->bind_param("ss",$hash_password,$id);
    
            if ($stmt->execute()) {
              return PASSWORD_CHANGED;
            }else {
              return PASSWORD_NOT_CHANGED;
            }
    
          }else{
    
            return PASSWORD_DO_NOT_MATCH;
    
            }
    
        }
        private function getUsersPasswordByUsername($id){

          $stmt = $this->con->prepare("SELECT Password FROM tb_pengajar WHERE ID = ?");
          $stmt->bind_param("s", $id);
          $stmt->execute();
          $stmt->bind_result($Password);
          $stmt->fetch();
          return $Password;
    
        }
    }
