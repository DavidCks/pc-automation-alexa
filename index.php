<?php
header("HTTP/1.1 200 OK",true,200);
header("Content-Type: application/json;charset=UTF-8",true);
header("Content-Length:",true);
$post = file_get_contents("php://input");

class alexaResponse
{
	public $HTTPResponse; //complete json format response string for alexa
	
	public $version; //always 1.0 as long as amazon doesn't change shit
	public $response; //array of outputSpeech, card, reprompt and directives arrays
	
	public $outputSpeech; //array of type, text and ssml strings
	public $card; //array of type, title, content and text strings as well as an image array
	public $reprompt; //array of outputSpeech array
	public $directives; //object of array of type and playBehaviour strings as well as an audioItem array
	public $shouldEndSession; //boolean variable determining if the session is to be ended
	public $audioItem; //array of token and url strings as well as offsetInMS integer
	
	public function __construct($v = '1.0')
	{
		$this->version = $v;
	}
	
	public function buildResponseBody()
	{
		$this->response = array();
		//output speech
		if($this->outputSpeech != "" || $this->outputSpeech != null)
		{
		$this->response['outputSpeech'] = $this->outputSpeech;
		}
		
		//card
		if($this->card != "" || $this->card != null)
		{
		$this->response['card'] = $this->card;
		}
		
		//directives
		if($this->directives != "" || $this->directives != null)
		{
		$this->response['directives'] = $this->directives;
		}
		
		//should End Session
		if($this->shouldEndSession != "" || $this->shouldEndSession != null)
		{
		$this->response['shouldEndSession'] = $this->shouldEndSession;
		}
		
		return $this->response;
	}
	
	
	public function http_response()
	{
		$this->HTTPResponse = array();
		$response = $this->buildResponseBody();
		
		$this->HTTPResponse['version'] = $this->version;
		$this->HTTPResponse['response'] = $response;
		
		return $this->HTTPResponse;
	}
	
	//settings
	public function directives($type,$playBehaviour)
	{
		$this->directives = array('type' => $type,
					     'playBehaviour' => $playBehaviour,
					     'audioItem' => $this->audioItem
					    );					
	}
	
	public function audioItem($token,$url,$offset)
	{
		$this->audioItem = array('stream' => array('token' => $token,
												   'url' => $url,
												   'offsetInMilliseconds' => $offset
												  )
								);
	}
	
	public function outputSpeech($type,$content)
	{
		if($type == "PlainText")
		{
			$this->outputSpeech = array('type' => $type,
										'text' => $content
										);
		}
		else if($type == "SSML")
		{
			$this->outputSpeech = array('type' => $type,
										'ssml' => $content
										);
		}
		else
		{
			return false;
		}
	}
	
	public function SimpleCard($title, $content)
	{
		$this->card = array('type' => 'Simple',
							'title' => $title,
							'content'=> $content
							);
	}
	
	public function StandardCard($title, $text, $smallIMG, $largeIMG)
	{
		$images = array('smallImageUrl' => $smallIMG,
						'largeImageUrl' => $largeIMG
						);                                            
		                                                              
		$this->card = array('type' => 'Standard',                     
							'title' => $title,                        
							'text' => $text,
							'image' => $images
							);
	}
} //ende der response klasse

$data = json_decode($post); //decode amazons Posted data
$intent = $data->request->intent->name; //get the intent
$slots = $data->request->intent->slots; //get the slots

//////////////////////////////////////////////
//	now we have our function name (intent)  //
//	with all the parameters (slots) 	    //
//	and their equivalent values			    //
//////////////////////////////////////////////

///////////////////////////////////////////////////////
// start coding here //////////////////////////////////
///////////////////////////////////////////////////////
function execute($path) //ability to start a .bat file without waiting for a response
{
	pclose(popen("start /B $path", "r"));
}

function executeCMD($path) //if the program needs cmd to run in the foreground
{
	pclose(popen("start $path", "r"));
}

function runCMD($cmd)
{
	pclose(popen("$cmd", "r"));
}

