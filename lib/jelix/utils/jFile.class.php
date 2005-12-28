<?php
/**
* @package    jelix
* @subpackage utils
* @version    $Id:$
* @author     Croes G�rald
* @contributor Laurent Jouanneau
* @copyright  2001-2005 CopixTeam, 2005-2006 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*
* Classe orginellement issue du framework Copix 2.3dev20050901. http://www.copix.org (CopixFile)
* Une partie du code est sous Copyright 2001-2005 CopixTeam (licence LGPL)
* Auteurs initiaux : Gerald Croes
* Adapt�e et am�lior�e pour Jelix par Laurent Jouanneau
*/

class jFile {
    /**
    * Reads the content of a file.
    * @param string $filename the filename we're gonna read
    * @return string the content of the file. false if cannot read the file
    */
    public function read ($filename){
        if ( file_exists ($filename) ) {
            return file_get_contents ($filename, false);
        } else {
            return false;
        }
    }

    /**
    * Write a file to the disk.
    * This function is heavily based on the way smarty process its own files.
    * Is using a temporary file and then rename the file. We guess the file system will be smarter than us, avoiding a writing / reading
    *  while renaming the file.
    */
    public function write ($file, $data){
        $_dirname = dirname($file);

        //asking to create the directory structure if needed.
        $this->_createDir ($_dirname);

        if(!@is_writable($_dirname)) {
            // cache_dir not writable, see if it exists
            if(!@is_dir($_dirname)) {
                trigger_error (jLocale::get ('jelix~errors.file.directory.notexists', array ($_dirname)));
                return false;
            }
            trigger_error (jLocale::get ('jelix~errors.file.directory.notwritable', array ($file, $_dirname)));
            return false;
        }

        // write to tmp file, then rename it to avoid
        // file locking race condition
        $_tmp_file = tempnam($_dirname, 'wrt');

        if (!($fd = @fopen($_tmp_file, 'wb'))) {
            $_tmp_file = $_dirname . '/' . uniqid('wrt');
            if (!($fd = @fopen($_tmp_file, 'wb'))) {
                trigger_error(jLocale::get ('jelix~errors.file.write.error', array ($file, $_tmp_file)));
                return false;
            }
        }

        fwrite($fd, $data);
        fclose($fd);

        // Delete the file if it allready exists (this is needed on Win,
        // because it cannot overwrite files with rename()
        if ($GLOBALS['gJConfig']->isWindows && file_exists($file)) {
            @unlink($file);
        }
        @rename($_tmp_file, $file);
        @chmod($file,  0664);

        return true;
    }

    /**
    * create directory structure.
    * @param string $dir the structure we're gonna try to create
    */
    protected function _createDir ($dir){
        // recursive feature on mkdir() is broken with PHP 5.0.4 for Windows
        // so should do own recursion
        if (!file_exists($dir)) {
            $this->_createDir(dirname($dir));
            mkdir($dir, 0775);
        }
    }
}
?>