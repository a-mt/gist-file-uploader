var fileInput = document.getElementById("file"),
    btnGist   = document.getElementById("btn-gist"),
    btnSecret = document.getElementById("btn-secret");

// When file is added, remove attribute disabled on submit
fileInput.addEventListener("change", function(){
  if(!fileInput.files.length) {
    btnGist.setAttribute("disabled", "disabled");
    btnSecret.setAttribute("disabled", "disabled");

  } else if(isLoggedIn){
    btnGist.removeAttribute("disabled");
    btnSecret.removeAttribute("disabled");
  }
});