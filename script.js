 var fonts = [
    {
      name: "Recycle font",
      url: "recycle",
      author: 'Made by <a href="http://www.vaclav-mach.cz/">Václav Mach</a> in 2014',
      color: 'light'
    },
    {
      name: "Bike type",
      url: "bike",
      author: 'Made by Michal Fojt, Lenka Horáková, <a href="http://mkj.is">Matěj Kašpar Jirásek</a> in 2014',
      color: 'dark'
    },
    {
      name: "Duck type",
      url: "duck",
      author: 'Made by Michal Fojt, Lenka Horáková, <a href="http://mkj.is">Matěj Kašpar Jirásek</a> in 2014',
      color: 'dark'
    }
 ];
 function shuffle(o){
  for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
  return o;
 };
 fonts = shuffle(fonts);
 var font = fonts[0];
 function preload(font) {
  var first = "A", last = "Z";
    for(var i = first.charCodeAt(0); i <= last.charCodeAt(0); i++) {
      var image = new Image();
      image.src = font + '/' + String.fromCharCode(i) + '.jpg';
    }
 }
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
 function resize() {
  var container = document.getElementById("letters");
  var w = 0;
  for(var i = 0; i < container.childNodes.length; i++) {
    var node = container.childNodes[i];
    w += node.offsetWidth;
  }
  container.style.width = w + "px";
 }
 function metadata(){
  document.title = font.name;
  var author = document.getElementById('author');
  author.innerHTML = font.author;
  var body = document.getElementById('body');
  body.setAttribute('class', font.color);
 }
 function onSelect(node){
  font = fonts[node.value];
  onSubmit();
  metadata();
 }
