<?php
require 'classes.php';
require 'db.php';
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["homeMenu"])) {
  session_start();
  // json aanmaken
  $outputString = "";
  $data = new jsonData(0, "");
  if (!isset($_SESSION["UserID"])) {
    header("Location: index.php");
    exit;
  }
  $userID = $_SESSION["UserID"];
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

  $groups = array();
  $statement = mysqli_prepare($con, "SELECT g.GroupID, g.GrName, g.GrDescription, g.GrOwner FROM groups g INNER JOIN UserGroups ug ON g.GroupID = ug.GroupID WHERE ug.UserID = ?");
  mysqli_stmt_bind_param($statement, "i", $userID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
          $group = new Group($row["GroupID"], $row["GrName"], $row["GrDescription"], $row["GrOwner"]);
          array_push($groups, $group);
      }
  }
  $result->close();
  $invites = array();
  $statement = mysqli_prepare($con, "SELECT i.InviteID, i.SenderID, i.ReceiverID, concat(us.FirstName, ' ',us.LastName) 'sName', concat(ur.FirstName,' ', ur.LastName) 'rName', i.GroupID, g.GrName FROM invites AS i
    INNER JOIN users AS us ON i.SenderID = us.userID
    INNER JOIN users AS ur ON i.ReceiverID = ur.UserID
    INNER JOIN groups g ON i.GroupID = g.GroupID
    WHERE i.ReceiverID = ? AND i.Answer = '0'");
  mysqli_stmt_bind_param($statement, "i", $userID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
          $invite = new Invite($row["InviteID"], $row["SenderID"], $row["ReceiverID"], $row["sName"], $row["rName"], $row["GroupID"], $row["GrName"]);
          array_push($invites, $invite);
      }
  }
  $result->close();
  // Body voor groepen
  if(!isset($_POST["homeSidebar"])) {
    $outputString .= ("
        <div class=\"body__home--home\">
        <div class=\"body__home--groups body__home--boxes\">
        <div class=\"body__home--title\">
            <h2>Mijn groepen</h2>
        </div>
        <div class=\"item__group--row\">");
          foreach ($groups as $group) {
            $outputString .= ("
            <a onclick=\"courses($group->GrID)\" class=\"group__link\">
              <div class=\"group__link--content\">
                <div class=\"group__link--title\">
                  <h3>$group->GrName</h3>
                 </div>
                <div>
                  <p>$group->GrDescr</p>
                </div>
              </div>
            </a>");
          }
    $outputString .= ("
        <a id=\"dom__btn--newgroup\" class=\"group__link\">
          <div class=\"group__link--symbol\">
            <p>&#43;</p>
          </div>
        </a>
      </div>
      </div>
  <div class=\"body__home--sidebar body__home--boxes\">
      <div class=\"body__home--title\">
          <h2>Meldingen</h2>
      </div>
      <div class=\"item__group--coloum\">");
        foreach ($invites as $invite) {
          $outputString .= ("
            <div class=\"item__group--invite\">
              <div>
                <h3>Uitnoding voor $invite->GroupName</h3>
                <p>Je hebt een uitnoding ontvangen van $invite->SenderName voor de groep $invite->GroupName</p>
              </div>
              <div class=\"invites__btn--response\">
                <button class=\"btn__border--green\" onclick=\"acceptInvite($invite->InvID)\">&radic;</button>
                <button class=\"btn__border--red\" onclick=\"declineInvite($invite->InvID)\">x</button>
              </div>
            </div>
          ");
        }
  $outputString .= ("</div></div></div><script src=\"js/modalsHome.js\"></script>");
  $data->output = $outputString;
  echo json_encode($data);
  }else{
    foreach ($groups as $group) {
      $outputString .= ("<li><a onclick=\"courses($group->GrID);\">$group->GrName</a></li>");
    }
    $data->output = $outputString;
    echo json_encode($data);
  }
}

//Uitnoding / invite accepteren

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["acceptInvite"]) && isset($_POST["inviteID"])) {
    //Invite gegevens ophalen
    // Nakijken of invite van juiste gebruiker is en nog niet beantwoord
    session_start();
    // json aanmaken
    $data = new jsonData(0, "");
    if (!isset($_SESSION["UserID"])) {
      header("Location: index.php");
    }
    $userID = $_SESSION["UserID"];
    if(!$con = mysqli_connect($host, $user, $pass, $db)) {
      $data->returnCode = 402;
      echo json_encode($data);
      exit;
    }
    $statement = mysqli_prepare($con, "SELECT GroupID FROM invites i WHERE i.ReceiverID = ? AND i.InviteID = ?;");
    mysqli_stmt_bind_param($statement, "ii", $userID, $_POST["inviteID"]);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
    $result = $statement->get_result();
    if(mysqli_num_rows($result) == 1) {
        while($row = mysqli_fetch_assoc($result)) {
            $groupID = $row["GroupID"];
        }
        $statement = mysqli_prepare($con, "UPDATE invites i SET i.Answer = '1' WHERE i.InviteID = ? AND i.ReceiverID = ? AND i.Answer = '0';");
        mysqli_stmt_bind_param($statement, "ii", $_POST["inviteID"], $userID);
        if(!mysqli_stmt_execute($statement)) {
          $data->returnCode = 401;
          echo json_encode($data);
          exit;
        }
        if($statement->affected_rows == 1) {
          $statement = mysqli_prepare($con, "INSERT INTO UserGroups(GroupID, UserID, UserRank) VALUES (?, ?, 3);");
          mysqli_stmt_bind_param($statement, "ii", $groupID , $userID);
          mysqli_stmt_execute($statement);
          if($statement->affected_rows == 1) {
              $data->returnCode = 0;
              echo json_encode($data);
          }
        }else{
          $data->returnCode = 501;
          echo json_encode($data);
          exit;
        }
    }else{
      $data->returnCode = 502;
      echo json_encode($data);
      exit;
    }
}

