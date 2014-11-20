<?php
require('resources/fpdf17/fpdf.php');
include('resources/board-creator/board-creator.php');

date_default_timezone_set('UTC');

function getOr($arr, $key, $def=null) {
	return isset($arr[$key])? $arr[$key] : $def;
}

function addMove($pdf, $key, $move, $next) {
	// round evals and convert to millimeters, scaling to a max val of 19mm
	if($key%2==0) {
		// White's move

		$pdf->SetLineWidth(0.2);
		
		$pdf->SetDrawColor(190);
		$pdf->SetFillColor(190);
		$pdf->SetTextColor(255);
		$pdf->SetFont('Arial','B',13);
		$pdf->Cell(9,4,floor($key/2)+1,0,0,'R',1);

		$pdf->SetTextColor(0);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(12,4,getOr($move, 'move', ''),'LTB',0,'L');

		if(isset($move['variation']) || isset($move['result'])) {
			$pdf->SetFont('Arial','B',7);
		} else {
			$pdf->SetFont('Arial','',7);
		}
		
		if (isset($move['result'])) {
			$pdf->Cell(8,4,$move['result'],'TBR',0,'R');
		} else if(isset($move['eval'])){
			$tmp = sprintf('%+3.2f',$move['eval']/100);
			if(strlen($tmp) == 7) {
				$tmp = substr($tmp,0,4);
			} else {
				$tmp = substr($tmp,0,5);
			}
			$pdf->Cell(8,4,$tmp,'TB',0,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['mate'])) {
			$pdf->Cell(8,4,'# '.$move['mate'],'TB',0,'R',isset($move['variation'])? 1 : 0);
		} else {
			$pdf->Cell(8,4,'','TBR',0);
		}
	} else {
		$pdf->SetLineWidth(0.2);

		$pdf->SetDrawColor(190);
		$pdf->SetTextColor(0);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(12,4,getOr($move, 'move', ''),'LTB',0,'L');
		
		if(isset($move['variation']) || isset($move['result'])) {
			$pdf->SetFont('Arial','B',7);
		} else {
			$pdf->SetFont('Arial','',7);
		}

		if (isset($move['result'])) {
			$pdf->Cell(8,4,$move['result'],'TBR',1,'R');
		} else if(isset($move['eval'])){
			$tmp = sprintf('%+3.2f',$move['eval']/100);
			if(strlen($tmp) == 7) {
				$tmp = substr($tmp,0,4);
			} else {
				$tmp = substr($tmp,0,5);
			}
			$pdf->Cell(8,4,$tmp,'TBR',1,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['mate'])) {
			$pdf->Cell(8,4,'# '.$move['mate'],'TBR',1,'R',isset($move['variation'])? 1 : 0);
		} else {
			$pdf->Cell(8,4,'','TBR',1);
		}
	}
}

function formatComment($key, $move, $game) {
	/*
	eval -> eval
	eval -> mate
	mate -> eval
	mate -> mate
	*/
	if (isset($game['analysis'][$key-1]['eval']) && isset($move['eval'])) {
		$output = '('.sprintf('%+3.2f',$game['analysis'][$key-1]['eval']/100).' to '.sprintf('%+3.2f',$move['eval']/100).')';
	} else if (isset($game['analysis'][$key-1]['eval']) && isset($move['mate'])) {
		$output = '('.sprintf('%+3.2f',$game['analysis'][$key-1]['eval']/100).' to Mate in '.$move['mate'].')';
	} else if (isset($game['analysis'][$key-1]['mate']) && isset($move['eval'])) {
		$output = '(Mate in '.$game['analysis'][$key-1]['mate'].' to '.sprintf('%+3.2f',$move['eval']/100).')';
	} else if (isset($game['analysis'][$key-1]['mate']) && isset($move['mate'])) {
		$output = '(Mate in '.$game['analysis'][$key-1]['mate'].' to Mate in '.$move['mate'].')';
	}
	return $output;
}

