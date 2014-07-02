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
    }
    container.appendChild(image);
  }
}

// when the images are changed
function resize() {
  var container = document.getElementById("letters");
  var w = 0;
  for(var i = 0; i < container.childNodes.length; i++) {
    var node = container.childNodes[i];
    w += node.offsetWidth;
  }
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
}

 // script on webpage load
fonts = shuffle(fonts);
var font = fonts[0];
preload(font.url);

window.onload = function() {
  var select = document.getElementById('font');
  for (var i in fonts) {
    var option = '<option value="' + i + '">' + fonts[i].name + '</option>';
    console.log(option);
    select.innerHTML += option;
  };
  metadata();
}
