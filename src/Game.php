<?php
namespace Blo\MultiplayerSudoku;

use AbcAeffchen\sudoku\Sudoku;

class Game {
    private $grid;
    private $solution;

    public function __construct($difficulty)
    {
        list($this->grid, $this->solution) = Sudoku::generateWithSolution(9, Sudoku::NORMAL);
    }

    private function validCoords($x, $y)
    {
        if ($x < 1 || $x > 9 || $y < 1 || $y > 9)
        {
            return false;
        }

        return true;
    }

    public function getGrid()
    {
        return $this->grid;
    }

    public function getCell($x, $y)
    {
        if (!$this->validCoords($x, $y))
            return null;

        --$x;
        --$y;

        return $this->grid[$x][$y];
    }

    public function isSolved()
    {
        return Sudoku::checkSolution($this->grid);
    }

    public function testCell($x, $y, $answer)
    {
        if (!$this->validCoords($x, $y))
            return null;

        --$x;
        --$y;

        if ($this->solution[$x][$y] === $answer)
        {
            $this->grid[$x][$y] = $answer;
            return true;
        }

        return false;
    }
}