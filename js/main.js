  function courses(){
    $.ajax({
        url:"../php/courses.php",
        type:"POST",
        datatype:"text",
        success: function(data){
          $("#dom__interactive").html(data);
        }
    })
  }


  function home(){
    $.ajax({
    url:"../php/actionsHome.php",
    type:"POST",
    datatype:"text",
    data: {homeMenu:1},
    success: function(data){
      $("#dom__interactive").html(data);
      }
    })
  }

function newGroup() {
  alert("Script voor nieuwe groep");
}

<<<<<<< HEAD
function account() {
  $.ajax({
    url:"../php/Account.php",
    type:"POST",
    datatype:"text",
    data: {homeMenu:2},
    succes: function(data){
      $("#dom__interactive").html(data);
=======
function acceptInvite(inviteID){
  $.ajax({
  url:"../php/actionsHome.php",
  type:"POST",
  datatype:"text",
  data: {acceptInvite:1,inviteID:inviteID},
  success: function(data){
    alert(data);
    home();
    }
  })
}

function declineInvite(inviteID){
  $.ajax({
  url:"../php/actionsHome.php",
  type:"POST",
  datatype:"text",
  data: {declineInvite:1,inviteID:inviteID},
  success: function(data){
    alert(data);
    home();
>>>>>>> 7b5db7fbe744794ef691c835248c9c58aef654a6
    }
  })
}
