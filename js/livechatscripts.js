
function openchat(){
var body = document.getElementById("DOM__livechat__body--main");
var bodytitle = document.getElementById("DOM__livechat__title");
var bodymessages = document.getElementById("DOM__livechat__body");


body.style.height= "50%";

bodymessages.style.display = "inline";
bodymessages.style.visibility = "visible";

bodytitle.style.display= "none";
bodytitle.style.visibility= "hidden";

}


function closechat(){
  var body = document.getElementById("DOM__livechat__body--main");
  var bodytitle = document.getElementById("DOM__livechat__title");
  var bodymessages = document.getElementById("DOM__livechat__body");


  body.style.height= "7%";

  bodymessages.style.display = "none";
  bodymessages.style.visibility = "hidden";

  bodytitle.style.display= "block";
  bodytitle.style.visibility= "visible";

}

$("#DOM__livechat__form").submit(function(event) {
event.preventDefault();
  // information to be sent to the server
  var livechatmessage = $('#DOM__livechat__text').val();

  $.ajax({
      type: 'POST',
      url: '../php/actionsHome.php',
      data: {livechat__text:livechatmessage},
      dataType: "text",
      success: function(data){
        //alert(data);
      }
  });
  $('#DOM__livechat__text').val('');
});




function ophalen() {

    $.ajax({
        type:"POST",
        url:"../php/actionsHome.php",
        dataType:"json",
        data:{pollchat:1},
        success: function(data){
          if(data.returnCode == 0) {
            $("#DOM__livechatmessages").append(data.output);
            $("#DOM__livechatmessages").animate({scrollTop:$('#DOM__livechatmessages').prop("scrollHeight")},500);
          }else{
            notify(data.returnCode);
          }
        }
    });

}

$(document).ready(function() {

  ophalen();
  setInterval("ophalen()", 5000);

})

window.addEventListener("beforeunload", function(event) {
  if(typeof logout === 'function') {
    logout();
  }
});