function addMoveString($pdf, $ply, $moves) {
	$moves = explode(' ', $moves);
	$output = '';
	foreach($moves as $key => $move) {
		$pdf->SetFont('Arial','B',8);
		if (($ply+$key)%2==0) {
			$pdf->Write(3.5, floor((($ply+$key)/2)+1) . '. ');
		} else if ($key == 0) {
			$pdf->Write(3.5, floor((($ply+$key)/2)+1) . ((($ply+$key)%2==0)? '. ' : '... '));
		}
		$pdf->SetFont('Arial','',8);
		$pdf->Write(3.5, $move.' ');
	}
	$pdf->Ln(6);
	return $output;
}

function addVariation($pdf, $key, $move, $game) {
	$pdf->SetFont('Arial','B',9.5);
	$pdf->Write(3.5,(floor($key/2)+1).(($key%2==0)? '. ' : '... ').getOr($move, 'move', '').' ');
	$pdf->SetFont('Arial','',9.5);
	$pdf->Write(3.5,formatComment($key, $move, $game).' The best move was ');
	$pdf->SetFont('Arial','B',9.5);
	$pdf->Write(3.5,explode(' ',$move['variation'])[0].'.');
	$pdf->SetFont('Arial','',8);
	$pdf->Ln(4.5);
	addMoveString($pdf, $key, $move['variation']);
	//$pdf->Ln(6);
}

function getUsername($id) {
	if (isset($id)) {
		$info = json_decode(file_get_contents('http://en.lichess.org/api/user/'.$id),true);
		if (isset($info['username'])){
			return ( isset($info['title']) ? strtoupper($info['title']).' ' : '' ).$info['username'];
		} else {
			return $id;
		}
	}

	return 'Unknown';
}

function formatWin($game) {
	if(isset($game['winner'])){
		if($game['winner'] == 'white') {
			switch($game['status']) {
				case 'mate':
					$output = 'Checkmate, White is victorious';
				break;
				case 'resign':
					$output = 'Black resigned, White is victorious';
				break;
				case 'timeout':
					$output = 'Black left the game, White is victorious';
				break;
				case 'outoftime':
					$output = 'Time out, White is victorious';
			}
		} else {
			switch($game['status']) {
				case 'mate':
					$output = 'Checkmate, Black is victorious';
				break;
				case 'resign':
					$output = 'White resigned, Black is victorious';
				break;
				case 'timeout':
					$output = 'White left the game, Black is victorious';
				break;
				case 'outoftime':
					$output = 'Time out, Black is victorious';
			}
		}
	} else {
		switch($game['status']) {
			case 'stalemate':
				$output = 'Stalemate';
			break;
			case 'draw':
				$output= 'Draw';
		}
	}
	if (!isset($output)){
		$output = 'Draw';
	}
	return $output;
}

function formatWinShort($game) {
	if(isset($game['winner'])){
		if($game['winner'] == 'white') {
			$output = '1-0';
		} else {
			$output = '0-1';
		}
	} else {
		$output = '1/2-1/2';
	}
	if (!isset($output)){
		$output = '1/2-1/2';
	}
	return $output;
}

function usernames($game) {
	$pattern = "/^([^\(]+)\((\d+)\)$/";

	foreach(array('white', 'black') as $side) {
		if(isset($game['players'][$side]['name'])) {
			if(preg_match_all($pattern, $game['players'][$side]['name'], $matches)) {
				$game['players'][$side]['username'] = trim(getOr($matches[1], 0, ''));
				$game['players'][$side]['rating'] = getOr($matches[2], 0);
			} else {
				$game['players'][$side]['username'] = trim($game['players'][$side]['name']);
			}
		} else {
			$game['players'][$side]['username'] = getUsername(getOr($game['players'][$side], 'userId'));
		}
	}

	return $game;
}

