var stage = new Kinetic.Stage({
	container: 'container',
	width: 800,
	height: 800
});
var layer = new Kinetic.Layer();

var begin = new Location(1, 0);
var end   = new Location(7, 8);
var maze  = new Maze(maze, 40, begin, end);

maze.draw(layer);
bindEvents();

// add the layer to the stage
stage.add(layer);

function bindEvents(){
    //bind events for each block...
    for(var y = 1; y < maze.blocks.length-1; y++){
        for(var x = 1; x < maze.blocks[0].length-1; x++){

            //on click
            maze.blocks[y][x].rect.on('click', function(){
                maze.toggleBlock(this.index, layer);
            });

            //on mouseover
            maze.blocks[y][x].rect.on('mouseover', function(){
                document.body.style.cursor = 'pointer';
            });

            //on mouseout
            maze.blocks[y][x].rect.on('mouseout', function(){
                document.body.style.cursor = 'default';
            });
            
        }
    }

    //bind events for locations
    for(var key in maze.locations){
        var locationRect = maze.locations[key].rect;

        locationRect.on('dragend', function(){ maze.recalculateLocation(this.id); console.log("COME BACK")});
        locationRect.on('mouseover', function(){ document.body.style.cursor = 'move'; });
        locationRect.on('mouseout', function(){ document.body.style.cursor = 'default'; });
    }
}
