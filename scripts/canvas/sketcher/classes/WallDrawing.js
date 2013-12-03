function WallDrawing(canvas, numbImages){

	//pick a random wallIndex to start on
	//this.currentImageIndex = Math.ceil(Math.random()*numbImages-1);
	this.currentImageIndex = 3;
	this.wallSize = 512;
	this.wallSegments = [];
	this.canvas = canvas;
	this.context = this.canvas.getContext('2d');

	var startX = this.canvas.width/2 - this.currentImageIndex * this.wallSize;
	var x = startX;

	for(var i = 0; i < numbImages; i++){
		var shouldLoad;
		if(i < 10){
			shouldLoad = true;
		}else shouldLoad = false;
		this.wallSegments[i] = new WallSegment(this.context, x, 0, this.wallSize, i + 1, shouldLoad);
		x += this.wallSize;
	}
}

WallDrawing.prototype.updateImages = function(){
	
	var visibleWalls = this._getVisibleWalls();
	for(var i = 0; i < visibleWalls.length; i++){
		var visibleWall = visibleWalls[i];
		if(visibleWall.needsUpdate()){
			visibleWalls[i].updateImage();
		}
	}
}

//called onMouseUp if dragging tool was enabled. 
WallDrawing.prototype.loadNewImages = function(startX, endX){

	//images dragged right, load left
	if(startX < endX){

	}else{ //images dragged left, load right

	}
}

WallDrawing.prototype.drag = function(previousMouseX, mouseX){

	var canDrag = false;

	//if dragged wall right
	if(previousMouseX < mouseX){
		if(this.wallSegments[0].x < 0){
			canDrag = true;
		}
	}else if(previousMouseX > mouseX){ //if dragged left
		var lastWallSegment = this.wallSegments[this.wallSegments.length - 1];
		if(lastWallSegment.x + lastWallSegment.size > this.canvas.width){
			canDrag = true;
		}
	}

	if(canDrag){
		this._walkWallSegments(function(wallSegment){
			wallSegment.update(previousMouseX, mouseX);
		});
		this.display();
	}
}

WallDrawing.prototype.display = function(){
	this._walkWallSegments(function(wallSegment){
		wallSegment.display();
	});
}

WallDrawing.prototype.notifyNeedsUpdate = function(previousMouseX, mouseX){
	this._walkWallSegments(function(wallSegment){
		if(wallSegment.inside(previousMouseX) ||
		   wallSegment.inside(mouseX)){
			wallSegment.notifyNeedsUpdate();
		}
	});
}

//------------------------------------------------------------------------
//PROTECTED FUNCTIONS

//returns array of all wallSegements that are inside the canvas frame
WallDrawing.prototype._getVisibleWalls = function(){
		
	var visibleWalls = [];
	this._walkWallSegments(function(wallSegment){
		//if at least part of the wallSegment is in the canvas frame...
		if(wallSegment.x > 0 &&
		   wallSegment.x < this.canvas.width ||
		   wallSegment.x + wallSegment.size > 0 &&
		   wallSegment.x + wallSegment.size < this.canvas.width){
			visibleWalls.push(wallSegment);
		}
	});
	return visibleWalls;
}

WallDrawing.prototype._walkWallSegments = function(fn){
	for(var i = 0; i < this.wallSegments.length; i++){
		var needsBreak = fn(this.wallSegments[i]);
		if(needsBreak) break;
	}
}