//Uitnoding / invite weigeren

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["declineInvite"]) && isset($_POST["inviteID"])) {
    //Invite gegevens ophalen
    // Nakijken of invite van juiste gebruiker is en nog niet beantwoord
    session_start();
    $data = new jsonData(0, "");
    if (!isset($_SESSION["UserID"])) {
      header("Location: index.php");
    }
    $userID = $_SESSION["UserID"];
    if(!$con = mysqli_connect($host, $user, $pass, $db)) {
      $data->returnCode = 402;
      echo json_encode($data);
      exit;
    }
    $statement = mysqli_prepare($con, "SELECT GroupID FROM invites i WHERE i.ReceiverID = ? AND i.InviteID = ?;");
    mysqli_stmt_bind_param($statement, "ii", $userID, $_POST["inviteID"]);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
    $result = $statement->get_result();
    if(mysqli_num_rows($result) == 1) {
        while($row = mysqli_fetch_assoc($result)) {
            $groupID = $row["GroupID"];
        }
        $statement = mysqli_prepare($con, "UPDATE invites i SET i.Answer = '2' WHERE i.InviteID = ? AND i.ReceiverID = ? AND i.Answer = '0';");
        mysqli_stmt_bind_param($statement, "ii", $_POST["inviteID"], $userID);
        if(!mysqli_stmt_execute($statement)) {
          $data->returnCode = 401;
          echo json_encode($data);
          exit;
        }
        if($statement->affected_rows == 1) {
        }else{
          $data->returnCode = 501;
          echo json_encode($data);
          exit;
        }
    }else{
      $data->returnCode = 502;
      echo json_encode($data);
      exit;
    }
}
//
// Group inladen
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["group"]) && isset($_POST["groupID"])) {
  session_start();
  $outputString = "";
  $data = new jsonData(0, "");
  if (!isset($_SESSION["UserID"])) {
    header("Location: index.php");
  }
  $userID = $_SESSION["UserID"];
  $_SESSION["GroupID"] = $_POST["groupID"];
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  $courses = array();
  //Nakijken of gebruiker tot groep behoort en group naam ophalen
  $statement = mysqli_prepare($con, "SELECT g.GrName FROM UserGroups us INNER JOIN groups g ON g.GroupID = us.GroupID WHERE us.UserID = ? AND us.GroupID = ?;");
  mysqli_stmt_bind_param($statement, "ii",  $userID,  $_POST["groupID"]);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
  if(mysqli_num_rows($result) != 1) {
    $data->returnCode = 700;
    echo json_encode($data);
    exit;
  }else{
    while($row = mysqli_fetch_assoc($result)) {
        $groupName = $row["GrName"];
    }
  }

  $statement = mysqli_prepare($con, "SELECT c.CourseID, c.CrName, c.CrDescription, g.GrName, c.GroupID FROM courses c INNER JOIN groups g on g.GroupID = c.GroupID INNER JOIN UserGroups us ON us.GroupID = g.GroupID WHERE c.GroupID = ? AND us.UserID = ?;");
  mysqli_stmt_bind_param($statement, "ii", $_POST["groupID"], $userID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }

  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
          $course = new Course($row["CourseID"], $row["CrName"], $row["CrDescription"], $row["GrName"], $row["GroupID"]);
          array_push($courses, $course);
      }
  }
  $result->close();
    $outputString .= ("
    <div class=\"body__home--home\">
      <div class=\"body__home--courses body__home--boxes\" id=\"groups-mainbox\">
        <div class=\"body__home--title\">
          <h2>$groupName</h2>
        </div>
    <div class=\"item__group--row\">");
    if(count($courses) > 0 ) {
      foreach ($courses as $course) {
        $outputString .= ("
        <a onclick=\"course($course->crID)\" class=\"group__link\">
          <div class=\"group__link--content\">
            <div class=\"group__link--title\">
              <h3>$course->crName</h3>
            </div>
            <div>
              <p>$course->crDescr</p>
            </div>
          </div>
        </a>");
      };
  }
$outputString .= (" <a id=\"dom__btn--newCourse\" class=\"group__link\">
        <div class=\"group__link--symbol\">
          <p>&#43;</p>
        </div>
      </a>
</div></div><div class=\"body__home--sidebar body__home--boxes\">
        <div class=\"body__home--title\">
            <h2>Acties</h2>
        </div>
        <div class=\"item__group--coloum\">
          <div class=\"groups__controls\">
              <button type=\"button\" id=\"dom__btn--members\">Leden lijst</button>
              <button type=\"button\" id=\"dom__btn--inviteUser\">Gebruiker toevoegen</button>
              <button type=\"button\" id=\"dom__btn--kickUser\">Gebruiker verwijderen</button>
              <button type=\"button\" id=\"dom__btn--leaveGroup\">Groep verlaten</button>
              <button type=\"button\" id=\"dom__btn--deleteGroup\">Groep verwijderen</button>
          </div>
        </div>
  </div>

  <div id=\"DOM__livechat__body--main\" class=\"body__home--sidebar body__home--boxes livechat__body--main\">

    <div id=\"DOM__livechat__title\" class=\"body__home--title livechat__title\">
      <a id=\"DOM__livechat__title--\" class=\"livechat__title--anchor\" onclick=\"openchat();\" >Live-Chat</a>
    </div>

    <div id=\"DOM__livechat__body\" class=\"livechat__body\">
      <div id=\"DOM__livechatmessages\" class=\"livechat__body--messages\">

      </div>

      <form id=\"DOM__livechat__form\">
          <div  class=\"livechat__body--input\">
          <div class=\"livechat__body--input\">
               <textarea id=\"DOM__livechat__text\" maxlength=\"256\" class=\"livechat__textinput\" required></textarea>
          </div>
          <div class=\"livechat__body--sendbutton\">
            <input type=\"submit\" id=\"DOM__livechat__button\" name=\"livechat_btn\" value=\"Verzenden\" class=\"livechat__submitbtn\">
          </div>
         </form>
         <div class=\"livechat__body--closechat\">
           <input type=\"button\" id=\"DOM__livechat__close\" name=\"livechat_closebtn\" value=\"Sluiten\" onclick=\"closechat()\" class=\"livechat__submitbtn\">
         </div>
         <div class=\"livechat__body--closechat\">
           <input type=\"button\" id=\"DOM__livechat__autoscroll\" name=\"livechat_closebtn\" value=\"Autoscroll\" class=\"livechat__submitbtn\">
         </div>
    </div>
  </div>
  <script src=\"js/livechatscripts.js\"></script>
  <script> $(document).ready(function(){ $.getScript(\"js/modalCourses.js\")}); </script>
</div></div>");
  $data->output = $outputString;
  echo json_encode($data);
  exit;
}
//
// Nieuwe groep aanmaken
//

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["grName"]) && isset($_POST["grDescription"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  $userID = $_SESSION["UserID"];
  $grName = $con->reaL_escape_string($_POST["grName"]);
  $grDescription = $con->real_escape_string($_POST["grDescription"]);

  $statement = mysqli_prepare($con, "INSERT INTO groups(`GrName`,`GrDescription`,`GrOwner`) VALUES(?,?,?);");
  mysqli_stmt_bind_param($statement, "ssi", $grName, $grDescription, $userID);
  if(mysqli_stmt_execute($statement)) {
    $newGroupId = $con->insert_id;
    $statement = mysqli_prepare($con, "INSERT INTO UserGroups(GroupID, UserID, UserRank) VALUES (?, ?, 1);");
    mysqli_stmt_bind_param($statement, "ii", $newGroupId , $userID);
    if(mysqli_stmt_execute($statement)) {
      if (!file_exists("../files/$newGroupId")) {
          mkdir("../files/$newGroupId", 0755, true);
          $data->returnCode = 0;
          echo json_encode($data);
          exit;
      }else{
        $data->returnCode = 801;
        echo json_encode($data);
        exit;
      }
    }else{
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
  }else{
    $data->returnCode = 601;
    echo json_encode($data);
    exit;
  }
}
//
// Gebruiker inviten
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["nickname"]) && isset($_POST["inviteUser"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if(!isset($_SESSION["GroupID"]) || !isset($_SESSION["UserID"])) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    exit;
  }else{
      $userID = $_SESSION["UserID"];
      $groupID = $_SESSION["GroupID"];
      $nickname = $con->reaL_escape_string($_POST["nickname"]);
      // Kijken of gebruiker bestaat en id ophalen
      $statement = mysqli_prepare($con, "SELECT UserID FROM users WHERE Nickname LIKE ?;");
      mysqli_stmt_bind_param($statement, "s", $nickname);
      if(!mysqli_stmt_execute($statement)) {
        $data->returnCode = 401;
        echo json_encode($data);
        exit;
      }
      $result = $statement->get_result();
      if(mysqli_num_rows($result) == 1) {
          while($row = mysqli_fetch_assoc($result)) {
              $invitedUserID = $row["UserID"];
          }
          $result->close();
          if($invitedUserID == $userID) {
            $data->returnCode = 504;
            echo json_encode($data);
            exit;
          }
          //Kijken of gebruiker niet al in groep zit
          $statement = mysqli_prepare($con, "SELECT * FROM UserGroups WHERE GroupID = ? AND UserID = ?;");
          mysqli_stmt_bind_param($statement, "ii", $groupID, $invitedUserID);
          if(!mysqli_stmt_execute($statement)) {
            $data->returnCode = 401;
            echo json_encode($data);
            exit;
          }
          $result = $statement->get_result();
          if(mysqli_num_rows($result) > 0) {
            $data->returnCode = 505;
            echo json_encode($data);
            exit;
          }
          $result->close();
          //Kijken of gebruiker al invite heeft voor de groep
          $statement = mysqli_prepare($con, "SELECT InviteID FROM invites WHERE Answer = '0' && ReceiverID = ? && GroupID = ?;");
          mysqli_stmt_bind_param($statement, "ii", $invitedUserID, $groupID);
          if(!mysqli_stmt_execute($statement)) {
            $data->returnCode = 401;
            echo json_encode($data);
            exit;
          }
          $result = $statement->get_result();
          if(mysqli_num_rows($result) > 1) {
            $result->close();
            $data->returnCode = 506;
            echo json_encode($data);
            exit;
          }else{
            //Invite toevoegen
            $result->close();
            $statement = mysqli_prepare($con, "INSERT INTO invites (`SenderID`,`ReceiverID`,`GroupID`) VALUES(?,?,?);");
            mysqli_stmt_bind_param($statement, "iii", $userID, $invitedUserID, $groupID);
            if(mysqli_stmt_execute($statement)) {
              echo json_encode($data);
              exit;
            }else{
              $data->returnCode = 401;
              echo json_encode($data);
              exit;
            }
          }
      }else{
        $data->returnCode = 507;
        echo json_encode($data);
        exit;
      }
  }
}
//
// Gebruiker kicken
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["nickname"]) && isset($_POST["deleteUser"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if(!isset($_SESSION["GroupID"]) || !isset($_SESSION["UserID"])) {
    header("Location: ../home.php");
  }else{
      $userID = $_SESSION["UserID"];
      $groupID = $_SESSION["GroupID"];
      $nickName = $con->reaL_escape_string($_POST["nickname"]);
      //Kijken of gebruiker bestaat en id ophalen
      $statement = mysqli_prepare($con, "Select us.UserID FROM UserGroups us INNER JOIN users u ON us.UserID = u.UserID WHERE u.Nickname LIKE ? AND us.GroupID = ?");
      mysqli_stmt_bind_param($statement, "si", $nickName, $groupID);
      if(!mysqli_stmt_execute($statement)) {
        $data->returnCode = 401;
        echo json_encode($data);
        exit;
      }

      $result = $statement->get_result();
      if(mysqli_num_rows($result) == 1) {
          while($row = mysqli_fetch_assoc($result)) {
              $deletedUserID = $row["UserID"];
          }
      }else{
        $data->returnCode = 901;
        echo json_encode($data);
        exit;
      }

      $result->close();
      if($deletedUserID == $userID) {
        $data->returnCode = 902;
        echo json_encode($data);
        exit;
      }
      //Kijken of gebruiker juiste rank heeft
      $statement = mysqli_prepare($con, "SELECT UserRank FROM UserGroups WHERE UserID = ? AND GroupID = ?;");
      mysqli_stmt_bind_param($statement, "ii", $userID, $groupID);
      if(!mysqli_stmt_execute($statement)) {
        $data->returnCode = 401;
        echo json_encode($data);
        exit;
      }

      $result = $statement->get_result();
      if(mysqli_num_rows($result) == 1) {
        while($row = mysqli_fetch_assoc($result)) {
            $userRank = $row["UserRank"];
        }
        $result->close();
        if($userRank > 2) {
          $data->returnCode = 700;
          echo json_encode($data);
          exit;
        }else{
        //Gebruiker verwijderen
          $statement = mysqli_prepare($con, "DELETE FROM UserGroups WHERE UserID = ? AND GroupID = ?;");
          mysqli_stmt_bind_param($statement, "ii", $deletedUserID, $groupID);
          if(!mysqli_stmt_execute($statement)) {
            $data->returnCode = 401;
            echo json_encode($data);
            exit;
          }else{
            $data->returnCode = 0;
            echo json_encode($data);
            exit;
          }
        }
      }else{
        $data->returnCode = 903;
        echo json_encode($data);
        exit;
      }
  }
}
//
// Group verwijderen
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["deleteGroup"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if(!isset($_SESSION["UserID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: ../home.php");
  }else{
      $userID = $_SESSION["UserID"];
      $groupID = $_SESSION["GroupID"];
      //Kijken of gebruiker in group zit en juiste rank heeft
      $statement = mysqli_prepare($con, "SELECT * FROM UserGroups WHERE GroupID = ? AND UserID = ? AND UserRank = 1;");
      mysqli_stmt_bind_param($statement, "ii", $groupID, $userID);
      if(!mysqli_stmt_execute($statement)) {
        $data->returnCode = 401;
        echo json_encode($data);
        exit;
      }
      $result = $statement->get_result();
      if(mysqli_num_rows($result) == 1) {
        //Group verwijderen en map
        $result->close();
        $statement = mysqli_prepare($con, "DELETE FROM groups WHERE groupID = ?");
        mysqli_stmt_bind_param($statement, "i", $groupID);
        if(!mysqli_stmt_execute($statement)) {
          $data->returnCode = 401;
          echo json_encode($data);
          exit;
        }else{
          $dir = "../files/$groupID";
          rrmdir($dir);
          $data->returnCode = 0;
          echo json_encode($data);
          exit;
        }
      }else{
        $data->returnCode = 700;
        echo json_encode($data);
        exit;
      }
      $result->close();
      }
}

//Functie mappen Verwijderen
function rrmdir($dir) {
if (is_dir($dir)) {
  $objects = scandir($dir);
  foreach ($objects as $object) {
    if ($object != "." && $object != "..") {
      if (filetype($dir."/".$object) == "dir")
         rrmdir($dir."/".$object);
      else unlink   ($dir."/".$object);
    }
  }
  reset($objects);
  rmdir($dir);
}
}

//
// Groep verlaten
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["leaveGroup"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if(!isset($_SESSION["UserID"]) || !isset($_SESSION["GroupID"])) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    header("Location: redirect.php?home=1");
    exit;
  }
    $userID = $_SESSION["UserID"];
    $groupID = $_SESSION["GroupID"];
    //Controleren of gebruiker eigenaar is
    $statement = mysqli_prepare($con, "SELECT * FROM UserGroups WHERE UserID = ? AND GroupID = ? AND UserRank = 1");
    mysqli_stmt_bind_param($statement, "ii", $userID, $groupID);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
    $result = $statement->get_result();
    if(mysqli_num_rows($result) == 1) {
      $data->returnCode = 904;
      echo json_encode($data);
      exit;
    }
    $result->close();
    $statement = mysqli_prepare($con, "DELETE FROM UserGroups WHERE UserID = ? AND GroupID = ?;");
    mysqli_stmt_bind_param($statement, "ii", $userID, $groupID);
    if(mysqli_stmt_execute($statement)) {
      $data->returnCode = 0;
      echo json_encode($data);
      exit;
    }else{
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
}
//
// Leden opvragen van groep
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["getGroupMembers"])) {
  session_start();
  $data = new jsonData(0, "");
  $outputString = "";
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if((!isset($_SESSION["GroupID"])) || (!isset($_SESSION["UserID"]))) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    header("Location: redirect.php?home=1");
    exit;
  }
  $userID = $_SESSION["UserID"];
  $groupID = $_SESSION["GroupID"];
  $members = array();
  $statement = mysqli_prepare($con, "SELECT u.UserID, u.FirstName, u.LastName, u.Nickname, ur.rankName FROM UserGroups ug INNER JOIN users u ON ug.UserID = u.UserID INNER JOIN userRanks ur ON ur.rankID = ug.UserRank WHERE GroupID = ?;");
  mysqli_stmt_bind_param($statement, "i", $groupID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
          $member = new Member($row["UserID"], $row["FirstName"], $row["LastName"], $row["Nickname"], $row["rankName"]);
          array_push($members, $member);
      }
      foreach ($members as $member) {
        $outputString .= ("<div class=\"item__member--items\">
                <div class=\"item__member--name\"><p>$member->lastName $member->firstName</p></div>
                <div class=\"item__member--nickname\"><p>$member->nickName</p></div>
                <div class=\"item__member--rank\"><p>$member->userRank</p></div>
              </div>");
      }
  }else{
    $data->returnCode = 905;
    echo json_encode($data);
    exit;
  }

  $data->output = $outputString;
  echo json_encode($data);
  exit;
}

//
// Vak aanmaken
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["crName"])  && isset($_POST["crDescription"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if((!isset($_SESSION["GroupID"])) || (!isset($_SESSION["UserID"]))) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    header("Location: redirect.php?home=1");
    exit;
  }
  $crName = $con->reaL_escape_string($_POST["crName"]);
  $crDescription = $con->real_escape_string($_POST["crDescription"]);
  $userID = $_SESSION["UserID"];
  $groupID = $_SESSION["GroupID"];
  $statement = mysqli_prepare($con, "INSERT INTO courses(`GroupID`,`CrName`,`CrDescription`) VALUES (?,?,?);");
  mysqli_stmt_bind_param($statement, "iss", $groupID, $crName, $crDescription);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }else{
    $newCrID = $con->insert_id;
    if (!file_exists("../files/$groupID")) {
        mkdir("../files/$groupID", 0755, true);
        mkdir("../files/$groupID/$newCrID", 0755, true);
        $data->output = $groupID;
        echo json_encode($data);
        exit;
    }else{
        mkdir("../files/$groupID/$newCrID", 0755, true);
        $data->output = $groupID;
        echo json_encode($data);
        exit;
    }
    $data->returnCode = -1;
    echo json_encode($data);
    exit;
  }
}

