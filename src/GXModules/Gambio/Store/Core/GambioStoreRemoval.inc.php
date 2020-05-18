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
 * Class GambioStoreRemoval
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
     * @var \GambioStoreCache
     */
    private $cache;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    
    /**
     * GambioStoreRemoval constructor.
     *
     * @param array                  $fileList
     * @param \GambioStoreCache      $cache
     * @param \GambioStoreFileSystem $fileSystem
     * @param \GambioStoreLogger     $logger
     */
    public function __construct(
        array $fileList,
        GambioStoreCache $cache,
        GambioStoreFileSystem $fileSystem,
        GambioStoreLogger $logger
    ) {
        $this->fileList   = $fileList;
        $this->cache      = $cache;
        $this->fileSystem = $fileSystem;
        $this->logger     = $logger;
    }
    
    
    /**
     * Starts the performing of a gambio store removal.
     * Removes for example the files of a gambio store package.
     *
     * @return bool[]
     */
    public function perform()
    {
        $this->createBackup();
        return ['success' => true];
    }
}
