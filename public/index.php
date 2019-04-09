<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';


require '../includes/DbOperations.php';

$app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

// //
//   endpoint: createuser
//   parameters: email, password, name, school
//   method: POST
// //
$app->post('/createuser', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('password', 'nama','jabatan','email'), $request,$response)){

        $request_data = $request->getParsedBody();

        $Password = $request_data['password'];
        $Nama = $request_data['nama'];
        $Jabatan = $request_data['jabatan'];
        $Email = $request_data['email'];

        $hash_password = password_hash($Password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $db->createuser($hash_password, $Nama, $Jabatan, $Email);

        if($result == USER_CREATED){

          $message = array();
          $message['error'] = false;
          $message['message'] = 'user created successfully';

          $response->write(json_encode($message));

          return $response
                      ->withHeader('Content-type', 'application/json')
                      ->withStatus(201);

        }else if($result == USER_FAILURE){

        $message = array();
        $message['error'] = true;
        $message['message'] = 'Some error occurred';
        $message['res'] = $result;

        $response->write(json_encode($message));

        return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);

      }else if($result == USER_EXISTS){
      $message = array();
      $message['error'] = true;
      $message['message'] = 'user Already Exists';

      $response->write(json_encode($message));

      return $response
                  ->withHeader('Content-type', 'application/json')
                  ->withStatus(422);

    }


  }

  return $response
              ->withHeader('Content-type', 'application/json')
              ->withStatus(422);


});

$app->post('/userlogin', function(Request $request, Response $response){

  if (!haveEmptyParameters(array('id','password'), $request,$response)){
    $request_data = $request->getParsedBody();

    $id = $request_data['id'];
    $password = $request_data['password'];

    $db = new DbOperations;

    $result = $db->userLogin($id, $password);

    if ($result == USER_AUTHENTICATED) {
      $user = $db->getUserByEmail($id);
      $response_data = array();

      $response_data['error']=false;
      $response_data['message'] = 'login successfully';
      $response_data['user']=$user;

      $response->write(json_encode($response_data));

      return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus(200);

      // code...
    }else if($result == USER_NOT_FOUND){
      $response_data = array();

      $response_data['error']=true;
      $response_data['message'] = 'Id Tidak Terdaftar';

      $response->write(json_encode($response_data));

      return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus(200);


    }else if($result == USER_PASSWORD_DO_NOT_MATCH){
      $response_data = array();

      $response_data['error']=true;
      $response_data['message'] = 'Password Salah';

      $response->write(json_encode($response_data));

      return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus(200);

    }


}
  return $response
  ->withHeader('Content-type', 'application/json')
  ->withStatus(422);

});

$app->get('/allusers', function(Request $request, Response $response){

  $db = new DbOperations;

  $users = $db->getAllusers();

  $response_data = array();

  $response_data['error'] = false;
  $response_data['users'] = $users;

  $response->write(json_encode($response_data));

  return $response
  ->withHeader('Content-type', 'application/json')
  ->withStatus(200);



});

function haveEmptyParameters($required_params,$request, $response){
  $error = false;
  $error_params = '';
  $request_params = $request->getParsedBody();

  foreach ($required_params as $param) {

    if (!isset($request_params[$param]) || strlen($request_params[$param])<=0) {

      $error = true;
      $error_params .= $param . ', ';

    }

  }

  if ($error) {

    $error_detail = array();
    $error_detail['error'] = true;
    $error_detail['message'] = 'Required parameters ' . substr($error_params, 0 ,-2) . ' are missing or empty';
    $response->write(json_encode($error_detail));

  }

  return $error;

}

$app->post('/insertabsen', function(Request $request, Response $response){


        $request_data = $request->getParsedBody();

        $ID_Karyawan = $request_data['ID_Karyawan'];
        $status = $request_data['status'];


        $db = new DbOperations;

        $result = $db->insertabsen($ID_Karyawan,$status);

        if($result == ABSEN_BERHASIL){

          $message = array();
          $message['error'] = false;
          $message['message'] = 'absen berhasil';

          $response->write(json_encode($message));

          return $response
                      ->withHeader('Content-type', 'application/json')
                      ->withStatus(201);

        }else {
      $message = array();
      $message['error'] = true;
      $message['message'] = 'absen  gagal';

      $response->write(json_encode($message));

      return $response
                  ->withHeader('Content-type', 'application/json')
                  ->withStatus(422);

    }


});