//
// Files vak inladen
//

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["course"])  && isset($_POST["courseID"])) {

  session_start();
  $data = new jsonData(0, "");
  $outputString = "";
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  if((!isset($_SESSION["GroupID"])) || (!isset($_SESSION["UserID"]))) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    header("Location: redirect.php?home=1");
    exit;
  }
  $crName = $con->reaL_escape_string($_POST["courseID"]);
  $userID = $_SESSION["UserID"];
  $groupID = $_SESSION["GroupID"];
  $courseID = $_POST["courseID"];
  $_SESSION["CourseID"] = $courseID;


  //Kijken of gebruiker toegang heeft tot group
  $statement = mysqli_prepare($con, "SELECT us.UserID, us.GroupID, c.CourseID, c.CrName FROM UserGroups us INNER JOIN groups g ON us.GroupID = g.GroupID INNER JOIN courses c ON c.GroupID = g.GroupID WHERE us.UserID = ? AND us.GroupID = ? AND c.CourseID = ?;");
  mysqli_stmt_bind_param($statement, "iii", $userID, $groupID, $courseID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
    if(mysqli_num_rows($result) != 1) {
      $data->returnCode = 700;
      echo json_encode($data);
      exit;
  }else{
    while($row = mysqli_fetch_assoc($result)) {
        $courseName = $row["CrName"];
    }
  }

  //Bestanden in directory ophalen
  $path = "../files/$groupID/$courseID";
  $files = array_diff(scandir($path), array('..', '.'));

  $outputString .= ("
  <div id=\"dom__fileManager\">
  <div class=\"body__home--title\">
    <h2>$courseName</h2>
  </div>
  <div class=\"\">");

  if(count($files) != 0 && $files != false) {
    foreach ($files as $file) {
      $pathFile = $path."/".$file;
      $outputString .= ("
      <div class=\"group__file\">
        <a href=\"$pathFile\" target=\"_blank\">
          $file
        </a>
        <button class=\"dom__fileManager--deleteButton\">Verwijderen</button>
      </div>");
    }
  }else{
    $outputString .= ("<p> Kon geen bestanden vinden in dit vak!</p>");
  }
  $outputString .= ("<form id=\"DOM__courses--fileUploader\" class=\"group__file\" action=\"actionsHome.php\" method=\"POST\"\">
    <input id=\"fileInputCourses\" type=\"file\" name=\"file\">
    <input id=\"fileSubmitCourses\" type=\"button\" value=\"uploaden\" name=\"upload\" onclick=\"uploadFileCourse();\">
  </form></div></div>");

  $data->output = $outputString;
  echo json_encode($data);
  exit;

}

//
// Account pagina
//

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["account"])) {

  session_start();
  $data = new jsonData(0, "");
  $outputString = "";
  if (!isset($_SESSION["UserID"])) {
    header("Location: index.php");
  }
  $userID = $_SESSION["UserID"];
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

  //statement maken en uitvoeren
  $statement = mysqli_prepare($con,"SELECT LastName,FirstName,NickName,Email,Password FROM users where UserID = ?" );
  mysqli_stmt_bind_param($statement,"i",$userID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
    if(mysqli_num_rows($result)>=1)
    {
      while($row = mysqli_fetch_assoc($result))
       {
        $firstname = $row["FirstName"];
        $lastname = $row["LastName"];
        $nickname = $row["NickName"];
        $email = $row["Email"];
        $psw_ori = $row["Password"];
       }
       $outputString .= ("
           <div class=\"body__home--accountbox body__home--boxes\">
           <div class=\"container\">
                   <div class=\"Account__title\">
                   <h2>Mijn account</h2>
                   </div>
                <div class=\"row\">
                <div class=\"col-sm-6\">
                      <div class=\"Account__data\">
                       <h3>Nickname</h3>
                       <p> $nickname </p>
                       <h3>Voornaam</h3>
                       <p> $firstname </p>
                       <h3>Achternaam</h3>
                       <p> $lastname </p>
                       <h3>Email</h3>
                       <p>$email </p>
                      </div>
                </div>


                  <div class=\"col-sm-6\">
                    <div id=\"DOM_paswreset\" class=\"Account__paswreset\">
                              <form id=\"DOM__form--psReset\" class=\"psw__resetform\" method=\"post\">
                                  <label for=\"passwordOld\"><b>Oud paswoord</b></label>
                                  <input type=\"password\" placeholder=\"wachtwoordoud\" class=\"account__input\" name=\"password\" id=\"passwordOld\">

                                  <label for=\"password1\"><b>paswoord</b></label>
                                  <input type=\"password\" placeholder=\"wachtwoord\" class=\"account__input\" name=\"psw\" id=\"password1\">

                                  <meter min=\"0\" max=\"4\" id=\"password_strength_meter\"></meter>
                                  <p id=\"password_strength_text\"> </p>
                                  <p id=\"password_suggestions\"></p>

                                  <label for=\"password2\"><b>Herhaal paswoord</b></label>
                                  <input type=\"password\" placeholder=\"wachtwoord herhalen\" class=\"account__input\" name=\"psw_repeat\" id=\"password2\">

                                  <div class=\"account__btn__body\">
                                    <input class=\"account__btn\" type=\"submit\" value=\"Paswoord veranderen\" name=\"Account__btn\" id=\"account__button\">
                                  </div>

                             </form>
                    </div>
               </div>
             </div>
             <input type=\"button\" id=\"easterbtn\" class=\"easterbtn\">
             <img src=\"../images/animal-blur-close-up-42754.jpg\" id=\"draak\" class=\"draak\">
             <script src=\"js/easterscript.js\"></script>
              <script src=\"js/account.js\"></script>
          </div>

       ");
    }

    $data->output = $outputString;
    echo json_encode($data);
    exit;
}

//Bestand verwijderen
if (($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["deleteFile"])  && isset($_POST["file"])) {
  session_start();
  $data = new jsonData(0, "");
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

  if((!isset($_SESSION["GroupID"])) || (!isset($_SESSION["UserID"])) || (!isset($_SESSION["CourseID"])) ) {
    $_SESSION["errormsg"] = "Er ging iets fout!";
    header("Location: redirect.php?home=1");
    exit;
  }
  $file = $con->reaL_escape_string($_POST["file"]);
  $userID = $_SESSION["UserID"];
  $groupID = $_SESSION["GroupID"];
  $courseID = $_SESSION["CourseID"];


  //Kijken of gebruiker toegang heeft tot group
  $statement = mysqli_prepare($con, "SELECT us.UserID, us.GroupID, c.CourseID, c.CrName FROM UserGroups us INNER JOIN groups g ON us.GroupID = g.GroupID INNER JOIN courses c ON c.GroupID = g.GroupID WHERE us.UserID = ? AND us.GroupID = ? AND c.CourseID = ? AND us.userRank = 1;");
  mysqli_stmt_bind_param($statement, "iii", $userID, $groupID, $courseID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();
    if(mysqli_num_rows($result) != 1) {
      $data->returnCode = 700;
      echo json_encode($data);
      exit;
  }

  //Bestanden verwijderen als het bestaat
  $filePath = "../files/$groupID/$courseID/$file";
  if(file_exists($filePath)) {
    if(unlink($filePath)) {
      $data->output = $courseID;
      echo json_encode($data);
      exit;
    }else{
      $data->returnCode = 802;
      echo json_encode($data);
      exit;
    }
  }
}


//
// Bericht versturen livechat
//
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["livechat__text"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $groupID = $_SESSION["GroupID"];
  $data = new jsonData(0, "");

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  $livechatmessage = $con->reaL_escape_string($_POST["livechat__text"]);

  $statement = mysqli_prepare($con, "INSERT INTO chatMessages(`GroupID`,`userID`,`chatMessage`) VALUES (?,?,?);");
  mysqli_stmt_bind_param($statement, "iis", $groupID, $userID, $livechatmessage);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 650;
    echo json_encode($data);
    exit;
  }
}


//Live Chat ophalen berichten
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["pollchat"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  $groupID = $_SESSION["GroupID"];
  $messages = array();

  //Kijken als er al eerder een tijd van laatste chatmessage is bijgehouden. Indien niet op 0 zetten.
  if(isset($_SESSION["LastMessageTime"])){
    //Timestamp van het laatste opgehaald bericht aanwezig
  }else{
    //Nog geen berichten opgehaald
    $_SESSION["LastMessageTime"] = 0;
  }
  //kijken als groep veranderd is indien ja reset van LastmessageTime
  if(isset($_SESSION["PrevGroupID"])){
     if($_SESSION["PrevGroupID"]!=$groupID){
        $_SESSION["LastMessageTime"] = 0;
        $_SESSION["PrevGroupID"]=$groupID;
     }
  }else{
    $_SESSION["PrevGroupID"] = $groupID;
  }

  $statement = mysqli_prepare($con, "SELECT chatMessages.chatMessage,chatMessages.chatSendtime,users.Nickname, users.UserID from chatMessages left join users on users.UserID = chatMessages.userID WHERE chatMessages.groupID = ? AND chatMessages.chatSendtime > ? ORDER BY chatSendtime asc limit 100;");
  mysqli_stmt_bind_param($statement, "is", $groupID,$_SESSION["LastMessageTime"]);

  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }

   $result = $statement->get_result();
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $message = new chatMessage($row["chatMessage"],$row["chatSendtime"],$row["Nickname"], $row["UserID"]);
        array_push($messages, $message);
    }
           //Tijd van laatste message bijhouden voor ophalen messages volgende keer
               //$_SESSION["LastMessageTime"] =$messages[0]->chatSendtime; OLD METHOD
    end($messages);
    $Lastarrayelement = key($messages);
    $_SESSION["LastMessageTime"] = $messages[$Lastarrayelement]->chatSendtime;

    foreach ($messages as $message) {
     //newlines omzetten naar <br>
     $correctmessage = str_replace('\n',"<br>",$message->chatMessage);

     if($message->userID == $userID) {
       $outputString .= ("<div class=\"recvchat__message--body2\">
              <p class=\"recvchat__nickname\">$message->nickname $message->chatSendtime</p><p class=\"recvchat__message\">$correctmessage</p>
            </div>");
     }else{
       $outputString .= ("<div class=\"recvchat__message--body1\">
              <p class=\"recvchat__nickname\">$message->nickname $message->chatSendtime</p><p class=\"recvchat__message\">$correctmessage</p>
            </div>");
     }
    }
    }else{
         //$data->returnCode = 905;
         //echo json_encode($data);
         //exit;
    }
    $data->output = $outputString;
    echo json_encode($data);
    exit;
}

   //Forum inladen
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["forum"])) {
  session_start();
  $con = mysqli_connect($host, $user, $pass, $db);
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
//Uitloggen indien niet geconnecteerd
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  $statement = mysqli_prepare($con, "SELECT * from categories;");
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $result = $statement->get_result();

  $outputString .= ("
  <div id=\"DOM_forum_body\" class=\"forum__body body__home--boxes\">
    <div id=\"DOM_forum_head\" class=\"forum__head\" >
      <h2 id=\"DOM__forum_title\" class=\"forum__title\" >Forum</h2>
      <hr class=\"forum__title__line\">
    </div>
    <div id=\"DOM_forum_container\" class=\"forum__container\" >");

  if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
      $category = $row["CategoryName"];
      $categoryID = $row["CategoryID"];
      $outputString .= ("<a onclick=\"forum_subcat($categoryID)\" class=\"DOM__forum_category group__link\">$category</a>");
    }

    $outputString .= ("
        </div>
        <div id=\"DOM_forum_footer\" class=\"forum__footer\">
        </div>
      </div>
      <div id=\"\" class=\"forum__actions body__home--boxes\">
      <div>
        <h2>Acties</h2>
      </div>
      <div>
      </div>
      </div>
   ");
  }else{
        $data->returnCode = 751;
        echo json_encode($data);
        exit;
  }
    $data->output = $outputString;
    echo json_encode($data);
    exit;
  }


