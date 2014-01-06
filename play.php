<?php
	require_once "includes/filevalidation.include.php";
?>

<!DOCTYPE html>
<html>
	<head>
		<?php require_once "includes/config.include.php";
			  require_once "includes/head.include.php";
	     ?>
	    <link rel="stylesheet" type="text/css" href="styles/styles.css">
		<link rel="stylesheet" type="text/css" href="styles/maze3d.css">
		<script src="scripts/three/three.js"></script>
		<script src="scripts/jquery-1.10.2.min.js"></script>
		<script type="text/javascript" src="scripts/helpers.js"></script>
		<script src="scripts/three/classes/Maze3D.js"></script>
		<script src="scripts/three/classes/Block3D.js"></script>
		<script src="scripts/three/classes/Location3D.js"></script>
		<script src="scripts/three/Detector.js"></script>
		<script src="scripts/three/THREEx.KeyboardState.js"></script>
		<script src="scripts/three/THREEx.WindowResize.js"></script>
		<script src="scripts/three/MTLLoader.js"></script>
		<script src="scripts/three/OBJMTLLoader.js"></script>
		<script src="scripts/three/Stats.js"></script>
		<script src="scripts/three/classes/CharacterController.js"></script>
		<script type="x-shader/x-vertex" id="vertexShader">

			varying vec3 vWorldPosition;
			void main() {
				vec4 worldPosition = modelMatrix * vec4( position, 1.0 );
				vWorldPosition = worldPosition.xyz;
				gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );
			}
		</script>
		<script type="x-shader/x-fragment" id="fragmentShader">

			uniform vec3 topColor;
			uniform vec3 bottomColor;
			uniform float offset;
			uniform float exponent;
			varying vec3 vWorldPosition;
			void main() {
				float h = normalize( vWorldPosition + offset ).y;
				gl_FragColor = vec4( mix( bottomColor, topColor, max( pow( h, exponent ), 0.0 ) ), 1.0 );
			}
		</script>
		<script type="text/javascript">

			var fileInputVals = [];
			var allowedExtensions = <?php echo $allowed_exts_JSON ?> ; //don't forget semi
			var maxFileSize = <?php echo $max_size ?> ; //don't forget semi
			var errors = [];
			var fileUploadSelector = '.file-upload-input-container input[type=file]';
			var fileUploadNotificationSelector = '#file-upload-notification';
			var endContainerSelector = '#end-container';
			var fileUploadSuccess = false;

		</script>
		<script type="text/javascript" src="scripts/fileupload.js"></script>
		<script>

			var hostname = <?php echo "'" . $HOSTNAME . "'"?>;
			$(document).ready(function(){

				if(!Detector.webgl){
					instructions.html("Oops, it looks like your browser doesn't support WebGL. Trying using Google Chrome.");
				}

				//postion instructions box in center of screen
				centerBoxes();

				//register resize event
				window.onresize = function(event){
					centerBoxes();
				}

				//register "No thanks" button click event
				$('#end-container button#no-submit').click(function(){
					hideEndContainer(0);
				});

				//register file upload button click event
				$('#end-container button#submit').click(function() {

                    
	                if(onFilesSubmit()){

	                	var data = new FormData();
	                    data.append('end',$(".file-upload-input-container [type='file']").get(0).files[0]);

	                    $.ajax({
	                        url:'fileupload.php?redirect=false',
	                        type:'POST',
	                        processData: false,
	                        contentType: false,
	                        data:data,
	                        success:function(response){
	                            
	                            //resultsArray will contain responses from fileupload.php
	                            //as properties and values in an object. I tried making them
	                            //an assoc array but that didn't work. For this reason, instead
	                            //of handling lots of conditionals to report backend file upload
	                            //errors all errors are handled frontend.
	                            console.log(response);
	                            console.log("success");
	                            for(var parameter in response){
	                            	
	                            	//if the upload was a success!
	                            	if(parameter == "file_upload_success" &&
	                            	   response[parameter] == "true"){

	                            		displayFileUploadSuccess();
	                            	}
	                            }    
	                        }
	                    });
		            }
	            });
            
            });

		</script>
	</head>

	<body>
		<?php require_once 'includes/navbar.include.php'; ?>
		<div id="instructions" class="centered-box">
			<img src="images/maze/move_instructions.png" alt="Move using the W, A, S, and D keys"/>
			<img src="images/maze/look_instructions.png" alt="Look using the mouse or the arrow keys"/>
			<button type="button" onclick="hideInstructions()">got it!</button>
		</div>
		<div id="end-container" class="centered-box">
			<p>
				You found the Finder's Folder! A <code>.zip</code> containing 25 files has been downloaded to your computer.
			 	Each of these files was uploaded by someone else who found the Finder's Folder. Now its your turn to upload.
			</p>

		<div id="file-upload-notification" class="file-upload-notification"><!--note: class and id duplicates are not a mistake--></div>
			<form class="zip-file-upload" action="" method="post" enctype="multipart/form-data">
				<div class="file-upload-input-container">
					<label for="end-file">File</label>
					<input type="file" name="end" id="end-file">
				</div>
			</form>
			<button id="submit">Upload</button>
			<button id="no-submit">No thanks</button>
		</div>
		<div id="blocker">
			<progress value="0" max="100" class="centered-box"></progress>
		</div>

		<script>

			//globals
			var element = document.body; //used for pointer lock
			var renderer, scene, camera, clock, character, stats, displayStats, maze3D;
			scene = new THREE.Scene();

			var progress = $('progress');
			var instructions = $("#instructions");

			var isLoaded = false;
			var isPointerLocked = false;
			
			// http://www.html5rocks.com/en/tutorials/pointerlock/intro/
			var havePointerLock = 'pointerLockElement' in document || 'mozPointerLockElement' in document || 'webkitPointerLockElement' in document;

			$.ajax({
				url: hostname + "/api.php",
				type: "get",
				dataType: "json",
				error: function(err){
					console.log(err);
				},
				success: function(response){
					var block3Dsize = 5;
					var mazeObj = response.data[0];
					console.log(mazeObj);
					maze3D = new Maze3D(hostname, scene, mazeObj, block3Dsize, "images/maze/textures_small/", "models/");

					//do it!
					init(maze3D);
					animate();
				}
			});
			
			function init(maze3D){

				renderer = new THREE.WebGLRenderer({ antialias: true }); 
				camera = new THREE.PerspectiveCamera(60, window.innerWidth/window.innerHeight, 0.1, 26);
				clock = new THREE.Clock();

				displayStats = true;

				//renderer
				var heightSubtractor = $('#navbar').height();
				renderer.setSize(window.innerWidth, window.innerHeight - heightSubtractor);
				var domElement = renderer.domElement;
				domElement.onclick = lockPointer;
				document.body.appendChild(domElement);
			    
			    //camera
			    scene.add(camera);

			    //light
				var hemisphereLight = new THREE.HemisphereLight(0xffffff);
			    hemisphereLight.position.set(1, 1, 1).normalize();
			    scene.add(hemisphereLight);

				//maze3D
				maze3D.addToScene();

				//character
				//character must be instantiated after maze3D is added to scene
				//var startPosition = new THREE.Vector3(4, 20, 5);
				var beginPosition = maze3D.getBeginPosition();
				character = new CharacterController(scene, camera, beginPosition);
				character.setEnabled(false);
				character.registerCollisionObjects(maze3D.getBlockMeshes(), maze3D.getBlockSize());

				//fog
			   //scene.fog = new THREE.Fog( 0xffffff, 16, 26);

				//skydome
				var vertexShader = document.getElementById( 'vertexShader' ).textContent;
				var fragmentShader = document.getElementById( 'fragmentShader' ).textContent;
				var uniforms = {
					topColor: 	 { type: "c", value: new THREE.Color( 0x0077ff ) },
					bottomColor: { type: "c", value: new THREE.Color( 0xffffff ) },
					offset:		 { type: "f", value: 33 },
					exponent:	 { type: "f", value: 1.5 },
					fogColor:    { type: "c", value: 0xffffff },
    				fogNear:     { type: "f", value: 16 },
    				fogFar:      { type: "f", value: 26 }
				};
				// uniforms.topColor.value.copy( hemiLight.color );

				//scene.fog.color.copy( uniforms.bottomColor.value );

				var skyGeo = new THREE.SphereGeometry( 26, 30, 30 );
				var skyMat = new THREE.ShaderMaterial({ vertexShader: vertexShader,
														fragmentShader: fragmentShader,
														uniforms: uniforms, 
														side: THREE.BackSide,
														fog: true
														});

				var sky = new THREE.Mesh( skyGeo, skyMat );
				character.body.add( sky );

				//bind resize event
				THREEx.WindowResize(renderer, camera, heightSubtractor);

				//stants
				if(displayStats){

					stats = new Stats();
					stats.domElement.style.position = 'absolute';
					stats.domElement.style.bottom = '0px';
					stats.domElement.style.zIndex = 100;
					document.body.appendChild( stats.domElement );
				}
			}

			function animate() {
		
				var delta = clock.getDelta();
				requestAnimationFrame( animate );
				if(displayStats) stats.update();

				if(!isLoaded){
				   progress.val(maze3D.getPercentLoaded());
				   isLoaded = maze3D.isLoaded();
				   //just loaded!
				   if(isLoaded){
				   		$('#blocker').remove();
						showInstructions();
				   		startGame();
				   }
				}

				maze3D.update(delta);
				character.update(delta);
				// console.log("x: " + character.getX());
				// console.log("z: " + character.getZ());
				renderer.render( scene, camera );
			}

			//called once loading bar finishes
			function startGame(){
				character.setEnabled(true);
			}

			function centerBoxes(){
				$('.centered-box').each(function(){
					$(this).css({
						top: window.innerHeight / 2 - $(this).height() / 2,
						left: window.innerWidth / 2 - $(this).width() / 2,
					});
				});

				//position progress bar in center of screen
				$('progress').css({ marginTop: window.innerHeight - $(this).height() / 2 });
			}

			function showInstructions(){
				instructions.show();
			}

			function hideInstructions(){
				instructions.hide();
			}

			function hideEndContainer(delay){
				setTimeout(function(){
					$(endContainerSelector).fadeOut(500, function(){
						$(endContainerSelector).css({display: "none"});
					});
				}, delay);
			}

			function displayFileUploadSuccess(){
				
				$(endContainerSelector).html('Upload Success!');
				$(endContainerSelector).addClass('success-text');
				centerBoxes();
				hideEndContainer(1500);
			}

			function onEndReached(){

				var havePointerLock = 'pointerLockElement' in document ||
								   'mozPointerLockElement' in document ||
								'webkitPointerLockElement' in document;
				
				if (havePointerLock){
					// Ask the browser to release the pointer
					document.exitPointerLock = document.exitPointerLock ||
											   document.mozExitPointerLock ||
											   document.webkitExitPointerLock;

					// Ask the browser to release the pointer
					document.exitPointerLock();
				}
				
				$(endContainerSelector).css({display: "block"});
			}

			function lockPointer() {

				var havePointerLock = 'pointerLockElement' in document ||
								   'mozPointerLockElement' in document ||
								'webkitPointerLockElement' in document;
				
				if ( !havePointerLock ) return;
				
				// Ask the browser to lock the pointer
				element.requestPointerLock = element.requestPointerLock ||
										  element.mozRequestPointerLock ||
									   element.webkitRequestPointerLock;

				// Ask the browser to lock the pointer
				element.requestPointerLock();

				function pointerlockerror(){
					console.log('pointer lock error');
					// instructions.style.display = '';
				}
				
				// Hook pointer lock state change events
				document.addEventListener(      'pointerlockchange', pointerLockChange, false);
				document.addEventListener(   'mozpointerlockchange', pointerLockChange, false);
				document.addEventListener('webkitpointerlockchange', pointerLockChange, false);

				document.addEventListener( 'pointerlockerror', pointerlockerror, false );
				document.addEventListener( 'mozpointerlockerror', pointerlockerror, false );
				document.addEventListener( 'webkitpointerlockerror', pointerlockerror, false );

				function pointerLockChange(event){

					
					if (document.pointerLockElement       === element ||
					    document.mozPointerLockElement    === element ||
				        document.webkitPointerLockElement === element) {

						// Pointer was just locked, enable the mousemove listener
						document.addEventListener("mousemove", mouseMove, false);
						isPointerLocked = true;
					} 
					else {
						// Pointer was just unlocked, disable the mousemove listener
						document.removeEventListener("mousemove", mouseMove, false);
						isPointerLocked = false;
					}

					var opacity = (isPointerLocked) ? 1 : 0;
					$('.navbar-insert span').css({opacity: opacity});
				}
			}

			function mouseMove(e){
				character.mouseMove(e);
			}

		</script>
	</body>
</html>