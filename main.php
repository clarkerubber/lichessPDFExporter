<?php
require('resources/fpdf17/fpdf.php');
date_default_timezone_set('UTC');

function addMove(&$pdf, $key, $move) {
	if($key%2==0) {
		$pdf->SetDrawColor(190);
		$pdf->SetFillColor(190);
		$pdf->SetTextColor(255);
		$pdf->SetFont('Arial','B',13);
		$pdf->Cell(9,4,floor($key/2)+1,0,0,'R',1);

		$pdf->SetTextColor(0);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(12,4,$move['move'],'LB',0,'L');

		if(isset($move['variation']) || isset($move['result'])) {
			$pdf->SetFont('Arial','B',7);
		} else {
			$pdf->SetFont('Arial','',7);
		}

		if(isset($move['eval'])){
			$tmp = sprintf('%+3.2f',$move['eval']/100);
			if(strlen($tmp) == 7) {
				$tmp = substr($tmp,0,4);
			} else {
				$tmp = substr($tmp,0,5);
			}
			$pdf->Cell(8,4,$tmp,'B',0,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['mate'])) {
			$pdf->Cell(8,4,'# '.$move['mate'],'B',0,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['result'])) {
			$pdf->Cell(8,4,$move['result'],'BR',1,'R');
		} else {
			$pdf->Cell(8,4,'','BR',1);
		}
	} else {
		$pdf->SetDrawColor(190);
		$pdf->SetTextColor(0);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(12,4,$move['move'],'LB',0,'L');
		
		if(isset($move['variation']) || isset($move['result'])) {
			$pdf->SetFont('Arial','B',7);
		} else {
			$pdf->SetFont('Arial','',7);
		}

		if(isset($move['eval'])){
			$tmp = sprintf('%+3.2f',$move['eval']/100);
			if(strlen($tmp) == 7) {
				$tmp = substr($tmp,0,4);
			} else {
				$tmp = substr($tmp,0,5);
			}
			$pdf->Cell(8,4,$tmp,'BR',1,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['mate'])) {
			$pdf->Cell(8,4,'# '.$move['mate'],'BR',1,'R',isset($move['variation'])? 1 : 0);
		} else if (isset($move['result'])) {
			$pdf->Cell(8,4,$move['result'],'BR',1,'R');
		} else {
			$pdf->Cell(8,4,'','BR',1);
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
		$output = '('.sprintf('%+3.2f',$game['analysis'][$key-1]['eval']/100).' to Mate in '.abs($move['mate']).')';
	} else if (isset($game['analysis'][$key-1]['mate']) && isset($move['eval'])) {
		$output = '(Mate in '.abs($game['analysis'][$key-1]['mate']).' to '.sprintf('%+3.2f',$move['eval']/100).')';
	} else if (isset($game['analysis'][$key-1]['mate']) && isset($move['mate'])) {
		$output = '(Mate in '.abs($game['analysis'][$key-1]['mate']).' to Mate in '.abs($move['mate']).')';
	}
	return $output;
}

function addVariation(&$pdf, $key, $move, $game) {
	$pdf->SetFont('Arial','B',10);
	$pdf->Write(5,(floor($key/2)+1).(($key%2==0)? '. ' : '... ').$move['move'].' ');
	$pdf->SetFont('Arial','',10);
	$pdf->Write(5,formatComment($key, $move, $game).' The best move was ');
	$pdf->SetFont('Arial','B',10);
	$pdf->Write(5,explode(' ',$move['variation'])[0].'.');
	$pdf->SetFont('Arial','',10);
	$pdf->Ln(5);
	$pdf->Write(5,$move['variation']);
	$pdf->Ln(8);
}

function getUsername($id) {
	if (isset($id)) {
		$info = json_decode(file_get_contents('http://lichess.org/api/user/'.$id),true);
		if (isset($info['username'])){
			return (($info['title'] != null)? strtoupper($info['title']).' ' : '' ).$info['username'];
		} else {
			return $id;
		}
	}

	return 'Unknown';
}

function formatWin(&$game) {
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

function formatWinShort(&$game) {
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

function addHeader(&$pdf, $game){
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
	$pdf->SetFont('Arial','B',15);
	$pdf->Cell(88,7,getUsername($game['players']['white']['userId']),0,0,'R');
	$pdf->Cell(14);
	$pdf->Cell(90,7,getUsername($game['players']['black']['userId']),0,1,'L');
	$pdf->Image('resources/images/swords.png', 100, 22, 10);

	// Ratings
	$pdf->SetFont('Arial','',15);
	$pdf->Cell(88,7,$game['players']['white']['rating'].(isset($game['players']['white']['ratingDiff'])? sprintf(' %+3d'):''),0,0,'R');
	$pdf->Cell(14);
	$pdf->Cell(90,7,$game['players']['black']['rating'].(isset($game['players']['white']['ratingDiff'])? sprintf(' %+3d'):''),0,1,'L');

	// Result
	$pdf->SetFont('Arial','',15);
	$pdf->SetTextColor(112);
	$pdf->Cell(190,7,formatWin($game),0,1,'C');
	$pdf->SetTextColor(0);
}

function addFooter(&$pdf) {
	$pdf->SetLeftMargin(10);
	$pdf->SetRightMargin(10);
	$pdf->SetXY(10,-20);
	$pdf->SetFont('Arial','IB',8);
	$pdf->Write(3,'Legend');
	$pdf->SetFont('Arial','I',8);
	$pdf->Write(3," +1.00 = 1 pawn advantage to white.  -1.00 = 1 pawn advantage to black. # 2 = White has mate in 2. # -2 = Black has mate in 2.\n");
	$pdf->Write(3,"                Highlighted evaluations are errors in play and have notes in the Comments & Variations section.");
	$pdf->SetXY(0,-15);
	$pdf->Cell(0,10,'Page '.$pdf->PageNo(),0,0,'C');
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
	$pdf->Cell(0,1,'',0,1);
	$pdf->SetFillColor(190);
	$pdf->SetTextColor(255);
	$pdf->SetFont('Arial','B',13);
	$pdf->Cell(95,6,'  #     WHITE   BLACK',0,0,'L',1);
	$pdf->Cell(95,6,' #     WHITE   BLACK',0,1,'L',1);

	$pdf->SetFillColor(255);
	$pdf->SetTextColor(0);

	if(isset($game['analysis'])) {
		foreach($game['analysis'] as $key => $move){
			addMove($pdf, $key, $move);
			if($key == 111) {
				$pdf->SetXY(104,49);
				$pdf->SetLeftMargin(104);
			} else if ($pdf->GetY > 260 && $pdf->GetX() >= 104) {
				$pdf->SetLeftMargin(10);
				$pdf->SetRightMargin(105);
				$pdf->AddPage();
				addHeader($pdf, $game);
				$pdf->Cell(0,1,'',0,1);
				$pdf->SetFillColor(190);
				$pdf->SetTextColor(255);
				$pdf->SetFont('Arial','B',13);
				$pdf->Cell(95,6,'  #     WHITE   BLACK',0,0,'L',1);
				$pdf->Cell(95,6,' #     WHITE   BLACK',0,1,'L',1);
			}
		}
		addMove($pdf, count($game['analysis']), array('move' => end(explode(' ',$game['moves'])),'result' => formatWinShort($game)));
		$pdf->Ln(5);

		// Determine where the cursor should be placed
		if($pdf->GetY() > 260) {
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
		}

		$pdf->SetFont('Arial','B',13);
		$pdf->SetTextColor(0);
		$pdf->Cell(30,7,'Comments & Variations', 0,1,'L');

		foreach($game['analysis'] as $key => $move){
			if($pdf->GetY() > 260) {
				if($pdf->GetX() >= 104) {
					addFooter($pdf);
					$pdf->SetLeftMargin(10);
					$pdf->SetRightMargin(110);
					$pdf->AddPage();
					addHeader($pdf, $game);
					$pdf->SetFont('Arial','B',13);
					$pdf->SetTextColor(0);
					$pdf->Cell(30,7,'Comments & Variations', 0,1,'L');
				} else {
					$pdf->SetXY(104,49);
					$pdf->SetLeftMargin(104);
					$pdf->SetRightMargin(10);
				}
			}
			if(isset($move['variation'])) {
				addVariation($pdf, $key, $move, $game);
			}
		}
	}
	//output
	addFooter($pdf);
	$pdf->Output();
}

$game = json_decode(file_get_contents('http://en.lichess.org/api/game/Yv01to6F?with_analysis=1&with_moves=1&with_fens=1'), TRUE);

createPDF($game);