//Subcategorieen forum inladen
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["forumsub"])&& isset($_POST["catid"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
  $catID = $_POST["catid"];

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

    //Naam Catergorie ophalen
    $statement = mysqli_prepare($con,"SELECT c.CategoryName FROM categories c WHERE c.CategoryID = ?;");
    mysqli_stmt_bind_param($statement, "i" ,$catID);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
    $result = $statement->get_result();
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $categoryName = $row["CategoryName"];
      }
    }else{
      $data->returnCode = 752;
      echo json_encode($data);
      exit;
    }


    //Subcatergorieën ophalen
    $statement = mysqli_prepare($con,"SELECT SubCategoryName,SubCategoryID FROM `subCategories` left join categories on categories.CategoryID =subCategories.CategoryID WHERE categories.CategoryID = ? ;");
    mysqli_stmt_bind_param($statement, "i" ,$catID);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
     $result = $statement->get_result();

     $outputString .= ("
         <div id=\"DOM_forum_body\" class=\"forum__body body__home--boxes\">
             <div id=\"DOM_forum_head\" class=\"forum__head\" >
               <h2 id=\"DOM__forum_title\" class=\"forum__title\">$categoryName</h2>
               <hr class=\"forum__title__line\">
             </div>
             <div id=\"DOM_forum_container\" class=\"forum__container\" >");

     if(mysqli_num_rows($result) > 0) {
       while($row = mysqli_fetch_assoc($result)) {
         $subcategory = $row["SubCategoryName"];
         $subcategoryID = $row["SubCategoryID"];
         $outputString .= ("<a onclick=\"forum_posts($subcategoryID)\" class=\"DOM__forum_category group__link\">$subcategory</a>");
       }

      $outputString .= ("
              </div>
              <div id=\"DOM_forum_footer\" class=\"forum__footer\">
              </div>
         </div>
     <div id=\"\" class=\"forum__actions body__home--boxes\">
        <div>
          <h2>Acties</h2>
         </div>
       <div>

       </div>
     </div>
 ");
}else{
      $data->returnCode = 753;
      echo json_encode($data);
      exit;
}
$data->output = $outputString;
echo json_encode($data);
exit;

}

