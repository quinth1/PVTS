
    var strength = {
       0: "Onaanvaardbaar",
       1: "Slecht",
       2: "Zwak",
       3: "Goed maar niet goed genoeg",
       4: "Sterk"
    }

  var password = document.getElementById('psw');
  var meter = document.getElementById('password_strength_meter');
  var text = document.getElementById('password_strength_text');
  var bt = document.getElementById('registratie__button');
  var text2 = document.getElementById('password_cracktime');

  password.addEventListener('input',function()
  {
    var val = password.value;
    var result = zxcvbn(val);

    //update password meter
    meter.value = result.score;
    //update text indicator

    if(val !== "")
      {
      text.innerHTML = "Sterkte:   " + strength[result.score];
      text2.innerHTML = "Geschatte aantal gokken om paswoord te raden:  " + result.guesses;
   if(result.score < 4)
   {
     bt.disabled = true;
   }else
   {
     bt.disabled = false;
   }



      }else
       {
         text.innerHTML = "";
       }
  });