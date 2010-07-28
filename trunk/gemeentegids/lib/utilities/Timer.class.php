<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link Timer}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* Implements basic timing functions. Currently only a microtime stopwatch.
* Usage: $t = new Timer(); ... echo $t->stop();
* @package utilities
*/
class Timer {

    var $startTime;
    var $endTime;
    
    /**
    * calls start().
    */
function Timer() 
    {        
        $this->start();
    }

    /**
    * Sets startTime to now.
    */
function start() 
    {
        list($usec, $sec) = explode(" ", microtime());
        $this->startTime = ((float)$usec + (float)$sec);
    }
    
    /**
    * Sets the endTime and returns elapsed seconds since start.
    * @return elapsedSeconds()
    */
function stop() 
    {
        list($usec, $sec) = explode(" ", microtime());
        $this->endTime = ((float)$usec + (float)$sec);        
        return $this->elapsedSeconds();
    }
    
    /**
    * Returns stop time - start time as float.
    * @return float elapsed seconds
    */
function elapsedSeconds()
    {
        return $time = $this->endTime - $this->startTime;
    }
}

?>