function addHeader($pdf, $game){
	// ----/// Header ///----
	// Logo
	$pdf->Image('resources/images/logo.png',160,9.5,37);

	// Game Setup
	$pdf->SetFont('Arial','',15);
	$pdf->SetTextColor(112);
	$pdf->Image('resources/images/'.$game['perf'].'.png',10,9,11);
	$pdf->Cell(10);
	$pdf->Cell(120,5, utf8_decode(strtoupper(
		(($game['speed'] == 'unlimited')? 'unlimited' : floor($game['clock']['initial']/60) . '+' . $game['clock']['increment'])
		. '   ' .
		(($game['variant'] == 'fromPosition')? 'from position' : (($game['perf'] == 'kingOfTheHill')? 'king of the hill' : (($game['perf'] == 'threeCheck')? 'three-check' : $game['perf'])))
		. '   ' . 
		($game['rated']? 'rated' : 'casual')
		)),0,2,'L');
	$pdf->SetTextColor(0);
	

	// Date
	$pdf->SetFont('Arial','',10);
	$pdf->SetTextColor(112);
	$pdf->Cell(60,4,date('l, F j, Y',substr($game['timestamp'],0,10)) . ' at lichess.org/' . $game['id'],0,1,'L');
	$pdf->Cell(0,2,'',0,1);
	$pdf->SetTextColor(0);

	// Names
	$game = usernames($game);

	$pdf->SetFont('Arial','B',15);
	$pdf->Cell(88,7,$game['players']['white']['username'],0,0,'R');
	$pdf->Cell(14);
	$pdf->Cell(90,7,$game['players']['black']['username'],0,1,'L');
	$pdf->Image('resources/images/swords.png', 100, 22, 10);

	// Ratings
	$pdf->SetFont('Arial','',15);
	$pdf->Cell(88,7,getOr($game['players']['white'], 'rating', '').($game['rated']?sprintf(' %+d', getOr($game['players']['white'], 'ratingDiff', '')):''),0,0,'R');
	$pdf->Cell(14);
	$pdf->Cell(90,7,getOr($game['players']['black'], 'rating', '').($game['rated']?sprintf(' %+d', getOr($game['players']['black'], 'ratingDiff', '')):''),0,1,'L');

	// Result
	$pdf->SetFont('Arial','',15);
	$pdf->SetTextColor(112);
	$pdf->Cell(190,7,formatWin($game),0,1,'C');
	$pdf->SetTextColor(0);
}

function addFooter($pdf) {
	$pdf->SetLeftMargin(10);
	$pdf->SetRightMargin(10);
	$pdf->SetXY(10,-15);
	$pdf->SetFont('Arial','IB',8);
	$pdf->Write(3,'Legend');
	$pdf->SetFont('Arial','I',8);
	$pdf->Write(3," +1.00 = 1 pawn advantage to white.  -1.00 = 1 pawn advantage to black. # 2 = White has mate in 2. # -2 = Black has mate in 2.\n");
	$pdf->Write(3,"                Highlighted evaluations are errors in play and have notes in the Comments & Variations section.");
	//$pdf->SetXY(0,-15);
	//$pdf->Cell(0,10,'Page '.$pdf->PageNo(),0,0,'C');
}

function addBoard($pdf, $location, $annotation, $position, $id) {
	$tmp = tempnam('/tmp', 'brd');
	if($tmp == false) {
		return;
	}
	createBoard($position, $tmp);
	switch($location) {
		case 0:
			$pdf->Image($tmp,59, 49, 45, null, 'PNG');
			$pdf->SetXY(59,94);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 1:
			$pdf->Image($tmp,59, 99+5, 45, null, 'PNG');
			$pdf->SetXY(59,144+5);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 2:
			$pdf->Image($tmp,59, 149+10, 45, null, 'PNG');
			$pdf->SetXY(59,194+10);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 3:
			$pdf->Image($tmp,59, 199+15, 45, null, 'PNG');
			$pdf->SetXY(59,244+15);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 4:
			$pdf->Image($tmp,153, 49, 45, null, 'PNG');
			$pdf->SetXY(153,94);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 5:
			$pdf->Image($tmp,153, 99+5, 45, null, 'PNG');
			$pdf->SetXY(153,144+5);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 6:
			$pdf->Image($tmp,153, 149+10, 45, null, 'PNG');
			$pdf->SetXY(153,194+10);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
		break;
		case 7:
			$pdf->Image($tmp,153, 199+15, 45, null, 'PNG');
			$pdf->SetXY(153,244+15);
			$pdf->SetFont('Arial','BI',9);
			$pdf->SetDrawColor(190);
			$pdf->Cell(45,5,$annotation,0,0,'C');
	}
	unlink($tmp);
}