//
// Wachtwoord veranderen van account pagina
//

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["psChange"]) && isset($_POST["psOld"]) && isset($_POST["password1"]) && isset($_POST["password2"])) {
  session_start();
  $data = new jsonData(0, "");

  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

  $psOld = $con->real_escape_string($_POST['psOld']);
  $password1 = $con->real_escape_string($_POST['password1']);
  $password2 = $con->real_escape_string($_POST['password2']);

  if (!isset($_SESSION["UserID"])) {
    $data->returnCode = 702;
    echo json_encode($data);
    exit;
  }

  $userID = $_SESSION["UserID"];

  //Account gegevens ophalen
  $statement = mysqli_prepare($con,"SELECT Password FROM users where UserID = ?;");
  mysqli_stmt_bind_param($statement,"i",$userID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }

  $result = $statement->get_result();
  if(mysqli_num_rows($result)>=1) {
    while($row = mysqli_fetch_assoc($result)) {
      $psw_ori = $row["Password"];
     }
  }else{
    $data->returnCode = 450;
    echo json_encode($data);
    exit;
  }

  //Wachtwoord herhalen controleren
  if($password1 != $password2) {
    $data->returnCode = 452;
    echo json_encode($data);
    exit;
  }

  //Oud wachtwoord controleren
  if(!password_verify($psOld,$psw_ori)) {
    $data->returnCode = 451;
    echo json_encode($data);
    exit;
  }else{
        //Wachtwoord in database aanpassen
        $pswHashed = password_hash($password1,PASSWORD_DEFAULT);
        $statement = mysqli_prepare ($con, ("UPDATE users SET Password = ? WHERE UserID = ?;"));
        mysqli_stmt_bind_param($statement, "si", $pswHashed, $userID);
        if(!mysqli_stmt_execute($statement)) {
          $data->returnCode = 401;
          echo json_encode($data);
          exit;
        }else{
          $data->returnCode = 0;
          echo json_encode($data);
          exit;
        }
    }
}