$app->get('/verf_absen/{id}', function(Request $request, Response $response,array $args){

  date_default_timezone_set("Asia/Jakarta");
  //jam masuk
  $batas_jam_telat = date('19:20:0');

  //jam closing x
  $batas_jam = date('19:45:00');
  $jam_sekarang = date('H:i:s');
  $message = array();
  $id = $args['id'];
  $cekabsen = new DBOperations;
  $ver_cek = $cekabsen->cekabsen($id);

  if(!$ver_cek){
    if($jam_sekarang > $batas_jam_telat){
      if ($jam_sekarang < $batas_jam) {
        $message['error'] = false;
        $message['status'] = 'telat';
        $message['pesan'] = "Anda Telat. Ini jam " . $jam_sekarang;
      }else{
        $message['error'] = true;
        $message['pesan'] = "Tidak Bisa Absen " . $jam_sekarang;

      }
    }else{
      $message['error'] = false;
      $message['status'] = 'Tepat Waktu';
      $message['pesan'] = "Absen";
    }
    // var_dump($ver_cek);
  }else{
    $message['error'] = true;

    $message['pesan'] = "Anda Sudah Absen Hari ini ";
  }

  $response->write(json_encode($message));

      return $response
                  ->withHeader('Content-type', 'application/json')
                  ->withStatus(422);



});

$app->get('/tampilAbsenById/{id}/{tgl}', function(Request $request, Response $response,array $args){

  $message = array();
  $id = $args['id'];
  $tgl = $args['tgl'];
  $tgl_real = date('Y-m-d',strtotime($tgl));
  $db = new DBOperations;
  $hasil = $db->tampilAbsenById($id,$tgl_real);
  if($hasil['jam']!=null){

    $message['error'] = false;
  $message['data'] = $hasil;

  }else{
    $message['data'] = "no data";
    $message['error'] = true;
  }
  $response->write(json_encode($message));

      return $response
                  ->withHeader('Content-type', 'application/json')
                  ->withStatus(422);



});

$app->post('/updateprofile', function(Request $request, Response $response){

  $request_data = $request->getParsedBody();
  $nama = $request_data['nama'];
  $email = $request_data['email'];
  $id = $request_data['id'];
  $db = new DbOperations;
  $hasil = $db->update_profile($id,$nama,$email);
  $message =array();
  if ($hasil==301) {
    $message['error'] = false;
    $message['pesan'] = "Berhasil Update Data";
  }else{
    $message['error'] = true;
    $message['pesan'] = "Gagal Update Data";
  }

  $response->write(json_encode($message));

      return $response
                  ->withHeader('Content-type', 'application/json')
                  ->withStatus(422);
});

//update password
$app->put('/updatepassword', function(Request $request, Response $response){


  if (!haveEmptyParameters(array('current_password','new_password','id'),$request,$response)) {

    $request_data = $request->getParsedBody();

    $current_password = $request_data['current_password'];
    $new_password = $request_data['new_password'];
    $id = $request_data['id'];

    $db = new DbOperations;

    $result= $db->updatePassword($current_password,$new_password,$id);
    if ($result == PASSWORD_CHANGED) {

      $response_data = array();
      $response_data['error'] = false;
      $response_data['message'] = 'Password Telah Diubah';
      $response->write(json_encode($response_data));
      return $response->withHeader('Content-type','application/json')
                      ->withStatus(200);

    }elseif ($result == PASSWORD_DO_NOT_MATCH) {
      $response_data = array();
      $response_data['error'] = true;
      $response_data['message'] = 'Password Salah';
      $response->write(json_encode($response_data));
      return $response->withHeader('Content-type','application/json')
                      ->withStatus(200);
    }elseif ($result == PASSWORD_NOT_CHANGED) {

      $response_data = array();
      $response_data['error'] = ture;
      $response_data['message'] = 'Telah Terjadi Kesalahan';
      $response->write(json_encode($response_data));
      return $response->withHeader('Content-type','application/json')
                      ->withStatus(200);
    }

  }

  return $response->withHeader('Content-type','application/json')
                  ->withStatus(422);

});

$app->POST('/ceklokasi', function(Request $request, Response $response){

  $request_data = $request->getParsedBody();
  $lokasi1_lat  = $request_data['lokasi1_lat'];
  $lokasi1_long = $request_data['lokasi1_long'];
  $lokasi2_lat  = $request_data['lokasi2_lat'];
  $lokasi2_long = $request_data['lokasi2_long'];
  $unit = 'km';
  $desimal = 4;

  $derajat = rad2deg(acos((sin(deg2rad($lokasi1_lat))*sin(deg2rad($lokasi2_lat))) + (cos(deg2rad($lokasi1_lat))*cos(deg2rad($lokasi2_lat))*cos(deg2rad($lokasi1_long-$lokasi2_long)))));
  $jarak = $derajat * 111.13384; // 1 derajat = 111.13384 km, berdasarkan diameter rata-rata bumi (12,735 km)
  //pembulatanya
  $pembulatan_jarak = round($jarak, $desimal);

  if ($jarak <= 1) {
    $message['error'] = false;
    $message['jarak'] = $pembulatan_jarak . " Km";
    $response->write(json_encode($message));

        return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
  }else{
    $message['error'] = true;
    $message['jarak'] = $pembulatan_jarak . " Km";
    $response->write(json_encode($message));

        return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
  }



});

$app->run();