function ProgramStart($p)
{
	$pathToPrograms = 'C:\xampp3\htdocs\programs';
	
	$statusOpen = array('oeffnen','starten','start','starte','oeffne','programm');
	$statusClose = array('beende','beenden','schliesse','schliessen','terminiere','terminieren');
	foreach($statusOpen as $statusO)
	{
		if($p->Status->value == $statusO) //open or close ?
		{
			switch($p->Program->value)	  //which program ?
			{
				case('osu'):
					execute("$pathToPrograms\osu.bat"); break;
					
				case('shutdown'):
					execute("$pathToPrograms\shutdown.bat"); break;
			}
			
			//build the response
			$r = new alexaResponse('1.0');
			$r->outputSpeech('PlainText','starte '.$p->Program->value);
			$r->SimpleCard($p->Program->value.' wurde gestartet',' ');
			$r->shouldEndSession = true;
			return $r;
		}
	}
	
	foreach($statusClose as $statusC)
	{
		if($p->Status->value == $statusC) //open or close ?
		{
			switch($p->Program->value)	  //which program ?
			{
				case('osu'):
					execute("$pathToPrograms\osuTerminate.bat"); break;
			}
			
			//build the response
			$r = new alexaResponse('1.0');
			$r->outputSpeech('PlainText','schliesse '.$p->Program->value);
			$r->SimpleCard($p->Program->value.' wurde geschlossen',' ');
			$r->shouldEndSession = true;
			return $r;
		}
	}
}

function switchSoundDevice($p)
{
	$pathToPrograms = 'C:\xampp3\htdocs\programs';
	
	$geraetDisplay = array('bildschirm','display','boxen','lautsprecher');
	$geraetHeadset = array('headset','kopfhoerer');
	foreach($geraetDisplay as $geraetD)
	{
		if($p->geraet->value == $geraetD)
		{
			execute("$pathToPrograms\stdWiedergabeDisplay.bat"); break;
		}
	}
	
	foreach($geraetHeadset as $geraetH)
	{
		if($p->geraet->value == $geraetH)
		{
			execute("$pathToPrograms\stdWiedergabeHeadset.bat"); break;
		}
	}
	
	//build the response
	$r = new alexaResponse('1.0');
	$r->outputSpeech('PlainText','sound wird nun ueber '.$p->geraet->value.' wiedergegeben');
	$r->SimpleCard($p->geraet->value.' wurde als Standardgerät ausgewählt',' ');
	$r->shouldEndSession = true;
	return $r;	
}

function setSysVolume($p)
{
	$volumen = $p->Volumen->value * 655.35;
	if($volumen >= 65535)
	{
		$volumen = 65534;
	}
	runCMD("nircmd.exe setsysvolume $volumen");
	
	//build the response
	$r = new alexaResponse('1.0');
	$r->outputSpeech('PlainText',' ');
	$r->SimpleCard($p->Volumen->value.'% lautstärke',' ');
	$r->shouldEndSession = true;
	return $r;	
}

function musik($p)
{
	$pathToPrograms = 'C:\xampp3\htdocs\programs';
	$list = true;
	
	switch($p->playlist->value)
	{
		//playlists
		case("rise against"):
			executeCMD("$pathToPrograms\playRiseAgainst.bat"); break;
			
		case("favoriten"):
			executeCMD("$pathToPrograms\playFavorites.bat"); break;
			
		case("random"):
			executeCMD("$pathToPrograms\playRandom.bat"); break;
		
		case("zufall"):
			executeCMD("$pathToPrograms\playRandom.bat"); break;
			
		case("zufällig"):
			executeCMD("$pathToPrograms\playRandom.bat"); break;
			
		case("dynamix"):
			executeCMD("$pathToPrograms\playDynamix.bat"); break;
			
		case("artcore"):
			executeCMD("$pathToPrograms\playArtcore.bat"); break;
		
		case("chillstep"):
			executeCMD("$pathToPrograms\playChillstep.bat"); break;
			
		case("osu classics"):
			executeCMD("$pathToPrograms\playOsuClassics.bat"); break;
			
		default:
			$list = false;
	}
	
	if($list) //falls es eine der playlists ist
	{
		//build the response
		$r = new alexaResponse('1.0');
		$r->outputSpeech('PlainText','spiele playlist '.$p->playlist->value);
		$r->SimpleCard('Playlist','spiele playlist '.$p->playlist->value);
		$r->shouldEndSession = true;
		return $r;
	}
	else
	{
		$modeNext = array("nächste","nächstes","nächsten","danach","next");
		$modePrev = array("previous","vorheriges","davor","zurrück");
		$modePause = array("pausiere","pause","stop","stopp","halt","anhalten");
		$modePlay = array("abspielen","weiter","spielen","spiele weiter","weiterspielen","spiel weiter");
		
		$mode = array($modeNext,$modePrev,$modePause,$modePlay);
		foreach($mode as $modus)
		{
			foreach($modus as $mod)
			{
				if($p->playlist->value == $mod)
				{
					switch($modus[0])
					{
						case("nächste"):
							execute("$pathToPrograms\songNext.bat"); break;
							
						case("previous"):
							execute("$pathToPrograms\songPrevious.bat"); break;
							
						case("pausiere"):
							execute("$pathToPrograms\songPause.bat"); break;
							
						case("abspielen"):
							execute("$pathToPrograms\songPlay.bat"); break;
					}
				}
			}
		}
		
		//build the response
		$r = new alexaResponse('1.0');
		$r->outputSpeech('PlainText',$p->playlist->value);
		$r->SimpleCard('Aktion',$p->playlist->value);
		$r->shouldEndSession = true;
		return $r;
	}
}

