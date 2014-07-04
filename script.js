var fonts = [
  {
    name: "Recycle font",
    url: "recycle",
    author: '<a href="http://www.vaclav-mach.cz/">Václav Mach</a>',
    color: 'light'
  },
  {
    name: "Bike type",
    url: "bike",
    author: 'Michal Fojt, Lenka Horáková, <a href="http://mkj.is">Matěj Kašpar Jirásek</a>',
    color: 'dark'
  },
  {
    name: "Duck type",
    url: "duck",
    author: 'Michal Fojt, Lenka Horáková, <a href="http://mkj.is">Matěj Kašpar Jirásek</a>',
    color: 'dark'
  },
  {
    name: "Králíky square",
    url: "square",
    author: 'Tomáš Kácel',
    color: 'dark'
  },
  {
    name: "Klam font",
    url: "klam",
    author: 'Petr Vacek',
    color: 'dark'
  },
  {
    name: "It Socks",
    url: "it-socks",
    author: 'Gabriel Ádám',
    color: 'light'
  },
  {
    name: "Walk This Way",
    url: "walk-this-way",
    author: 'Gabriel Ádám',
    color: 'dark'
  },
  {
    name: "Mouka Type",
    url: "mouka",
    author: 'Jana Ludvíková',
    color: 'light'
  },
  {
    name: "Káva Type",
    url: "kava",
    author: 'Martina Marešová',
    color: 'dark'
  },
  {
    name: "Maru Type",
    url: "maru",
    author: 'Marie Lukášová',
    color: 'dark'
  },
  {
    name: "Alergie Font",
    url: "alergie",
    author: 'Richard Záň',
    color: 'light'
  },
  {
    name: "Strawberdose Font",
    url: "strawberdose",
    author: 'Patrik Slamka, Martin Havlíček',
    color: 'light'
  },
  {
    name: "Towel Type",
    url: "towel",
    author: 'Jakub Špiřík, Martina Krasnayová',
    color: 'light'
  },
  {
    name: "Huge Font",
    url: "huge",
    author: 'Jiří Gerat',
    color: 'dark'
  },
  {
    name: "Mark Me Font",
    url: "mark-me",
    author: 'Andrea Mužíková',
    color: 'dark'
  },
  {
    name: "Eat It",
    url: "eat-it",
    author: 'Martina Krasnayová, Jakub Špiřík, Gabriela Véghová',
    color: 'light'
  }
];

// shuffles an array
function shuffle(o){
  for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
  return o;
};

// preloads font with name
function preload(font) {
  var first = "A", last = "Z";
  for(var i = first.charCodeAt(0); i <= last.charCodeAt(0); i++) {
    var image = new Image();
    image.src = font + '/' + String.fromCharCode(i) + '.jpg';
  }
}

// on submitting the input
function onSubmit() {
  var text = document.getElementById("message").value;
  text = text.toUpperCase();
  var container = document.getElementById("letters");
  container.innerHTML = '';
  for (var i = 0; i < text.length; i++) {
    var character = text.charAt(i);
    var regexp = /[a-zA-Z ]/;
    if (!regexp.test(character)) {
      continue;
    }
    switch(character) {
      case ' ':
        character = 'SPACE';
        break;
    }
    var src = font.url + '/' + character + '.jpg';
    var image = new Image();
    image.src = src;
    image.onload = function() {
    resize();
    resizeImages();
    }
    container.appendChild(image);
  }
  resizeImages();

}



// when the images are changed
function resize() {
  var container = document.getElementById("letters");
  var w = 0;
  for(var i = 0; i < container.childNodes.length; i++) {
    var node = container.childNodes[i];
    w += node.offsetWidth;
  }
  // add some width
  w += 10;

  container.style.width = w + "px";
}


// change the title, author and colors
function metadata(){
  document.title = font.name;
  var author = document.getElementById('author');
  author.innerHTML = font.author;
  var body = document.getElementById('body');
  body.setAttribute('class', font.color);
}

 // change the font
function onSelect(node){
  font = fonts[node.value];
  onSubmit();
  metadata();
  // focus on text to prevent scrolling in select
  document.getElementById("message").focus();
  // setHash
  setHash();
}

 // script on webpage load
fonts = shuffle(fonts);
var font = fonts[0];
preload(font.url);
window.onload = function() {
  var select = document.getElementById('font');
  select.innerHTML = "<option>Choose your font...</option>";
  for (var i in fonts) {
    var option = '<option value="' + i + '">' + fonts[i].name + '</option>';
    select.innerHTML += option;
  };
  document.getElementById("message").setAttribute("placeholder", "WRITE SOMETHING!");
  updateFromHash();
  onSubmit();
  metadata();
  resizeImages();
  window.onresize = resizeImages;
  //remove disabled from inputs
  document.getElementById("message").removeAttribute("disabled");
  document.getElementById("font").removeAttribute("disabled");
  document.getElementById("download_button").innerHTML = "Download as JPEG";
  
  // set font in select
  for(var i in fonts){
	var f = fonts[i];
	if(f.url == font.url){
	  select.value = i;
	  break;
	}
  }
}

// RESIZES IMAGES (image height = window height)
var resizeImages = function() {
  var images = document.getElementsByTagName("img");  
  var height = document.documentElement.clientHeight;
  for (var i = 0; i < images.length; i++) {
    var image = images[i];
    image.style.height = height + "px";
  }
}

// SETS HASH ACCORDING TO THE CHOSEN FONT AND TEXT
function setHash(){
  var text = document.getElementById("message").value;
  if(!text) return;
  window.location.hash = font.url + "/" + Url.encode(text);
}

// UPDATES CURRENT TEXT AND FONT FROM URL HASH
function updateFromHash()
{
  if(window.location.hash == "") return;
  
  var parts = window.location.hash.split("/", 2);
  if(!(parts[0] && parts[1])) return;
  
  var f = parts[0];
  var t = parts[1];
  f = f.substr(1, f.length - 1);
  document.getElementById("message").value = Url.decode(t);
  
  var found = findFontByUrl(f);
  if(found){
    font = found;
  }

}

// FINDS FONT IN OUR ARRAY
function findFontByUrl(url)
{
  for(var i in fonts){
    if(fonts[i].url == url){
      return fonts[i];
    }
  }
  return false;
}

// DOWNLOADS IMAGE
function downloadText()
{
	if(!isCanvasSupported()){
		alert("Downloading not supported. Update to modern browser with CANVAS support.");
		return;
	}
	var m = document.getElementById("message").value;
	var letters = document.getElementById("letters");
	var images = document.getElementById("letters").childNodes;
	canvasMergeImages(images, letters.offsetWidth - 10, letters.offsetHeight - 5, function(jpeg){
		//window.open(jpeg, "_blank");
		document.getElementById("download_button").setAttribute("href", jpeg);
		document.getElementById("download_button").setAttribute("download", m + ".jpg");
	});

}

