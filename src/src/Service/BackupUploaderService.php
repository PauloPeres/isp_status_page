<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;

/**
 * Backup Uploader Service
 *
 * Handles uploading backup files to remote FTP or SFTP servers.
 * FTP uses native PHP functions; SFTP uses phpseclib if available.
 */
class BackupUploaderService
{
    /**
     * Setting service for reading configuration
     *
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * FTP connection resource
     *
     * @var \FTP\Connection|null
     */
    private mixed $ftpConnection = null;

    /**
     * SFTP connection object
     *
     * @var object|null
     */
    private mixed $sftpConnection = null;

    /**
     * Constructor
     *
     * @param \App\Service\SettingService|null $settingService Optional setting service instance
     */
    public function __construct(?SettingService $settingService = null)
    {
        $this->settingService = $settingService ?? new SettingService();
    }

    /**
     * Upload a local file to the configured remote server
     *
     * @param string $localPath Full path to the local file
     * @return bool True on success
     */
    public function upload(string $localPath): bool
    {
        if (!$this->settingService->getBool('backup_ftp_enabled', false)) {
            $this->log('Backup FTP upload is disabled. Skipping.');
            return false;
        }

        if (!file_exists($localPath)) {
            $this->log("Local file not found: {$localPath}", 'error');
            return false;
        }

        $type = $this->settingService->getString('backup_ftp_type', 'ftp');
        $remotePath = $this->settingService->getString('backup_ftp_path', '/backups');
        $remoteFile = rtrim($remotePath, '/') . '/' . basename($localPath);

        try {
            if ($type === 'sftp') {
                return $this->uploadViaSftp($localPath, $remoteFile);
            }

            return $this->uploadViaFtp($localPath, $remoteFile);
        } catch (\Exception $e) {
            $this->log("Upload failed: {$e->getMessage()}", 'error');
            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Test the connection to the configured remote server
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $type = $this->settingService->getString('backup_ftp_type', 'ftp');
        $host = $this->settingService->getString('backup_ftp_host', '');

        if (empty($host)) {
            return ['success' => false, 'message' => 'Host not configured'];
        }

        try {
            if ($type === 'sftp') {
                return $this->testSftpConnection();
            }

            return $this->testFtpConnection();
        } catch (\Exception $e) {
            $this->log("Connection test failed: {$e->getMessage()}", 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Disconnect from remote server
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->ftpConnection !== null) {
            try {
                @ftp_close($this->ftpConnection);
            } catch (\Exception $e) {
                // Ignore close errors
            }
            $this->ftpConnection = null;
        }

        if ($this->sftpConnection !== null) {
            $this->sftpConnection = null;
        }
    }

    /**
     * Upload file via FTP using native PHP functions
     *
     * @param string $localPath Local file path
     * @param string $remoteFile Remote file path
     * @return bool
     */
    private function uploadViaFtp(string $localPath, string $remoteFile): bool
    {
        $conn = $this->connectFtp();
        if ($conn === null) {
            return false;
        }

        $this->log("Uploading via FTP: {$localPath} -> {$remoteFile}");

        // Ensure remote directory exists
        $remoteDir = dirname($remoteFile);
        @ftp_mkdir($conn, $remoteDir);

        $result = @ftp_put($conn, $remoteFile, $localPath, FTP_BINARY);

        if ($result) {
            $this->log("FTP upload successful: {$remoteFile}");
            return true;
        }

        $this->log("FTP upload failed for: {$remoteFile}", 'error');
        return false;
    }

    /**
     * Upload file via SFTP using phpseclib
     *
     * @param string $localPath Local file path
     * @param string $remoteFile Remote file path
     * @return bool
     */
    private function uploadViaSftp(string $localPath, string $remoteFile): bool
    {
        $sftp = $this->connectSftp();
        if ($sftp === null) {
            return false;
        }

        $this->log("Uploading via SFTP: {$localPath} -> {$remoteFile}");

        // Ensure remote directory exists
        $remoteDir = dirname($remoteFile);
        $sftp->mkdir($remoteDir, -1, true);

        $result = $sftp->put($remoteFile, $localPath, 1); // 1 = NET_SFTP_LOCAL_FILE

        if ($result) {
            $this->log("SFTP upload successful: {$remoteFile}");
            return true;
        }

        $this->log("SFTP upload failed for: {$remoteFile}", 'error');
        return false;
    }

    /**
     * Connect to FTP server
     *
     * @return \FTP\Connection|null
     */
    private function connectFtp(): mixed
    {
        $host = $this->settingService->getString('backup_ftp_host', '');
        $port = $this->settingService->getInt('backup_ftp_port', 21);
        $username = $this->settingService->getString('backup_ftp_username', '');
        $password = $this->settingService->getString('backup_ftp_password', '');
        $passive = $this->settingService->getBool('backup_ftp_passive', true);

        $this->log("Connecting to FTP: {$host}:{$port}");

        $conn = @ftp_connect($host, $port, 30);
        if ($conn === false) {
            $this->log("FTP connection failed to {$host}:{$port}", 'error');
            return null;
        }

        $loginResult = @ftp_login($conn, $username, $password);
        if ($loginResult === false) {
            $this->log("FTP login failed for user {$username}", 'error');
            @ftp_close($conn);
            return null;
        }

        if ($passive) {
            ftp_pasv($conn, true);
        }

        $this->ftpConnection = $conn;
        $this->log('FTP connection established successfully');
        return $conn;
    }

    /**
     * Connect to SFTP server using phpseclib
     *
     * @return object|null SFTP connection or null on failure
     */
    private function connectSftp(): mixed
    {
        if (!class_exists('\phpseclib3\Net\SFTP')) {
            $this->log('phpseclib3 is not installed. Install with: composer require phpseclib/phpseclib', 'error');
            return null;
        }

        $host = $this->settingService->getString('backup_ftp_host', '');
        $port = $this->settingService->getInt('backup_ftp_port', 22);
        $username = $this->settingService->getString('backup_ftp_username', '');
        $password = $this->settingService->getString('backup_ftp_password', '');

        $this->log("Connecting to SFTP: {$host}:{$port}");

        try {
            $sftp = new \phpseclib3\Net\SFTP($host, $port, 30);

            if (!$sftp->login($username, $password)) {
                $this->log("SFTP login failed for user {$username}", 'error');
                return null;
            }

            $this->sftpConnection = $sftp;
            $this->log('SFTP connection established successfully');
            return $sftp;
        } catch (\Exception $e) {
            $this->log("SFTP connection error: {$e->getMessage()}", 'error');
            return null;
        }
    }

    /**
     * Test FTP connection
     *
     * @return array{success: bool, message: string}
     */
    private function testFtpConnection(): array
    {
        $conn = $this->connectFtp();
        if ($conn === null) {
            return ['success' => false, 'message' => 'Failed to connect or authenticate via FTP'];
        }

        $sysType = @ftp_systype($conn);
        $remotePath = $this->settingService->getString('backup_ftp_path', '/backups');

        return [
            'success' => true,
            'message' => "FTP connection successful. Server type: {$sysType}. Remote path: {$remotePath}",
        ];
    }

    /**
     * Test SFTP connection
     *
     * @return array{success: bool, message: string}
     */
    private function testSftpConnection(): array
    {
        $sftp = $this->connectSftp();
        if ($sftp === null) {
            return ['success' => false, 'message' => 'Failed to connect or authenticate via SFTP'];
        }

        $remotePath = $this->settingService->getString('backup_ftp_path', '/backups');
        $pwd = $sftp->pwd();

        return [
            'success' => true,
            'message' => "SFTP connection successful. Current directory: {$pwd}. Remote path: {$remotePath}",
        ];
    }

    /**
     * Log a message to the backup log
     *
     * @param string $message Log message
     * @param string $level Log level (info, error, warning, debug)
     * @return void
     */
    private function log(string $message, string $level = 'info'): void
    {
        $configuredScopes = Log::configured();
        // Always try to log to backup scope; fall back to default
        try {
            Log::write($level, "[BackupUploader] {$message}", ['scope' => ['backup']]);
        } catch (\Exception $e) {
            Log::write($level, "[BackupUploader] {$message}");
        }
    }
}