//Posts van een subcategorie inladen
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["forumposts"])&& isset($_POST["subcatid"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
  $subcatID = $_POST["subcatid"];
  $_SESSION["subcatid"]= $_POST["subcatid"];
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
  //Subcatergory naam ophalen
  $statement = mysqli_prepare($con,"SELECT SubCategoryName FROM subCategories WHERE SubCategoryID = ?;");
  mysqli_stmt_bind_param($statement, "i" ,$subcatID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $posts = array();
  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
      $subcategoryName = $row["SubCategoryName"];
    }
  }else{
      $data->returnCode = 754;
      echo json_encode($data);
      exit;
  }

  //posts ophalen
  $statement = mysqli_prepare($con,"SELECT FPostID,FPostTitle FROM forumposts fp left join subCategories sc on sc.SubCategoryID = fp.SubCategoryID WHERE sc.SubCategoryID = ?;");
  mysqli_stmt_bind_param($statement, "i" ,$subcatID);
  if(!mysqli_stmt_execute($statement)) {
    $data->returnCode = 401;
    echo json_encode($data);
    exit;
  }
  $posts = array();
  $result = $statement->get_result();
  if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
      $post = new forumPost($row["FPostTitle"], $row["FPostID"]);
      array_push($posts, $post);
    }
  }

 $outputString .= ("
     <div id=\"DOM_forum_body\" class=\"forum__body body__home--boxes\">
         <div id=\"DOM_forum_head\" class=\"forum__head\" >
           <h2 id=\"DOM__forum_title\" class=\"forum__title\">$subcategoryName</h2>
           <hr class=\"forum__title__line\">
         </div>
      <div id=\"DOM_forum_container\" class=\"forum__container\" >");

  foreach ($posts as $post) {
    $outputString .= ("<a onclick=\"load_post($post->FPostID)\" class=\"DOM__forum_post group__link\">$post->FPostTitle</a>");
  }

  $outputString .= ("
              </div>
              <div id=\"DOM_forum_footer\" class=\"forum__footer\">
              </div><script src=\"js/modalPost.js\"></script>
         </div>
     <div id=\"\" class=\"forum__actions body__home--boxes\">
        <div>
          <h2>Acties</h2>
         </div>
       <div>
       <div>
       <input type=\"button\" id=\"DOM__new__post\" class=\"forum__controls__button\" value=\"Post aanmaken\">
       </div>
       </div>
     </div>
  ");
$data->output = $outputString;
echo json_encode($data);
exit;

}

