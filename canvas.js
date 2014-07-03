function isCanvasSupported(){
  var elem = document.createElement('canvas');
  return !!(elem.getContext && elem.getContext('2d'));
}

function canvasMergeImages(files, width, height, completed)
{
	var canvas = document.createElement('canvas');
	canvas.width = width;
	canvas.height = height;
	var ctx = canvas.getContext("2d");
	var x = 0;
	for(var i in files)
	{
		var image = files[i];
		if(typeof image.src == "undefined") continue;
		ctx.drawImage(image, x, 0, image.width, image.height);
		x+=image.width;
	}
	var output = canvas.toDataURL("image/jpeg");
	completed(output);
}