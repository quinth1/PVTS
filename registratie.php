<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <?php include_once("php/header.php") ?>
  </head>
  <body>
    <?php
     session_start();
     $_SESSION['msg'] = '';
     echo "test";
     require 'php/db.php';
     $con = mysqli_connect($host, $user, $pass, $db);
     if(!$con) {
       throw new Exception ('Could not connect: ' . mysqli_error());
     }else{
     if ($_SERVER['REQUEST_METHOD']=='POST') {
echo "test2";
     if ($_POST['psw'] == $_POST['psw_repeat']){
      $nickname = $con->real_escape_string($_POST['nickname']);
      $email = $con->real_escape_string($_POST['email']);
      $voornaam = $con->real_escape_string($_POST['voornaam']);
      $achternaam = $con->real_escape_string($_POST['achternaam']);
      $pswhashed = password_hash(($con->real_escape_string($_POST['psw'])),PASSWORD_DEFAULT);

echo "test3";


      $statement = mysqli_prepare ($con, ("INSERT INTO users (Email ,Password,LastName,FirstName,Nickname) VALUES (?,?,?,?,?)"));
      mysqli_stmt_bind_param($statement, "sssss", $email,$pswhashed,$achternaam,$voornaam,$nickname);


     if(mysqli_stmt_execute($statement) == true) {
       $_SESSION['message'] = "Registration is succesfull. Added $nickname to the database.";
       echo $_SESSION['message'];
          }
     else {
       $_SESSION['message'] = 'User could not be added to the database.';
       echo $_SESSION['message'];
     }

     }
     else {
       $_SESSION['message'] = 'The two passwords do not match!';
     }
     }
     }

     ?>

<!-- <script src = "js/RegistratiePaginaFuncties.js"></script>
-->
<div class="registratie_pagina">

     <div class="body__registratie">
        <form action="registratie.php" method="post" id="DOM__regform">

        <h1>Registratie</h1>
        <p>Vul deze lijst in om een account aan te maken.</p>
        <div class="body__black__line">
          <hr>
        </div>

      <label for="nickname"><b>Nickname</b></label>
      <input type="text" placeholder="nickname" name="nickname" id ="nickname" required>

      <label for="email"><b>Email</b></label>
      <input type="email" placeholder="Email" name="email" id="email" required>

      <label for="voornaam"><b>Voornaam</b></label>
      <input type="text" placeholder="Quinten" name="voornaam" id="voornaam" required>


      <label for="achternaam"><b>Achternaam</b></label>
      <input type="text" placeholder="Euh" name="achternaam" id="achternaam" required>


      <label for="psw"><b>password</b></label>
      <input type="password" placeholder="paswoord" name="psw" id="psw" required>

      <label for="psw_repeat"><b>Repeat Password</b></label>
      <input type="password" placeholder="herhaal paswoord" name="psw_repeat" id="psw_repeat" required>

      <p>Door een account te maken gaat u akkoord met de Terms Of Service.</p>

     <div class="registratie__btn">
      <input type="submit" value="Registreren" name="registratie__btn">
    </div>

    <div class="">

     <p>Heeft u al een account? <a href="index.php">Log in</a></p>
     <div class="body__black__line">
       <hr>
     </div>
    </div>

    </form>
    </div>

    </div>

  </body>
</html>