//Post inladen
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["initpost"])&& isset($_POST["postid"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
  $postID = $_POST["postid"];
  $_SESSION["postid"] = $_POST["postid"];
//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

    $statement = mysqli_prepare($con,"SELECT FPostID,FPostTitle,FPostMessage,FPostTimestamp,Nickname FROM `forumposts` left join users on users.UserID =forumposts.UserID WHERE forumposts.FPostID = ? AND users.userID = forumposts.userID;");
    mysqli_stmt_bind_param($statement, "i" ,$postID);
    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
     $result = $statement->get_result();


     if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
       //post gedeelte
         $posttitle = $row["FPostTitle"];
         $postID = $row["FPostID"];
         $postmessage =$row["FPostMessage"];
         $posttimestamp = $row["FPostTimestamp"];
         $postmaker = $row["Nickname"];
         $correctmessage = str_replace('\n',"<br>",$postmessage);
         $outputString .= ("





                                 <div id=\"DOM_forum_body\" class=\"forum__body body__home--boxes\">
                                     <div id=\"DOM_forum_head\" class=\"forum__head\" >
                                       <h2 id=\"DOM__forum_title\" class=\"forum__title\" >$posttitle</h2>
                                       <hr class=\"forum__title__line\">
                                     </div>
                                     <div id=\"DOM_forum_container\" class=\"forum__container\" >

                                     <div id=\"DOM__forum_userpost\" class=\"forum__userpost\">
                                             <div ><p class\"post__info\">Door $postmaker aangemaakt op: $posttimestamp</p></div><div class=\"post__message\"><p>$correctmessage</p></div>
                                      </div>
                                      <div id=\"DOM__forum__useranswers\" class=\"forum__useranswersdiv\"></div>


                                          </div>
                                          <div id=\"DOM_forum_footer\" class=\"forum__footer\">
                                          </div><script src=\"js/modalAnswer.js\"></script>
                                     </div>
                                 <div id=\"\" class=\"forum__actions body__home--boxes\">

                                    <div>
                                      <h2>Acties</h2>
                                     </div>

                                   <div>
                                   <div>
                                   <input type=\"button\" id=\"DOM__new__answer\" class=\"answer__controls__button\" value=\"Post antwoorden\">
                                   </div>
                                   </div>
                                 </div>

                    ");
               }
}else{
    $data->returnCode = 755;
    echo json_encode($data);
    exit;
}
$data->output = $outputString;
echo json_encode($data);
exit;

}