function addBoards($pdf, $game) {
	$pageX = $pdf->GetX();
	$pageY = $pdf->GetY();

	if (isset($game['fens'])) {
		$pageNo = $pdf->PageNo();

		$remain = count($game['analysis']) - 224*($pageNo-1);
		$remain = ($remain > 224)? 224 : $remain;

		if ($pageNo == 1 && isset($game['initialFen'])) {
			addBoard($pdf, 0, 'Initial position', $game['initialFen'], 0);
			$adder = 28;
			$y = 1;
		} else {
			$adder = 0;
			$y = 0;
		}
		
		for ($x = $adder; $x < $remain; $x+=28) {
			$moveNo = 224*($pageNo-1)+$x+24;
			if (!isset($game['analysis'][$moveNo])) {
				$moveNo = count($game['analysis'])-1;
			}
			$annotation = (floor($moveNo/2 + 1) ) . (($moveNo%2 == 0)? '. ' : '... ') . $game['analysis'][$moveNo]['move'];
			addBoard($pdf, $y, $annotation, $game['fens'][$moveNo], $moveNo);
			$y++;
		}
		$final = array('x' => $pdf->getX(), 'y' => $pdf->GetY());
	}
	
	$pdf->SetXY($pageX, $pageY);

	return $final;
}

function createPDF($game) {
	/*
	Basic procedure:
	1. Header:
		-Players
		-Outcome
		-URL
		-Rated?
		-Variant
		-Time Control
		-Date/Time
	2. Moves list and comments
	3. Boards
	*/
	$pdf = new FPDF('P','mm','A4');
	$pdf->AddPage();
	$pdf->SetAutoPageBreak(false);

	// Header
	addHeader($pdf, $game);

	// Moves list
	$movesList = explode(' ', $game['moves']);

	if (!isset($game['analysis'])) {
		$game['analysis'] = array();
	}

	foreach ($movesList as $key => $move) {
		if (!isset($game['analysis'][$key])) {
			array_push($game['analysis'], array('move' => $move, 'result' => null));
		} else {
			$game['analysis'][$key] = array_merge($game['analysis'][$key], array('move' => $move, 'result' => null));
		}
	}

	// Images
	$addBoardsPos = addBoards($pdf, $game);

	$pdf->Cell(0,1,'',0,1);
	$pdf->SetFillColor(190);
	$pdf->SetTextColor(255);
	$pdf->SetFont('Arial','B',13);
	$pdf->Cell(95,6,'  #     WHITE   BLACK',0,0,'L',1);
	if(count($game['analysis']) > 112) {
		$pdf->Cell(95,6,' #     WHITE   BLACK',0,1,'L',1);
	} else {
		$pdf->Cell(95,6,'',0,1,'L',1);
	}

	$pdf->SetFillColor(255);
	$pdf->SetTextColor(0);

	if(isset($game['analysis'])) {
		foreach($game['analysis'] as $key => $move){
			if ($key == count($game['analysis'])-1) {
				addMove($pdf, $key, 
					array('move' => getOr($move, 'move'), 
						'eval' => getOr($move, 'eval'), 
						'mate' => getOr($move, 'mate'), 
						'result' => formatWinShort($game)
						),
					null
				);
			} else {
				addMove($pdf, $key, $move, getOr($game['analysis'], $key+1));
			}
			
			if($key%111 == 0 && $key%222 != 0 && $key != 0) {
				$pdf->SetXY(104,49);
				$pdf->SetLeftMargin(104);
			} else if ($key%223 == 0 && $key != 0) {
				addFooter($pdf);
				$pdf->SetLeftMargin(10);
				$pdf->SetRightMargin(105);
				$pdf->AddPage();
				addHeader($pdf, $game);
				$addBoardsPos = addBoards($pdf, $game);
				$pdf->Cell(0,1,'',0,1);
				$pdf->SetFillColor(190);
				$pdf->SetTextColor(255);
				$pdf->SetFont('Arial','B',13);
				$pdf->Cell(95,6,'  #     WHITE   BLACK',0,0,'L',1);
				$pdf->Cell(95,6,' #     WHITE   BLACK',0,1,'L',1);
			}
		}
		$pdf->Ln(5);

		// Determine where the cursor should be placed
		if($pdf->GetY() < $addBoardsPos['y']) {
			$pdf->SetY($addBoardsPos['y']+5);
		}

		if($pdf->GetY() > 255) {
			if($pdf->GetX() >= 104) {
				addFooter($pdf);
				$pdf->SetLeftMargin(10);
				$pdf->SetRightMargin(110);
				$pdf->AddPage();
				addHeader($pdf, $game);
			} else {
				$pdf->SetXY(104,49);
				$pdf->SetLeftMargin(104);
				$pdf->SetRightMargin(10);
			}
		} else if ($pdf->GetX() < 104) {
			$pdf->SetLeftMargin(10);
			$pdf->SetRightMargin(110);
		} else {
			$pdf->SetLeftMargin(104);
			$pdf->SetRightMargin(10);
		}

		// Comments & Variations
		if($pdf->GetY() > 240 && $pdf->GetX() < 104) {
			$pdf->SetXY(104,49);
			$pdf->SetLeftMargin(104);
			$pdf->SetRightMargin(10);
		}
		
		$printedCom = false;
		

		if (isset($game['opening']['name'])) {
			$pdf->SetFont('Arial','B',13);
			$pdf->SetTextColor(0);
			$pdf->Cell(30,8,'Comments & Variations',0,1,'L');

			$pdf->SetFont('Arial','B',9.5);
			$pdf->Write(3.5,'Opening ');
			$pdf->SetFont('Arial','',9.5);
			$pdf->Write(3.5,$game['opening']['code'].' - '.$game['opening']['name']);
			$pdf->Ln(5);

			$printedCom = true;
		}

		foreach($game['analysis'] as $key => $move){
			if(isset($move['variation'])) {
				if ($printedCom == false) {
					$pdf->SetFont('Arial','B',13);
					$pdf->SetTextColor(0);
					$pdf->Cell(30,8,'Comments & Variations',0,1,'L');
					$printedCom = true;
				}
				if($pdf->GetY() > 255) {
					if($pdf->GetX() >= 104) {
						addFooter($pdf);
						$pdf->SetLeftMargin(10);
						$pdf->SetRightMargin(110);
						$pdf->AddPage();
						addHeader($pdf, $game);
						$pdf->SetFont('Arial','B',13);
						$pdf->SetTextColor(0);
						$pdf->Cell(30,8,'Comments & Variations', 0,1,'L');
					} else {
						$pdf->SetXY(104,49.7);
						$pdf->SetLeftMargin(104);
						$pdf->SetRightMargin(10);
					}
				}
				addVariation($pdf, $key, $move, $game);
			}
		}
	}
	//output
	addFooter($pdf);
	$pdf->Output();
}

if (isset($argv[1])) {
	$game = json_decode(file_get_contents('http://en.lichess.org/api/game/'.$argv[1].'?with_analysis=1&with_moves=1&with_fens=1&with_opening=1'), TRUE);
} else {
	$game = json_decode(file_get_contents('http://en.lichess.org/api/game/'.$_GET['id'].'?with_analysis=1&with_moves=1&with_fens=1&with_opening=1'), TRUE);	
}

createPDF($game);