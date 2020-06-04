<?php
/* --------------------------------------------------------------
   GambioStoreLoggerFacade.php 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreLoggerFacade_included')) {
        
        define('GambioStoreLoggerFacade_included', true);
        
        /**
         * Class GambioStoreLoggerFacade
         *
         * This class enables store related debug logging, information that are particularly useful for troubleshooting.
         */
        class GambioStoreLoggerFacade
        {
            
            /**
             * @var \GambioStoreCacheFacade
             */
            private $cache;
            
            
            /**
             * GambioStoreLogger constructor.
             *
             * @param \GambioStoreCacheFacade $cache
             */
            public function __construct(GambioStoreCacheFacade $cache)
            {
                $this->cache = $cache;
            }
            
            
            /**
             * System is unusable.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function emergency($message, array $context = [])
            {
                $this->log('EMERGENCY', $message, $context);
            }
            
            
            /**
             * Action must be taken immediately.
             *
             * Example: Entire website down, database unavailable, etc. This should
             * trigger the SMS alerts and wake you up.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function alert($message, array $context = [])
            {
                $this->log('ALERT', $message, $context);
            }
            
            
            /**
             * Critical conditions.
             *
             * Example: Application component unavailable, unexpected exception.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function critical($message, array $context = [])
            {
                $this->log('CRITICAL', $message, $context);
            }
            
            
            /**
             * Runtime errors that do not require immediate action but should typically
             * be logged and monitored.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function error($message, array $context = [])
            {
                $this->log('ERROR', $message, $context);
            }
            
            
            /**
             * Exceptional occurrences that are not errors.
             *
             * Example: Use of deprecated APIs, poor use of an API, undesirable things
             * that are not necessarily wrong.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function warning($message, array $context = [])
            {
                $this->log('WARNING', $message, $context);
            }
            
            
            /**
             * Normal but significant events.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function notice($message, array $context = [])
            {
                $this->log('NOTICE', $message, $context);
            }
            
            
            /**
             * Interesting events.
             *
             * Example: User logs in, SQL logs.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function info($message, array $context = [])
            {
                $this->log('INFO', $message, $context);
            }
            
            
            /**
             * Detailed debug information.
             *
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function debug($message, array $context = [])
            {
                $this->log('DEBUG', $message, $context);
            }
            
            
            /**
             * Logs with an arbitrary level.
             *
             * @param mixed  $level
             * @param string $message
             * @param array  $context
             *
             * @return void
             */
            public function log($level, $message, array $context = [])
            {
                if (isset($context['actionName'])) {
                    $suffix = $context['actionName'];
                    unset($context['actionName']);
                } else {
                    $suffix = 'general';
                }
                
                $now   = new DateTime();
                $today = $now->format('Y-m-d');
                $time  = $now->format('H:i:s');
                
                $fileName = $today . '-' . $suffix . '.log';
                $logPath  = __DIR__ . '/../../Logs/';
                $cacheKey = 'LAST_LOG_FILE_CHECK';
                
                if ($this->cache->has($cacheKey)) {
                    if ($this->cache->get($cacheKey) !== $fileName) {
                        $this->deleteOneMonthOldLogsFromNow($now, $logPath, $suffix);
                        $this->cache->set($cacheKey, $fileName);
                    }
                } else {
                    $this->cache->set($cacheKey, $fileName);
                }
                
                if (count($context) === 0) {
                    $contextMessage = PHP_EOL;
                } else {
                    ob_start();
                    print_r($context);
                    $prettifiedContext = ob_get_clean();
                    $contextMessage    = PHP_EOL . 'context: ' . $prettifiedContext;
                }
                
                $logMeta = '[' . $time . '] [' . $level . '] ';
                
                @file_put_contents($logPath . $fileName, $logMeta . $message . $contextMessage, FILE_APPEND);
            }
            
            
            /**
             * Deletes all logs older than a month from now.
             *
             * @param \DateTime $now
             * @param string    $logPath
             * @param string    $suffix
             */
            private function deleteOneMonthOldLogsFromNow(DateTime $now, $logPath, $suffix)
            {
                $logFiles = glob($logPath . '*.log');
                
                foreach ($logFiles as $logFile) {
                    $fileName       = str_replace($logPath, '', $logFile);
                    $fileDateString = str_replace('-' . $suffix . '.log', '', $fileName);
                    $fileDate       = DateTime::createFromFormat('Y-m-d', $fileDateString);
                    $timeDifference = $now->diff($fileDate);
                    
                    if ((int)$timeDifference->days > 30) {
                        @unlink($logFile);
                    }
                }
            }
        }
    }
}