function youtube($p)
{
	$pathToPrograms = 'C:\xampp3\htdocs\programs';
	$search = "";
	$pArray = (array)$p;
	
	foreach($pArray as $parameter)
	{
		if(isset($parameter->value))
		{
			$search .= $parameter->value . "+";
		}
	}

	//get the first result
	$s = file_get_contents("https://www.youtube.com/results?sp=EgIQAw%253D%253D&q=".$search);
	$start = strpos($s,'<h3 class="yt-lockup-title ">');
	$ende = strpos($s,'class="yt-uix-tile-link yt-ui-ellipsis yt-ui-ellipsis-2 yt-u');
	$length = $ende - $start;
	$l = substr($s,$start,$length - 2);
	$href = strpos($l,'href=');
	$link = substr($l,$href + 6);
	$l = strpos($link,"list=");
	$playlistLink = substr($link,$l);
	$playlist = "https://www.youtube.com/playlist?" . $playlistLink;
	
	$cmd = 'taskkill /IM vlc.exe
"D:\Program Files (x86)\VideoLAN\VLC\vlc.exe" '.$playlist.'
taskkill /IM cmd.exe';

	file_put_contents("$pathToPrograms/youtubePlaylist.bat",$cmd);
	executeCMD("$pathToPrograms/youtubePlaylist.bat");

	$ans = str_replace("+"," ",$search);
	//build the response
	$r = new alexaResponse('1.0');
	$r->outputSpeech('PlainText','die playlist für '.$ans.'wird geladen, bitte warte ein wenig');
	$r->SimpleCard('Playlist',$link.'\n'.'Dies ist das erste ergebniss für '.$ans);
	$r->shouldEndSession = true;
	return $r;
}

function UnknownIntent()
{
	//build the response
	$r = new alexaResponse('1.0');
	$r->outputSpeech('PlainText','diesen befehl kenne ich nicht');
	$r->SimpleCard('Fehler','diesen befehl kenne ich nicht');
	$r->shouldEndSession = true;
	return $r;
}

////////////////////////////////////////////////////////
// call all of your functions here /////////////////////
////////////////////////////////////////////////////////
switch($intent)
{
	case('ProgramStart'):
		$r = ProgramStart($slots); 
		break;
	
	case('switchSoundDevice'):
		$r = switchSoundDevice($slots);
		break;
		
	case('setSysVolume'):
		$r = setSysVolume($slots);
		break;
	
	case('musik'):
		$r = musik($slots);
		break;
	
	//youtube controls
	case('query'):
		$r = youtube($slots);
		break;
	
	default:
		$r = UnknownIntent();
		break;
}

$http_response = $r->http_response();
///////////////////////////////////////////////////////
// end coding here ////////////////////////////////////
///////////////////////////////////////////////////////
$response = json_encode($http_response);

//fix square brakets "[" "]" for directives
$directs_posStart = strpos($response,'"directives":') + 13;
$directs_posEnd = strpos($response,'"offsetInMilliseconds":');
$directs_posEnd = strpos($response,'}',$directs_posEnd) + 4;
if($directs_posStart != 13) //falls es directives gibt
{
	$response = substr_replace($response,'[',$directs_posStart,0);
	$response = substr_replace($response,']',$directs_posEnd,0);
}
//fix square brakets "[" "]" for directives

$fp = fopen('php://output', 'w');
fwrite($fp, $response);
fclose($fp);
file_put_contents("test.txt",$post);
?>
