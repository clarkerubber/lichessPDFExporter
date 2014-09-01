board-creator
=============

Create png images of a board position given a position string

A position's string is defined as following:
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

rnbqkbnrpppppppp--------------------------------PPPPPPPPRNBQKBNR

When requesting a page, it'll look something like this:

board-creator.php?p=rnbqkbnrpppppppp--------------------------------PPPPPPPPRNBQKBNR

This will then return a page containing a board image with that set up.