//Antwoorden post inladen
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["initanswer"])&& isset($_POST["postid"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }
$answers = array();
$postID = $_POST["postid"];


  //Kijken als er al eerder een tijd van laatste postmessage is bijgehouden. Indien niet op 0 zetten.
  if(isset($_SESSION["ForumLastAnswer"])){
    //Timestamp van het laatste opgehaalde post aanwezig
  }else{
    //Nog geen nieuwe posts opgehaald
    $_SESSION["ForumLastAnswer"] = 0;
  }
  //kijken als er van post is veranderd, indien ja reset van ForumLastAnswer
  if(isset($_SESSION["PrevPostID"])){

     if($_SESSION["PrevPostID"]!=$postID){
        $_SESSION["ForumLastAnswer"] = 0;
        $_SESSION["PrevPostID"]=$postID;
     }
  }else{
    $_SESSION["PrevPostID"] = $postID;
  }

    $statement = mysqli_prepare($con,"SELECT users.Nickname,FPostAnswerMessage,FPostAnswerTimestamp FROM `forumpostsAnswers` left join users on users.UserID = forumpostsAnswers.UserID WHERE forumpostsAnswers.FPostID = ? AND FPostAnswerTimestamp > ? ORDER BY FPostAnswerTimestamp asc;");
    mysqli_stmt_bind_param($statement, "is" ,$postID,$_SESSION["ForumLastAnswer"]);

    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode = 401;
      echo json_encode($data);
      exit;
    }
     $result = $statement->get_result();

     if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
       $answer = new forumAnswer($row["FPostAnswerMessage"],$row["FPostAnswerTimestamp"],$row["Nickname"]);
       array_push($answers, $answer);
     }
     end($answers);
     $Lastarrayelement = key($answers);
     $_SESSION["ForumLastAnswer"] = $answers[$Lastarrayelement]->FPostAnswerTimestamp;

        foreach($answers as $answer ){
         $correctmessage = str_replace('\n',"<br>",$answer->FPostAnswerMessage);
         $outputString .= ("

                          <div id=\"DOM__forum__answer\" class=\"forum__answer\">
                               <div class=\"forum__answer__head\"><p>Door $answer->Nickname beantwoord op:$answer->FPostAnswerTimestamp</p></div>
                               <div class=\"forum__answer__message\"><p>$correctmessage</p></div>
                          </div>
                    ");
                  }
}else{
    //  $data->returnCode = 756;
    //  echo json_encode($data);
    //  exit;
}
$data->output = $outputString;
echo json_encode($data);
exit;
}


//post in database zetten
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["newpost"])&& isset($_POST["postmessage"])&& isset($_POST["posttitle"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
  $subcatid = $_SESSION["subcatid"];

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

$postmessage = $con->reaL_escape_string($_POST["postmessage"]);
$posttitle = $con->reaL_escape_string($_POST["posttitle"]);

    $statement = mysqli_prepare($con,"INSERT INTO `forumposts`(`SubCategoryID`, `UserID`, `FPostTitle`, `FPostMessage`) VALUES (?,?,?,?);");
    mysqli_stmt_bind_param($statement, "iiss" ,$subcatid,$userID,$posttitle,$postmessage);

    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode =757;
      echo json_encode($data);
      exit;
    }else{
      $data->returnCode =0;
      echo json_encode($data);
      exit;
    }

}

//answer in database zetten
if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["newanswer"])&& isset($_POST["answermessage"])) {
  session_start();
  $userID = $_SESSION["UserID"];
  $data = new jsonData(0, "");
  $outputString = "";
  $postid = $_SESSION["postid"];

//Uitloggen indien niet geconnect
  if(!$con = mysqli_connect($host, $user, $pass, $db)) {
    $data->returnCode = 402;
    echo json_encode($data);
    exit;
  }

$answermessage = $con->reaL_escape_string($_POST["answermessage"]);


    $statement = mysqli_prepare($con,"INSERT INTO `forumpostsAnswers`(`FPostID`, `UserID`, `FPostAnswerMessage`) VALUES (?,?,?);");
    mysqli_stmt_bind_param($statement, "iis" ,$postid,$userID,$answermessage);

    if(!mysqli_stmt_execute($statement)) {
      $data->returnCode =758;
      echo json_encode($data);
      exit;
    }else{
      $data->returnCode =0;
      echo json_encode($data);
      exit;
    }

}

if(($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST["resetcf"])) {
  session_start();

  $data = new jsonData(0, "");
//Verwijderen van de laatste message en antwoord tijden
 unset($_SESSION['ForumLastAnswer']);
 unset($_SESSION['LastMessageTime']);


 $data->returnCode =0;
 echo json_encode($data);
 exit;

}
?>
