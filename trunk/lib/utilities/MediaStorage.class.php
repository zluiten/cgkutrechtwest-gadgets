<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link GoogleMaps}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* Utility class to store binary Media in a MySQL database. 
* This class needs its own 3 column table (mediaId, tag, binaryData).
* tag can be any string, preferrably the mime type for web applications
* For reusability reasons this class has no dependencies on other classes.
*
* @package utilities
*/
class MediaStorage
{
    var $dbTableName;
    
function MediaStorage($dbTableName)
    {
        $this->dbTableName = mysql_real_escape_string($dbTableName);
    }
    
function createDBTable()
    {
        return "CREATE TABLE IF NOT EXISTS '" . mysql_real_escape_string($dbTableName) ."' (id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, tag VARCHAR(30), binaryData MEDIUMBLOB DEFAULT NULL) TYPE=MyISAM;";
    }
    
    /**
    * Stores any media data of this object.
    * @param string $tag default 'image/jpeg'; stored as is as string no processing; max chars (30) depend on table.
    * @return integer id of the DB entry useful as relation to other object
    */
function storeMedia(&$binaryData,$tag='image/jpeg')
    {
        $sql = "REPLACE INTO $this->dbTableName SET tag='" . mysql_real_escape_string($mediaId) . "' binaryData='" . mysql_real_escape_string($binaryData);
        mysql_query($sql);
        
        return mysql_insert_id();
    }
    
    /**
    * Updates any media data of this object. BinaryData and tag will be changed.
    * @param integer $mediaId 
    * @param string $tag default 'image/jpeg'; stored as is as string no processing; max chars depend on table.
    * @return boolean success
    */
function updateMedia($mediaId,&$binaryData,$tag='image/jpeg')
    {
        $sql = "UPDATE $this->dbTableName SET tag='" . mysql_real_escape_string($mediaId) . "' binaryData='" . mysql_real_escape_string($binaryData) . "' WHERE mediaId=" . mysql_real_escape_string($mediaId);
        mysql_query($sql);
        
        return mysql_affected_rows()==1;
    }
 
    /**
    * Returns any media data by id.
    * @param string $mediaName 'pictureData'
    * @param string $tag for example 'image/jpeg'
    * @return string binary data or null
    */
function retrieveMedia($mediaId,&$tag)
    {
        $sql = "SELECT * FROM $this->dbTableName WHERE mediaId=" . mysql_real_escape_string($mediaId);
        
        $r = mysql_query($sql);

        if(mysql_num_rows() != 1)
            return false;
            
        $tag = $r['tag'];
        return $r['binaryData'];
    }

    /**
    * Deletes any media by id.
    * @return boolean success
    */
function deleteMedia($mediaId)
    {
        $sql = "DELETE FROM $this->dbTableName WHERE mediaId=" . mysql_real_escape_string($mediaId);
        mysql_query($sql);
        return mysql_affected_rows()==1;
    }
}

?>
