<?php

/* A positions string is defined as following:
White King 		= K
White Queen 	= Q
White Rook 		= R
White Bishop 	= B
White Knight 	= N
White Pawn 		= P

Black King		= k
Black Queen 	= q
Black Rook 		= r
Black Bishop 	= b
Black Knight 	= k
Black Pawn 		= p

From the top left of the board to the bottom right, the string is
either the pieces letter, or a dask (-), for example, the starting
position would be:

*/

function createBoard ($p = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR', $name, $s = 'w', $lastmove = '', $board = 'hashed') {
	//header("Content-type: image/png");
	$board = imagecreatefrompng("resources/board-creator/images/boards/$board.png");
	$move = imagecreatefrompng("resources/board-creator/images/last.png");
	$pieces = array(
		'K' => imagecreatefrompng("resources/board-creator/images/wk.png"),
		'Q' => imagecreatefrompng("resources/board-creator/images/wq.png"),
		'R' => imagecreatefrompng("resources/board-creator/images/wr.png"),
		'B' => imagecreatefrompng("resources/board-creator/images/wb.png"),
		'N' => imagecreatefrompng("resources/board-creator/images/wn.png"),
		'P' => imagecreatefrompng("resources/board-creator/images/wp.png"),
		'k' => imagecreatefrompng("resources/board-creator/images/k.png"),
		'q' => imagecreatefrompng("resources/board-creator/images/q.png"),
		'r' => imagecreatefrompng("resources/board-creator/images/r.png"),
		'b' => imagecreatefrompng("resources/board-creator/images/b.png"),
		'n' => imagecreatefrompng("resources/board-creator/images/n.png"),
		'p' => imagecreatefrompng("resources/board-creator/images/p.png")
		);

	$a_to_n = array(
		'a' => 0,
		'b' => 1,
		'c' => 2,
		'd' => 3,
		'e' => 4,
		'f' => 5,
		'g' => 6,
		'h' => 7
		);

	if ( strlen( $lastmove ) == 4 ) {
		$lastmove = str_split( strtolower( $lastmove ) );
		if ( $s == 'w' ) {
			imagecopy( $board, $move, 50*($a_to_n[$lastmove[0]]), 50*(8-$lastmove[1]), 0, 0, 50, 50 );
			imagecopy( $board, $move, 50*($a_to_n[$lastmove[2]]), 50*(8-$lastmove[3]), 0, 0, 50, 50 );
		} else {
			imagecopy( $board, $move, 50*(7-$a_to_n[$lastmove[0]]), 50*($lastmove[1]-1), 0, 0, 50, 50 );
			imagecopy( $board, $move, 50*(7-$a_to_n[$lastmove[2]]), 50*($lastmove[3]-1), 0, 0, 50, 50 );
		}
	}

	$position = explode('/', $p);

	if ( $s == 'b' ) {
		foreach ( $position as $key => $rank ) {
			$position[7-$key] = array_reverse( str_split ($rank) );
		}
	} else {
		foreach ( $position as $key => $rank ) {
			$position[$key] = str_split ($rank);
		}
	}

	foreach ( $position as $number => $rank ) {
		$row = 0;
		foreach ( $rank as $square ) {
			if ( intval( $square ) > 0 ) {
				$row += intval( $square );
			} else {
				if ( isset($pieces[$square]) ) {
					imagecopy( $board, $pieces[$square], 50*$row, 50*$number, 0, 0, 50, 50 );	
				}
				$row++;
			}
		}
	}

	imagepng($board, $name);
	imagedestroy($board);
}

//createBoard('4r3/p3rp2/2p3k1/2n1N3/5P2/3P4/P3P2P/2R1R1K1', 'w', 'c4e5');