<?php
/* --------------------------------------------------------------
   GambioStoreRemoval.inc.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
require_once 'Exceptions/WrongFilePermissionException.inc.php';
require_once 'GambioStoreLogger.inc.php';

/**
 * Class StoreRemoval
 *
 * Performs a Store package removal and take care of all the required actions.
 *
 * Execute the downgrade script if needed.
 */
class GambioStoreRemoval
{
    /**
     * @var array
     */
    private $fileList;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array $fileList
     */
    public function __construct(array $fileList)
    {
        $this->fileList = $fileList;
    }
    
    
    /**
     * Starts the performing of a gambio store removal.
     * Removes for example the files of a gambio store package.
     *
     * @return bool[]
     */
    public function perform()
    {
        //$wrongPermittedFiles = $this->checkFilesPermissionsWithFileList($this->fileList);
        //if (count($wrongPermittedFiles) !== 0) {
        //    throw new WrongFilePermissionException('Wrong permissions, cannot remove gambio store package',
        //        $wrongPermittedFiles);
        //}
        //
        //// $this->createBackup($this->fileList);
        //try {
        //    foreach ($this->fileList as $file) {
        //         if(!@unlink($file)){
        //          throw new \RuntimeException();
        //         }
        //    }
        //} catch (\Exception $exception) {
        //    $this->restoreBackup();
        //    throw new RuntimeException('Removing of package failed, because of : ', 0, $exception);
        //}
        
        return ['success' => true];
    }
}
