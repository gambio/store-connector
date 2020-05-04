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
require_once './Exceptions/WrongFilePermissionException.inc.php';
require_once './Abstract/AbstractGambioStoreFileSystem.inc.php';
require_once './GambioStoreLogger.inc.php';

/**
 * Class StoreRemoval
 *
 * Performs a Store package removal and take care of all the required actions.
 *
 * Execute the downgrade script if needed.
 */
class GambioStoreRemoval extends AbstractGambioStoreFileSystem
{
    /**
     * @var array
     */
    private $fileList;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array              $fileList
     * @param \GambioStoreLogger $logger
     */
    public function __construct(array $fileList, GambioStoreLogger $logger)
    {
        $this->fileList = $fileList;
    }
    
    
    /**
     * Starts the performing of a gambio store removal.
     * Removes for example the files of a gambio store package.
     *
     * @throws \RuntimeException
     * @throws \WrongFilePermissionException
     */
    public function perform()
    {
        $wrongPermittedFiles = $this->checkFilesPermissionsWithFileList($this->fileList);
        if (count($wrongPermittedFiles) !== 0) {
            throw new WrongFilePermissionException('Wrong permissions, cannot remove gambio store package',
                $wrongPermittedFiles);
        }
        
        // $this->createBackup($this->fileList);
        try {
            foreach ($this->fileList as $file) {
                @unlink($file);
            }
        } catch (Exception $exception) {
            $this->restoreBackup();
            throw new RuntimeException('Removing of package failed, because of : ', 0, $exception);
        }
    }
}
